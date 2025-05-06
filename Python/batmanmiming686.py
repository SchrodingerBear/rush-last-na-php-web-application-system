from flask import Flask, request, jsonify
from google import genai
import os
import json
import re
import time

app = Flask(__name__)

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

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=int(os.environ.get('PORT', 5000)))