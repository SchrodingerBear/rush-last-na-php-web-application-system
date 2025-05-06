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

if __name__ == '__main__':
    app.run(debug=True)