<?php
session_start();
require_once('../classes/connection.php');

if (!isset($_SESSION['creation_id']) || !isset($_POST['quiz_id'])) {
    echo "<script>alert('Error: Session expired or invalid request'); window.location.href='../AcademAI-Quiz-Room.php';</script>";
    exit();
}

$db = new Database();
$conn = $db->connect();

try {
    $conn->beginTransaction();

    // Generate random quiz code function
    function generateRandomCode() {
        $letters = '';
        for ($i = 0; $i < 3; $i++) {
            $letters .= chr(rand(65, 90));
        }
        $chars = '';
        $charPool = array_merge(range('0', '9'), range('A', 'Z'));
        for ($i = 0; $i < 3; $i++) {
            $chars .= $charPool[array_rand($charPool)];
        }
        return $letters . '-' . $chars;
    }
    
    // Generate unique code
    $isUnique = false;
    $quizCode = '';
    while (!$isUnique) {
        $quizCode = generateRandomCode();
        $checkCodeSQL = "SELECT COUNT(*) FROM quizzes WHERE quiz_code = :quiz_code";
        $stmt = $conn->prepare($checkCodeSQL);
        $stmt->bindParam(':quiz_code', $quizCode);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $isUnique = true;
        }
    }

  
        // Insert new quiz with allow_file_submission field
        $insertQuizSQL = "INSERT INTO quizzes 
        (creation_id, title, subject, description, 
        start_date, end_date, start_time, end_time, 
        is_active, quiz_total_points_essay, quiz_code, allow_file_submission) 
        VALUES 
        (:creation_id, :title, :subject, :description, 
        :start_date, :end_date, :start_time, :end_time, 
        :is_active, :total_points, :quiz_code, :allow_file_submission)";


    $stmt = $conn->prepare($insertQuizSQL);
    $stmt->execute([
        ':creation_id' => $_SESSION['creation_id'],
        ':title' => $_POST['title'],
        ':subject' => $_POST['subject'],
        ':description' => $_POST['description'],
        ':start_date' => $_POST['startDate'],
        ':end_date' => $_POST['endDate'],
        ':start_time' => $_POST['startTime'],
        ':end_time' => $_POST['endTime'],
        ':is_active' => isset($_POST['is_active']) ? 1 : 0,
        ':total_points' => $_POST['quiz_total_points_essay'],
        ':quiz_code' => $quizCode,
        ':allow_file_submission' => isset($_POST['allow_file_submission']) ? 1 : 0 // Added this line
    ]);

    $new_quiz_id = $conn->lastInsertId();
    
    // Process each question with file uploads
    foreach ($_POST['question_ids'] as $index => $questionId) {
        // Handle multiple answers
        $answerText = '';
        if ($questionId === 'new') {
            if (isset($_POST['answers_new'][$index])) {
                $answerText = implode('|', array_filter($_POST['answers_new'][$index]));
            }
        } else {
            if (isset($_POST['answers'][$questionId])) {
                $answerText = implode('|', array_filter($_POST['answers'][$questionId]));
            }
        }
        
        // Handle file upload
        $file_name = null;
        $file_content = null;
        $fileInputName = ($questionId === 'new') ? 'file-essay-new-' . $index : 'file-essay-' . $questionId;
        
        if (!empty($_FILES[$fileInputName]['name'])) {
            $allowed_types = ['application/pdf', 'application/msword', 
                             'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $file_type = $_FILES[$fileInputName]['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Invalid file type. Only PDF and Word documents allowed.");
            }
            
            $file_name = $_FILES[$fileInputName]['name'];
            $file_content = file_get_contents($_FILES[$fileInputName]['tmp_name']);
        }
        
        // Insert question
        $insertSQL = "INSERT INTO essay_questions 
            (quiz_id, question, points_per_item, min_words, max_words, answer, rubric_id, file_name, file_upload) 
            VALUES (:quiz_id, :question, :points, :min_words, :max_words, :answer, :rubric_id, :file_name, :file_content)";
        
        $stmt = $conn->prepare($insertSQL);
        $stmt->execute([
            ':quiz_id' => $new_quiz_id,
            ':question' => $_POST['questions'][$index],
            ':points' => $_POST['points'][$index],
            ':min_words' => $_POST['min_words'][$index],
            ':max_words' => $_POST['max_words'][$index],
            ':answer' => $answerText,
            ':rubric_id' => !empty($_POST['rubric_id']) ? $_POST['rubric_id'] : null,
            ':file_name' => $file_name,
            ':file_content' => $file_content
        ]);
    }

    $conn->commit();
    
    // Success message with alert and redirect
    echo "<script>
        alert('Successfully created a new quiz!');
        window.location.href='../user/AcademAI-Library-Upcoming-View-Card.php?quiz_id=$new_quiz_id';
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