<?php
session_start();
header('Content-Type: application/json');

$host   = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

error_log("Script started.");

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful.");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$currentDateTime = date('Y-m-d H:i:s');
error_log("Current DateTime: " . $currentDateTime);

error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("POST Data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_code'])) {
    $quizCode = $_POST['quiz_code'];
    error_log("Quiz Code: " . $quizCode);

    $query = "SELECT * FROM quizzes WHERE quiz_code = :quiz_code";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':quiz_code', $quizCode);
    $stmt->execute();

    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quiz) {
        error_log("Quiz found: " . print_r($quiz, true));
        
        // Check if current user is the quiz owner
        if (isset($_SESSION['creation_id']) && $quiz['creation_id'] == $_SESSION['creation_id']) {
            error_log("Quiz owner attempted to join their own quiz.");
            echo json_encode(['error' => 'You cannot join your own quiz as the quiz owner.']);
            exit;
        }

        $startDateTime = $quiz['start_date'] . ' ' . $quiz['start_time'];
        $endDateTime   = $quiz['end_date']   . ' ' . $quiz['end_time'];

        if ($currentDateTime < $startDateTime) {
            error_log("Quiz is upcoming.");
            echo json_encode([
                'status'     => 'upcoming',
                'quiz_id'    => $quiz['quiz_id'],
                'start_date' => $quiz['start_date'],
                'start_time' => $quiz['start_time'],
            ]);
        } elseif ($currentDateTime >= $startDateTime && $currentDateTime <= $endDateTime) {
            error_log("Quiz is running.");
            echo json_encode([
                'status' => 'running',
                'quiz_id' => $quiz['quiz_id']
            ]);
        } else {
            error_log("Quiz is done.");
            echo json_encode([
                'status'    => 'done',
                'quiz_id'   => $quiz['quiz_id'],
                'end_date'  => $quiz['end_date'],
                'end_time'  => $quiz['end_time']
            ]);
        }
    } else {
        error_log("Invalid quiz code.");
        echo json_encode(['error' => 'Invalid quiz code.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_quiz']) && isset($_POST['quiz_id'])) {
    error_log("Join quiz request detected.");

    if (!isset($_SESSION['creation_id'])) {
        error_log("User not logged in.");
        echo json_encode(['error' => 'User not logged in.']);
        exit;
    }

    $userId = $_SESSION['creation_id'];
    $quizId = $_POST['quiz_id'];
    
    $debug = [];
    $debug[] = "User ID: " . $userId;
    $debug[] = "Quiz ID: " . $quizId;

    // Check if the quiz exists and get creator ID
    try {
        $parentQuery = "SELECT creation_id FROM quizzes WHERE quiz_id = :quiz_id";
        $parentStmt = $conn->prepare($parentQuery);
        $parentStmt->bindValue(':quiz_id', (int)$quizId, PDO::PARAM_INT);
        $parentStmt->execute();
        $quizData = $parentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quizData) {
            error_log("No quiz exists with quiz_id {$quizId}.");
            echo json_encode([
                'error' => "No quiz exists with quiz_id {$quizId}.",
                'debug' => $debug
            ]);
            exit;
        }

        // Check if user is the quiz owner
        if ($quizData['creation_id'] == $userId) {
            error_log("Quiz owner attempted to join their own quiz.");
            echo json_encode(['error' => 'You cannot join your own quiz as the creator.']);
            exit;
        }
    } catch (PDOException $e) {
        $debug[] = "Parent check SQL Error: " . $e->getMessage();
        error_log("Parent check SQL Error: " . $e->getMessage());
        echo json_encode(['error' => 'Parent check error: ' . $e->getMessage(), 'debug' => $debug]);
        exit;
    }
    
    // Rest of your existing participation check and insertion code...
    $checkQuery = "SELECT * FROM quiz_participation WHERE user_id = :user_id AND quiz_id = :quiz_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
    $checkStmt->bindValue(':quiz_id', (int)$quizId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        $debug[] = "User already joined this quiz.";
        error_log("User already joined this quiz.");
        echo json_encode(['message' => 'You have already joined this quiz.', 'debug' => $debug]);
        exit;
    }

    try {
        $insertQuery = "INSERT INTO quiz_participation (quiz_id, user_id, join_date, status) 
                        VALUES (:quiz_id, :user_id, NOW(), 'pending')";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindValue(':quiz_id', (int)$quizId, PDO::PARAM_INT);
        $insertStmt->bindValue(':user_id', (int)$userId, PDO::PARAM_INT);
        
        if ($insertStmt->execute()) {
            $debug[] = "Insert successful.";
            error_log("Insert successful.");
            echo json_encode(['success' => true, 'redirect' => 'AcademAI-Activity-Upcoming-Card.php', 'debug' => $debug]);
        } else {
            $debug[] = "Insert failed for an unknown reason.";
            error_log("Insert failed for an unknown reason.");
            echo json_encode(['error' => 'Failed to insert record.', 'debug' => $debug]);
        }
    } catch (PDOException $e) {
        $debug[] = "SQL Error: " . $e->getMessage();
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['error' => 'SQL Error: ' . $e->getMessage(), 'debug' => $debug]);
    }
}
?>