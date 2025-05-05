
<?php
require_once('../include/extension_links.php');
// ✅ Ensure session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Check if `creation_id` exists in session
if (!isset($_SESSION['creation_id'])) {
    die("Error: creation_id is NOT set in session.");
}

// ✅ Check if `quiz_id` is provided in the query string
if (!isset($_GET['quiz_id'])) {
    die("Error: quiz_id is missing from the request.");
}

$quiz_id = $_GET['quiz_id']; // Get quiz_id from URL

// ✅ Define a new Database class to avoid conflicts
class MyDatabase {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'academaidb';
    protected $connection;

    public function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host=$this->host;dbname=$this->database", 
                $this->username, 
                $this->password
            );
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage(), 3, 'errors.log');
            die("A database connection error occurred. Please try again later.");
        }
        return $this->connection;
    }
}

// ✅ Initialize the database connection if not already set
if (!isset($GLOBALS['pdo'])) {
    $db = new MyDatabase();
    $pdo = $db->connect();
    $GLOBALS['pdo'] = $pdo;
}

// ✅ Ensure `$pdo` is available
$pdo = $GLOBALS['pdo'] ?? null;
if (!$pdo) {
    die("Error: Database connection not initialized.");
}



// ✅ Check if user has already submitted this quiz (NEW CODE)
$submissionCheck = $pdo->prepare(
    "SELECT status FROM quiz_participation 
     WHERE quiz_id = ? AND user_id = ?"
);
$submissionCheck->execute([$quiz_id, $_SESSION['creation_id']]);
$participation = $submissionCheck->fetch(PDO::FETCH_ASSOC);

if ($participation && $participation['status'] === 'completed') {
    // Store in session to trigger modal later
    $_SESSION['already_submitted'] = true;
}





// ✅ Fetch the correct `quiz_taker_id` using `quiz_id` and `creation_id`
$stmt = $pdo->prepare("SELECT quiz_taker_id FROM quiz_participation WHERE quiz_id = ? AND user_id = ?");
$stmt->execute([$quiz_id, $_SESSION['creation_id']]);
$quiz_taker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz_taker) {
    die("Error: No quiz participation record found for this user in this quiz.");
}
// ✅ Store `quiz_taker_id` in session (ONLY removing the duplicate line)
$_SESSION['quiz_taker_id'] = $quiz_taker['quiz_taker_id'];

// [Rest of your original code remains exactly the same]
require_once('../include/extension_links.php');
require_once('../tools/quiz_ongoing.php');

// Fetch quiz and essay questions based on quiz_id - YOUR ORIGINAL QUERY
$quiz_id = $_GET['quiz_id'];
$essayQuestionsQuery = "SELECT * FROM essay_questions WHERE quiz_id = :quiz_id ORDER BY num_quiz ASC";
$stmt = $conn->prepare($essayQuestionsQuery);  // Keeping your original $conn
$stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
$stmt->execute();
$essayQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Extract `rubric_id` from the first question (your original code)
if (!empty($essayQuestions)) {
    $rubric_id = $essayQuestions[0]['rubric_id'];
} else {
    die("Error: No essay questions found for this quiz.");
}

// Fetch end_date and end_time for the quiz - YOUR ORIGINAL QUERY
$endDateTimeQuery = "SELECT end_date, end_time, is_active, allow_file_submission FROM quizzes WHERE quiz_id = :quiz_id";
$stmt = $pdo->prepare($endDateTimeQuery);
$stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
$stmt->execute();
$endDateTime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$endDateTime) {
    die("Error: End date and time not found for this quiz.");
}

// Add end_date and end_time to the $quizDetails array - YOUR ORIGINAL CODE
$quizDetails['end_date'] = $endDateTime['end_date'];
$quizDetails['end_time'] = $endDateTime['end_time'];
$quizDetails['is_active'] = $endDateTime['is_active'];
// NEW: Add the file submission setting
$quizDetails['allow_file_submission'] = $endDateTime['allow_file_submission'];


// Fetch user details - ADDED THIS TO FIX THE userDetails ERROR BUT KEPT YOUR STYLE
$userQuery = "SELECT first_name, middle_name, last_name, email, photo_path FROM academai WHERE creation_id = ?";
$stmt = $pdo->prepare($userQuery);
$stmt->execute([$_SESSION['creation_id']]);
$userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userDetails) {
    die("Error: User details not found.");
}
?>




