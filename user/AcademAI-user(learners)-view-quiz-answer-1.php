<?php
 require_once('../include/extension_links.php');
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit();
}

// Database Connection
$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get current user info from session
if (!isset($_SESSION['creation_id'])) die("User data missing.");
$current_user_id = $_SESSION['creation_id'];

// Get user profile data with email
$stmt = $pdo->prepare("SELECT first_name, middle_name, last_name, email, photo_path FROM academai WHERE creation_id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $full_name = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
    $email = $user['email'];
    $photo_path = $user['photo_path'] ? '../' . $user['photo_path'] : '../img/default-avatar.jpg';
} else {
    // Default values if user not found (shouldn't happen since they're logged in)
    $full_name = "User";
    $email = "user@example.com";
    $photo_path = '../img/default-avatar.jpg';
}

// Get quiz ID from URL or latest quiz
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : null;
if (!$quiz_id) {
    $stmt = $pdo->query("SELECT quiz_id FROM quizzes ORDER BY quiz_id DESC LIMIT 1");
    $quiz_id = $stmt->fetchColumn();
    if (!$quiz_id) die("No quizzes found.");
}

// Fetch quiz details and creator info
$stmt = $pdo->prepare("SELECT q.*, a.first_name, a.middle_name, a.last_name 
                      FROM quizzes q
                      JOIN academai a ON q.creation_id = a.creation_id
                      WHERE q.quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) die("Quiz not found.");

$creator_name = trim($quiz['first_name'] . ' ' . $quiz['middle_name'] . ' ' . $quiz['last_name']);

// Get quiz taker ID
$stmt = $pdo->prepare("SELECT quiz_taker_id FROM quiz_participation WHERE user_id = ? AND quiz_id = ?");
$stmt->execute([$current_user_id, $quiz_id]);
$quiz_taker = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz_taker) die("Quiz participation not found.");
$quiz_taker_id = $quiz_taker['quiz_taker_id'];

