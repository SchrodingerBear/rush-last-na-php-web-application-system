<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$response = ["success" => false, "error" => "Unknown error"];

try {
    require '../classes/connnection.php'; // Ensure database connection is included

    // Debugging: Log incoming POST data
    file_put_contents('debug.log', print_r($_POST, true)); 

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['quiz_id'])) {
        $quiz_id = $_POST['quiz_id'];

        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE quiz_id = :quiz_id");
        $stmt->execute(['quiz_id' => $quiz_id]);

        if ($stmt->rowCount() > 0) {
            $response = ["success" => true];
        } else {
            $response = ["success" => false, "error" => "Quiz not found or already deleted."];
        }
    } else {
        $response = ["success" => false, "error" => "Invalid request. No quiz_id received."];
    }
} catch (PDOException $e) {
    $response = ["success" => false, "error" => $e->getMessage()];
}

echo json_encode($response);
exit;

?>
