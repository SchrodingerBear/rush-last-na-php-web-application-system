<?php
require_once '../classes/connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get JSON data from POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if data is valid
if ($data === null || !isset($data['rubric_id'])) {
    echo json_encode(['success' => false, 'message' => 'Rubric ID is required']);
    exit;
}

try {
    // Create database connection
    $db = new Database();
    $conn = $db->connect();
    
    // Start transaction
    $conn->beginTransaction();
    
    // Sanitize input
    $rubric_id = (int)$data['rubric_id'];
    
    // First get the subject_id from the rubric
    $stmt = $conn->prepare("SELECT subject_id FROM rubrics WHERE id = :rubric_id");
    $stmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['subject_id'])) {
        $subject_id = $result['subject_id'];
        
        // Delete the rubric first (child record)
        $stmt = $conn->prepare("DELETE FROM rubrics WHERE id = :rubric_id");
        $stmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Then delete the subject (parent record)
            $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = :subject_id");
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Rubric and subject deleted successfully']);
            } else {
                throw new Exception('Failed to delete subject');
            }
        } else {
            throw new Exception('Failed to delete rubric');
        }
    } else {
        throw new Exception('Rubric not found or no associated subject');
    }
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    error_log("Delete rubric error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>