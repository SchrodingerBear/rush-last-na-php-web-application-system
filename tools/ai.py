from flask import Flask, request, jsonify
import requests
import json
import re
from collections import Counter
import re
import requests
from bs4 import BeautifulSoup
import nltk
from nltk.tokenize import sent_tokenize
import time
import random
from concurrent.futures import ThreadPoolExecutor, as_completed
from nltk.corpus import stopwords
import nltk
import os
import shutil


nltk_path = os.path.join(os.getcwd(), "nltk_data")
if os.path.exists(nltk_path):
    shutil.rmtree(nltk_path)  
os.makedirs(nltk_path, exist_ok=True)


nltk.data.path.append(nltk_path)
 
def search_google_api(query, api_key, cse_id):
    """
    Perform a real Google search using the Google Custom Search API and return a list of URLs.
    """
    url = f"https://www.googleapis.com/customsearch/v1?key={api_key}&cx={cse_id}&q={query}"
    
    response = requests.get(url)
    if response.status_code == 200:
        results = response.json().get('items', [])
        return [result['link'] for result in results]
    else:
        return []

def get_page_content(url):
    """
    Fetch content from a URL with timeout and error handling
    """
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'en-US,en;q=0.5',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Cache-Control': 'max-age=0'
        }
        
        response = requests.get(url, headers=headers, timeout=2)
        if response.status_code != 200:
            return ""
        soup = BeautifulSoup(response.text, 'html.parser')
        
        for tag in ['script', 'style', 'nav', 'footer', 'header', 'aside']:
            for element in soup.find_all(tag):
                element.decompose()
        
        text = ' '.join([p.get_text(' ', strip=True) for p in soup.find_all(['p', 'h1', 'h2', 'h3', 'h4'])])
        
        return text[:2000] 
    
    except Exception as e:
        return ""

def split_into_sentences(text):
    """Split text into sentences using NLTK"""
    return sent_tokenize(text)

def get_key_sentences(text, n=3):
    """
    Extract key sentences for search, optimized to be faster
    """
    sentences = split_into_sentences(text)
    if not sentences:
        return []
    sorted_sentences = sorted(sentences, key=len, reverse=True)
    results = []
    for sentence in sorted_sentences:
        if 40 <= len(sentence) <= 200 and len(results) < n:
            results.append(sentence)
    return results

def simple_similarity(text1, text2):
    """
    Simple text similarity check using word overlap
    """
    if not text1 or not text2:
        return 0, []
    sentences1 = split_into_sentences(text1)
    sentences2 = split_into_sentences(text2)
    if not sentences1 or not sentences2:
        return 0, []
    matches = []
    match_scores = []
    for s1 in sentences1:
        words1 = set(s1.lower().split())
        if len(words1) < 5:
            continue
        best_score = 0
        for s2 in sentences2:
            words2 = set(s2.lower().split())
            if len(words2) < 5:
                continue
            intersection = len(words1.intersection(words2))
            union = len(words1.union(words2))
            score = intersection / union if union > 0 else 0
            if score > best_score:
                best_score = score
        if best_score > 0.5:
            matches.append(s1)
            match_scores.append(best_score)
    avg_similarity = sum(match_scores) / len(sentences1) if sentences1 else 0
    return avg_similarity, matches

def fetch_and_compare(url, text):
    """Worker function for parallel processing"""
    content = get_page_content(url)
    if not content:
        return None
    similarity, matches = simple_similarity(text, content)
    if similarity > 0 and matches:
        return {
            'url': url,
            'similarity': similarity * 100,
            'matched_parts': matches
        }
    return None

