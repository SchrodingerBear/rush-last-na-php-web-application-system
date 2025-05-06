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

        print(f"Text to check: {text_to_check}")

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

            print(f"Gemini API response: {explanation}")

            # Use regex to extract the JSON object from the explanation
            match = re.search(r'\{\s*"plagiarism_score"\s*:\s*\d*\.?\d+\s*,\s*"message"\s*:\s*".*?"\s*,\s*"plagiarism_results"\s*:\s*\[.*?\]\s*\}', explanation)
            if match:
                gemini_results = json.loads(match.group())
                plagiarism_score = gemini_results.get('plagiarism_score')
                message = gemini_results.get('message')
                plagiarism_results = gemini_results.get('plagiarism_results')
            else:
                return jsonify({'error': "Could not extract JSON with plagiarism data from Gemini AI response."}), 500

        print(f"Plagiarism results: {plagiarism_results}")
        print(f"Plagiarism score: {plagiarism_score}")
        print(f"Message: {message}")

        response = {
            'plagiarism_score': plagiarism_score,
            'message': message,
            'sources': plagiarism_results
        }

        return jsonify(response)

    except Exception as e:
        print(f"Error: {e}")
        return jsonify({'error': str(e)}), 500
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
