<?php
// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("../classes/connection.php");

// Create an instance of the Database class
$database = new Database();
$pdo = $database->connect();

if (!isset($pdo)) {
    die("PDO connection not established.");
}

$creation_id = $_SESSION['creation_id']; // Get creation_id from session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['subject_id'])) {
        $subject_id = intval($_POST['subject_id']);

        try {
            // Start transaction
            $pdo->beginTransaction();

            // Delete associated criteria first
            $deleteCriteriaStmt = $pdo->prepare("DELETE FROM criteria WHERE subject_id = :subject_id");
            $deleteCriteriaStmt->bindParam(':subject_id', $subject_id);
            $deleteCriteriaStmt->execute();

            // Now delete the subject
            $deleteSubjectStmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = :subject_id AND creation_id = :creation_id");
            $deleteSubjectStmt->bindParam(':subject_id', $subject_id);
            $deleteSubjectStmt->bindParam(':creation_id', $creation_id);
            $deleteSubjectStmt->execute();

            // Commit transaction
            $pdo->commit();

            // Respond with success and redirect URL
            echo json_encode(['success' => true, 'redirect' => '../user/AcademAI-Essay-Viewing-Rubric-Setting.php']);
        } catch (PDOException $e) {
            // Rollback transaction in case of error
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Subject ID not provided.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
