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
    $comment = $data['comment'] ?? '';

    if (!$answer_id) {
        throw new Exception('Invalid answer ID');
    }

    $stmt = $conn->prepare("UPDATE essay_evaluations 
                          SET teacher_comment = :comment 
                          WHERE answer_id = :answer_id");
    $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
    $stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database update failed');
    }
} catch (Exception $e) {
    error_log('Error saving comment: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}