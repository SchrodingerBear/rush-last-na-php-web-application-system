<?php
include('../classes/connection.php');

if(isset($_GET['quiz_id'])) {
    $quiz_id = $_GET['quiz_id'];
    
    $db = new Database();
    $conn = $db->connect();
    
    try {
        // Include creation_id in your SELECT statement
        $query = "SELECT a.creation_id, a.first_name, a.middle_name, a.last_name, qp.join_date, qp.status
                  FROM quiz_participation qp
                  INNER JOIN academai a ON qp.user_id = a.creation_id
                  WHERE qp.quiz_id = :quiz_id";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($participants);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Quiz ID is required']);
}
?>