def check_plagiarism(text, api_key, cse_id):
    """
    Faster plagiarism detection function using simplified algorithms
    and parallel processing
    """
    start_time = time.time()
    key_sentences = get_key_sentences(text, n=1)
    if not key_sentences:
        return [], text, 0, 100, "No analyzable content found."
    search_query = key_sentences[0]
    urls = search_google_api(search_query, api_key, cse_id)
    plagiarism_results = []
    matched_sentences = set()
    with ThreadPoolExecutor(max_workers=4) as executor:
        future_to_url = {executor.submit(fetch_and_compare, url, text): url for url in urls}
        for future in as_completed(future_to_url):
            result = future.result()
            if result:
                plagiarism_results.append(result)
                matched_sentences.update(result['matched_parts'])
    plagiarism_results = sorted(plagiarism_results, key=lambda x: x['similarity'], reverse=True)
    total_sentences = len(split_into_sentences(text))
    plagiarism_score = (len(matched_sentences) / total_sentences * 100) if total_sentences > 0 else 0
    originality_score = 100 - plagiarism_score
    message = ""
    elapsed_time = time.time() - start_time
    if elapsed_time > 8:
        message = "Analysis time limit reached. Results may be complete or incomplete."
    highlighted_text = text
    for sentence in matched_sentences:
        highlighted_text = highlighted_text.replace(
            sentence, 
            f'<span class="highlight" title="Potential match">{sentence}</span>'
        )
    return plagiarism_results, highlighted_text, plagiarism_score, originality_score, message

def get_top_repeated_words(text):
    """Get most frequently used words in the text"""
    words = re.findall(r'\b[a-zA-Z]{3,}\b', text.lower())
    stop_words = set(stopwords.words('english'))
    filtered_words = [word for word in words if word not in stop_words]
    word_counts = Counter(filtered_words)
    return [{'word': word, 'count': count} for word, count in word_counts.most_common(5)]

def get_limitations():
    return """
    Disclaimer: This plagiarism detection tool provides a preliminary analysis and is not a substitute for comprehensive plagiarism checks. It may not detect all instances of plagiarism, especially if the content has been heavily paraphrased or modified. For academic or professional purposes, consider using specialized plagiarism detection services that offer more advanced analysis and higher accuracy.
    """
app = Flask(__name__)

API_KEY = "AIzaSyC7VxTT2Gjo5MLdwwGXiKaDvpdx2IGge2I"
URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent"

headers = {
    "Content-Type": "application/json"
}

@app.route('/check_plagiarism', methods=['POST'])
def check_plagiarism_endpoint():
    try:
        data = request.get_json(force=True)
        text_to_check = data.get('text', '')

        if not text_to_check.strip():
            return jsonify({'error': 'No text provided'}), 400

        if len(text_to_check) > 25000:
            return jsonify({'error': 'Text exceeds the maximum character limit of 25,000 characters.'}), 400

        plagiarism_results, _, plagiarism_score, _, message = check_plagiarism(
            text_to_check, api_key='AIzaSyD7pDUCZfKOibhM5fTlY2yl0TLWjHgUO_g', cse_id='84e326e702f3a4031'
        )

        prompt = (
        "Please check the following academic paragraph for any potential plagiarism, paraphrasing, or AI-generated content. "
        "I would like a detailed analysis including a plagiarism probability score and any sources or segments that may have been copied or slightly modified. "
        f"Here is the text: {text_to_check}\n\n"
        "strictly respond a format it like this do not say anything else \n"
        "{\n"
        '  "plagiarism_score": 66.66666666666666,\n'
        '  "message": "",\n'
        '  "sources": [\n'
        "    {\n"
        '      "url": "https://dfwchild.com/events/romeo-and-juliet-2/",\n'
        '      "similarity": 53.59147025813692,\n'
        '      "matched_parts": [\n'
        '        "\\"Romeo and Juliet is a tragedy written by William Shakespeare early in his career about two young star-crossed lovers whose deaths ultimately reconcile their feuding families.",\n'
        '        "It was among Shakespeare\'s most popular plays during his lifetime and, along with Hamlet, is one of his most frequently performed plays."\n'
        "      ]\n"
        "    }\n"
        "  ]\n"
        "}"
        )

        if not plagiarism_results:
            print('ai')
            payload = {
                "contents": [
                    {
                        "parts": [
                            {"text": prompt}
                        ]
                    }
                ]
            }

            response = requests.post(
                f"{URL}?key={API_KEY}",
                headers=headers,
                data=json.dumps(payload)
            )

            if response.status_code != 200:
                return jsonify({'error': f"Error {response.status_code}: {response.text}"}), response.status_code

            try:
                response_data = response.json()
                
                text_content = response_data['candidates'][0]['content']['parts'][0]['text']

                if text_content.startswith("```json"):
                    clean_json_str = text_content.replace("```json", "").replace("```", "").strip()
                else:
                    clean_json_str = text_content.strip("` \n")
                    
                    inner_data = json.loads(clean_json_str)
                    
                    plagiarism_score = inner_data.get('plagiarism_score', 0)
                    message = inner_data.get('message', '')
                    plagiarism_results = inner_data.get('sources', [])

            except json.JSONDecodeError as e:
                print("Raw string was:", repr(clean_json_str))
                return jsonify({'error': 'Failed to parse JSON response'}), 500

            except Exception as e:
                print(f"An unexpected error occurred: {e}")
                return jsonify({'error': str(e)}), 500
        print("Plagiarism results:", plagiarism_results)    
        response = {
            'plagiarism_score': plagiarism_score,
            'message': message,
            'sources': plagiarism_results
            }
        
        return jsonify(response)
    
    except Exception as e:
        print(f"Error: {e}")
        return jsonify({'error': str(e)}), 500
    
