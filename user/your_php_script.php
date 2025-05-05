<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!isset($_SESSION['creation_id'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

$creation_id = $_SESSION['creation_id'];

// Fetch upcoming quizzes
$upcomingQuery = "
    SELECT quiz_id, title, subject, start_date, end_date, start_time, end_time 
    FROM quizzes 
    WHERE creation_id = :creation_id 
    AND (
        (start_date > CURDATE()) 
        OR (start_date = CURDATE() AND start_time > CURTIME())
    )
";
$upcomingStmt = $conn->prepare($upcomingQuery);
$upcomingStmt->bindParam(':creation_id', $creation_id, PDO::PARAM_INT);
$upcomingStmt->execute();
$upcomingQuizzes = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch ongoing quizzes
$ongoingQuery = "
    SELECT quiz_id, title, subject, start_date, end_date, start_time, end_time 
    FROM quizzes 
    WHERE creation_id = :creation_id 
    AND STR_TO_DATE(CONCAT(start_date, ' ', start_time), '%Y-%m-%d %H:%i:%s') <= NOW()
    AND STR_TO_DATE(CONCAT(end_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') >= NOW()
";
$ongoingStmt = $conn->prepare($ongoingQuery);
$ongoingStmt->bindParam(':creation_id', $creation_id, PDO::PARAM_INT);
$ongoingStmt->execute();
$ongoingQuizzes = $ongoingStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch completed quizzes
$completedQuery = "
    SELECT quiz_id, title, subject, start_date, end_date, start_time, end_time 
    FROM quizzes 
    WHERE creation_id = :creation_id 
    AND STR_TO_DATE(CONCAT(end_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') < NOW()
";
$completedStmt = $conn->prepare($completedQuery);
$completedStmt->bindParam(':creation_id', $creation_id, PDO::PARAM_INT);
$completedStmt->execute();
$completedQuizzes = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'upcoming' => $upcomingQuizzes,
    'ongoing' => $ongoingQuizzes,
    'completed' => $completedQuizzes
]);
?>