<?php
// [Previous PHP code remains exactly the same]
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Essay Quiz | AcademAI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1B4242;
            --primary-dark: #092635;
            --secondary-color: #5C8374;
            --accent-color: #9EC8B9;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --success-color: #4bb543;
            --warning-color: #ffcc00;
            --danger-color: #f44336;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
           
        }

        .container-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 300px;
            background: white;
            padding: 2rem 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), 0 6px 20px rgba(0, 0, 0, 0.1); 
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .quiz-info-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1), 0 6px 20px rgba(0, 0, 0, 0.1); 
        }

        .quiz-info-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--primary-dark);
        }

        .quiz-info-card p {
            font-size: 0.87em;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .quiz-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
            border-bottom:1px solid #e0e0e0;
        }

        .meta-item {
            flex: 1 1 45%;
        }

        .meta-item span {
            font-size: 1em;
            font-weight: 600;
            color: #092635;
            margin-bottom: 0.25rem;
        }

        .meta-item p {
            font-size: 0.875em;
          
            color: var(--primary-dark);
        }

        .restriction-setting {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0;
        }

        .form-check-label {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }

        .text-sm {
            font-size: 0.8125rem;
        }

        .text-[#5C8374] {
            color: var(--secondary-color);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 300px;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .text-4xl {
            font-size: 2.25rem;
        }

        .font-bold {
            font-weight: 700;
        }

        .text-[#092635] {
            color: var(--primary-dark);
        }

        .text-[#5C8374] {
            color: var(--secondary-color);
        }

        .text-lg {
            font-size: 1.125rem;
        }

        .text-[#1B4242] {
            color: var(--primary-color);
        }

        .mt-2 {
            margin-top: 0.5rem;
        }

        .timer {
            background: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            box-shadow: var(--box-shadow);
            font-weight: 500;
            color: var(--primary-color);
        }

        /* Quiz Content */
        .quiz-container {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .question-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .rubric-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .rubric-link:hover {
            background: rgba(27, 66, 66, 0.1);
        }

        .question-content {
            margin-bottom: 2rem;
        }

        .question-number {
            font-size: 0.875em;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .question-text {
            font-size: 1em;
            font-weight: 500;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            color: var(--primary-dark);
        }

        .answer-section {
            margin-bottom: 2rem;
        }

        .answer-label {
            display: block;
            font-size: 0.875em;
            font-weight: 500;
            margin-bottom: 0.75rem;
            color: var(--primary-dark);
        }

        textarea {
            width: 100%;
            min-height: 200px;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1em;
            resize: vertical;
            transition: var(--transition);
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(27, 66, 66, 0.2);
        }

        .word-count {
            font-size: 0.87em;
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }

        .word-error {
            font-size: 0.875em;
            color: var(--danger-color);
            margin-top: 0.5rem;
            display: none;
        }

        .file-upload-section {
            margin: 2rem 0;
            text-align: center;
            position: relative;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: var(--secondary-color);
            font-size: 0.87em;
        }

        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e9ecef;
        }

        .divider::before {
            margin-right: 1rem;
        }

        .divider::after {
            margin-left: 1rem;
        }

        .file-upload-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--primary-dark);
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
        }

        .file-input-button {
            background: var(--primary-dark);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius:5px;
            font-weight: 500;
            cursor: pointer;
            font-size:0.875em;
            transition: var(--transition);
        }

        .file-input-button:hover {
            background: var(--primary-dark);
        }

        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--border-radius);
            font-size: 0.8125rem;
        }

        .file-preview p {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Navigation Buttons */
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            border-top: 1px solid #e9ecef;
            padding-top: 10px;
        }

        .nav-button {
            padding: 0.75rem 1.5rem;
            border-radius:5px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-button {
            background: white;
            color: var(--secondary-color);
            border: 1px solid #e9ecef;
        }

        .back-button:hover {
            background: #f8f9fa;
        }

        .next-button {
            background: var(--secondary-color);
            color: white;
            border: none;
        }

        .next-button:hover {
            background: var(--accent-color);
        }

        .submit-button {
            background: #1b4242;
            color: white;
            border: none;
        }

        .submit-button:hover {
            background: var(--secondary-color);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 3% auto;
            max-width: 500px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--primary-dark);
        }

        .close {
            font-size: 1.5rem;
            font-weight: 300;
            color: var(--secondary-color);
            cursor: pointer;
            background: none;
            border: none;
        }

        .modal-body {
            padding: 1.5rem;
            text-align: center;
        }

        .modal-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            margin-bottom: 1rem;
        }

        .modal-text {
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-secondary {
            background: white;
            color: var(--secondary-color);
            border: 1px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        /* Loading Spinner */
        .loading-spinner {
            display: flex;
            justify-content: center;
            margin: 1rem 0;
        }

        .spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid rgba(92, 131, 116, 0.1);
            border-top: 4px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Progress Bar */
        .progress {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: var(--secondary-color);
            border-radius: 4px;
            transition: width 0.6s ease;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .container-wrapper {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .quiz-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .meta-item {
                flex: 1 1 100%;
            }
            
            .navigation-buttons {
                flex-direction: column-reverse;
                gap: 0.75rem;
            }
            
            .nav-button {
                width: 100%;
                justify-content: center;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .text-4xl {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
    <!-- Sidebar with quiz information -->
<!-- Sidebar with quiz information -->
<div class="sidebar" style="box-shadow: 0 4px 20px rgba(27, 66, 66, 0.15);"> <!-- Enhanced box shadow -->
    <div class="sidebar-header">
        <h2>Quiz Details</h2>
    </div>
    
    <div class="quiz-info-card">
        <!-- Quiz Title with Label -->
        <div style="margin-bottom: 1rem;">
            <span style="display: block; font-size: 1em; color: #092635; margin-bottom: 0.25rem; font-weight:600;">Title:</span>
            <p style=" font-size: 0.875em; color: #092635; margin: 0;"><?php echo htmlspecialchars($quizDetails['title']); ?></p>
        </div>
        
        <!-- Subject with Label -->
        <div style="margin-bottom: 1rem;">
            <span style="display: block; font-size: 1em; color: #092635; margin-bottom: 0.25rem; font-weight:600;">Subject:</span>
            <p style="font-size: 0.875em; color: #092635; margin: 0;"><?php echo htmlspecialchars($quizDetails['subject']); ?></p>
        </div>
        
        <!-- Description with Label -->
        <div style="margin-bottom: 1.5rem;">
            <span style="display: block; font-size: 1em; color: #092635; margin-bottom: 0.25rem; font-weight:600;">Description:</span>
            <p style="font-size: 0.875em; color: #092635; margin: 0;"><?php echo htmlspecialchars($quizDetails['description']); ?></p>
        </div>


         <!-- Quiz Creator with Label -->
         <div style="margin-bottom: 1.5rem;">
            <span style="display: block; font-size: 1em; color: #092635; margin-bottom: 0.25rem; font-weight:600;">Created by:</span>
            <p style="font-size: 0.875em; color: #092635; margin: 0;"><?php echo htmlspecialchars($quizDetails['first_name'] . ' ' . $quizDetails['last_name']); ?></p>
        </div>
        
        <div class="quiz-meta">     
            <div class="meta-item">
                <span>Start Date</span>
                <p><?php echo htmlspecialchars($quizDetails['start_date']); ?></p>
            </div>
            
            <div class="meta-item">
                <span>Start Time</span>
                <p><?php echo htmlspecialchars($quizDetails['start_time']); ?></p>
            </div>
            
            <div class="meta-item">
                <span>End Date</span>
                <p><?php echo htmlspecialchars($quizDetails['end_date'] ?? 'Not available'); ?></p>
            </div>
            
            <div class="meta-item">
                <span>End Time</span>
                <p><?php echo htmlspecialchars($quizDetails['end_time'] ?? 'Not available'); ?></p>
            </div>
        </div>


        <?php
$allowFileSubmission = isset($quizDetails['allow_file_submission']) && $quizDetails['allow_file_submission'] == 1;
?>

<!-- File Submission Status Message (place this where you want the notice to appear) -->
<div class="file-submission-status" style="margin: 15px 0; padding: 10px 5px; border-radius: 4px; 
     background-color: <?php echo $allowFileSubmission ? '#e8f5e9' : '#ffebee'; ?>;
     color: <?php echo $allowFileSubmission ? '#2e7d32' : '#c62828'; ?>;
     font-size: <?php echo $allowFileSubmission ? '0.875em' : '0.875em'; ?>;
     border-left: 4px solid <?php echo $allowFileSubmission ? '#4caf50' : '#f44336'; ?>;">
    <i class="fas <?php echo $allowFileSubmission ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
    <?php echo $allowFileSubmission 
        ? 'File submissions are permitted for this quiz (DOC, DOCX, PDF)' 
        : 'No file submissions are permitted for this quiz'; ?>
</div>
        
        <div class="restriction-setting" style="margin: 1.5rem 0; padding: 1rem 0; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0;">
            <p class="text-sm text-[#5C8374]">
                <?php if ($quizDetails['is_active'] == 1): ?>
                    <i class="fas fa-lock"></i> Restricted: Quiz will auto-close at <?php echo htmlspecialchars($quizDetails['end_time']); ?> on <?php echo htmlspecialchars($quizDetails['end_date']); ?>
                <?php else: ?>
                    <i class="fas fa-unlock"></i> Unrestricted: The quiz will remain open even after the deadline.
                <?php endif; ?>
            </p>
        </div>


        
        <!-- Total Points Display (Moved below restriction) -->
        <div class="total-points" style="margin-top: 1.5rem; ">
            <div style="display: inline-block;">
                <span style="font-size: 1.2em; font-weight: 600; color: #092635;">Total: </span>
                <span style="font-size: 1.2em; font-weight: 600; color: #1B4242;">
                    <?php
                    $totalPoints = 0;
                    foreach ($essayQuestions as $question) {
                        $totalPoints += isset($question['points']) ? (int)$question['points'] : 10;
                    }
                    echo $totalPoints . ' Points';
                    ?>
                </span>
            </div>
        </div>
    </div>

            
            <div class="progress-section">
                <h3 style = "font-size:1.5em; color:#092635">Progress</h3>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo (1/count($essayQuestions))*100; ?>%" 
                        aria-valuenow="<?php echo (1/count($essayQuestions))*100; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p class="text-sm text-[#5C8374]">Question 1 of <?php echo count($essayQuestions); ?></p>
            </div>
        </div>
        
        <!-- Main content area -->
        <div class="main-content">
    <div class="header">
        <!-- Dark Mode Toggle Button (Top Right) -->
   
        <div class="user-info">
            <h1 class="font-bold " style = "color: #092635;font-size:2.5em;">Hello, <span class="text-[#5C8374] dark:text-[#9EC8B9]"><?php echo htmlspecialchars($userDetails['first_name']); ?></span> 👋</h1>
            <p class="text-[#1B4242] dark:text-[#E5E7EB] mt-2" style = "font-size:1.2em">Welcome to your quiz portal.</p>
        </div>
        <div class="timer dark:bg-[#1B4242] dark:text-[#E5E7EB]">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="display: flex; align-items: center; gap: 0.5rem; font-size:1em; color:#092635;">
                    <!-- Medium Clock Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="dark:stroke-[#E5E7EB]">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="dark:text-[#E5E7EB]">Time Remaining:</span>
                </span>
        
     
   
  






    <strong id="time-remaining-display" style="font-family: monospace;">
        <?php 
        // Initial PHP calculation
        $endDateTime = new DateTime($quizDetails['end_date'] . ' ' . $quizDetails['end_time']);
        $currentDateTime = new DateTime();
        
        if ($currentDateTime < $endDateTime) {
            $interval = $currentDateTime->diff($endDateTime);
            echo $interval->format('%m months %d days %H:%I:%S');
        } else {
            echo '00:00:00';
        }
        ?>
    </strong>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeDisplay = document.getElementById('time-remaining-display');
    const endTime = new Date("<?php echo $endDateTime->format('Y-m-d H:i:s'); ?>");
    
    function updateCountdown() {
        const now = new Date();
        const diff = endTime - now;
        
        if (diff > 0) {
            // Calculate all time units
            const months = Math.floor(diff / (1000 * 60 * 60 * 24 * 30));
            const days = Math.floor((diff % (1000 * 60 * 60 * 24 * 30)) / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            // Build display string
            let displayStr = '';
            if (months > 0) displayStr += `${months} month${months !== 1 ? 's' : ''} `;
            if (days > 0 || months > 0) displayStr += `${days} day${days !== 1 ? 's' : ''} `;
            displayStr += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            timeDisplay.textContent = displayStr;
            
            // Visual warnings
            if (months === 0 && days === 0) {
                if (hours < 1) {
                    timeDisplay.style.color = '#f44336';
                    timeDisplay.style.fontWeight = 'bold';
                    if (minutes < 1) {
                        timeDisplay.style.animation = 'pulse 0.5s infinite';
                    }
                }
            }
        } else {
            timeDisplay.textContent = '00:00:00';
            timeDisplay.style.color = '#f44336';
            clearInterval(countdownTimer);
            
            // Disable submission
            const submitBtn = document.getElementById('yes-submit-btn');
            if (submitBtn) submitBtn.disabled = true;
        }
    }
    
    // Initial update and set interval
    updateCountdown();
    const countdownTimer = setInterval(updateCountdown, 1000);
    
    // Cleanup
    const modal = document.getElementById('submit-quiz-modal');
    modal.querySelector('.close').addEventListener('click', () => clearInterval(countdownTimer));
    document.getElementById('cancel-btn').addEventListener('click', () => clearInterval(countdownTimer));
});

// Add pulse animation
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
`;
document.head.appendChild(style);
</script>


                    </div>
                </div>
            </div>
            
            <form id="quizForm" action="../tools/quiz_participants_answers.php?quiz_id=<?php echo $quiz_id;?>" method="POST" enctype="multipart/form-data">
                <div class="quiz-container">
                    <?php if (!empty($essayQuestions)): ?>
                        <?php foreach ($essayQuestions as $index => $question): ?>
                            <div class="question" id="question-<?php echo $index; ?>" style="display: <?php echo $index == 0 ? 'block' : 'none'; ?>">
                        <div class="question-nav">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="question-count" style="font-weight: 500; color: #092635; display: flex; align-items: center; gap: 0.4rem;">
                    <!-- New Question Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#092635" aria-label="Question Icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm1-6h-2V7h2v5z"/>
                    </svg>

                    <!-- Dynamic Question Count -->
                    Question <?php echo $index + 1; ?> of <?php echo count($essayQuestions); ?>
                    </span>


                    <span class="points-display" style="background-color: #5C8374; color: white; padding: 3px 10px; border-radius: 16px; font-size: 0.85rem; font-weight: 600;">
                    <?php 
                    // Display points, default to 10 if not set
                    $points = isset($question['points']) ? $question['points'] : 10;
                    echo $points . ' ' . ($points == 1 ? 'point' : 'points'); 
                    ?>
                </span>
            </div>
            <a href="#" class="view-rubric-btn" 
            data-bs-toggle="modal" 
            data-bs-target="#rubricModal"
            data-rubric-id="<?php echo htmlspecialchars($rubric_id); ?>"
            data-quiz-id="<?php echo htmlspecialchars($quiz_id); ?>">
                <i class="fas fa-table-list"></i> View Rubric
            </a>
        </div>
    </div>
                                
    <div class="question-content">
    <p class="question-text"><?php echo htmlspecialchars($question['question']); ?></p>
    
    <div class="answer-section">
        <label for="answeressay_<?php echo $index; ?>" class="answer-label">Your Answer:</label>
        <textarea 
    id="answeressay_<?php echo $index; ?>" 
    name="answer_<?php echo $index; ?>" 
    placeholder="Type your answer here..."
    oninput="validateWordCount(this, <?php echo $question['min_words']; ?>, <?php echo $question['max_words']; ?>, <?php echo $index; ?>); handleTextareaInput(this, <?php echo $index; ?>);">
</textarea>

        <p id="wordCount_<?php echo $index; ?>" class="word-count">Words: 0 / <?php echo $question['max_words']; ?></p>
        <p id="wordError_<?php echo $index; ?>" class="word-error">Word count must be between <?php echo $question['min_words']; ?> and <?php echo $question['max_words']; ?>.</p>
    </div>

    
    <?php
    $allowFileSubmission = isset($quizDetails['allow_file_submission']) && $quizDetails['allow_file_submission'] == 1;
    ?>
<?php if ($allowFileSubmission): ?>
<div class="file-upload-section" style="margin-top: 15px;">
    <div class="divider" style="text-align: center; margin: 10px 0; color: #666;">OR</div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
        <label class="file-upload-label" style="display: block; margin-bottom: 8px; font-weight: 500;">
            <i class="fas fa-paperclip"></i> Upload Answer File:
        </label>
        
        <div class="file-input-wrapper">
            <button type="button" class="file-input-button">
                <i class="fas fa-upload"></i> Choose Files
            </button>
            <input 
                id="input-2_<?php echo $index; ?>" 
                name="input2[]" 
                type="file"
                class="file-input" 
                accept=".doc,.docx,.pdf"
                onchange="validateFileBeforeUpload(this, <?php echo $question['min_words']; ?>, <?php echo $question['max_words']; ?>, <?php echo $index; ?>);">
        </div>
        
        <div id="file-preview_<?php echo $index; ?>" class="file-preview"></div>
        <div id="fileWordCount_<?php echo $index; ?>" class="word-count" style="margin-top: 5px;"></div>
        <p id="fileError_<?php echo $index; ?>" class="word-error" style="display: none; color: red;"></p>
    </div>
</div>

<!-- Include required libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.0/mammoth.browser.min.js"></script>


<script>
// Initialize PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.12.313/pdf.worker.min.js';

// Track which inputs have files uploaded
const uploadedFiles = {};

async function validateFileBeforeUpload(input, minWords, maxWords, index) {
    const file = input.files[0];
    const errorElement = document.getElementById(`fileError_${index}`);
    const previewElement = document.getElementById(`file-preview_${index}`);
    const wordCountElement = document.getElementById(`fileWordCount_${index}`);
    const textInput = document.getElementById(`input-1_${index}`);
    const fileInput = document.getElementById(`input-2_${index}`);
    
    // Reset previous states
    errorElement.style.display = 'none';
    previewElement.innerHTML = '';
    wordCountElement.innerHTML = '';
    
    if (!file) {
        delete uploadedFiles[index];
        // Enable text input when no file is selected
        if (textInput) textInput.disabled = false;
        if (fileInput) fileInput.disabled = false;
        return;
    }
    
    // Always show the file name and clear button, even for invalid files
    const showFilePreview = (isValid, wordCount = 0) => {
        const statusClass = isValid ? 'valid-file' : 'invalid-file';
        const statusIcon = isValid ? 'fa-check-circle' : 'fa-exclamation-circle';
        const statusColor = isValid ? 'green' : 'red';
        
        previewElement.innerHTML = `
            <div style="margin-top: 10px; padding: 8px; background: #e9ecef; border-radius: 4px; border-left: 4px solid ${statusColor};">
                <i class="fas ${statusIcon}" style="color: ${statusColor};"></i> 
                ${file.name} (${(file.size/1024).toFixed(2)} KB)
                <button type="button" onclick="clearFileInput(${index})" style="margin-left: 10px; color: red; background: none; border: none; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        if (wordCount > 0) {
            wordCountElement.textContent = `Word count: ${wordCount}`;
            wordCountElement.style.color = isValid ? 'inherit' : 'red';
        }
    };
    
    // Validate file type
    const validTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (!validTypes.includes(file.type)) {
        errorElement.textContent = 'Invalid file type. Please upload PDF or Word documents only.';
        errorElement.style.display = 'block';
        showFilePreview(false);
        return;
    }
    
    // Validate file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        errorElement.textContent = 'File size exceeds 5MB limit.';
        errorElement.style.display = 'block';
        showFilePreview(false);
        return;
    }
    
    try {
        let textContent = '';
        
        if (file.type === 'application/pdf') {
            textContent = await extractTextFromPDF(file);
        } else if (file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            textContent = await extractTextFromDocx(file);
        } else {
            errorElement.textContent = 'Unsupported file format. Please upload PDF or DOCX.';
            errorElement.style.display = 'block';
            showFilePreview(false);
            return;
        }
        
        const wordCount = countWords(textContent);
        const isValid = wordCount >= minWords && wordCount <= maxWords;
        
        if (!isValid) {
            errorElement.textContent = `Word count (${wordCount}) must be between ${minWords} and ${maxWords}.`;
            errorElement.style.display = 'block';
        }
        
        showFilePreview(isValid, wordCount);
        
        // Mark this index as having a file uploaded
        uploadedFiles[index] = true;
        
        // Disable text input when file is uploaded
        if (textInput) textInput.disabled = true;
        
        // Clear any text input if file is uploaded
        if (textInput && textInput.value) {
            textInput.value = '';
            alert("You can't have both text input and file upload. The text input has been disabled.");
        }
        
    } catch (error) {
        console.error('Error processing file:', error);
        errorElement.textContent = 'Error processing file. Please try another file.';
        errorElement.style.display = 'block';
        showFilePreview(false);
    }
}



async function extractTextFromPDF(file) {
    const arrayBuffer = await file.arrayBuffer();
    const loadingTask = pdfjsLib.getDocument(arrayBuffer);
    const pdf = await loadingTask.promise;
    let textContent = '';
    
    for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const text = await page.getTextContent();
        textContent += text.items.map(item => item.str).join(' ');
    }
    
    return textContent;
}

async function extractTextFromDocx(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function(event) {
            const arrayBuffer = event.target.result;
            mammoth.extractRawText({arrayBuffer: arrayBuffer})
                .then(result => resolve(result.value))
                .catch(error => reject(error));
        };
        reader.onerror = reject;
        reader.readAsArrayBuffer(file);
    });
}