def standardize_response(raw_text):
    json_match = re.search(r'```json\s*([\s\S]*?)\s*```', raw_text)
    if json_match:
        try:
            
            json_content = json.loads(json_match.group(1))
            return json_content
        except json.JSONDecodeError:
            pass
    
    
    try:
        return json.loads(raw_text)
    except json.JSONDecodeError:
        
        return {
            "evaluation": raw_text
        }


@app.route('/evaluate', methods=['POST'])
def evaluate_essay():
    data = request.json
    if not data or 'essay' not in data or 'rubrics_criteria' not in data:
        return jsonify({"error": "Missing required fields: essay and/or rubrics_criteria"}), 400

    essay = data['essay']
    rubrics_criteria = data['rubrics_criteria']
    levels = data['level']
    initiate_prompt = """You are an expert essay evaluator. Grade the essay based on the provided rubric criteria. 
    
YOUR OUTPUT MUST BE IN VALID JSON FORMAT ONLY WITH NO ADDITIONAL TEXT OR FORMATTING. FOLLOW THIS EXACT STRUCTURE:
if essay is wrong almost wrong grammar, make it 100% human, if it is perfect english and deepwords, it will automatic 100% ai
ensure that feedback has complete levels not just 3
example are
```json
{
  "criteria_scores": {
    "Criterion Name (Weight: X%)": {
      "score": [numeric score],
      "feedback": "(note:add always breaktag<br> in icons checks,books,letter x and book) the levels are + """ + ", ".join(levels) + """✅ Why [current_level]: [1 short sentences with specific evidence]<br>❌ Why not [higher_level]: [specific missing elements].<br>❌ Why not [lower_level]: [what the essay did well to avoid this]. if there are 5 levels excluding weight %, there should also 5 why's tp make criteria assement longer and detailed <br> .
",
      "suggestions": [
        "[suggestion 1]",
        "[suggestion 2]"
      ]
    },
    /* Additional criteria here */
  },
  "overall_weighted_score": [numeric score],
  "general_assessment": {
    "strengths": [
      "[contents is all about General Assessment and Feedback: for example1 like this simple. This essay provides a solid explanation of how photosynthesis contributes to energy flow in an ecosystem, covering key concepts such as the transformation of light energy into chemical energy and how that energy is passed through the food chain. The overall clarity of the position is strong, and the essay logically organizes the points. The essay could improve by incorporating rhetorical devices to make it more persuasive and emotionally engaging. A stronger persuasive tone and more effective use of rhetorical appeals could elevate the essay's impact.]"
    ],
    "areas_for_improvement": [
      "[contents is about improvements: for example1.	Use Rhetorical Devices: Your essay would greatly benefit from including rhetorical strategies like emotional appeals (pathos) or logical reasoning (logos) to make your points more compelling. Try introducing analogies or vivid language to create a stronger emotional response in the reader.
2.	Transitions and Flow: Although the logical flow is generally present, transitions between ideas could be smoother. Use linking phrases to help guide the reader through your points. For example, after explaining the process of photosynthesis, a better transition could be, "As a result of this process, plants provide essential energy to herbivores, and the entire food web is supported."
3.	Persuasiveness: Consider strengthening your persuasive tone. Use more evocative language to convince the reader why photosynthesis is crucial. Instead of simply stating that ecosystems would collapse without it, explore the emotional consequences of such a collapse—how it would affect all life forms in the ecosystem, adding urgency to the argument.
]"
    ]
  },
  "ai_detection": {
    "formatted": "AI Generated: XX.XX% and Human: XX.XX%",
    "ai_probability": XX.XX,
    "human_probability": XX.XX
  },
  "plagiarism": {
    "assessment": "[NEGLIGIBLE/LOW/MODERATE/HIGH]",
    "color": "[blue/yellow/orange/red]",
    "description": "[description text]",
    "overall_percentage": XX.XX,
    "overall_score": 0.XXXX,
    "sources": [
      /* Source details if any */
    ],
    "success": true,
    "total_parts": X,
    "total_sources_analyzed": X,
    "total_sources_found": X
  },
  "plagiarism_sources": [
    /* Source URLs if any */
  ]
}
```

CRITERIA:
""" + rubrics_criteria + """

IMPORTANT: The score for each criterion must be a percentage (0-100) OF the criterion's weight. For example, if a criterion has a weight of 20% and performance is excellent, the score should be 20. If performance is average, the score might be 10 (50% of 20%).

The overall_weighted_score should be the sum of all criteria scores, with a maximum possible value of 100.

Also evaluate if the essay is AI-generated or human-written, and include plagiarism assessment based on patterns in the text.

The essay to evaluate is:
"""
    

    payload = {
        "contents": [
            {
                "parts": [
                    {"text": initiate_prompt + essay}
                ]
            }
        ]
    }

    try:
        response = requests.post(
            f"{URL}?key={API_KEY}",
            headers=headers,
             data=json.dumps(payload)
    )
        response_data = response.json()
        raw_text = response_data['candidates'][0]['content']['parts'][0]['text']

        
        criteria_levels = {}
        criteria_matches = re.finditer(r'"(.*?) \(Weight: \d+%\)": {', raw_text)

        for match in criteria_matches:
            criteria_name = match.group(1)
            criteria_block_start = match.end()
            criteria_block_end = raw_text.find('}', criteria_block_start) + 1
            criteria_block = raw_text[criteria_block_start:criteria_block_end]

            levels = []
            for level_match in re.finditer(r'(\\u2705|\\u274c) Why (.*?)\: (.*?)\\n', criteria_block):
                level_symbol, level_name, level_sentence = level_match.groups()
                level_name = level_name.replace("not ", "").strip()
                levels.append({
                    "Level": level_name,
                    "Sentence": level_sentence.strip()
                })

                criteria_levels[criteria_name] = levels

  
        return jsonify({"evaluation": raw_text})

    except Exception as e:
        print(f"Error during evaluation: {e}")  
        return jsonify({"error": str(e)}), 500
    
