from flask import Flask, request, jsonify
import re
import os
import uuid
import json
import tensorflow as tf
import numpy as np
import pickle
from datetime import datetime
from tensorflow.keras.preprocessing.sequence import pad_sequences
import requests
from bs4 import BeautifulSoup
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import textwrap
import time
from flask import Flask, request, jsonify

from flask import Flask, request, jsonify
from google import genai
import os
import json
import re
import time


app = Flask(__name__)

def google_search(query, api_key, cx, num=3):
    """
    Perform a Google search and return the top results.

    Args:
        query (str): Search query
        api_key (str): Google API key
        cx (str): Google Custom Search Engine ID
        num (int): Number of results to return

    Returns:
        list: List of dictionaries containing title, link, and snippet
    """
    url = "https://www.googleapis.com/customsearch/v1"
    params = {
        "key": api_key,
        "cx": cx,
        "q": query,
        "num": num
    }

    try:
        response = requests.get(url, params=params)
        response.raise_for_status()  # Raise an exception for HTTP errors
        results = response.json()

        if "items" in results:
            return [{"title": item["title"],
                     "link": item["link"],
                     "snippet": item["snippet"]}
                    for item in results["items"]]
        else:
            return []

    except requests.exceptions.RequestException as e:
        return []

def scrape_content(url):
    """
    Scrape the main content from a webpage.

    Args:
        url (str): URL to scrape

    Returns:
        str: Extracted content
    """
    try:
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        }
        response = requests.get(url, headers=headers, timeout=10)
        response.raise_for_status()

        soup = BeautifulSoup(response.content, "html.parser")

        # Remove script and style elements
        for script in soup(["script", "style", "nav", "header", "footer"]):
            script.decompose()

        # Extract text
        text = soup.get_text(separator=' ')

        # Clean text (remove extra whitespace)
        lines = (line.strip() for line in text.splitlines())
        chunks = (phrase.strip() for line in lines for phrase in line.split("  "))
        text = ' '.join(chunk for chunk in chunks if chunk)

        return text

    except Exception as e:
        return ""

def calculate_similarity(text1, text2):
    """
    Calculate cosine similarity between two texts.

    Args:
        text1 (str): First text
        text2 (str): Second text

    Returns:
        float: Cosine similarity score (0-1)
    """
    if not text1 or not text2:
        return 0.0

    # Create TF-IDF vectorizer
    vectorizer = TfidfVectorizer()

    try:
        # Create TF-IDF matrix
        tfidf_matrix = vectorizer.fit_transform([text1, text2])

        # Calculate cosine similarity
        similarity = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:2])[0][0]
        return similarity

    except Exception as e:
        return 0.0

def split_long_query(query, words_per_chunk=500):
    """
    Split a long query into chunks of approximately 500 words.

    Args:
        query (str): Long query text
        words_per_chunk (int): Maximum number of words per chunk

    Returns:
        list: List of query chunks
    """
    words = query.split()

    # If query is shorter than the limit, return it as is
    if len(words) <= words_per_chunk:
        return [query]

    # Split into chunks of approximately 500 words
    chunks = []
    for i in range(0, len(words), words_per_chunk):
        chunk = ' '.join(words[i:i + words_per_chunk])
        chunks.append(chunk)

    return chunks

def get_plagiarism_level(similarity):
    """
    Determine plagiarism level based on similarity score.

    Args:
        similarity (float): Similarity score (0-1)

    Returns:
        tuple: (level, description, color_code)
    """
    if similarity >= 0.80:
        return ("CRITICAL", "Highly likely plagiarism", "red")
    elif similarity >= 0.60:
        return ("HIGH", "Substantial similarity detected", "orange")
    elif similarity >= 0.40:
        return ("MODERATE", "Moderate similarity detected", "yellow")
    elif similarity >= 0.20:
        return ("LOW", "Minor similarity detected", "green")
    else:
        return ("NEGLIGIBLE", "Likely original content", "blue")

