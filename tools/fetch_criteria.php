<?php
include('../classes/connection.php');
header('Content-Type: application/json');

if (isset($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];

    // Connect to the database
    $db = new Database();
    $conn = $db->connect();

    // Fetch criteria for the given subject_id
    $sql = "SELECT criteria_name, advanced_text, proficient_text, needs_improvement_text, warning_text, weight 
            FROM criteria 
            WHERE subject_id = :subject_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
    $stmt->execute();
    $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($criteria);
} else {
    echo json_encode([]); // Return an empty array if no subject_id is provided
}
?>
