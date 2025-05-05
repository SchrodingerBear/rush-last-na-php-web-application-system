<?php
// Database connection settings
$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

// Establish PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

if (isset($_GET['quiz_id'])) {
    $quiz_id = $_GET['quiz_id'];

    try {
        $sql = "SELECT quiz_code FROM quizzes WHERE quiz_id = :quiz_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $quiz_code = $row['quiz_code'];

            echo json_encode(['quiz_code' => $quiz_code]);
        } else {
            echo json_encode(['error' => 'Quiz not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No quiz_id provided']);
}
?>