def check_plagiarism(essay_text, api_key, cx):
    """Function to check plagiarism and return results in JSON format"""
    # Split the query if it's long (approximately 500 words per chunk)
    query_parts = split_long_query(essay_text)

    # Track all unique search results
    all_results = []
    unique_links = set()

    # Process each query part
    for part in enumerate(query_parts):
        # Search for this query part
        search_results = google_search(part[1], api_key, cx, num=3)

        if not search_results:
            continue

        # Add only unique links
        for result in search_results:
            if result["link"] not in unique_links:
                unique_links.add(result["link"])
                all_results.append(result)

        # Add a small delay to avoid hitting API rate limits
        if part[0] < len(query_parts) - 1:
            time.sleep(1)

    # Process each unique result
    plagiarism_results = []

    for result in all_results:
        # Scrape the content
        content = scrape_content(result['link'])
        if not content:
            continue

        # Calculate similarity against each part
        part_similarities = []
        for part in query_parts:
            similarity = calculate_similarity(part, content)
            part_similarities.append(similarity)

        # Calculate average similarity
        avg_similarity = sum(part_similarities) / len(part_similarities)

        # Calculate maximum similarity (most similar part)
        max_similarity = max(part_similarities)
        max_similar_part = part_similarities.index(max_similarity) + 1

        # Get plagiarism assessment
        plagiarism_level, description, color = get_plagiarism_level(max_similarity)

        # Store the result
        plagiarism_results.append({
            "title": result['title'],
            "link": result['link'],
            "part_similarities": [float(sim) for sim in part_similarities],
            "max_similarity": float(max_similarity),
            "max_similar_part": max_similar_part,
            "avg_similarity": float(avg_similarity),
            "plagiarism_level": plagiarism_level,
            "description": description,
            "color": color
        })

    # Sort by maximum similarity (highest first)
    plagiarism_results.sort(key=lambda x: x['max_similarity'], reverse=True)

    if not plagiarism_results:
        return {
            "success": False,
            "error": "No sources were successfully analyzed. Try again with different text."
        }

    # Calculate overall plagiarism score (weighted average of top 3)
    weights = [0.6, 0.3, 0.1]  # 60% weight to highest, 30% to second, 10% to third
    top_results = plagiarism_results[:min(3, len(plagiarism_results))]

    if len(top_results) == 1:
        overall_score = top_results[0]['max_similarity']
    elif len(top_results) == 2:
        normalized_weights = [0.7, 0.3]  # Adjust if only 2 results
        overall_score = sum(r['max_similarity'] * w for r, w in zip(top_results, normalized_weights))
    else:
        overall_score = sum(r['max_similarity'] * w for r, w in zip(top_results, weights))

    overall_level, overall_desc, overall_color = get_plagiarism_level(overall_score)

    # Return the JSON results
    return {
        "success": True,
        "overall_score": float(overall_score),
        "overall_percentage": float(overall_score * 100),
        "assessment": overall_level,
        "description": overall_desc,
        "color": overall_color,
        "total_parts": len(query_parts),
        "total_sources_found": len(all_results),
        "total_sources_analyzed": len(plagiarism_results),
        "sources": plagiarism_results
    }


# Function to validate and standardize JSON output
def standardize_response(raw_text):
    # Try to find JSON in the string if there are markdown code blocks
    json_match = re.search(r'```json\s*([\s\S]*?)\s*```', raw_text)
    if json_match:
        try:
            # Parse the JSON content from within the code block
            json_content = json.loads(json_match.group(1))
            return json_content
        except json.JSONDecodeError:
            pass
    
    # If no valid JSON in code blocks, try parsing the whole text
    try:
        return json.loads(raw_text)
    except json.JSONDecodeError:
        # If all attempts fail, return the original text wrapped in a structured format
        return {
            "evaluation": raw_text
        }

