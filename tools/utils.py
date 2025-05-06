import streamlit as st
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
from sentence_transformers import SentenceTransformer

import nltk
import os
import shutil

# Ensure a clean nltk_data directory
nltk_path = os.path.join(os.getcwd(), "nltk_data")
if os.path.exists(nltk_path):
    shutil.rmtree(nltk_path)  # Remove old data to avoid corruption
os.makedirs(nltk_path, exist_ok=True)

# Set NLTK data path
nltk.data.path.append(nltk_path)

# Download only necessary NLTK resources
nltk.download('punkt', download_dir=nltk_path)
nltk.download('punkt_tab', download_dir=nltk_path)
nltk.download('stopwords', download_dir=nltk_path)


def add_custom_css():
    st.markdown("""
    <style>
    body {
        background-color: #5f5f5f;
        color: gray;
    }
    .highlight { background-color: red; }
    .input-text { 
        background-color: #2c3e50; 
        color: white; 
        padding: 10px; 
        border-radius: 5px; 
        margin-bottom: 10px;
        max-height: 300px;
        overflow-y: auto;
    } 
    .detection-result { 
        background-color: #e74c3c;
        color: white; 
        padding: 10px; 
        border-radius: 5px; 
        margin-bottom: 10px;
    }
    .detection-score {
        font-size: 24px;
        font-weight: bold;
        text-align: center;
        margin: 10px 0;
    }
    .disclaimer { 
        background-color: #f39c12;
        color: white;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
    } 
    .source-website { 
        background-color: #2c3e50; 
        color: white; 
        padding: 10px; 
        border-radius: 5px; 
        margin-bottom: 10px; 
    }
    .source-website a { 
        color: #3498db;
        text-decoration: none;
    }
    .source-website a:hover {
        text-decoration: underline;
    }

    /* Scrollbar styling */
    .input-text::-webkit-scrollbar {
        width: 8px;
    }
    .input-text::-webkit-scrollbar-track {
        background: #34495e;
        border-radius: 4px;
    }
    .input-text::-webkit-scrollbar-thumb {
        background: #95a5a6;
        border-radius: 4px;
    }
    .input-text::-webkit-scrollbar-thumb:hover {
        background: #7f8c8d;
    }

    div.stButton > button { 
        background-color: #403e3e; 
        color: white !important; 
        border: none; 
        padding: 10px 20px; 
        font-size: 16px; 
        border-radius: 5px; 
        transition: background-color 0.3s; 
    } 
    div.stButton > button:hover, 
    div.stButton > button:active, 
    div.stButton > button:focus { 
        background-color: #45a049; 
        color: white !important; 
    } 
    .section-header { 
        background-color: #34495e; 
        color: white; 
        padding: 5px 10px; 
        font-size: 14px; 
        font-weight: bold; 
    } 
    .plot-container {
        background-color: #2c3e50;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .detection-score {
        display: flex;
        font-size: 1.2rem;
        font-weight: bold;
        text-align: center;
        justify-content: center;
    }
    .stFileUploader > div > div > button {
        color: rgb(157, 166, 177) !important;
    }
    .stFileUploader > div > small {
        color: rgb(157, 166, 177) !important;
    } 
    </style>
    """, unsafe_allow_html=True)

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