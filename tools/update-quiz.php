<?php
session_start();
require_once('../classes/connection.php');

// Validate session and required data
if (!isset($_SESSION['creation_id']) || !isset($_POST['quiz_id'])) {
    echo "<script>alert('Error: Session expired or invalid request'); window.location.href='../AcademAI-Quiz-Room.php';</script>";
    exit();
}

$db = new Database();
$conn = $db->connect();

try {
    $conn->beginTransaction();

    // 1. Update quiz basic information
    $updateQuizSQL = "UPDATE quizzes SET 
        title = :title,
        subject = :subject,
        description = :description,
        start_date = :start_date,
        end_date = :end_date,
        start_time = :start_time,
        end_time = :end_time,
        is_active = :is_active,
        quiz_total_points_essay = :total_points,
         allow_file_submission = :allow_file_submission
        WHERE quiz_id = :quiz_id AND creation_id = :creation_id";

    $stmt = $conn->prepare($updateQuizSQL);
    $stmt->execute([
        ':title' => $_POST['title'],
        ':subject' => $_POST['subject'],
        ':description' => $_POST['description'],
        ':start_date' => $_POST['startDate'],
        ':end_date' => $_POST['endDate'],
        ':start_time' => $_POST['startTime'],
        ':end_time' => $_POST['endTime'],
        ':is_active' => isset($_POST['is_active']) ? 1 : 0,
        ':allow_file_submission' => isset($_POST['allow_file_submission']) ? 1 : 0, // Add this line
        ':total_points' => $_POST['quiz_total_points_essay'],
        ':quiz_id' => $_POST['quiz_id'],
        ':creation_id' => $_SESSION['creation_id']
    ]);

    $quiz_id = $_POST['quiz_id'];
    
    // 2. Get ALL existing questions (not just IDs)
    $fetchExistingSQL = "SELECT essay_id, file_name, file_upload FROM essay_questions WHERE quiz_id = :quiz_id";
    $stmt = $conn->prepare($fetchExistingSQL);
    $stmt->bindParam(':quiz_id', $quiz_id);
    $stmt->execute();
    $existingQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingQuestionIds = array_column($existingQuestions, 'essay_id');
    
    // 3. Process questions and answers
    $updatedQuestionIds = [];
    $questionsToKeep = [];
    
    // First collect all question IDs we want to keep
    foreach ($_POST['question_ids'] as $index => $questionId) {
        if ($questionId !== 'new') {
            $questionsToKeep[] = $questionId;
        }
    }
    
    // Now process each question
    foreach ($_POST['question_ids'] as $index => $questionId) {
        // Prepare answer data
        $answerText = '';
        
        if ($questionId === 'new') {
            if (isset($_POST['answers_new'][$index])) {
                $answerText = implode('|', array_filter($_POST['answers_new'][$index]));
            }
        } else {
            if (isset($_POST['answers'][$questionId])) {
                $answerText = implode('|', array_filter($_POST['answers'][$questionId]));
            }
            $updatedQuestionIds[] = $questionId;
        }
        
        // Handle file upload
// Handle file upload
$file_name = null;
$file_content = null;

if ($questionId === 'new') {
    $fileInputName = 'file-essay-new-' . $index;
    if (!empty($_FILES[$fileInputName]['name'])) {
        // Validate new file
        $allowed_types = ['application/pdf', 'application/msword', 
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES[$fileInputName]['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Invalid file type. Only PDF and Word documents allowed.");
        }
        
        $file_name = $_FILES[$fileInputName]['name'];
        $file_content = file_get_contents($_FILES[$fileInputName]['tmp_name']);
    }
} else {
    $fileInputName = 'file-essay-' . $questionId;
    
    if (!empty($_FILES[$fileInputName]['name'])) {
        // Validate replacement file
        $allowed_types = ['application/pdf', 'application/msword', 
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = $_FILES[$fileInputName]['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Invalid file type. Only PDF and Word documents allowed.");
        }
        
        $file_name = $_FILES[$fileInputName]['name'];
        $file_content = file_get_contents($_FILES[$fileInputName]['tmp_name']);
    } elseif (!isset($_POST['delete_file'][$questionId])) {
        // Keep existing file
        foreach ($existingQuestions as $eq) {
            if ($eq['essay_id'] == $questionId) {
                $file_name = $eq['file_name'];
                $file_content = $eq['file_upload'];
                break;
            }
        }
    }
}
    
        if ($questionId === 'new') {
            $insertSQL = "INSERT INTO essay_questions 
                (quiz_id, question, points_per_item, min_words, max_words, answer, rubric_id, file_name, file_upload) 
                VALUES (:quiz_id, :question, :points, :min_words, :max_words, :answer, :rubric_id, :file_name, :file_content)";
                
            $stmt = $conn->prepare($insertSQL);
            $params = [
                ':quiz_id' => $quiz_id,
                ':question' => $_POST['questions'][$index],
                ':points' => $_POST['points'][$index],
                ':min_words' => $_POST['min_words'][$index],
                ':max_words' => $_POST['max_words'][$index],
                ':answer' => $answerText,
                ':rubric_id' => $_POST['rubric_id'] ?? null,
                ':file_name' => $file_name,
                ':file_content' => $file_content
            ];
            $stmt->execute($params);
        } else {
            $updateSQL = "UPDATE essay_questions SET 
                question = :question,
                points_per_item = :points,
                min_words = :min_words,
                max_words = :max_words,
                answer = :answer,
                rubric_id = :rubric_id";
            
            if ($file_name !== null) {
                $updateSQL .= ", file_name = :file_name, file_upload = :file_content";
            } elseif (isset($_POST['delete_file'][$questionId])) {
                $updateSQL .= ", file_name = NULL, file_upload = NULL";
            }
            
            $updateSQL .= " WHERE essay_id = :essay_id AND quiz_id = :quiz_id";
            
            $stmt = $conn->prepare($updateSQL);
            $params = [
                ':question' => $_POST['questions'][$index],
                ':points' => $_POST['points'][$index],
                ':min_words' => $_POST['min_words'][$index],
                ':max_words' => $_POST['max_words'][$index],
                ':answer' => $answerText,
                ':rubric_id' => $_POST['rubric_id'] ?? null,
                ':essay_id' => $questionId,
                ':quiz_id' => $quiz_id
            ];
            
            if ($file_name !== null) {
                $params[':file_name'] = $file_name;
                $params[':file_content'] = $file_content;
            }
            
            $stmt->execute($params);
        }
    }
    
    // 4. Delete questions that were removed from the form
    $questionsToDelete = array_diff($existingQuestionIds, $questionsToKeep);
    if (!empty($questionsToDelete)) {
        // First delete any related records in other tables if needed
        $deleteAnswersSQL = "DELETE FROM quiz_answers WHERE question_id = ?";
        $stmtAnswers = $conn->prepare($deleteAnswersSQL);
        
        // Then delete the questions
        $deleteSQL = "DELETE FROM essay_questions WHERE essay_id = ? AND quiz_id = ?";
        $stmt = $conn->prepare($deleteSQL);
        
        foreach ($questionsToDelete as $delId) {
            // Delete related answers first
            $stmtAnswers->execute([$delId]);
            // Then delete the question
            $stmt->execute([$delId, $quiz_id]);
        }
    }

    $conn->commit();
    
    echo "<script>
        alert('Quiz updated successfully!');
        window.location.href='../user/AcademAI-Library-Upcoming-View-Card.php?quiz_id=$quiz_id';
    </script>";
    exit();
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo "<script>
        alert('Database error: ".addslashes($e->getMessage())."');
        window.location.href='../edit-quiz.php?quiz_id=".$_POST['quiz_id']."';
    </script>";
    exit();
} catch (Exception $e) {
    $conn->rollBack();
    echo "<script>
        alert('Error: ".addslashes($e->getMessage())."');
        window.location.href='../edit-quiz.php?quiz_id=".$_POST['quiz_id']."';
    </script>";
    exit();
}
?>