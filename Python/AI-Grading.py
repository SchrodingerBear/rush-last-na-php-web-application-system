import mysql.connector
import torch
from transformers import AutoModelForSequenceClassification, AutoTokenizer
import sys  
import logging
logging.getLogger("transformers.modeling_utils").setLevel(logging.ERROR)


# ✅ Step 1: Connect to the Database
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="academaidb"
)
cursor = db.cursor(dictionary=True)  # Fetch results as dictionaries

# ✅ Step 2: Load AI Model (RoBERTa for scoring)
model_name = "roberta-large-mnli"
tokenizer = AutoTokenizer.from_pretrained(model_name)
model = AutoModelForSequenceClassification.from_pretrained(model_name)

# ✅ Step 3: Retrieve Rubric Criteria for the Given Subject
def get_rubric_criteria(subject_id):
    """Fetch rubric criteria dynamically based on the subject"""
    cursor.execute("SELECT criteria_id, criteria_name, weight FROM criteria WHERE subject_id = %s", (subject_id,))
    return cursor.fetchall()  # Returns a list of dictionaries containing criteria info


# ✅ Step 4: Retrieve Teacher's Expected Answer
def get_teacher_answer(question_id):
    cursor.execute("SELECT * FROM essay_questions WHERE essay_id = %s", (question_id,))
    return cursor.fetchone()

# ✅ Step 5: Retrieve Student's Essay Answers Based on quiz_taker_id
def get_student_answers(quiz_taker_id):
    cursor.execute("SELECT * FROM quiz_answers WHERE quiz_taker_id = %s", (quiz_taker_id,))
    return cursor.fetchall()

# ✅ Step 6: AI-Based Scoring of Essay
from difflib import SequenceMatcher
from difflib import SequenceMatcher
from difflib import SequenceMatcher

def score_essay(student_answer, rubric_criteria, question_id):
    # Fetch the correct answer
    correct_answer = get_teacher_answer(question_id)

    # Debugging: print the fetched correct_answer and student_answer
    print(f"Debug: Correct Answer: {correct_answer}")  # Debug line
    print(f"Debug: Student Answer: {student_answer}")  # Debug line

    # Ensure correct_answer and student_answer are both strings
    if not isinstance(student_answer, str):
        student_answer = ""  # Default to empty string if not a string

    # Process the correct answer if it's a list or dict
    if isinstance(correct_answer, list):
        correct_answer = " ".join([item.get("text", "") for item in correct_answer if isinstance(item, dict)])
    elif isinstance(correct_answer, dict):
        correct_answer = correct_answer.get("text", "")
    elif not isinstance(correct_answer, str):
        correct_answer = ""  # Default empty string if no valid answer

    # Debugging: print the processed correct_answer
    print(f"Debug: Processed Correct Answer: {correct_answer}")  # Debug line

    # Now safely use .lower() on both strings
    similarity = SequenceMatcher(None, student_answer.lower(), correct_answer.lower()).ratio() * 100

    # Debugging: print the similarity score
    print(f"Debug: Similarity Score: {similarity}")

    # Assign feedback dynamically based on similarity score
    if similarity >= 90:
        feedback = "Impressive performance! Your content is highly relevant, thorough, and accurate."
    elif similarity >= 75:
        feedback = "Well done! Your essay is well-organized and relevant to the topic."
    elif similarity >= 50:
        feedback = "Good effort! Your response is somewhat relevant but needs more details."
    else:
        feedback = "Needs improvement. Your response lacks relevance or sufficient details."

    # Get rubric criteria dynamically
    ai_scores = {}  # Dictionary to store AI evaluation results

    total_weight = 0
    total_weighted_score = 0

    # Check rubric criteria is not empty
    print(f"Debug: Rubric Criteria: {rubric_criteria}")

    for criterion in rubric_criteria:
        criteria_id = criterion['criteria_id']
        criteria_name = criterion['criteria_name']
        max_weight = criterion['weight']

        # Ensure the weight is valid (greater than 0)
        if max_weight <= 0:
            print(f"Warning: Invalid weight for {criteria_name}, skipping.")
            continue

        # Weighted score based on similarity
        weighted_score = (similarity / 100) * max_weight

        # Debugging: print weighted score for each criterion
        print(f"Debug: Weighted Score for {criteria_name}: {weighted_score}")

        # Store the weighted score and feedback for each criterion
        ai_scores[criteria_id] = {
            "Criteria Name": criteria_name,
            "Raw Score": similarity,
            "Weighted Score": round(weighted_score, 2),
            "Feedback": feedback
        }

        # Accumulate total weight and total weighted score for overall score calculation
        total_weight += max_weight
        total_weighted_score += weighted_score

    # Calculate and return the overall score
    if total_weight > 0:
        overall_score = (total_weighted_score / total_weight) * 100
    else:
        overall_score = 0  # In case there are no valid rubric criteria with weight

    print(f"Debug: Overall Score: {overall_score}")  # Debug line

    return ai_scores, round(overall_score, 2)  # Return both individual AI scores and the overall score



