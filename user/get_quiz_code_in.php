<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';


// Debugging: Check if `quiz_id` is received
if (!isset($_GET['quiz_id'])) {
    echo json_encode(["error" => "Quiz ID missing"]);
    exit;
}

$quiz_id = $_GET['quiz_id'];
error_log("🔍 Received quiz_id: " . $quiz_id); // Log to server

// Fetch the quiz code
$stmt = $pdo->prepare("SELECT quiz_code FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    error_log("❌ Quiz not found for ID: " . $quiz_id); // Log error
    echo json_encode(["error" => "Quiz not found"]);
    exit;
}

// Success
echo json_encode(["quiz_code" => $quiz['quiz_code']]);
?>
