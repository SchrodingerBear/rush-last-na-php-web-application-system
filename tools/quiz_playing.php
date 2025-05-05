<?php
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Get the quiz ID from POST data
$quizId = $_POST['quiz_id'] ?? null;

// Debugging line to check quiz_id
error_log("Quiz ID: " . $quizId); // This will log to the PHP error log

if ($quizId) {
    // Check if there are essay questions linked to this quiz
    $query = "SELECT COUNT(*) AS cnt FROM essay_questions WHERE quiz_id = :quiz_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':quiz_id', $quizId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['cnt'] > 0) {
        echo json_encode(['has_essay' => true]);
    } else {
        echo json_encode(['has_essay' => false]);
    }
} else {
    echo json_encode(['error' => 'Quiz ID not provided.']);
}
?>
