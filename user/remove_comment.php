<?php
require_once('../classes/connection.php');

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    $answer_id = filter_var($data['answer_id'] ?? null, FILTER_VALIDATE_INT);

    if (!$answer_id) {
        throw new Exception('Invalid answer ID');
    }

    $stmt = $conn->prepare("UPDATE essay_evaluations 
                          SET teacher_comment = NULL 
                          WHERE answer_id = :answer_id");
    $stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database update failed');
    }
} catch (Exception $e) {
    error_log('Error removing comment: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}