@app.route('/evaluate', methods=['POST'])
def evaluate_essay():
    # Get data from POST request
    data = request.json
    if not data or 'essay' not in data or 'rubrics_criteria' not in data:
        return jsonify({"error": "Missing required fields: essay and/or rubrics_criteria"}), 400
    
    essay = data['essay']
    rubrics_criteria = data['rubrics_criteria']
    
    # Set up Google Generative AI client
    api_key = os.environ.get('GENAI_API_KEY', "AIzaSyBHvzt_UwTBHhcw3AYV8NSdjiQASNh1vlo")
    client = genai.Client(api_key=api_key)
    
    # Comprehensive prompt to ensure consistent JSON output
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
    
    full_prompt = initiate_prompt + essay
    
    # Maximum number of retry attempts
    max_retries = 3
    retry_delay = 2  # seconds between retries
    
    for attempt in range(max_retries):
        try:
            # Generate content
            response = client.models.generate_content(
                model="gemini-2.0-flash", 
                contents=full_prompt
            )
            
            # Get raw response text
            raw_text = response.text
            
            # Check if the response contains valid JSON
            try:
                standardized_response = standardize_response(raw_text)
                # Verify if the response has the expected structure
                if "criteria_scores" not in standardized_response:
                    raise ValueError("Invalid response structure: missing criteria_scores")
                
                # Return the evaluation results on success
                return jsonify({"evaluation": raw_text})
                
            except (json.JSONDecodeError, ValueError) as json_error:
                if attempt == max_retries - 1:  # If this was the last attempt
                    raise json_error
                print(f"Attempt {attempt+1} failed with JSON validation error: {str(json_error)}. Retrying...")
                time.sleep(retry_delay)
                continue
                
        except Exception as e:
            if attempt < max_retries - 1:
                print(f"Attempt {attempt+1} failed with error: {str(e)}. Retrying in {retry_delay} seconds...")
                time.sleep(retry_delay)
                continue
            else:
                # Return error with fallback JSON structure after all retries fail
                error_message = f"Failed after {max_retries} attempts. Last error: {str(e)}"
                return jsonify({
                    "evaluation": "```json\n{\n  \"criteria_scores\": {},\n  \"overall_weighted_score\": 0,\n  \"general_assessment\": {\n    \"strengths\": [],\n    \"areas_for_improvement\": []\n  },\n  \"error\": \"" + error_message + "\"\n}\n```"
                }), 500

# Optional: More advanced retry with exponential backoff
def generate_with_backoff(client, model, contents, max_retries=5):
    """Helper function to handle retries with exponential backoff"""
    for attempt in range(max_retries):
        try:
            response = client.models.generate_content(
                model=model,
                contents=contents
            )
            return response
        except Exception as e:
            if attempt < max_retries - 1:
                backoff_time = (2 ** attempt) + (0.1 * attempt)  # Exponential backoff
                print(f"Attempt {attempt+1} failed: {str(e)}. Retrying in {backoff_time:.2f} seconds...")
                time.sleep(backoff_time)
            else:
                raise e




    from flask import Flask, request, jsonify
import os
import json
import re
from google import genai

app = Flask(__name__)

