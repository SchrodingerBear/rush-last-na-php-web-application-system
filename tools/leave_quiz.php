<?php
// Start session if not already started
session_start();

// Include database connection
require_once('../classes/connection.php');

// Create a database connection
$db = new Database();
$conn = $db->connect();

// Set header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['creation_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Check if quiz_id is provided
if (!isset($_POST['quiz_id'])) {
    echo json_encode(['error' => 'Quiz ID is required']);
    exit;
}

$user_id = $_SESSION['creation_id'];
$quiz_id = $_POST['quiz_id'];

try {
    // Begin transaction
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("SELECT quiz_taker_id FROM quiz_participation WHERE user_id = ? AND quiz_id = ?");
    $stmt->execute([$user_id, $quiz_id]);
    $quiz_taker = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz_taker) {
        // If no participation found, return success with a message
        // This is more user-friendly than throwing an exception
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'No quiz participation found to delete']);
        exit;
    }
    
    $quiz_taker_id = $quiz_taker['quiz_taker_id'];
    $stmt = $conn->prepare("SELECT quiz_taker_id FROM quiz_participation WHERE user_id = ? AND quiz_id = ?");
    $stmt->execute([$user_id, $quiz_id]);
    $quiz_taker = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Now delete the answers using quiz_taker_id
    $stmt = $conn->prepare("DELETE FROM quiz_answers WHERE quiz_taker_id = ?");
    $stmt->execute([$quiz_taker_id]);

 // Then delete the essay evaluation record
 $stmt = $conn->prepare("DELETE FROM essay_evaluations WHERE student_id = ?");
 $stmt->execute([$quiz_taker_id]);
 

    // Then delete the participation record
    $stmt = $conn->prepare("DELETE FROM quiz_participation WHERE user_id = ? AND quiz_id = ?");
    $stmt->execute([$user_id, $quiz_id]);
    
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Roll back the transaction if something failed
    $conn->rollback();
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>