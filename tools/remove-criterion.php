<?php

require_once('../classes/connection.php');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['criteria_id'])) {
    $criteriaId = $data['criteria_id'];
    $db = (new Database())->connect(); // Create a new instance and connect

    try {
        // Get the subject ID for the deleted criterion
        $stmtSubject = $db->prepare("SELECT subject_id FROM criteria WHERE criteria_id = ?");
        $stmtSubject->execute([$criteriaId]);
        $subjectId = $stmtSubject->fetchColumn();

        // Prepare and execute the deletion query
        $stmt = $db->prepare("DELETE FROM criteria WHERE criteria_id = ?");
        $stmt->execute([$criteriaId]);

        if ($stmt->rowCount() > 0) {
            // Update num_criteria in the subjects table
            $stmtUpdate = $db->prepare("UPDATE subjects SET num_criteria = num_criteria - 1 WHERE subject_id = ?");
            $stmtUpdate->execute([$subjectId]);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete criterion or criterion not found.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input: criteria_id not set.']);
}
?>


