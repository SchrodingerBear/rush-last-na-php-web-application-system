<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if creation_id is set in the session or URL
$user_id = isset($_SESSION['creation_id']) ? $_SESSION['creation_id'] : (isset($_GET['user_id']) ? $_GET['user_id'] : null);

// If user_id (creation_id) is still not found, show an error message
if (!$user_id) {
    die("User ID (creation_id) is not set in session or URL!");
}

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
            error_log("Database connection failed: " . $e->getMessage(), 3, 'errors.log');
            die("A database connection error occurred. Please try again later.");
        }
        return $this->connection;
    }
}

// Instantiate the Database object
$db = new Database();
$conn = $db->connect();

// Retrieve quiz_id from the URL
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : null;

if ($quiz_id) {
    // Fetch quiz details based on quiz_id
    $quizQuery = "
        SELECT q.title, q.subject, q.description, q.start_date, q.start_time, a.first_name, a.middle_name, a.last_name, q.creation_id
        FROM quizzes q
        JOIN academai a ON a.creation_id = q.creation_id
        WHERE q.quiz_id = ?";
    
    $stmt = $conn->prepare($quizQuery);
    $stmt->bindParam(1, $quiz_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Fetch quiz details
    $quizDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($quizDetails) {
        // Check if the logged-in user (based on creation_id) is the owner of the quiz
        if ($quizDetails['creation_id'] == $user_id) {
            // User is the quiz owner, no output shown here
        } else {
            // Fetch the participant's details
            $userQuery = "
                SELECT u.first_name, u.last_name
                FROM quiz_participation qp
                JOIN quizzes q ON q.quiz_id = qp.quiz_id
                JOIN academai u ON u.creation_id = qp.user_id
                WHERE qp.quiz_id = :quiz_id
                AND qp.user_id = :user_id
                LIMIT 1";
            
            // Prepare the query
            $stmt = $conn->prepare($userQuery);
            
            // Bind parameters
            $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            // Execute the query
            $stmt->execute();
            
            // Fetch the result
            $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If user details found, we would output the name (not shown now)
        }

        // Fetch essay questions
        $essayQuery = "SELECT * FROM essay_questions WHERE quiz_id = ?";
        $stmt = $conn->prepare($essayQuery);
        $stmt->bindParam(1, $quiz_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $essayQuestions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $essayQuestions[] = $row;
        }
        
        // If essay questions exist, you can process them (display logic removed for now)
        if (!empty($essayQuestions)) {
            foreach ($essayQuestions as $question) {
                // Logic for displaying essay questions removed
            }
        }
    } else {
        die("Quiz details not found.");
    }
} else {
    die("Quiz ID is not set!");
}
?>