@app.route('/autogenerate', methods=['POST'])
def autogenerate_essay():
    data = request.json
    if not data or 'essay' not in data or 'rubrics_criteria' not in data:
        return jsonify({"error": "Missing required fields: essay and/or rubrics_criteria"}), 400

    essay = data['essay']
    rubrics_criteria = data['rubrics_criteria']

    
    is_auto_generate = rubrics_criteria == "auto-generate"

    
    row_count = 4  
    column_count = 4  

    
    if "row_count:" in essay:
        row_match = re.search(r'row_count:\s*(\d+)', essay)
        if row_match:
            row_count = int(row_match.group(1))
            row_count = max(2, min(8, row_count))  

    if "column_count:" in essay:
        col_match = re.search(r'column_count:\s*(\d+)', essay)
        if col_match:
            column_count = int(col_match.group(1))
            column_count = max(2, min(5, column_count))  

    
    headers_str = ", ".join([f'"Level {i+1} ({column_count+1-i})"' for i in range(column_count)])
    rows_str = ""

    for i in range(row_count):
        cells_str = ", ".join([f'"LEVEL {j+1} DESCRIPTION"' for j in range(column_count)])
        weight = 100 // row_count  
        extra = 100 % row_count  

        if i == 0:
            weight += extra  

        rows_str += f"""
    {{
        "criteria": "CRITERION NAME {i+1}",
        "cells": [{cells_str}, "{weight}"]
    }}{"," if i < row_count-1 else ""}"""

    
    json_template = f"""{{
    "headers": [{headers_str}, "Weight %"],
    "rows": [{rows_str}
    ]
}}"""

    
    initiate_prompt = f"""Create a detailed academic rubric in JSON format with exactly {row_count} criteria rows and exactly {column_count} scoring levels plus a weight column.
The output must follow this exact JSON structure:
{json_template}

IMPORTANT:
1. Each criterion must have detailed descriptions (25-35 words) for all {column_count} performance levels.
2. All weight percentages must sum to exactly 100%.
3. Return ONLY the JSON object with no additional text before or after.
4. Do not use markdown code blocks or any other formatting - just return the raw JSON.
Based on this information: """

    full_prompt = initiate_prompt + essay

    payload = {
        "contents": [
            {
                "parts": [
                    {"text": full_prompt}
                ]
            }
        ]
    }

    try:
        response = requests.post(
            f"{URL}?key={API_KEY}",
            headers=headers,
            data=json.dumps(payload)
        )
        response_data = response.json()
        raw_text = response_data['candidates'][0]['content']['parts'][0]['text']

        
        json_match = re.search(r'\{[\s\S]*"headers"[\s\S]*"rows"[\s\S]*\}', raw_text)
        if json_match:
            json_str = json_match.group(0)
            try:
                json_data = json.loads(json_str)  
                if "headers" in json_data and "rows" in json_data:
                    return jsonify({"evaluation": json_data})  
                else:
                    return jsonify({"evaluation": raw_text})
            except json.JSONDecodeError:
                return jsonify({"evaluation": raw_text})  
        else:
            return jsonify({"evaluation": raw_text})  

    except Exception as e:
        return jsonify({"error": str(e)}), 500