@app.route('/evaluate', methods=['POST'])
def evaluate_essay():
    # Get data from POST request
    data = request.json
    if not data or 'essay' not in data or 'rubrics_criteria' not in data:
        return jsonify({"error": "Missing required fields: essay and/or rubrics_criteria"}), 400
    
    essay = data['essay']
    rubrics_criteria = data['rubrics_criteria']
    
    # Check if this is an auto-generate request
    is_auto_generate = rubrics_criteria == "auto-generate"
    
    # Extract row and column counts if provided
    row_count = 4  # Default
    column_count = 4  # Default
    
    # Parse the essay for row and column parameters if specified
    if "row_count:" in essay:
        row_match = re.search(r'row_count:\s*(\d+)', essay)
        if row_match:
            row_count = int(row_match.group(1))
            # Limit row count to reasonable range
            row_count = max(2, min(8, row_count))
    
    if "column_count:" in essay:
        col_match = re.search(r'column_count:\s*(\d+)', essay)
        if col_match:
            column_count = int(col_match.group(1))
            # Limit column count to reasonable range
            column_count = max(2, min(5, column_count))
    
    # Set up Google Generative AI client
    api_key = os.environ.get('GENAI_API_KEY', "AIzaSyBHvzt_UwTBHhcw3AYV8NSdjiQASNh1vlo")
    client = genai.Client(api_key=api_key)
    
    # Construct prompt with dynamic row and column counts
    headers_str = ", ".join([f'"Level {i+1} ({column_count+1-i})"' for i in range(column_count)])
    rows_str = ""
    
    for i in range(row_count):
        cells_str = ", ".join([f'"LEVEL {j+1} DESCRIPTION"' for j in range(column_count)])
        weight = 100 // row_count  # Distribute weights evenly
        extra = 100 % row_count  # Handle remainder
        
        # Add extra weight to first criterion if there's a remainder
        if i == 0:
            weight += extra
            
        rows_str += f"""
    {{
      "criteria": "CRITERION NAME {i+1}",
      "cells": [{cells_str}, "{weight}"]
    }}{"," if i < row_count-1 else ""}"""
    
    # Create the JSON template with dynamic structure
    json_template = f"""{{
  "headers": [{headers_str}, "Weight %"],
  "rows": [{rows_str}
  ]
}}"""
    
    # Construct the full prompt
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
    
    try:
        # Generate content
        response = client.models.generate_content(
            model="gemini-2.0-flash",
            contents=full_prompt
        )
        response_text = response.text
        
        # Try to extract JSON from the response
        json_match = re.search(r'\{[\s\S]*"headers"[\s\S]*"rows"[\s\S]*\}', response_text)
        if json_match:
            json_str = json_match.group(0)
            try:
                # Validate that it's proper JSON
                json_data = json.loads(json_str)
                # Validate structure
                if "headers" in json_data and "rows" in json_data:
                    # Return the cleaned JSON
                    return jsonify({"evaluation": json_data})
                else:
                    return jsonify({"evaluation": response_text})
            except json.JSONDecodeError:
                # If JSON parsing fails, return the original text
                return jsonify({"evaluation": response_text})
        else:
            # If no JSON pattern found, return the original text
            return jsonify({"evaluation": response_text})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

 

@app.route('/check-plagiarism', methods=['POST'])
def api_check_plagiarism():
    """API endpoint to check plagiarism"""
    # Check if request has JSON data
    if not request.is_json:
        return jsonify({"success": False, "error": "Request must be JSON"}), 400

    data = request.json

    # Check if required fields are present
    if 'essay' not in data:
        return jsonify({"success": False, "error": "Missing required field: essay"}), 400

    # Get API credentials
    api_key = data.get('api_key', "AIzaSyDnMPyjZv76NaXXXJhsykc6BU7FP-gvdX8")
    cx = data.get('cx', "4233ed3f6435f4486")

    # Check plagiarism
    result = check_plagiarism(data['essay'], api_key, cx)

    return jsonify(result)

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({"status": "ok"})

os.makedirs('results', exist_ok=True)

# Global variables for model and tokenizer
model = None
tokenizer = None
max_sequence_length = 500

# Text preprocessing function
def preprocess_text(text):
    if isinstance(text, str):
        # Convert to lowercase
        text = text.lower()
        # Remove special characters, keeping only letters and spaces
        text = re.sub(r'[^a-zA-Z\s]', '', text)
        # Remove extra spaces
        text = re.sub(r'\s+', ' ', text).strip()
        return text
    return ""

# Function to split text into chunks
def split_into_chunks(text, chunk_size=350, overlap=50):
    """Split text into overlapping chunks of approximately chunk_size words."""
    words = text.split()
    chunks = []

    if len(words) <= chunk_size:
        return [text]

    i = 0
    while i < len(words):
        chunk = ' '.join(words[i:i + chunk_size])
        chunks.append(chunk)
        i += chunk_size - overlap  # Move forward with overlap

    return chunks

# Function to load the model and tokenizer
def load_model_and_tokenizer():
    try:
        global model, tokenizer
        # Load model
        model = tf.keras.models.load_model('ai_text_detection_model.keras')
        
        # Load tokenizer
        with open('tokenizer.pickle', 'rb') as handle:
            tokenizer = pickle.load(handle)
            
        print("Model and tokenizer loaded successfully!")
        return True
    except Exception as e:
        print(f"Error loading model or tokenizer: {e}")
        return False