# ✅ Step 7: Store AI Scores and Feedback
def save_ai_grades(quiz_taker_id, question_id, subject_id, answer_id, scores):
    """Save AI grades dynamically while preventing duplicates"""
    
    for criteria_id, details in scores.items():
        try:
            # Check if AI grade already exists for this quiz taker and question
            cursor.execute("""
                SELECT COUNT(*) AS count FROM ai_grades 
                WHERE quiz_taker_id = %s AND question_id = %s AND criteria_id = %s AND answer_id = %s
            """, (quiz_taker_id, question_id, criteria_id, answer_id))
            result = cursor.fetchone()

            if result["count"] > 0:
                print(f"⚠️ Skipping duplicate AI grade for criteria {criteria_id}")
                continue

            # Insert AI grade
            cursor.execute("""
                INSERT INTO ai_grades (quiz_taker_id, question_id, subject_id, criteria_id, ai_score, feedback, answer_id)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
            """, (quiz_taker_id, question_id, subject_id, criteria_id, details["Weighted Score"], details["Feedback"], answer_id))

        except Exception as e:
            print(f"❌ SQL Error: {e}")

    db.commit()


# ✅ Step 8: Store Essay Score
def save_essay_score(quiz_taker_id, question_id, scores, answer_id):
    """Save final essay score dynamically based on AI evaluation"""
    
    # Calculate total weighted score for this question
    total_weighted_score = sum(details["Weighted Score"] for details in scores.values())
    
    # Get cumulative total for the quiz taker
    cursor.execute("""
        SELECT COALESCE(SUM(total_weighted_score), 0) AS total_weighted_criteria_score
        FROM essay_scores WHERE quiz_taker_id = %s
    """, (quiz_taker_id,))
    result = cursor.fetchone()
    total_weighted_criteria_score = result["total_weighted_criteria_score"] + total_weighted_score if result else total_weighted_score

    # Convert weighted percentage into equivalent points
    total_points_criteria_score = (total_weighted_score / 100) * 10  # Example: Convert to 10-point scale

    try:
        # Check for duplicate entry
        cursor.execute("""
            SELECT COUNT(*) AS count FROM essay_scores 
            WHERE quiz_taker_id = %s AND question_id = %s AND answer_id = %s
        """, (quiz_taker_id, question_id, answer_id))
        result = cursor.fetchone()

        if result["count"] > 0:
            print(f"⚠️ Duplicate essay score detected for question {question_id}, skipping.")
            return

        # Insert new essay score dynamically
        cursor.execute("""
            INSERT INTO essay_scores (quiz_taker_id, question_id, total_weighted_score, total_weighted_criteria_score, total_points_criteria_score, answer_id)
            VALUES (%s, %s, %s, %s, %s, %s)
        """, (quiz_taker_id, question_id, total_weighted_score, total_weighted_criteria_score, total_points_criteria_score, answer_id))

        db.commit()

    except Exception as e:
        print(f"❌ SQL Error: {e}")
        db.rollback()

# ✅ Step 9: Store Final Quiz Score
def save_quiz_score(quiz_taker_id, subject_id, total_quiz_score):
    try:
        cursor.execute("""
            INSERT INTO quiz_scores (quiz_taker_id, subject_id, total_score)
            VALUES (%s, %s, %s)
        """, (quiz_taker_id, subject_id, total_quiz_score))
        
        db.commit()
        print("✅ Debug: quiz_scores inserted successfully.")

    except Exception as e:
        print(f"❌ SQL Error: Failed to insert quiz score - {e}")
        db.rollback()


# ✅ Step 10: Main Function to Run the AI Grading System
def grade_essays(quiz_taker_id):
    student_answers = get_student_answers(quiz_taker_id)
    if not student_answers:
        print("❌ Error: No answers found for this quiz_taker_id.")
        return

    total_quiz_score = 0
    total_weighted_criteria_score = 0  

    for answer in student_answers:
        question_id = answer["question_id"]
        answer_id = answer["answer_id"]

        cursor.execute("""
          SELECT s.subject_id 
          FROM subjects s
          JOIN essay_questions eq ON s.subject_id = eq.rubric_id
          WHERE eq.essay_id = %s
        """, (question_id,))
        subject = cursor.fetchone()

        if not subject:
            print(f"❌ Error: No subject found for question_id {question_id}. Skipping...")
            continue
        subject_id = subject["subject_id"]

        rubric_criteria = get_rubric_criteria(subject_id)
        teacher_answer = get_teacher_answer(question_id)  # Ensure question_id is passed

        if not rubric_criteria:
            print(f"❌ Error: No rubric criteria for subject_id {subject_id}. Skipping question_id {question_id}.")
            continue

        scores, total_weighted_score = score_essay(answer["answer_text"], rubric_criteria, question_id)  # Pass question_id here

        points_per_item = teacher_answer["points_per_item"]
        total_weighted_criteria_score += total_weighted_score  
        total_points_criteria_score = total_weighted_criteria_score  

        save_ai_grades(quiz_taker_id, question_id, subject_id, answer_id, scores)

        total_points = save_essay_score(
            quiz_taker_id,
            question_id,
            total_weighted_score,
            total_weighted_criteria_score,
            total_points_criteria_score,
            answer_id
        )

        total_quiz_score += total_points

    save_quiz_score(quiz_taker_id, subject_id, total_quiz_score)

    print(f"✅ All essays graded for quiz_taker_id {quiz_taker_id}! Total Score: {total_quiz_score}")


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("❌ Error: Please provide quiz_taker_id as an argument.")
        sys.exit(1)

    quiz_taker_id = int(sys.argv[1])
    grade_essays(quiz_taker_id)

    cursor.close()
    db.close()
