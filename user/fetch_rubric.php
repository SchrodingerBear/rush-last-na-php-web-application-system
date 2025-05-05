<?php
// fetch_rubric.php
require_once('../classes/connection.php');
session_start();

// Check if the user is logged in and a rubric ID is provided
if (!isset($_SESSION['creation_id']) || !isset($_GET['rubric_id'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$rubricId = intval($_GET['rubric_id']);
$creation_id = $_SESSION['creation_id'];

// Connect to the database
$db = new Database();
$conn = $db->connect();

try {
    // Fetch the rubric data
    $sql = "SELECT * FROM rubrics WHERE id = :rubric_id AND creation_id = :creation_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':rubric_id', $rubricId, PDO::PARAM_INT);
    $stmt->bindParam(':creation_id', $creation_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $rubric = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rubric) {
        echo json_encode(['error' => 'Rubric not found or not authorized']);
        exit;
    }
    
    // Return the rubric data
    echo json_encode($rubric);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>