function countWords(text) {
    if (!text.trim()) return 0;
    // Remove non-alphanumeric characters and split into words
    return text.replace(/[^\w\s]|_/g, '')
               .replace(/\s+/g, ' ')
               .trim()
               .split(' ')
               .filter(word => word.length > 0)
               .length;
}

function clearFileInput(index) {
    const fileInput = document.getElementById(`input-2_${index}`);
    const textInput = document.getElementById(`input-1_${index}`);
    const previewElement = document.getElementById(`file-preview_${index}`);
    const wordCountElement = document.getElementById(`fileWordCount_${index}`);
    const errorElement = document.getElementById(`fileError_${index}`);
    
    fileInput.value = '';
    previewElement.innerHTML = '';
    wordCountElement.innerHTML = '';
    errorElement.style.display = 'none';
    
    // Remove from uploaded files tracking
    delete uploadedFiles[index];
    
    // Enable the text input when file is cleared
    if (textInput) textInput.disabled = false;
    
    // Enable the file input
    if (fileInput) fileInput.disabled = false;
}
</script>


<script>
// Track answer methods (text or file) for each question
const answerMethods = {};

// Modified version of your validateFileBeforeUpload that integrates with the answer tracking
async function validateFileBeforeUpload(input, minWords, maxWords, index) {
    const file = input.files[0];
    const errorElement = document.getElementById(`fileError_${index}`);
    const previewElement = document.getElementById(`file-preview_${index}`);
    const wordCountElement = document.getElementById(`fileWordCount_${index}`);
    const textInput = document.getElementById(`answeressay_${index}`); // Changed to match your textarea ID
    
    // Reset previous states
    errorElement.style.display = 'none';
    previewElement.innerHTML = '';
    wordCountElement.innerHTML = '';
    
    if (!file) {
        delete answerMethods[index];
        // Enable text input when no file is selected
        if (textInput) textInput.disabled = false;
        return;
    }
    
    // Check if text answer already exists
    if (textInput && textInput.value.trim().length > 0) {
        input.value = '';
        alert('You have already entered a text answer. Please remove the text answer first if you want to upload a file instead.');
        return;
    }
    
    // Show file preview immediately (modified from your original)
    const showFilePreview = (isValid, wordCount = 0) => {
        const statusClass = isValid ? 'valid-file' : 'invalid-file';
        const statusIcon = isValid ? 'fa-check-circle' : 'fa-exclamation-circle';
        const statusColor = isValid ? 'green' : 'red';
        
        previewElement.innerHTML = `
            <div style="margin-top: 10px; padding: 8px; background: #e9ecef; border-radius: 4px; border-left: 4px solid ${statusColor};">
                <i class="fas ${statusIcon}" style="color: ${statusColor};"></i> 
                ${file.name} (${(file.size/1024).toFixed(2)} KB)
                <button type="button" onclick="clearFileInput(${index})" style="margin-left: 10px; color: red; background: none; border: none; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        if (wordCount > 0) {
            wordCountElement.textContent = `Word count: ${wordCount}`;
            wordCountElement.style.color = isValid ? 'inherit' : 'red';
        }
    };
    
    // Rest of your original validation logic remains the same
    const validTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    if (!validTypes.includes(file.type)) {
        errorElement.textContent = 'Invalid file type. Please upload PDF or Word documents only.';
        errorElement.style.display = 'block';
        showFilePreview(false);
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        errorElement.textContent = 'File size exceeds 5MB limit.';
        errorElement.style.display = 'block';
        showFilePreview(false);
        return;
    }
    
    try {
        let textContent = '';
        
        if (file.type === 'application/pdf') {
            textContent = await extractTextFromPDF(file);
        } else if (file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            textContent = await extractTextFromDocx(file);
        } else {
            errorElement.textContent = 'Unsupported file format. Please upload PDF or DOCX.';
            errorElement.style.display = 'block';
            showFilePreview(false);
            return;
        }
        
        const wordCount = countWords(textContent);
        const isValid = wordCount >= minWords && wordCount <= maxWords;
        
        if (!isValid) {
            errorElement.textContent = `Word count (${wordCount}) must be between ${minWords} and ${maxWords}.`;
            errorElement.style.display = 'block';
        }
        
        showFilePreview(isValid, wordCount);
        
        // Track this as a file answer and disable text input
        answerMethods[index] = 'file';
        if (textInput) textInput.disabled = true;
        
    } catch (error) {
        console.error('Error processing file:', error);
        errorElement.textContent = 'Error processing file. Please try another file.';
        errorElement.style.display = 'block';
        showFilePreview(false);
    }
}

// Modified clearFileInput function
function clearFileInput(index) {
    const fileInput = document.getElementById(`input-2_${index}`);
    const textInput = document.getElementById(`answeressay_${index}`);
    const previewElement = document.getElementById(`file-preview_${index}`);
    const wordCountElement = document.getElementById(`fileWordCount_${index}`);
    const errorElement = document.getElementById(`fileError_${index}`);
    
    fileInput.value = '';
    previewElement.innerHTML = '';
    wordCountElement.innerHTML = '';
    errorElement.style.display = 'none';
    
    // Remove from answer tracking
    delete answerMethods[index];
    
    // Enable the text input when file is cleared
    if (textInput) textInput.disabled = false;
}

// Text input handler that works with your existing setup
function handleTextareaInput(textarea, index) {
    const fileInput = document.getElementById(`input-2_${index}`);
    
    if (textarea.value.trim().length > 0) {
        // Check if file already uploaded
        if (fileInput && fileInput.files.length > 0) {
            textarea.value = '';
            alert('You have already uploaded a file. Please remove the file first if you want to type your answer instead.');
            return;
        }
        answerMethods[index] = 'text';
    } else {
        if (answerMethods[index] === 'text') {
            delete answerMethods[index];
        }
    }
    
    // Your existing word count logic can go here
    // ...
}


</script>




<style>
.valid-file {
    border-left: 4px solid green !important;
}    // ... your existing implementation ...

.invalid-file {
    border-left: 4px solid red !important;
}
</style>
<?php endif; ?>
</div>




    
    <div class="navigation-buttons">
        <?php if ($index > 0): ?>
            <button type="button" class="nav-button back-button" onclick="showPreviousQuestion(<?php echo $index; ?>)">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
        <?php else: ?>
            <div></div> <!-- Empty div for spacing -->
        <?php endif; ?>
        
        <?php if ($index < count($essayQuestions) - 1): ?>
            <button type="button" class="nav-button next-button" onclick="showNextQuestion(<?php echo $index; ?>)">
                Next <i class="fas fa-chevron-right"></i>
            </button>
        <?php else: ?>
            <button type="button" id="submit-quiz-modalBtn" class="nav-button submit-button">
                <i class="fas fa-paper-plane"></i> Submit Quiz
            </button>
        <?php endif; ?>
    </div>
</div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No essay questions available for this quiz.
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

<!-- Modern Quiz Submission Modal -->
<div id="submit-quiz-modal" class="modal">
    <div class="modal-content" style="max-width: 700px; border-radius: 5px; overflow: hidden; border: none;">
        <!-- Modal Header -->
        <div class="modal-header" style="padding: 1.2rem;">
            <h3 class="modal-title" style="color: #092635; margin-bottom: 0; font-size: 1.2em;">Submit Quiz</h3>
            <button class="close" style="color: #092635; font-size: 1.8rem;">&times;</button>
        </div>
        
        <!-- Modal Body -->
        <div class="modal-body" style="padding: 0;">
            <!-- Hero Image -->
            <div style="height: 290px; overflow: hidden;">
                <img src="../img/modal/modal-1.gif" alt="Confirmation" 
                     style="width: 100%; height: 100%;  object-position: center;">
            </div>
            
            <!-- Confirmation Content -->
            <div id="confirmation-content" style="padding: 2rem; border-top: 1px solid #e9ecef;">
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#092635" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <h4 style="color: #092635; font-size: 1.2em; margin-bottom: 0;">Ready to Submit?</h4>
                    <p style="font-size:1em; color: #5C8374; line-height: 1.6;">You're about to submit your quiz answers. This action cannot be undone.</p>
                </div>
                
                <!-- Action Buttons -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                    <button id="cancel-btn" style="padding: 0.8rem; border: 1px solid #e0e0e0; background: white; color: #5C8374; border-radius: 5px; cursor: pointer; transition: all 0.3s ease;">
                        Cancel
                    </button>
                    <button id="yes-submit-btn" style="padding: 0.8rem; background-color: #092635; color: white; border: none; border-radius: 5px;  cursor: pointer; transition: all 0.3s ease;">
                        Submit Quiz
                    </button>
                </div>
            </div>
            
            <!-- Processing Content (Hidden) -->
            <div id="processing-content" style="display: none; padding: 3rem 2rem; text-align: center;">
                <!-- Modern Spinner -->
                <div style="margin: 0 auto 2rem; width: 60px; height: 60px; position: relative;">
                    <div style="position: absolute; width: 100%; height: 100%; border: 4px solid rgba(92, 131, 116, 0.2); border-radius: 50%;"></div>
                    <div style="position: absolute; width: 100%; height: 100%; border: 4px solid transparent; border-top-color: #5C8374; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
                <h4 style="color: #092635; font-size: 1.2rem; margin-bottom: 0.5rem;">Processing Submission</h4>
                <p style="color: #5C8374;">We're grading your answers. This may take a moment...</p>
                <div style="width: 100%; height: 4px; background: #f0f0f0; border-radius: 2px; margin-top: 2rem; overflow: hidden;">
                    <div style="width: 60%; height: 100%; background: linear-gradient(90deg, #9EC8B9, #5C8374); animation: progress 2s ease-in-out infinite;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes progress {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(200%); }
}
#cancel-btn:hover {
    background: #f5f5f5 !important;
}
#yes-submit-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
</style>









<script>
// Configuration
let currentQuestion = 0;
const totalQuestions = <?php echo count($essayQuestions); ?>;
const debugMode = false; // Set to true to enable debugging logs

// Main Navigation Functions
function showNextQuestion(index) {
    if (debugMode) console.group('Debugging showNextQuestion()');
    if (debugMode) console.log('Current index:', index, 'Total questions:', totalQuestions);
    
    // Validate current question before proceeding
    if (!validateCurrentQuestion(index)) {
        if (debugMode) console.warn('Validation failed - not proceeding');
        if (debugMode) console.groupEnd();
        return;
    }

    // Hide current question
    const currentQuestionElement = document.getElementById('question-' + index);
    if (debugMode) console.log('Current question element:', currentQuestionElement);
    currentQuestionElement.style.display = 'none';
    if (debugMode) console.log('Current question hidden');
    
    // Calculate and show next question
    currentQuestion = index + 1;
    const nextQuestionElement = document.getElementById('question-' + currentQuestion);
    if (debugMode) console.log('Next question element:', nextQuestionElement);
    nextQuestionElement.style.display = 'block';
    if (debugMode) console.log('Next question shown');
    
    // Update UI
    updateProgress();
    if (document.getElementById('currentQuestionDisplay')) {
        document.getElementById('currentQuestionDisplay').textContent = currentQuestion + 1;
    }
    
    // Scroll to top of new question
    nextQuestionElement.scrollIntoView({ behavior: 'smooth' });
    if (debugMode) console.log('Scrolled to question');
    if (debugMode) console.groupEnd();
}

function showPreviousQuestion(index) {
    if (debugMode) console.group('Debugging showPreviousQuestion()');
    if (debugMode) console.log('Current index:', index);
    
    // Hide current question
    const currentQuestionElement = document.getElementById('question-' + index);
    if (debugMode) console.log('Current question element:', currentQuestionElement);
    currentQuestionElement.style.display = 'none';
    if (debugMode) console.log('Current question hidden');
    
    // Calculate and show previous question
    currentQuestion = index - 1;
    const prevQuestionElement = document.getElementById('question-' + currentQuestion);
    if (debugMode) console.log('Previous question element:', prevQuestionElement);
    prevQuestionElement.style.display = 'block';
    if (debugMode) console.log('Previous question shown');
    
    // Update UI
    updateProgress();
    if (document.getElementById('currentQuestionDisplay')) {
        document.getElementById('currentQuestionDisplay').textContent = currentQuestion + 1;
    }
    
    // Scroll to top of previous question
    prevQuestionElement.scrollIntoView({ behavior: 'smooth' });
    if (debugMode) console.log('Scrolled to question');
    if (debugMode) console.groupEnd();
}

// Validation Functions
function validateCurrentQuestion(index) {
    if (debugMode) console.group('Validating question ' + index);
    
    const textarea = document.getElementById(`answeressay_${index}`);
    const fileInput = document.getElementById(`input-2_${index}`);
    const wordErrorElement = document.getElementById(`wordError_${index}`);
    const fileErrorElement = document.getElementById(`fileError_${index}`);
    const fileWordCountElement = document.getElementById(`fileWordCount_${index}`);
    const fileUploadAllowed = fileInput !== null; // Check if file upload is available
    
    // Reset errors and styling
    wordErrorElement.style.display = "none";
    if (fileErrorElement) fileErrorElement.style.display = "none";
    textarea.style.borderColor = "";
    if (fileInput) fileInput.style.borderColor = "";

    // Case 1: File upload is allowed and file is uploaded - validate it
    if (fileUploadAllowed && fileInput.files && fileInput.files.length > 0) {
        if (debugMode) console.log('File upload detected - validating');
        
        const minWords = <?php echo $question['min_words']; ?>;
        const maxWords = <?php echo $question['max_words']; ?>;
        
        // Get the word count from the preview element or calculate it
        let wordCount = 0;
        if (fileWordCountElement && fileWordCountElement.textContent) {
            const match = fileWordCountElement.textContent.match(/\d+/);
            wordCount = match ? parseInt(match[0]) : 0;
        }
        
        // Validate word count
        if (wordCount < minWords || wordCount > maxWords) {
            fileErrorElement.textContent = `File word count (${wordCount}) must be between ${minWords} and ${maxWords}`;
            fileErrorElement.style.display = "block";
            fileInput.style.borderColor = "var(--danger-color)";
            
            if (debugMode) console.warn('File validation failed - word count out of range');
            if (debugMode) console.groupEnd();
            return false;
        }
        
        if (debugMode) console.log('File validation passed');
        if (debugMode) console.groupEnd();
        return true;
    } 
    // Case 2: Validate text answer (whether file upload is allowed or not)
    else if (textarea && textarea.value.trim()) {
        if (debugMode) console.log('Validating text answer');
        const minWords = parseInt(textarea.getAttribute("oninput").match(/\d+/g)[0]);
        const maxWords = parseInt(textarea.getAttribute("oninput").match(/\d+/g)[1]);
        const words = textarea.value.trim().split(/\s+/).filter(word => word.length > 0);
        const wordCount = words.length;

        if (wordCount < minWords || wordCount > maxWords) {
            wordErrorElement.style.display = "block";
            textarea.style.borderColor = "var(--danger-color)";
            textarea.focus();
            
            if (debugMode) console.warn('Validation failed - word count out of range');
            if (debugMode) console.groupEnd();
            return false;
        }
        
        if (debugMode) console.log('Text validation passed');
        if (debugMode) console.groupEnd();
        return true;
    }
    // Case 3: No answer provided
    else {
        wordErrorElement.textContent = fileUploadAllowed 
            ? "Please provide either a text answer or upload a file" 
            : "Please provide a text answer";
        wordErrorElement.style.display = "block";
        textarea.style.borderColor = "var(--danger-color)";
        if (fileUploadAllowed && fileInput) fileInput.style.borderColor = "var(--danger-color)";
        
        if (debugMode) console.warn('Validation failed - no answer provided');
        if (debugMode) console.groupEnd();
        return false;
    }
}
function validateWordCount(textarea, minWords, maxWords, index) {
    if (debugMode) console.group('Validating word count for question ' + index);
    
    const words = textarea.value.trim().split(/\s+/).filter(word => word.length > 0);
    const wordCount = words.length;
    const wordCountElement = document.getElementById(`wordCount_${index}`);
    const wordErrorElement = document.getElementById(`wordError_${index}`);

    // Update word count display
    wordCountElement.textContent = `Words: ${wordCount} / ${maxWords}`;
    wordCountElement.style.color = wordCount > maxWords ? "var(--danger-color)" : "var(--secondary-color)";
    if (debugMode) console.log('Word count updated:', wordCount);

    // Show/hide error message
    if (wordCount < minWords || wordCount > maxWords) {
        wordErrorElement.style.display = "block";
        textarea.style.borderColor = "var(--danger-color)";
        if (debugMode) console.log('Word count out of range - showing error');
    } else {
        wordErrorElement.style.display = "none";
        textarea.style.borderColor = "";
        if (debugMode) console.log('Word count valid');
    }
    
    if (debugMode) console.groupEnd();
}

// UI Update Functions
function updateProgress() {
    const progressPercent = ((currentQuestion + 1) / totalQuestions) * 100;
    if (document.querySelector('.progress-bar')) {
        document.querySelector('.progress-bar').style.width = `${progressPercent}%`;
    }
    if (document.querySelector('.progress-section p')) {
        document.querySelector('.progress-section p').textContent = 
            `Question ${currentQuestion + 1} of ${totalQuestions}`;
    }
    if (debugMode) console.log('Progress updated:', progressPercent + '%');
}

function toggleInputField(index) {
    if (debugMode) console.group(`toggleInputField(${index})`);
    
    const textInputField = document.getElementById("textInputField_" + index);
    const fileUploadField = document.getElementById("fileUploadField_" + index);
    const uploadBtn = document.getElementById("upload-btn_" + index);
    const selectedOption = document.querySelector('input[name="submissionType_' + index + '"]:checked').value;
    
    if (debugMode) console.log('Selected option:', selectedOption);
    if (debugMode) console.log('UI elements:', {textInputField, fileUploadField, uploadBtn});

    if (selectedOption === "text") {
        if (debugMode) console.log('Showing text input, hiding file upload');
        if (textInputField) textInputField.style.display = "block";
        if (fileUploadField) fileUploadField.style.display = "none";
        if (uploadBtn) uploadBtn.style.display = "none";
    } else {
        if (debugMode) console.log('Showing file upload, hiding text input');
        if (textInputField) textInputField.style.display = "none";
        if (fileUploadField) fileUploadField.style.display = "block";
        if (uploadBtn) uploadBtn.style.display = "inline-block";
    }
    
    if (debugMode) console.groupEnd();
}

// Initialization
document.addEventListener("DOMContentLoaded", function() {
    if (debugMode) {
        console.log('--- Quiz Initialization ---');
        console.log('Total questions:', totalQuestions);
        console.group('Question Elements');
        for (let i = 0; i < totalQuestions; i++) {
            const q = document.getElementById('question-' + i);
            console.log(`Question ${i}:`, q);
        }
        console.groupEnd();
    }

    // File input preview functionality
    document.querySelectorAll(".file-input").forEach(input => {
        input.addEventListener("change", function() {
            const previewId = this.id.replace("input-2_", "file-preview_");
            const previewDiv = document.getElementById(previewId);
            previewDiv.innerHTML = "";
            
            if (this.files.length > 0) {
                Array.from(this.files).forEach(file => {
                    const fileItem = document.createElement("p");
                    fileItem.innerHTML = `<i class="fas fa-file-alt"></i> ${file.name} (${(file.size/1024).toFixed(1)} KB)`;
                    previewDiv.appendChild(fileItem);
                });
            }
            
            if (debugMode) {
                console.log(`File input changed for ${this.id}`);
                console.log('Files:', this.files);
            }
        });
    });

    // Initialize progress display
    updateProgress();

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') return;

        if (e.key === 'ArrowLeft' && currentQuestion > 0) {
            showPreviousQuestion(currentQuestion);
        }
        else if (e.key === 'ArrowRight' && currentQuestion < totalQuestions - 1) {
            showNextQuestion(currentQuestion);
        }
    });

    // Modal handling
    const modal = document.getElementById('submit-quiz-modal');
    if (modal) {
        const confirmationContent = document.getElementById('confirmation-content');
        const processingContent = document.getElementById('processing-content');
        const quizForm = document.getElementById('quizForm');
        const submitBtn = document.getElementById('submit-quiz-modalBtn');
        const yesSubmitBtn = document.getElementById('yes-submit-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const closeBtn = document.querySelector('.close');

        // Show modal when submit button is clicked
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Validate all questions before showing submit modal
                let allValid = true;
                for (let i = 0; i < totalQuestions; i++) {
                    if (!validateCurrentQuestion(i)) {
                        allValid = false;
                        document.querySelectorAll('.question').forEach(q => q.style.display = 'none');
                        document.getElementById(`question-${i}`).style.display = 'block';
                        currentQuestion = i;
                        updateProgress();
                        break;
                    }
                }
                
                if (allValid) {
                    modal.style.display = 'block';
                    if (debugMode) console.log('Showing submission modal');
                }
            });
        }

        // Close modal handlers
        function closeModal() {
            modal.style.display = 'none';
            if (processingContent) processingContent.style.display = 'none';
            if (confirmationContent) confirmationContent.style.display = 'block';
            if (debugMode) console.log('Modal closed');
        }

        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // Close when clicking outside modal
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        // Handle form submission
        if (yesSubmitBtn) {
            yesSubmitBtn.addEventListener('click', function() {
                if (confirmationContent) confirmationContent.style.display = 'none';
                if (processingContent) processingContent.style.display = 'block';
                if (debugMode) console.log('Submitting quiz...');
                
                setTimeout(function() {
                    if (quizForm) quizForm.submit();
                }, 1500);
            });
        }
    }
});
</script>



<style>
.modal-fullscreen {
    width: 100vw!important;
    max-width: none!important;
    height: 100%!important;
    margin: 0!important;
}
.modal-content-rubric {
    width: 100vw!important;
    max-width: none!important;
    height: 100%!important;
    margin: 0!important;
}
#closemodal {
    background-color: #092635;
    color:white;
    padding:10px 30px;
}

#closemodal:hover {
    background-color: #5C8374;;
    color:white;
    padding:10px 30px;
}
#rubricModalLabel {
    color: #092635;
    font-size: 1.5em;
}
.view-rubric-btn {
    color: #5C8374;
    text-decoration;
    outline:none;
}

.view-rubric-btn:hover {
    color: #5C8374;
    text-decoration;
    outline:none;
}

.progress-bar {
    color: #5C8374;
}
</style>




<!-- Fullscreen Rubric Modal -->
<!-- Fullscreen Rubric Modal -->
<div class="modal fade" id="rubricModal" tabindex="-1" aria-labelledby="rubricModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content modal-content-rubric">
            <div class="modal-header">
                <h5 class="modal-title" id="rubricModalLabel">Rubric Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="rubricModalContent">
                <!-- Content will be loaded here via AJAX -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading rubric details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn " id ="closemodal"data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle modal show event
    $('#rubricModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var rubricId = button.data('rubric-id');
        var quizId = button.data('quiz-id');
        
        // Load content via AJAX
        $.ajax({
            url: 'AcademAI-Join-Quiz-Essay-Rubric.php',
            type: 'GET',
            data: {
                rubric_id: rubricId,
                quiz_id: quizId
            },
            success: function(response) {
                $('#rubricModalContent').html(response);
            },
            error: function() {
                $('#rubricModalContent').html(
                    '<div class="alert alert-danger">Failed to load rubric. Please try again.</div>'
                );
            }
        });
    });
    
    // Clean up when modal is closed
    $('#rubricModal').on('hidden.bs.modal', function () {
        $('#rubricModalContent').html(
            '<div class="text-center py-5">' +
            '    <div class="spinner-border text-primary" role="status">' +
            '        <span class="visually-hidden">Loading...</span>' +
            '    </div>' +
            '    <p>Loading rubric details...</p>' +
            '</div>'
        );
    });
});
</script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>






<!-- Include required libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.0/mammoth.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/docx/7.1.0/docx.js"></script>

<script>
// Set PDF.js worker path
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.worker.min.js';

async function validateFileWordCount(input, minWords, maxWords, index) {
    const fileWordCountElement = document.getElementById(`fileWordCount_${index}`);
    const fileErrorElement = document.getElementById(`fileError_${index}`);
    
    fileWordCountElement.textContent = "";
    fileErrorElement.style.display = "none";
    input.style.borderColor = "#ddd";

    if (!input.files || input.files.length === 0) return;

    const file = input.files[0];
    
    // Validate file type
    const validTypes = ["application/pdf", "application/msword", 
                       "application/vnd.openxmlformats-officedocument.wordprocessingml.document"];
    if (!validTypes.includes(file.type)) {
        showFileError(fileErrorElement, input, "Invalid file type. Only DOC, DOCX, and PDF files are allowed.");
        return;
    }

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showFileError(fileErrorElement, input, "File size exceeds 5MB limit.");
        return;
    }

    fileWordCountElement.textContent = "Processing file...";
    
    try {
        let text = "";
        
        if (file.type === "application/pdf") {
            text = await extractTextFromPDF(file);
        } 
        else if (file.type === "application/vnd.openxmlformats-officedocument.wordprocessingml.document") {
            text = await extractTextFromDocx(file);
        }
        else if (file.type === "application/msword") {
            text = await extractTextFromDoc(file);
        }
        
        const words = text.trim().split(/\s+/).filter(word => word.length > 0);
        const wordCount = words.length;
        
        fileWordCountElement.textContent = `Words: ${wordCount} / ${maxWords}`;
        fileWordCountElement.style.color = wordCount > maxWords ? "red" : "green";
        
        if (wordCount < minWords || wordCount > maxWords) {
            showFileError(fileErrorElement, input, 
                `Word count must be between ${minWords} and ${maxWords}. Current: ${wordCount}`);
        } else {
            fileErrorElement.style.display = "none";
            input.style.borderColor = "#4CAF50"; // Green border for valid files
        }
        
    } catch (error) {
        console.error("Error processing file:", error);
        showFileError(fileErrorElement, input, "Error processing file. Please try another file.");
    }
}

function showFileError(errorElement, input, message) {
    errorElement.textContent = message;
    errorElement.style.display = "block";
    input.style.borderColor = "red";
    input.value = "";
}

// PDF text extraction
async function extractTextFromPDF(file) {
    return new Promise((resolve) => {
        const fileReader = new FileReader();
        fileReader.onload = async function() {
            const typedArray = new Uint8Array(this.result);
            const pdf = await pdfjsLib.getDocument(typedArray).promise;
            let text = "";
            
            for (let i = 1; i <= pdf.numPages; i++) {
                const page = await pdf.getPage(i);
                const content = await page.getTextContent();
                text += content.items.map(item => item.str).join(" ");
            }
            
            resolve(text);
        };
        fileReader.readAsArrayBuffer(file);
    });
}

// DOCX text extraction
async function extractTextFromDocx(file) {
    return new Promise((resolve) => {
        const fileReader = new FileReader();
        fileReader.onload = function() {
            mammoth.extractRawText({arrayBuffer: this.result})
                .then(result => resolve(result.value))
                .catch(() => resolve(""));
        };
        fileReader.readAsArrayBuffer(file);
    });
}

// DOC text extraction (limited browser support)
async function extractTextFromDoc(file) {
    return new Promise((resolve) => {
        // Note: This has limited browser support
        // Consider server-side processing for .doc files
        const fileReader = new FileReader();
        fileReader.onload = function() {
            // Simple text extraction - won't work for all .doc files
            const text = new TextDecoder().decode(this.result);
            resolve(text.replace(/[^\w\s]/g, " "));
        };
        fileReader.readAsArrayBuffer(file);
    });
}
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeDisplay = document.getElementById('time-remaining-display');
    const endTime = new Date("<?php echo $endDateTime->format('Y-m-d H:i:s'); ?>");
    const isRestricted = <?php echo $quizDetails['is_active'] == 1 ? 'true' : 'false'; ?>;
    
    function updateCountdown() {
        const now = new Date();
        const diff = endTime - now;
        
        if (diff > 0) {
            // Calculate time remaining
            const months = Math.floor(diff / (1000 * 60 * 60 * 24 * 30));
            const days = Math.floor((diff % (1000 * 60 * 60 * 24 * 30)) / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            let displayStr = '';
            if (months > 0) displayStr += `${months} month${months !== 1 ? 's' : ''} `;
            if (days > 0 || months > 0) displayStr += `${days} day${days !== 1 ? 's' : ''} `;
            displayStr += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            timeDisplay.textContent = displayStr;
            
            // Visual warning when less than 5 minutes remain
            if (isRestricted && hours === 0 && minutes < 5) {
                timeDisplay.style.color = '#f44336';
                timeDisplay.style.fontWeight = '600';
                timeDisplay.style.animation = 'pulse 1s infinite';
            }
        } else {
            // Time has expired
            if (isRestricted) {
                showQuizEndedModal();
            }
            clearInterval(countdownTimer);
        }
    }

    function showQuizEndedModal() {
        // Create modern modal HTML
        const modalHTML = `
        <div id="quiz-ended-modal" class="modal">
            <div class="modal-overlay"></div>
            <div class="modal-container">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f44336" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h3 class="modal-title">Time's Up!</h3>
                    <p class="modal-subtitle">The quiz period has ended</p>
                </div>
                <div class="modal-body">
                    <p>This quiz is time-restricted and has officially ended. Your progress has been saved.</p>
                    <div class="countdown">Redirecting in <span id="redirect-countdown">5</span> seconds...</div>
                </div>
                <div class="modal-footer">
                    <button id="redirect-btn" class="modal-button primary">
                        Return to Dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>`;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Disable all interactive elements
        document.querySelectorAll('input, textarea, button, select, a').forEach(el => {
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.6';
        });
        
        // Start countdown
        let countdown = 5;
        const countdownElement = document.getElementById('redirect-countdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                redirectUser();
            }
        }, 1000);
        
        // Manual redirect
        document.getElementById('redirect-btn').addEventListener('click', redirectUser);
        
        // Show modal with animation
        setTimeout(() => {
            document.getElementById('quiz-ended-modal').classList.add('visible');
        }, 100);
    }

    function redirectUser() {
        window.location.href = 'AcademAI-Activity-Not-Taken-Card.php?status=time_expired';
    }

    // Initialize countdown only if quiz is restricted
    if (isRestricted) {
        updateCountdown();
        const countdownTimer = setInterval(updateCountdown, 1000);
    }
});

const modalStyles = `
<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    #quiz-ended-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    #quiz-ended-modal.visible {
        opacity: 1;
        visibility: visible;
    }
    
    #quiz-ended-modal .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
    }
    
    #quiz-ended-modal .modal-container {
        position: relative;
        width: 90%;
        max-width: 420px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        overflow: hidden;
        transform: translateY(20px);
        transition: all 0.3s ease;
        animation: slideUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    }
    
    #quiz-ended-modal.visible .modal-container {
        transform: translateY(0);
    }
    
    #quiz-ended-modal .modal-header {
        padding: 24px;
        text-align: center;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
    }
    
    #quiz-ended-modal .modal-icon {
        margin: 0 auto 16px;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff5f5;
        border-radius: 50%;
    }
    
    #quiz-ended-modal .modal-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #092635;
        margin-bottom: 4px;
    }
    
    #quiz-ended-modal .modal-subtitle {
        font-size: 0.9rem;
        color: #5C8374;
        margin-bottom: 0;
    }
    
    #quiz-ended-modal .modal-body {
        padding: 24px;
        text-align: center;
        color: #5C8374;
        line-height: 1.6;
    }
    
    #quiz-ended-modal .countdown {
        margin-top: 16px;
        font-size: 0.9rem;
        color: #1B4242;
        font-weight: 500;
    }
    
    #quiz-ended-modal .modal-footer {
        padding: 16px 24px;
        display: flex;
        justify-content: center;
        background: #f9f9f9;
    }
    
    #quiz-ended-modal .modal-button {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }
    
    #quiz-ended-modal .modal-button.primary {
        background: #1B4242;
        color: white;
    }
    
    #quiz-ended-modal .modal-button.primary:hover {
        background: #092635;
        transform: translateY(-2px);
    }
    
    #quiz-ended-modal .modal-button svg {
        width: 16px;
        height: 16px;
    }
