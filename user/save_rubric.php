<?php
session_start();
$creation_id = $_SESSION["creation_id"];

require_once '../classes/connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get JSON data from POST request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate rubric data to ensure no cell is empty, null, or blank
if (isset($data['data']['rows'])) {
    foreach ($data['data']['rows'] as $row) {
        if (isset($row['cells']) && is_array($row['cells'])) {
            foreach ($row['cells'] as $cell) {
                if (trim($cell) === '') {
                    echo json_encode(['success' => false, 'message' => 'All rubric cells must be filled']);
                    exit;
                }
            }
        }
    }
}

// Check if data is valid
if ($data === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

try {
    // Create database connection
    $db = new Database();
    $conn = $db->connect();
    
    // Handle different actions
    if (isset($data['action'])) {
        // Save new rubric
        if ($data['action'] === 'save_new') {
            // Validate required fields
            if (empty($data['title']) || empty($data['data'])) {
                echo json_encode(['success' => false, 'message' => 'Title and data are required']);
                exit;
            }
            
            // Begin transaction
            $conn->beginTransaction();
            
            try {
                // Calculate number of criteria from rubric data
                $num_criteria = count($data['data']['rows']);
                
                // First, insert into subjects table
                $stmt = $conn->prepare("INSERT INTO subjects (title, num_criteria, creation_id) VALUES (:title, :num_criteria, :creation_id)");
                
                $title = $data['title'];
                
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':num_criteria', $num_criteria, PDO::PARAM_INT);
                $stmt->bindParam(':creation_id', $creation_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $subject_id = $conn->lastInsertId();
                
                // Then, insert into rubrics table with the subject_id
                // Note: You'll need to alter your rubrics table to add a subject_id column
                $stmt = $conn->prepare("INSERT INTO rubrics (title, description, data, creation_id, subject_id) VALUES (:title, :description, :data, :creation_id, :subject_id)");
                
                $description = $data['description'] ?? '';
                $rubric_data = json_encode($data['data']);
                
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':data', $rubric_data);
                $stmt->bindParam(':creation_id', $creation_id);
                $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $rubric_id = $conn->lastInsertId();
                
                // Commit transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Subject and rubric saved successfully', 
                    'subject_id' => $subject_id,
                    'rubric_id' => $rubric_id
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollBack();
                throw $e;
            }
        }
        
        // Update existing rubric
        else if ($data['action'] === 'update') {
            // Validate required fields
            if (empty($data['rubric_id']) || empty($data['data'])) {
                echo json_encode(['success' => false, 'message' => 'Rubric ID and data are required']);
                exit;
            }
            
            // Begin transaction
            $conn->beginTransaction();
            
            try {
                $rubric_id = (int)$data['rubric_id'];
                
                // Get the current rubric to find subject_id, current title, and description
                $stmt = $conn->prepare("SELECT subject_id, title, description FROM rubrics WHERE id = :rubric_id");
                $stmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
                $stmt->execute();
                $rubric = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Calculate number of criteria from rubric data
                $num_criteria = count($data['data']['rows']);
                
                // Use the title and description from the input data if provided, otherwise keep existing values
                $title = isset($data['title']) && !empty($data['title']) ? $data['title'] : $rubric['title'];
                $description = isset($data['description']) ? $data['description'] : $rubric['description'];
                
                if ($rubric && $rubric['subject_id']) {
                    // Update subjects table first
                    $stmt = $conn->prepare("UPDATE subjects SET title = :title, num_criteria = :num_criteria WHERE subject_id = :subject_id");
                    
                    $stmt->bindParam(':title', $title);
                    $stmt->bindParam(':num_criteria', $num_criteria, PDO::PARAM_INT);
                    $stmt->bindParam(':subject_id', $rubric['subject_id'], PDO::PARAM_INT);
                    $stmt->execute();
                }
                
                // Update rubrics table
                $stmt = $conn->prepare("UPDATE rubrics SET title = :title, description = :description, data = :data, updated_at = CURRENT_TIMESTAMP WHERE id = :rubric_id");
                
                $rubric_data = json_encode($data['data']);
                
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':data', $rubric_data);
                $stmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
                
                $stmt->execute();
                
                // Commit transaction
                $conn->commit();
                
                echo json_encode(['success' => true, 'message' => 'Subject and rubric updated successfully']);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollBack();
                throw $e;
            }
        }
        
        else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No action specified']);
    }
} catch (PDOException $e) {
    error_log("Save rubric error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred: ' . $e->getMessage()]);
}
?>