@app.route('/analyze', methods=['POST'])
def analyze_text():
    try:
        data = request.get_json(force=True)
        essay = data.get('essay', '')

        if not essay.strip():
            return jsonify({'error': 'No essay provided'}), 400

        prompt = (
            "Carefully analyze the following text to determine whether it is likely to be AI-generated or written by a human. "
            "Focus on identifying subtle patterns such as sentence structure, coherence, vocabulary usage, and any detectable AI-specific traits. "
            "Highlight inconsistencies, overuse of common words, or overly polished phrasing that might indicate AI involvement. "
            "Additionally, consider human-like imperfections such as typos, slang, or variability in style. "
            "Provide your explanation in the following format no need for introduction explanation and conclusion:\n\n"
            "Potential AI Generated Traits:\n"
            "-\n\n"
            "Human-Like Traits:\n"
            "-\n\n"
            "At the end of your explanation, strictly output a scoring in JSON format with 'ai_probability' and 'human_probability'.\n\n"
            f"Text: {essay}"
        )

        payload = {
            "contents": [
            {
                "parts": [
                {"text": prompt}
                ]
            }
            ]
        }

        response = requests.post(
            f"{URL}?key={API_KEY}",
            headers=headers,
            data=json.dumps(payload)
        )

        if response.status_code != 200:
            return jsonify({'error': f"Error {response.status_code}: {response.text}"}), response.status_code

        response_data = response.json()
        explanation = response_data['candidates'][0]['content']['parts'][0]['text']

        
        match = re.search(r'\{\s*"ai_probability"\s*:\s*\d*\.?\d+\s*,\s*"human_probability"\s*:\s*\d*\.?\d+\s*\}', explanation)
        if match:
            probabilities = json.loads(match.group())
            ai_probability = probabilities.get('ai_probability')
            human_probability = probabilities.get('human_probability')

            
            explanation = re.sub(r'\{\s*"ai_probability"\s*:\s*\d*\.?\d+\s*,\s*"human_probability"\s*:\s*\d*\.?\d+\s*\}', '', explanation).strip()
        else:
            return jsonify({'error': "Could not extract JSON with probabilities from response."}), 500

        return jsonify({
            'explanation': explanation,
            'ai_probability': ai_probability,
            'human_probability': human_probability
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True)
