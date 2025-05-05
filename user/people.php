<?php
// Database Class definition
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'academaidb';
    protected $connection;

    public function connect() {
        try {
            $this->connection = new PDO("mysql:host=$this->host;dbname=$this->database", 
                                        $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Log error to a file
            error_log("Database connection failed: " . $e->getMessage(), 3, 'errors.log');
            // Show a generic message to the user
            die("A database connection error occurred. Please try again later.");
        }
        return $this->connection;
    }
}

// Instantiate the Database object
$db = new Database();
$conn = $db->connect();

// Initialize participants as an empty array to prevent undefined variable issues
$participants = [];

// Get the quiz_id from the URL
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : null;

if ($quiz_id) {
    // Query to get the quiz start and end times
    $quizQuery = "SELECT start_date, end_date FROM quizzes WHERE quiz_id = ?";
    $stmt = $conn->prepare($quizQuery);
    $stmt->bindParam(1, $quiz_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $quizDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($quizDetails) {
        $startDate = strtotime($quizDetails['start_date']);
        $endDate = strtotime($quizDetails['end_date']);
        $currentDate = time();
        
        // Query to get participants and their status
        $participantsQuery = "
            SELECT u.first_name, u.last_name, qp.status, qp.join_date
            FROM quiz_participation qp
            JOIN academai u ON u.user_id = qp.user_id
            WHERE qp.quiz_id = :quiz_id";
        
        $stmt = $conn->prepare($participantsQuery);
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Get participants as an associative array
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update participant statuses
        foreach ($participants as &$participant) {
            if ($participant['status'] === 'completed') {
                $participant['status'] = 'Completed';
            } elseif ($participant['status'] === 'taking') {
                $participant['status'] = 'Taking';
            } elseif ($currentDate > $endDate) {
                $participant['status'] = 'Not Taken';
            } elseif ($currentDate < $startDate) {
                $participant['status'] = 'Pending';
            }
        }
    }
}

?>