// Get essay questions
$stmt = $pdo->prepare("SELECT * FROM essay_questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$questions) die("No questions available.");

// Process answers and evaluations
$totalPoints = 0;
$questionData = [];

foreach ($questions as $index => $question) {
    $question_id = $question['essay_id'];
    
    // Get answer
    $stmt = $pdo->prepare("SELECT * FROM quiz_answers WHERE question_id = ? AND quiz_taker_id = ?");
    $stmt->execute([$question_id, $quiz_taker_id]);
    $answer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $questionData[$index] = [
        'question' => $question,
        'answer' => $answer,
        'evaluation' => null,
        'rubric_link' => null
    ];
    
    if ($answer) {
        // Get evaluation
        $stmt = $pdo->prepare("SELECT * FROM essay_evaluations WHERE answer_id = ? ORDER BY evaluation_date DESC LIMIT 1");
        $stmt->execute([$answer['answer_id']]);
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($evaluation) {
            $evalData = json_decode(str_replace(["```json\n", "\n```"], "", 
                             json_decode($evaluation['evaluation_data'], true)["evaluation"]["evaluation"]), true);
            
            $points_per_item = $question['points_per_item'];
            $score = $evalData["overall_weighted_score"];
            $earnedPoints = ($score / 100) * $points_per_item;
            $totalPoints += $earnedPoints;
            
            $questionData[$index]['evaluation'] = [
                'data' => $evalData,
                'score' => $score,
                'earnedPoints' => $earnedPoints,
                'points_possible' => $points_per_item
            ];
            
            // Create assessment link with all required parameters
            $questionData[$index]['rubric_link'] = "AcademAI-Assessment.php?quiz_id=$quiz_id&answer_id={$answer['answer_id']}&rubric_id={$question['rubric_id']}";
        }
    }
}




?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Answers | AcademAI</title>
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   
   <style>
        :root {
            --primary: #092635;
            --primary-dark: #1B4242;
            --secondary: #5C8374;
            --accent: #9EC8B9;
            --light: #f8f9fa;
            --dark: #212529;
            --text: #2b2d42;
            --text-light: #8d99ae;
            --bg: #ffffff;
            --card-bg: #ffffff;
            --border: #e9ecef;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --primary: #9EC8B9;
            --primary-dark: #5C8374;
            --secondary: #1B4242;
            --accent: #092635;
            --light: #2b2d42;
            --dark: #f8f9fa;
            --text: #f8f9fa;
            --text-light: #adb5bd;
            --bg: #121212;
            --card-bg: #1e1e1e;
            --border: #2d2d2d;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            transition: var(--transition);
           
            min-height: 100vh;
        }

        .quiz-creator-info-container{
          
        
            padding: 30px;
        }

       
        /* Profile */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #092635;
            box-shadow: 0 4px 6px -1px rgba(100, 100, 100, 0.3), 0 2px 4px -1px rgba(50, 50, 50, 0.2);
            color: #ffffff;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
        }
        .user-info {
            display: flex;
            flex-direction: column;
            max-width: 800px; /* or whatever fits your layout */
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .user-name {
            font-size: 1em;
            font-weight: 700;
        
        }
        .user-email {
            font-style: italic;
            font-size:0.875em;
        }


        .user-name,
        .user-email {
            white-space: normal;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        


        .profile-pic {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #5C8374;
        }

        .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        color: #ffffff;
        font-weight: 500;
        font-size:2em;
        transition: color 0.3s ease, transform 0.3s ease;
        }
    
        .back-btn:hover {
            color: #ffffff;
            transform: translateX(-5px); /* move slightly to the left */
            text-decoration: none;
        }

        /* Profile */





        .user-role {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Quiz Creator Info */
        .creator-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 60px;
            padding: 16px 25px;
            background-color: var(--card-bg);
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary);
            transition: var(--transition);
        }

        .creator-info:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
        }

        .creator-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
        }

        .creator-text {
            font-size:1em;
        }

        .creator-text strong {
            color: var(--primary);
        }

        /* Navigation Tabs */
        .nav-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 30px;
            overflow-x: auto;
            border-bottom: 1px solid rgb(196, 196, 196);
            align-items: center; /* Add this to vertically center all items */
        }

        .tab-btn {
            padding: 12px 24px;
            background: var(--card-bg);
            border: none;

            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
            transition: var(--transition);
            color: #092635;
            font-size:1.2em;

        }

        .tab-btn i {
            font-size: 1rem;
        }

        .tab-btn.active {
            background: var(--primary);
            color: white;
          
        }

        .tab-btn:hover:not(.active) {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }

        /* Tab Content */
        .tab-content {
            display: none;
            padding:0px 20px;
            margin-bottom: 30px;
            transition: var(--transition);
            border: none;
           
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }



        /* Add this new style for the total score in tabs */
        .tabs-total-score {
            margin-left: auto; /* Pushes it to the right */
            padding: 8px 16px;
            color: #092635;
            border-radius: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.2em;
        }

        .tabs-total-score i {
            font-size: 0.9rem;
        }





        /* Quiz Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: var(--card-bg);
            border-radius: 5px;
            padding: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        
            position: relative;
            overflow: hidden;
           
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15); /* soft shadow that's not too harsh */
        }

        .info-card h4 {
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 1em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-card p {
            color: var(--text);
            font-size: 1em;
            line-height: 1.6;
        }

        /* Questions Section */
        .questions-container {
            margin-top: 80px;
            position: relative;
            padding:20px;
        }

        .question-card {
            background: var(--card-bg);
            border-radius: 5px;
            padding: 28px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: none;
            transition: var(--transition);
            border: 1px solid var(--border);
            position: relative;
        }

   

        .question-card.active {
            display: block;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .question-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .question-title i {
            color: var(--primary);
            font-size: 1.4rem;
        }

        .question-number {
            font-weight: 600;
            color: var(--text);
            font-size: 1.2em;
        }

        .question-points {
            background: var(--primary);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 1em;
            font-weight: 500;
            box-shadow: var(--shadow);
        }

        .question-text {
            font-size: 1em;
            margin-bottom: 24px;
            line-height: 1.7;
            color: var(--text);
            padding: 0 10px;
        }

        .answer-section {
         
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 28px;
        
            position: relative;
            border: 1px solid var(--border);
          

        }

        .rubric-selection-btn {
            display:flex;
            justify-content:center;
        }

 
        .answer-section h5 {
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 1.2em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .answer-section h5 i {
            font-size: 1.1rem;
        }

        .answer-text {
            white-space: pre-wrap;
            line-height: 1.8;
            color: var(--text);
        }

        /* Rubrics Section */
        .rubrics-section {
            
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 28px;
            border: 1px solid var(--border);
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Light, soft shadow */
        }

      

        .rubrics-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            color: var(--primary);
        }

        .rubrics-header h4 {
            font-weight: 600;
            font-size: 1.2em;
        }

        .total-score {
            font-size: 1.2em;
            font-weight: 700;
            color: var(--primary);
            margin-left: auto;
        }

        .rubric-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed var(--border);
        }

        .rubric-item:last-child {
            border-bottom: none;
        }

        .rubric-item p {
            flex: 1;
            font-size:1em;
            color: var(--text);
        }

        .rubric-score {
            font-weight: 600;
            font-size:1em;
            color: var(--primary);
        }

        /* Assessment Button */
        .assessment-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: var(--shadow);
            margin-top: 20px;
            border: 2px solid transparent;
            width:100%;
            justify-content:center;
        }

        .assessment-btn:hover {
            background-color: #5C8374;;
            color: white;
            text-decoration:none;
          
            transform: translateY(-2px);
        }

        /* Navigation Buttons */
        .question-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .nav-btn {
            padding: 12px 24px;
            background: #1b4242;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            box-shadow: var(--shadow);
            border: 2px solid transparent;
            font-size:0.875em;
        }

        .nav-btn:hover {
            background: white;
            color: var(--primary);
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .nav-btn:disabled {
            background: var(--text-light);
            cursor: not-allowed;
            transform: none;
        }

        /* Total Points */
        .total-points-card {
        color: #092635;
        margin-top: 40px;
        text-align: center;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
        padding: 10px;
        transition: transform 0.3s ease; /* Smooth movement */
        }

        .total-points-card:hover {
            transform: translateY(-5px); /* Moves the card up by 5px */
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15); /* soft shadow that's not too harsh */
        }


        .total-points-card h3 {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1.2rem;
            position: relative;
        }

        .total-score-display {
            font-size: 2rem;
            font-weight: 700;
            color: #092635;
            position: relative;
            margin: 10px 0;
        }

        /* Progress Indicator */
        .progress-indicator {
            text-align: center;
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 1em;
            font-weight: 600;
         
            padding: 10px;
            border-radius: 50px;
            display: inline-block;
            position: relative;
            left: 50%;
            transform: translateX(-50%);
        }

        /* Responsive Adjustments */
        @media (max-width: 1024px) {
            .quiz-creator-info-container {
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .question-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .question-nav {
                flex-direction: column;
                gap: 12px;
            }

            .nav-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .quiz-creator-info-container{
                padding: 15px;
            }

            .tab-btn {
                padding: 10px 15px;
                font-size: 0.85rem;
            }

            .tab-content {
                padding: 20px;
            }

            .question-card {
                padding: 20px;
            }

            .creator-info {
                flex-direction: column;
                text-align: center;
            }
            
            .total-score-display {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>


     <!-- Header with Back Buttonand User Profile -->
     <div class="header">
            <a href="AcademAI-Activity-Completed-Card.php" class="back-btn">
            <i class="fa-solid fa-chevron-left"></i>
            </a>   
            <div class="header-right">  
                <div class="user-profile">
                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="Profile Picture" class="profile-pic">
                    <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                      
                    </div>
                </div>
            </div>
        </div>
     <!-- Header with Back Buttonand User Profile -->




    <div class="quiz-creator-info-container">
       
        
        <!-- Quiz Creator Info -->
        <div class="creator-info">
            <div class="creator-icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="creator-text">
                Quiz created by <strong><?php echo htmlspecialchars($creator_name); ?></strong>
            </div>
        </div>
        
        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="tab-btn active" onclick="showTab('quiz-info')">
                <i class="fas fa-book-open"></i> Quiz Details
            </button>
            <button class="tab-btn" onclick="showTab('date-info')">
                <i class="fas fa-calendar-day"></i> Schedule
            </button>
            <button class="tab-btn" onclick="showTab('submission-info')">
                <i class="fas fa-paper-plane"></i> Submission
            </button>
            <div class="tabs-total-score"> Quiz Total Score:
       
        <?php echo number_format($totalPoints, 2); ?> / <?php echo htmlspecialchars($quiz['quiz_total_points_essay']); ?> pts
    </div>
</div>

        </div>
        
        <!-- Quiz Info Tab -->
        <div id="quiz-info" class="tab-content active">
            <div class="info-grid">
                <div class="info-card">
                    <h4><i class="fas fa-heading"></i> Quiz Title</h4>
                    <p><?php echo htmlspecialchars($quiz['title']); ?></p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-book"></i> Subject</h4>
                    <p><?php echo htmlspecialchars($quiz['subject']); ?></p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-align-left"></i> Description</h4>
                    <p><?php echo htmlspecialchars($quiz['description']); ?></p>
                </div>
                
                <div class="info-card">
    <h4><i class="fas fa-cog"></i> Quiz Settings</h4>
    <p>
        <?php if ($quiz['is_active']): ?>
            <strong>Restricted:</strong> This quiz will automatically close once the deadline is reached.
        <?php else: ?>
            <strong>Unrestricted:</strong> This quiz will remain open even after the deadline.
        <?php endif; ?>
    </p>
</div>


            </div>
        </div>
        
        <!-- Date Info Tab -->
        <div id="date-info" class="tab-content">
            <div class="info-grid">
                <div class="info-card">
                    <h4><i class="fas fa-calendar-check"></i> Start Date</h4>
                    <p><?php echo date('F j, Y', strtotime($quiz['start_date'])); ?></p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-calendar-times"></i> End Date</h4>
                    <p><?php echo date('F j, Y', strtotime($quiz['end_date'])); ?></p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-clock"></i> Start Time</h4>
                    <p><?php echo date('g:i A', strtotime($quiz['start_time'])); ?></p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-clock"></i> End Time</h4>
                    <p><?php echo date('g:i A', strtotime($quiz['end_time'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Submission Info Tab -->
        <div id="submission-info" class="tab-content">
            <div class="info-grid">
                <div class="info-card">
                    <h4><i class="fas fa-check-circle"></i> Submission Status</h4>
                    <p>Completed</p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-calendar-day"></i> Submission Date</h4>
                    <p><?php echo date('F j, Y'); ?></p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-clock"></i> Submission Time</h4>
                    <p><?php echo date('g:i A'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Questions Section -->
        <div class="questions-container">
            <div class="progress-indicator" id="progressIndicator">
                Question 1 of <?php echo count($questionData); ?>
            </div>
            
            <?php foreach ($questionData as $index => $data): ?>
            <div class="question-card <?php echo $index === 0 ? 'active' : ''; ?>" id="question-<?php echo $index + 1; ?>">
                <div class="question-header">
                    <div class="question-title">
                        <i class="fas fa-question-circle"></i>
                        <span class="question-number">Question <?php echo $index + 1; ?></span>
                    </div>
                    <div class="question-points">
                        <?php if (isset($data['evaluation'])): ?>
                            <?php echo number_format($data['evaluation']['earnedPoints'], 2); ?> / <?php echo $data['evaluation']['points_possible']; ?> pts
                        <?php else: ?>
                            0 / <?php echo $data['question']['points_per_item']; ?> pts
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="question-text">
                    <?php echo htmlspecialchars($data['question']['question']); ?>
                </div>
                
                <div class="answer-section">
                    <h5><i class="fas fa-edit"></i> Your Answer:</h5>
                    <div class="answer-text">
                        <?php echo $data['answer'] ? nl2br(htmlspecialchars($data['answer']['answer_text'])) : 'No answer submitted.'; ?>
                    </div>
                </div>
                
                <?php if (isset($data['evaluation'])): ?>
                <div class="rubrics-section">
                    <div class="rubrics-header">
                        <i class="fas fa-chart-bar"></i>
                        <h4>Evaluation Results</h4>
                        <span class="total-score"><?php echo $data['evaluation']['score']; ?>%</span>
                    </div>
                    
                    <?php foreach ($data['evaluation']['data']['criteria_scores'] as $criteria => $scoreData): ?>
                    <div class="rubric-item">
                        <p><?php echo htmlspecialchars($criteria); ?></p>
                        <span class="rubric-score"><?php echo $scoreData['score']; ?>%</span>
                    </div>
                    <?php endforeach; ?>

                    
                    <div class="rubric-selection-btn">
                    <?php if ($data['rubric_link']): ?>
                    <a href="<?php echo htmlspecialchars($data['rubric_link']); ?>&question_number=<?php echo $index + 1; ?>" 
                    class="assessment-btn"
                    data-question-number="<?php echo $index + 1; ?>">
                        <i class="fas fa-table-list"></i> View Detailed Rubric & Assessment
                    </a>
                    </div>




                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="question-nav">
                    <button class="nav-btn" onclick="prevQuestion()" <?php echo $index === 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-chevron-left"></i> Previous Question
                    </button>
                    <button class="nav-btn" onclick="nextQuestion()" <?php echo $index === count($questionData) - 1 ? 'disabled' : ''; ?>>
                        Next Question <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        

    
   <script>
        // Tab Navigation
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Update active tab button
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
        
        // Question Navigation
        let currentQuestion = 1;
        const totalQuestions = <?php echo count($questionData); ?>;
        const progressIndicator = document.getElementById('progressIndicator');
        
        function showQuestion(questionNum) {
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.remove('active');
            });
            document.getElementById(`question-${questionNum}`).classList.add('active');
            currentQuestion = questionNum;
            
            // Update progress indicator
            progressIndicator.textContent = `Question ${questionNum} of ${totalQuestions}`;
            
            // Scroll to top of question
            window.scrollTo({
                top: document.getElementById(`question-${questionNum}`).offsetTop - 120,
                behavior: 'smooth'
            });
        }
        
        function nextQuestion() {
            if (currentQuestion < totalQuestions) {
                showQuestion(currentQuestion + 1);
            }
        }
        
        function prevQuestion() {
            if (currentQuestion > 1) {
                showQuestion(currentQuestion - 1);
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') {
                nextQuestion();
            } else if (e.key === 'ArrowLeft') {
                prevQuestion();
            }
        });
        
        // Initialize first question
        document.addEventListener('DOMContentLoaded', () => {
            showQuestion(1);
        });
</script>























</body>
</html>