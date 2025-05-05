<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

if (!isset($_GET['quiz_id']) || empty($_GET['quiz_id'])) {
    echo json_encode(['error' => 'No quiz_id provided']);
    exit;
}

$quiz_id = $_GET['quiz_id'];
$user_id = $_GET['user_id'] ?? null;

try {
    // Check if the quiz exists
    $checkQuiz = $pdo->prepare("SELECT quiz_code FROM quizzes WHERE quiz_id = :quiz_id");
    $checkQuiz->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $checkQuiz->execute();
    $quiz_code = $checkQuiz->fetchColumn();

    if (!$quiz_code) {
        echo json_encode(['error' => 'Quiz not found.']);
        exit;
    }

    // Check if user is in quiz_participation
    if ($user_id) {
        $checkUser = $pdo->prepare("SELECT COUNT(*) FROM quiz_participation WHERE quiz_id = :quiz_id AND user_id = :user_id");
        $checkUser->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $checkUser->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $checkUser->execute();
        $userParticipated = $checkUser->fetchColumn();

        if (!$userParticipated) {
            echo json_encode(['error' => 'User has not joined this quiz.']);
            exit;
        }
    }

    echo json_encode(['quiz_code' => $quiz_code]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
