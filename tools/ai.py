from flask import Flask, request, jsonify
import requests
import json
import re
from utils import check_plagiarism

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

        if not plagiarism_results:
            prompt = (
                "Please check the following academic paragraph for any potential plagiarism, paraphrasing, or AI-generated content. "
                "I would like a detailed analysis including a plagiarism probability score and any sources or segments that may have been copied or slightly modified. "
                f"Here is the text: {text_to_check}"
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


            # Use regex to extract the JSON object from the explanation
            match = re.search(r'\{\s*"plagiarism_score"\s*:\s*\d*\.?\d+\s*,\s*"message"\s*:\s*".*?"\s*,\s*"plagiarism_results"\s*:\s*\[.*?\]\s*\}', explanation)
            if match:
                gemini_results = json.loads(match.group())
                plagiarism_score = gemini_results.get('plagiarism_score')
                message = gemini_results.get('message')
                plagiarism_results = gemini_results.get('plagiarism_results')
            else:
                return jsonify({'error': "Could not extract JSON with plagiarism data from Gemini AI response."}), 500

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
        return {"evaluation": raw_text}


@app.route('/evaluate', methods=['POST'])
def evaluate_essay():
    data = request.json
    if not data or 'essay' not in data or 'rubrics_criteria' not in data:
        return jsonify({"error": "Missing required fields: essay and/or rubrics_criteria"}), 400

    essay = data['essay']
    rubrics_criteria = data['rubrics_criteria']
    initiate_prompt = """You are an expert essay evaluator. Grade the essay based on the provided rubric criteria. 
    
YOUR OUTPUT MUST BE IN VALID JSON FORMAT ONLY WITH NO ADDITIONAL TEXT OR FORMATTING. FOLLOW THIS EXACT STRUCTURE:
if essay is wrong almost wrong grammar, make it 100% human, if it is perfect english and deepwords, it will automatic 100% ai

```json
{
  "criteria_scores": {
    "Criterion Name (Weight: X%)": {
      "score": [numeric score],
      "feedback": "(note:add always breaktag<br> in icons checks,books,letter x and book)✅ Why [current_level]: [2-3 sentences with specific evidence]<br>❌ Why not [higher_level]: [specific missing elements].<br>❌ Why not [lower_level]: [what the essay did well to avoid this].<br>📚 Teacher\'s Benchmark: [specific exemplar or standard].
example ✅ Why Proficient: The essay gives a reasonable persuasive tone, especially when describing what would happen without photosynthesis (e.g., "collapse of food webs"). However, it lacks emotionally compelling or powerful language to deeply persuade the reader. ❌ Why not Advanced: No use of emotional hooks or vivid imagery.
 ❌ Why not Needs Improvement: Argument is clear and moderately convincing. 
❌ Why not Warning: Persuasion is definitely present. 
📚 Teacher's Benchmark: Uses logical clarity to present the critical role of photosynthesis, suggesting Advanced-level persuasion through structured reasoning.
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
        print(response.text)  # Debugging line to check the response
        if response.status_code != 200:
            print(f"Error: {response.status_code} - {response.text}")
            return jsonify({'error': f"Error {response.status_code}: {response.text}"}), response.status_code

        response_data = response.json()
        raw_text = response_data['candidates'][0]['output']

        standardized_response = standardize_response(raw_text)
        if "criteria_scores" not in standardized_response:
            return jsonify({"error": "Invalid response structure: missing criteria_scores"}), 500

        return jsonify({"evaluation": standardized_response})

    except Exception as e:
        print(f"Error during evaluation: {e}")  
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

        # Use regex to extract the JSON object from the explanation
        match = re.search(r'\{\s*"ai_probability"\s*:\s*\d*\.?\d+\s*,\s*"human_probability"\s*:\s*\d*\.?\d+\s*\}', explanation)
        if match:
            probabilities = json.loads(match.group())
            ai_probability = probabilities.get('ai_probability')
            human_probability = probabilities.get('human_probability')

            # Remove the JSON part from the explanation
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
