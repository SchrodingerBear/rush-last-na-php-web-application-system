<?php
session_start();

// Database connection
$host = 'localhost'; // Database host
$dbname = 'academaidb'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the quiz_id is provided
    if (isset($_POST['quiz_id'])) {
        $quiz_id = $_POST['quiz_id'];
        
        // First, check if the user has permission to delete this quiz
        $checkQuery = "SELECT creation_id FROM quizzes WHERE quiz_id = :quiz_id";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $checkStmt->execute();
        $quiz = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify that the quiz exists and belongs to the current user
        if ($quiz && isset($_SESSION["creation_id"]) && $_SESSION["creation_id"] == $quiz['creation_id']) {
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // 1. First, get all quiz_taker_ids associated with this quiz
                $taker_ids_query = "SELECT quiz_taker_id FROM quiz_participation WHERE quiz_id = :quiz_id";
                $taker_ids_stmt = $pdo->prepare($taker_ids_query);
                $taker_ids_stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
                $taker_ids_stmt->execute();
                $taker_ids = $taker_ids_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // 2. Delete from quiz_answers for each quiz_taker_id (if there are any takers)
                if (!empty($taker_ids)) {
                    $placeholders = implode(',', array_fill(0, count($taker_ids), '?'));
                    $delete_answers_query = "DELETE FROM quiz_answers WHERE quiz_taker_id IN ($placeholders)";
                    $delete_answers_stmt = $pdo->prepare($delete_answers_query);
                    foreach ($taker_ids as $index => $id) {
                        $delete_answers_stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
                    }
                    $delete_answers_stmt->execute();
                }
                
                // 3. Delete from quiz_participation
                $delete_participation_query = "DELETE FROM quiz_participation WHERE quiz_id = :quiz_id";
                $delete_participation_stmt = $pdo->prepare($delete_participation_query);
                $delete_participation_stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
                $delete_participation_stmt->execute();
                
                // 4. Delete from essay_questions
                $delete_essay_query = "DELETE FROM essay_questions WHERE quiz_id = :quiz_id";
                $delete_essay_stmt = $pdo->prepare($delete_essay_query);
                $delete_essay_stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
                $delete_essay_stmt->execute();
                
                // 5. Delete any other related records (add more as needed)
                // Example: Delete from rubrics if they're linked to this quiz
                // $delete_rubrics_query = "DELETE FROM rubrics WHERE quiz_id = :quiz_id";
                // $delete_rubrics_stmt = $pdo->prepare($delete_rubrics_query);
                // $delete_rubrics_stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
                // $delete_rubrics_stmt->execute();
                
                // 6. Finally, delete the quiz itself
                $delete_quiz_query = "DELETE FROM quizzes WHERE quiz_id = :quiz_id";
                $delete_quiz_stmt = $pdo->prepare($delete_quiz_query);
                $delete_quiz_stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
                $delete_quiz_stmt->execute();
                
                $delete_quiz_query1 = "DELETE FROM essay_evaluations WHERE quiz_id = :quiz_id";
                $delete_quiz_stmt1 = $pdo->prepare($delete_quiz_query1);
                $delete_quiz_stmt1->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
                $delete_quiz_stmt1->execute();
                

                // Commit transaction
                $pdo->commit();
                
                $response['success'] = true;
                $response['message'] = 'Quiz deleted successfully';
            } catch (Exception $e) {
                // Roll back transaction on error
                $pdo->rollBack();
                $response['message'] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'You do not have permission to delete this quiz';
        }
    } else {
        $response['message'] = 'Quiz ID not provided';
    }
} catch (PDOException $e) {
    $response['message'] = 'Database connection failed: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>