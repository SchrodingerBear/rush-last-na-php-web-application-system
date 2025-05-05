<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/remove_participant_errors.log');

class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'academaidb';
    protected $connection;

    public function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host=$this->host;dbname=$this->database;charset=utf8mb4",
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $this->connection;
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed", 500);
        }
    }
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed', 405);
    }

    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $quiz_id = filter_var($input['quiz_id'] ?? null, FILTER_VALIDATE_INT);
    $user_id = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$quiz_id || !$user_id) {
        throw new Exception('Both quiz_id and user_id are required and must be integers', 400);
    }

    // Check session authentication
    if (!isset($_SESSION['creation_id'])) {
        throw new Exception('Authentication required', 401);
    }

    // Database operations
    $db = new Database();
    $conn = $db->connect();
    
    // Verify quiz ownership
    $stmt = $conn->prepare("SELECT creation_id FROM quizzes WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        throw new Exception('Quiz not found', 404);
    }

    if ($quiz['creation_id'] != $_SESSION['creation_id']) {
        throw new Exception('You do not own this quiz', 403);
    }

    // Get quiz_taker_id for this user/quiz combination
    $stmt = $conn->prepare("SELECT quiz_taker_id FROM quiz_participation WHERE quiz_id = ? AND user_id = ?");
    $stmt->execute([$quiz_id, $user_id]);
    $participation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participation) {
        throw new Exception('No participant found with those IDs', 404);
    }

    $quiz_taker_id = $participation['quiz_taker_id'];

    // Perform deletion with transaction
    $conn->beginTransaction();
    
    try {
        // 1. First delete all evaluations for this participant's answers
        $stmt = $conn->prepare("
            DELETE e FROM essay_evaluations e
            JOIN quiz_answers a ON e.answer_id = a.answer_id
            WHERE a.quiz_taker_id = ?
        ");
        $stmt->execute([$quiz_taker_id]);
        
        // 2. Then delete all answers by this participant
        $stmt = $conn->prepare("DELETE FROM quiz_answers WHERE quiz_taker_id = ?");
        $stmt->execute([$quiz_taker_id]);
        
        // 3. Finally delete the participation record
        $stmt = $conn->prepare("DELETE FROM quiz_participation WHERE quiz_taker_id = ?");
        $stmt->execute([$quiz_taker_id]);
        
        $deletedCount = $stmt->rowCount();
        $conn->commit();
        
        if ($deletedCount === 0) {
            throw new Exception('No participant found with those IDs', 404);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Participant and all related data removed successfully',
            'removed_count' => $deletedCount
        ]);
        
    } catch (PDOException $e) {
        $conn->rollBack();
        throw new Exception('Database operation failed: ' . $e->getMessage(), 500);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    exit;
}
?>