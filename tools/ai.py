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
    data = request.json
    if not data or 'essay' not in data or 'rubrics_criteria' not in data:
        return jsonify({"error": "Missing required fields: essay and/or rubrics_criteria"}), 400

    essay = data['essay']
    rubrics_criteria = data['rubrics_criteria']
    levels = ', '.join(data['level']) if isinstance(data['level'], list) else data['level']
    initiate_prompt = """You are an expert essay evaluator. Assess the given essay using the rubric criteria outlined below.

    YOUR OUTPUT MUST BE IN STRICT JSON FORMAT ONLY — no extra text, no markdown. Use the **exact structure** provided below.

    For AI Detection:
    - If the essay has many grammatical issues or lacks coherence, assume it is 100% human-written.
    - If the essay uses flawless grammar, advanced vocabulary, and complex sentence structures, assume it is 100% AI-generated.

    Be RIGOROUS in your justifications. Each criterion evaluation must include:
    - ✅ Why the current level was assigned (with direct evidence from the essay)
    - ❌ Why not a higher level (specific missing elements)
    - ❌ Why not a lower level (positive elements that justify avoiding a lower level)
    - strictly include whys in all of this + """ + levels + """ in the feedback based on our requirements.   
    - 📚 Creator’s Benchmark (an ideal model or expected standard)

    JSON STRUCTURE TO FOLLOW:

    {
    "criteria_scores": {
        "Criterion Name (Weight: X%)": {
        "score": [numeric score],
        "feedback": "✅ Why [current_level]: [justification with evidence]<br>❌ Why not [higher_level]: [reasons]<br>❌ Why not [lower_level]: [reasons]<br>📚 Creator’s Benchmark: [ideal model or standard]",
        "suggestions": [
            "[suggestion 1]",
            "[suggestion 2]"
        ]
        }
    },
    "overall_weighted_score": [numeric score],
    "general_assessment": {
        "strengths": [
        "[e.g., The essay clearly explains photosynthesis and how energy flows in an ecosystem. Logical organization supports clarity.]"
        ],
        "areas_for_improvement": [
        "[e.g., Add emotional appeal using rhetorical devices. Improve transitions between sections.]"
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
        "description": "[summary of findings]",
        "overall_percentage": XX.XX,
        "overall_score": 0.XXXX,
        "sources": [],
        "success": true,
        "total_parts": X,
        "total_sources_analyzed": X,
        "total_sources_found": X
    },
    "plagiarism_sources": []
    }

    NOTES:
    - Each score must be a percentage of the criterion’s weight. For example, if weight = 20% and the essay performs excellently, score = 20.
    - Ensure the `overall_weighted_score` totals 100 or less.
    - Every `feedback` must explain why the chosen level was assigned, why a higher level was not awarded, and why a lower level was not applicable.

    RUBRIC CRITERIA:
    """ + rubrics_criteria + """

    ESSAY TO EVALUATE:
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
        raw_text = response_data['candidates'][0]['content']['parts'][0]['text']

        standardized_response = standardize_response(raw_text)
        if "criteria_scores" not in standardized_response:
            return jsonify({"error": "Invalid response structure: missing criteria_scores"}), 500

        return jsonify({"evaluation": raw_text, "prompt": initiate_prompt})

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