</style>
`;
// Add styles to head
document.head.insertAdjacentHTML('beforeend', modalStyles);
</script>




<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeDisplay = document.getElementById('time-remaining-display');
    const endTime = new Date("<?php echo $endDateTime->format('Y-m-d H:i:s'); ?>");
    const isRestricted = <?php echo $quizDetails['is_active'] == 1 ? 'true' : 'false'; ?>;
    
    function updateCountdown() {
        const now = new Date();
        const diff = endTime - now;
        
        if (diff > 0) {
            // Calculate time remaining
            const months = Math.floor(diff / (1000 * 60 * 60 * 24 * 30));
            const days = Math.floor((diff % (1000 * 60 * 60 * 24 * 30)) / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            let displayStr = '';
            if (months > 0) displayStr += `${months} month${months !== 1 ? 's' : ''} `;
            if (days > 0 || months > 0) displayStr += `${days} day${days !== 1 ? 's' : ''} `;
            displayStr += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            timeDisplay.textContent = displayStr;
            
            // Visual warning when less than 5 minutes remain
            if (isRestricted && hours === 0 && minutes < 5) {
                timeDisplay.style.color = '#f44336';
                timeDisplay.style.fontWeight = '600';
                timeDisplay.style.animation = 'pulse 1s infinite';
            }
        } else {
            // Time has expired
            if (isRestricted) {
                showQuizEndedModal();
            }
            clearInterval(countdownTimer);
        }
    }

    function showQuizOpenModal() {
    // Create modal with unique IDs and classes
    const modalHTML = `
    <div id="quiz-open-notice-modal" class="quiz-open-modal">
        <div class="quiz-open-modal-overlay"></div>
        <div class="quiz-open-modal-container">
            <div class="quiz-open-modal-header">
                <div class="quiz-open-modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#4CAF50" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h3 class="quiz-open-modal-title">Quiz Available</h3>
                <p class="quiz-open-modal-subtitle">Complete at your own pace</p>
            </div>
            <div class="quiz-open-modal-body">
                <p>This quiz is not time-restricted. You may complete it at your own pace before the final deadline.</p>
                
                <div class="deadline-notice">
                    <span>🗓️ Final submission deadline:</span>
                    <strong><?php echo $endDateTime->format('F j, Y, g:i a'); ?></strong>
                </div>
                
                <div class="accessibility-note">
                    <p><em>Note: The quiz will remain accessible after the deadline and will not automatically close.</em></p>
                </div>
            </div>
            <div class="quiz-open-modal-footer">
                <button id="quiz-open-close-btn" class="quiz-open-modal-button primary">
                    Start Quiz
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 19V5M5 12l7-7 7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <style>
        /* Your existing styles plus these additions */
        .quiz-open-modal-body {
            text-align: left;
            padding: 24px;
            color: #455a64;
            line-height: 1.6;
        }
        
        .deadline-notice {
            margin: 16px 0;
            padding: 12px;
            background: #f5f5f5;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .accessibility-note {
            margin-top: 16px;
            font-size: 0.9em;
            color: #666;
        }
        
        .accessibility-note em {
            font-style: italic;
        }
    </style>`;

    // Rest of your implementation remains the same
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modal = document.getElementById('quiz-open-notice-modal');
    const closeBtn = document.getElementById('quiz-open-close-btn');
    
    setTimeout(() => {
        modal.classList.add('visible');
    }, 100);
    
    closeBtn.addEventListener('click', function() {
        modal.classList.remove('visible');
        setTimeout(() => {
            modal.remove();
        }, 300);
    });
}

    function showQuizEndedModal() {
        // (Keep your existing showQuizEndedModal function here)
    }

    // Initialize based on quiz restriction status
    if (isRestricted) {
        updateCountdown();
        const countdownTimer = setInterval(updateCountdown, 1000);
    } else {
        // Show quiz open modal if not restricted
        showQuizOpenModal();
    }
});

// (Keep your existing modal styles here)
</script>

<style>
            /* Unique styles scoped to this modal only */
            #quiz-open-notice-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            #quiz-open-notice-modal.visible {
                opacity: 1;
                visibility: visible;
            }
            
            .quiz-open-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(4px);
            }
            
            .quiz-open-modal-container {
                position: relative;
                width: 90%;
                max-width: 450px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                overflow: hidden;
                transform: translateY(20px);
                transition: all 0.3s ease;
                animation: quiz-open-slideUp 0.4s cubic-bezier(0.22, 1, 0.36, 1) forwards;
            }
            
            @keyframes quiz-open-slideUp {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            
            .quiz-open-modal-header {
                padding: 24px;
                text-align: center;
                background: #f8f9fa;
                border-bottom: 1px solid #e9ecef;
            }
            
            .quiz-open-modal-icon {
                margin: 0 auto 16px;
                width: 60px;
                height: 60px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #e8f5e9;
                border-radius: 50%;
            }
            
            .quiz-open-modal-title {
                font-size: 1.5rem;
                font-weight: 600;
                color: #092635;
                margin-bottom: 4px;
            }
            
            .quiz-open-modal-subtitle {
                font-size: 0.9rem;
                color: #5C8374;
                margin-bottom: 0;
            }
            
            .quiz-open-modal-body {
                padding: 24px;
                text-align: center;
                color: #092635;
                line-height: 1.6;
            }
            
            .quiz-open-modal-footer {
                padding: 16px 24px;
                display: flex;
                justify-content: center;
                background: #f8f9fa;
            }
            
            .quiz-open-modal-button {
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
                transition: all 0.2s ease;
                border: none;
            }
            
            .quiz-open-modal-button.primary {
                background: #092635;
                color: white;
            }
            
            .quiz-open-modal-button.primary:hover {
                background: #5C8374;
                transform: translateY(-2px);
            }
        </style>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeDisplay = document.getElementById('time-remaining-display');
    const endTime = new Date("<?php echo $endDateTime->format('Y-m-d H:i:s'); ?>");
    const isRestricted = <?php echo $quizDetails['is_active'] == 1 ? 'true' : 'false'; ?>;
    
    function updateCountdown() {
        const now = new Date();
        const diff = endTime - now;
        
        if (diff > 0) {
            // Calculate time remaining
            const months = Math.floor(diff / (1000 * 60 * 60 * 24 * 30));
            const days = Math.floor((diff % (1000 * 60 * 60 * 24 * 30)) / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            let displayStr = '';
            if (months > 0) displayStr += `${months} month${months !== 1 ? 's' : ''} `;
            if (days > 0 || months > 0) displayStr += `${days} day${days !== 1 ? 's' : ''} `;
            displayStr += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            timeDisplay.textContent = displayStr;
            
            // Visual warning when less than 5 minutes remain
            if (isRestricted && hours === 0 && minutes < 5) {
                timeDisplay.style.color = '#f44336';
                timeDisplay.style.fontWeight = '600';
                timeDisplay.style.animation = 'pulse 1s infinite';
            }
        } else {
            // Time has expired
            if (isRestricted) {
                showQuizEndedModal();
            }
            clearInterval(countdownTimer);
        }
    }

    function showRestrictedQuizModal() {
        const modalHTML = `
        <div id="restricted-quiz-modal" class="restricted-quiz-modal-wrapper">
            <div class="restricted-quiz-modal-overlay"></div>
            <div class="restricted-quiz-modal-content">
                <div class="restricted-quiz-modal-header">
                    <div class="restricted-quiz-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#e53935" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h3 class="restricted-quiz-modal-title">Time-Restricted Quiz</h3>
                    <p class="restricted-quiz-modal-subtitle">Complete within the time limit</p>
                </div>
                <div class="restricted-quiz-modal-body">
                    <p>This quiz is time-restricted. You must complete it within the allocated time shown below.</p>
                    
                    <div class="time-restriction-notice">
                        <div class="time-remaining-box">
                            <span>⏱️ Time remaining:</span>
                            <strong id="modal-time-remaining">${timeDisplay.textContent}</strong>
                        </div>
                        <div class="deadline-box">
                            <span>📅 Deadline:</span>
                            <strong><?php echo $endDateTime->format('F j, Y, g:i a'); ?></strong>
                        </div>
                    </div>
                    
                    <div class="important-note">
                        <p><em>Important: The quiz won’t auto-submit when time runs out. Once the time limit is reached, you won’t be able to submit your answers and will be exited from the quiz.</em></p>
                    </div>
                </div>
                <div class="restricted-quiz-modal-footer">
                    <button id="restricted-quiz-close-btn" class="restricted-quiz-modal-button">
                        I Understand
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <style>
            /* Unique styles for the restricted quiz modal */
            #restricted-quiz-modal.restricted-quiz-modal-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            #restricted-quiz-modal.restricted-quiz-modal-wrapper.visible {
                opacity: 1;
                visibility: visible;
            }
            
            .restricted-quiz-modal-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
            }
            
            .restricted-quiz-modal-content {
                position: relative;
                background: white;
                border-radius: 12px;
                width: 90%;
                max-width: 500px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                transform: translateY(20px);
                transition: all 0.3s ease;
                overflow: hidden;
            }
            
            .restricted-quiz-modal-wrapper.visible .restricted-quiz-modal-content {
                transform: translateY(0);
            }
            
            .restricted-quiz-modal-header {
                padding: 24px;
                text-align: center;
                background: #fff9f9;
                border-bottom: 1px solid #ffebee;
            }
            
            .restricted-quiz-modal-icon {
                margin-bottom: 16px;
            }
            
            .restricted-quiz-modal-title {
                margin: 0;
                color: #092635;
                font-size: 1.5rem;
            }
            
            .restricted-quiz-modal-subtitle {
                margin: 8px 0 0;
                color: #092635;
                font-size: 1rem;
            }
            
            .restricted-quiz-modal-body {
                padding: 24px;
                color: #455a64;
                line-height: 1.6;
            }
            
            .time-restriction-notice {
                margin: 20px 0;
            }
            
            .time-remaining-box, .deadline-box {
                display: flex;
                justify-content: space-between;
                padding: 12px;
                margin-bottom: 10px;
                background: #fff5f5;
                border-radius: 6px;
                border-left: 4px solid #1b4242;
            }
            
            .important-note {
                margin-top: 20px;
                padding: 12px;
                background: #fff3e0;
                border-radius: 6px;
                font-size: 0.9em;
                color: #1b4242;
            }
            
            .restricted-quiz-modal-footer {
                padding: 16px 24px;
                text-align: right;
                background: #fafafa;
                border-top: 1px solid #eeeeee;
            }
            
            .restricted-quiz-modal-button {
                padding: 10px 20px;
                background: #092635;
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 500;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
            }
            
            .restricted-quiz-modal-button:hover {
                background: #1b4242;
                transform: translateY(-2px);
            }
            
            .restricted-quiz-modal-button svg {
                transition: transform 0.2s ease;
            }
            
            .restricted-quiz-modal-button:hover svg {
                transform: translateX(3px);
            }
        </style>`;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const modal = document.getElementById('restricted-quiz-modal');
        const closeBtn = document.getElementById('restricted-quiz-close-btn');
        
        // Update the time remaining in the modal periodically
        const modalTimeDisplay = document.getElementById('modal-time-remaining');
        function updateModalTime() {
            modalTimeDisplay.textContent = timeDisplay.textContent;
        }
        const modalTimeUpdater = setInterval(updateModalTime, 1000);
        
        setTimeout(() => {
            modal.classList.add('visible');
        }, 100);
        
        closeBtn.addEventListener('click', function() {
            modal.classList.remove('visible');
            clearInterval(modalTimeUpdater);
            setTimeout(() => {
                modal.remove();
            }, 300);
        });
    }

    function showQuizOpenModal() {
        // (Keep your existing showQuizOpenModal function here)
    }

    function showQuizEndedModal() {
        // (Keep your existing showQuizEndedModal function here)
    }

    // Initialize based on quiz restriction status
    if (isRestricted) {
        updateCountdown();
        const countdownTimer = setInterval(updateCountdown, 1000);
        // Show restricted quiz modal
        showRestrictedQuizModal();
    } else {
        // Show quiz open modal if not restricted
        showQuizOpenModal();
    }
});
</script>


<style>
    .modal-submitted-already-footer {
        display:flex;
        justify-content:center;
    }
    .btn-already-submitted {
        background-color:#092635;
        color:white;
        border-radius:5px;
        width:100%;
    }

    .btn-already-submitted:hover{
        background-color:#1b4242;
        color:white;
    }
</style>
<!-- Already Submitted Modal (Unique ID) -->
<div id="alreadySubmittedModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ">
                <h5 class="modal-title">Quiz Already Submitted</h5>
            </div>
            <div class="modal-body">
                <p>You have already submitted this quiz. Resubmission is not allowed.</p>
            </div>
            <div class="modal-footer modal-submitted-already-footer">
                <a href="AcademAI-user(learners)-view-quiz-answer-1.php?quiz_id=<?= $quiz_id ?>" class="btn btn-already-submitted">
                    View Results
                </a>
            </div>
        </div>
    </div>
</div>


<?php if (isset($_SESSION['already_submitted'])) : ?>
<script id="triggerAlreadySubmittedModal">
    $(document).ready(function() {
        $('#alreadySubmittedModal').modal('show');
        
        // Optional: Redirect after modal closes
        $('#alreadySubmittedModal').on('hidden.bs.modal', function () {
            window.location.href = 'AcademAI-user(learners)-view-quiz-answer-1.php?quiz_id=<?= $quiz_id ?>';
        });
    });
</script>
<?php 
    unset($_SESSION['already_submitted']); // Clear the flag
endif; 
?>
</body>
</html>