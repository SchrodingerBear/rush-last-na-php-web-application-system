<?php
// get_rubric.php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set content type to JSON
header('Content-Type: application/json');

// Include database connection
require_once '../classes/connection.php';

// Check if ID parameter exists
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Rubric ID is required']);
    exit;
}

// Get rubric ID
$rubricId = intval($_GET['id']);

try {
    // Create database connection
    $db = new Database();
    $conn = $db->connect();
    
    // Fetch the specific rubric with prepared statement
    $stmt = $conn->prepare("SELECT id, title, description, data FROM rubrics WHERE id = :id");
    $stmt->bindParam(':id', $rubricId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch as associative array
    $rubric = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rubric) {
        // Decode the JSON data
        $rubricData = json_decode($rubric['data'], true);
        
        // Validate the data structure
        if (!isset($rubricData['headers']) || !isset($rubricData['rows'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid rubric data structure'
            ]);
            exit;
        }
        
        // Return success with rubric data
        echo json_encode([
            'success' => true,
            'rubric' => $rubricData,
            'title' => $rubric['title'],
            'description' => $rubric['description']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rubric not found']);
    }
} 
catch (PDOException $e) {
    // Log general error
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred']);
}
?>