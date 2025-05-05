<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Check if the user is logged in
if (!isset($_SESSION['creation_id'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

try {
    $currentDateTime = new DateTime();
    $userId = $_SESSION['creation_id'];

    // Fetch quizzes the user has joined with creator information
    $query = "SELECT quizzes.*, 
              COALESCE(quiz_participation.status, 'pending') AS status,
              creator.email AS creator_email,
              creator.first_name AS creator_first_name,
              creator.middle_name AS creator_middle_name,
              creator.last_name AS creator_last_name,
              creator.photo_path AS creator_photo
              FROM quiz_participation
              INNER JOIN quizzes ON quiz_participation.quiz_id = quizzes.quiz_id
              INNER JOIN academai AS creator ON quizzes.creation_id = creator.creation_id
              WHERE quiz_participation.user_id = :userId";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $joinedQuizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no quizzes found
    if (empty($joinedQuizzes)) {
        echo json_encode([
            'runningQuizzes'  => [],
            'upcomingQuizzes' => [],
            'completedQuizzes' => [],
            'missedQuizzes' => [],
            'debug' => 'No joined quizzes found in the database.'
        ]);
        exit;
    }

    // Initialize arrays
    $runningQuizzes = [];
    $upcomingQuizzes = [];
    $completedQuizzes = [];
    $missedQuizzes = [];

    // Categorize quizzes
    foreach ($joinedQuizzes as $quiz) {
        $quizStatus = strtolower($quiz['status']);

        // Skip if essential date/time fields are missing
        if (empty($quiz['start_date']) || empty($quiz['start_time']) || 
            empty($quiz['end_date']) || empty($quiz['end_time'])) {
            continue;
        }

        try {
            $startDateTime = new DateTime($quiz['start_date'] . ' ' . $quiz['start_time']);
            $endDateTime = new DateTime($quiz['end_date'] . ' ' . $quiz['end_time']);

            // First check if quiz is completed
            if ($quizStatus === 'completed') {
                $completedQuizzes[] = $quiz;
            }
            // Then check if currently running
            elseif ($currentDateTime >= $startDateTime && $currentDateTime <= $endDateTime) {
                $runningQuizzes[] = $quiz;
            }
            // Then check if upcoming
            elseif ($currentDateTime < $startDateTime) {
                $upcomingQuizzes[] = $quiz;
            }
            // Everything else is considered missed (including unrestricted quizzes past end time)
            else {
                $missedQuizzes[] = $quiz;
            }
        } catch (Exception $e) {
            // Log date parsing errors but continue processing other quizzes
            error_log("Error parsing dates for quiz ID {$quiz['quiz_id']}: " . $e->getMessage());
            continue;
        }
    }

    // Return categorized quizzes
    echo json_encode([
        'runningQuizzes'  => $runningQuizzes,
        'upcomingQuizzes' => $upcomingQuizzes,
        'completedQuizzes' => $completedQuizzes,
        'missedQuizzes' => $missedQuizzes,
        'debug' => [
            'currentDateTime' => $currentDateTime->format('Y-m-d H:i:s'),
            'joinedQuizzesCount' => count($joinedQuizzes),
            'runningCount' => count($runningQuizzes),
            'upcomingCount' => count($upcomingQuizzes),
            'completedCount' => count($completedQuizzes),
            'missedCount' => count($missedQuizzes),
            'message' => 'Quizzes categorized successfully.'
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>