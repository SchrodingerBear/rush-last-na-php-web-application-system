<?php
session_start();
$creation_id = $_SESSION["creation_id"];
require_once '../classes/connection.php';

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Create database connection
    $db = new Database();
    $conn = $db->connect();
    
    // Get all rubrics from the database with prepared statement
    $stmt = $conn->prepare("SELECT id, title, description, created_at, updated_at FROM rubrics  WHERE creation_id = $creation_id  ORDER BY updated_at DESC");
    $stmt->execute();
    
    $rubrics = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rubrics[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    // Return the rubrics as JSON
    echo json_encode($rubrics);
} catch (PDOException $e) {
    error_log("Get all rubrics error: " . $e->getMessage());
    echo json_encode([]);
}
?>