# Function to predict using the loaded model
def predict_with_model(text):
    """
    Make predictions using the loaded TensorFlow model.
    """
    global model, tokenizer, max_sequence_length
    
    # Preprocess the text
    processed_text = preprocess_text(text)
    
    # Split into chunks for longer texts
    chunks = split_into_chunks(processed_text)
    
    chunk_details = []
    chunk_predictions = []
    
    for i, chunk in enumerate(chunks):
        # Tokenize and pad the sequence
        sequence = tokenizer.texts_to_sequences([chunk])
        padded = pad_sequences(sequence, maxlen=max_sequence_length)
        
        # Make prediction
        prediction = float(model.predict(padded)[0][0])
        
        # Calculate probabilities
        ai_probability = prediction
        human_probability = 1 - ai_probability
        
        chunk_predictions.append(ai_probability)
        
        # Store details for each chunk
        chunk_details.append({
            "chunk_id": i + 1,
            "text": chunk[:100] + "..." if len(chunk) > 100 else chunk,  # Preview of chunk
            "ai_probability": ai_probability * 100,
            "human_probability": human_probability * 100
        })
    
    # Calculate average prediction across all chunks
    avg_ai_prob = sum(chunk_predictions) / max(len(chunk_predictions), 1)
    
    # Determine overall classification
    if avg_ai_prob > 0.5:
        classification = "AI-generated"
        confidence = avg_ai_prob * 100
    else:
        classification = "Human-written"
        confidence = (1 - avg_ai_prob) * 100
    
    return {
        "classification": classification,
        "confidence": confidence,
        "ai_probability": avg_ai_prob * 100,
        "human_probability": (1 - avg_ai_prob) * 100,
        "chunk_details": chunk_details
    }

# Initialize function to be called during app startup
def initialize_app():
    global model, tokenizer
    
    if model is None or tokenizer is None:
        print("Initializing AI Detection API with TensorFlow model...")
        success = load_model_and_tokenizer()
        if not success:
            print("WARNING: Failed to load model. Using simulation mode.")
    
    return True

# API routes
@app.route('/api/analyze', methods=['POST'])
def analyze_essay():
    # Ensure initialization
    initialize_app()

    if not request.is_json:
        return jsonify({"error": "Request must be JSON"}), 400

    data = request.get_json()

    if 'essay' not in data:
        return jsonify({"error": "No essay provided"}), 400

    essay = data.get('essay', '')

    if len(essay) < 50:
        return jsonify({"error": "Essay too short for accurate analysis (minimum 50 characters)"}), 400

    # Generate a unique ID for this analysis
    analysis_id = str(uuid.uuid4())

    # Get prediction from model
    if model is not None and tokenizer is not None:
        result = predict_with_model(essay)
    else:
        # Fallback to simulation if model failed to load
        return jsonify({"error": "Model not available. Please check server logs."}), 500

    # Add timestamp and ID
    result['timestamp'] = datetime.now().isoformat()
    result['analysis_id'] = analysis_id

    # Save the result
    with open(f'results/{analysis_id}.json', 'w') as f:
        json.dump(result, f)

    # Return the analysis
    return jsonify(result)

@app.route('/api/results/<analysis_id>', methods=['GET'])
def get_analysis(analysis_id):
    try:
        with open(f'results/{analysis_id}.json', 'r') as f:
            result = json.load(f)
        return jsonify(result)
    except FileNotFoundError:
        return jsonify({"error": "Analysis not found"}), 404

@app.route('/api/health', methods=['GET'])
def health_check():
    # Check if model is loaded
    model_status = "loaded" if model is not None else "not loaded"
    tokenizer_status = "loaded" if tokenizer is not None else "not loaded"
    
    return jsonify({
        "status": "healthy" if model is not None and tokenizer is not None else "degraded",
        "model_status": model_status,
        "tokenizer_status": tokenizer_status
    })

@app.route('/', methods=['GET'])
def root():
    return jsonify({
        "service": "AI Text Detection API",
        "status": "running",
        "model_loaded": model is not None and tokenizer is not None,
        "endpoints": {
            "/api/analyze": "POST - Submit an essay for analysis",
            "/api/results/<analysis_id>": "GET - Retrieve a previously analyzed result",
            "/api/health": "GET - Check service health"
        }
    })

# Initialize the app at startup
with app.app_context():
    initialize_app()

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=int(os.environ.get('PORT', 5000)))