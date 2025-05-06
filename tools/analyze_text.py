from utils import check_plagiarism

text_to_analyze = """Please check the plagiarism of the following text by comparing it with publicly available sources on the web using an external API (like Google Custom Search API or SerpApi). Break the text into smaller sections (e.g., 500-1,000 characters each) to ensure faster processing. For each section, perform a web search to find matching content. If matches are found, calculate the percentage of similarity based on the results. Also, apply string matching or NLP-based similarity algorithms (e.g., cosine similarity or Jaccard similarity) to detect paraphrased content. Ensure that the analysis is efficient and provides a detailed plagiarism percentage."""

results, highlighted_text, plagiarism_score, originality_score, message = check_plagiarism(text_to_analyze)

print("\nPlagiarism Analysis Results:")
print("-" * 50)
print(f"Plagiarism Score: {plagiarism_score:.1f}%")
print(f"Originality Score: {originality_score:.1f}%")
print("\nMatched Sources:")
print("-" * 50)
for result in results:
    print(f"URL: {result['url']}")
    print(f"Similarity: {result['similarity']:.1f}%")
    print("-" * 30)

if message:
    print(f"\nMessage: {message}")