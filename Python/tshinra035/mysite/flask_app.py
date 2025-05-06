import requests
from bs4 import BeautifulSoup
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import textwrap
import time
from flask import Flask, request, jsonify

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

if __name__ == "__main__":
    app.run(debug=True)