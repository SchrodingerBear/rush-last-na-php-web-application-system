<?php
require_once('../include/extension_links.php');
?>

<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get the quiz_id from the URL
if (isset($_GET['quiz_id'])) {
    $quiz_id = $_GET['quiz_id'];

    // Fetch quiz details including photo_path
    $quizQuery = "SELECT q.*, a.first_name, a.middle_name, a.last_name, a.email, a.photo_path 
                  FROM quizzes q 
                  JOIN academai a ON q.creation_id = a.creation_id 
                  WHERE q.quiz_id = :quiz_id";
    $quizStmt = $pdo->prepare($quizQuery);
    $quizStmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $quizStmt->execute();
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    // Handle profile picture path for the quiz creator
    if (!empty($quiz['photo_path'])) {
        $possible_paths = [
            '../uploads/profile/' . basename($quiz['photo_path']),
            '../' . $quiz['photo_path'],
            'uploads/profile/' . basename($quiz['photo_path']),
            $quiz['photo_path']
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $quiz['photo_path'] = $path;
                break;
            }
        }
    } else {
        $quiz['photo_path'] = '../img/default-avatar.jpg'; // Default avatar if no photo
    }

    // Fetch essay questions for the quiz
    $essayQuery = "SELECT * FROM essay_questions WHERE quiz_id = :quiz_id";
    $essayStmt = $pdo->prepare($essayQuery);
    $essayStmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $essayStmt->execute();
    $essayQuestions = $essayStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$quiz) {
        die("Quiz not found.");
    }
    if (empty($essayQuestions)) {
        die("No essay questions found for this quiz.");
    }
} else {
    die("Quiz ID not provided.");
}

if (!function_exists('mime_content_type_from_name')) {
    function mime_content_type_from_name($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'txt' => 'text/plain',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
        ];
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
?>

























<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['title']); ?> - Quiz Archive QUIZ VIEW</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <style>
        :root {
            --primary-dark: #092635;
            --primary: #1B4242;
            --secondary: #5C8374;
            --light: #9EC8B9;
            --white: #ffffff;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 6px 20px rgba(0, 0, 0, 0.15);
            --radius: 5px;
            --transition: all 0.3s ease;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-dark: #092635;
            --text-light: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            
        }
        
        body {
            color: var(--text-dark);
            line-height: 1.6;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }
        
        .container {
            width: 100%;
            max-width: 100%;
          
            display: grid;
            grid-template-columns: 250px 1fr ;
            gap: 30px;
            margin:0;
            padding:0;
          
           
        }
        
        .main-content {
            grid-column: 2;
            margin-top:50px;
      
            padding-right:30px;
        }
        
        .sidebar {
            grid-column: 1;
            padding:10px;
            padding-top:50px;


            box-shadow: 4px 0 6px -1px rgba(0, 0, 0, 0.1), 2px 0 4px -1px rgba(0, 0, 0, 0.06);

        }

                
        /* Profile */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        
      
        
        
        
        /* Quiz Navigation Tabs */
        .quiz-nav-tabs {
            display: flex;
            border-bottom: 2px solid var(--primary-dark);
            margin-bottom: 25px;
            width: 100%;
           
        }
        
        .quiz-tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            color: var(--primary-dark);
            position: relative;
            transition: color 0.3s ease, border-bottom 0.3s ease, background 0.3s ease;
        }

        .quiz-tab-btn i {
            font-size: 1rem;
        }

        /* Active tab style */
        .quiz-tab-btn.active {
            background: var(--primary-dark);
            color: white;
        
        }

        /* Hover state (only if not active) */
        .quiz-tab-btn:hover:not(.active) {
            color: var(--primary);
            border-bottom: 3px solid var(--primary);
        }


        
        .quiz-tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-dark);
        }
        
        .quiz-tab-content {
            display: none;
            width: 100%;
        }
        
        .quiz-tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Quiz Card */
        .quiz-card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 20px;
            transition: var(--transition);
            width: 100%;
        }
        
        .quiz-card:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .card-title {
            font-size: 1.2rem;
            color: var(--primary-dark);
            position: relative;
            padding-bottom: 8px;

        }
        
        .card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
         
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            width: 100%;
        }
        
        .info-item {
            margin-bottom: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius:5px;
            padding:10px;
            transition: all 0.3s ease;
        }

        .info-item:hover {
    transform: translateY(-2px); /* Slight lift */
    box-shadow: 0 6px 12px -2px rgba(0, 0, 0, 0.15), 
                0 4px 8px -2px rgba(0, 0, 0, 0.1);
}
        
        .info-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1em;
        }
        
        .info-label i {
            color: var(--primary-dark);
            font-size: 1em;
        }
        
        .info-value {
            padding-left: 25px;
            font-size: 0.9rem;
        }
        
        /* Restriction Setting */
        .restriction-setting {
            margin: 1.2rem 0;
            padding: 0.8rem 0;
            border-top: 1px solid rgba(92, 131, 116, 0.3);
            border-bottom: 1px solid rgba(92, 131, 116, 0.3);
            grid-column: 1 / -1;
        }
        
        .restriction-text {
            color: var(--primary-dark);
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .quiz-settings {
            display:flex;
            justify-content:space-between;
            border-top: 1px solid rgba(92, 131, 116, 0.3);
            border-bottom: 1px solid rgba(92, 131, 116, 0.3);
        }
        
        /* Rubric Button */
        .rubric-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 30px;
            border-radius: var(--radius);
            background-color: var(--primary-dark);
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            margin-top: 12px;
            font-size: 0.875em;
        }
        
        .rubric-btn:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }
        
        
        
        /* Essay Questions */
        .essay-container {
            display: grid;
            gap: 15px;
            width: 100%;
        }

        .essay-header {
            display:flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed rgb(229, 229, 229);
            margin-bottom:20px;
        }

        .see-rubric-btn {
            outline:none;
            color:#1b4242;
            font-weight:600px;
        }
    
        
        .essay-question {
            background-color: var(--card-bg);
            border-radius: var(--radius);
             box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
             padding: 0px 15px 15px;
            transition: var(--transition);
            width: 100%;
        }
        
        .essay-question:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            width: 100%;
            border-bottom: 1px solid #e9ecef;
            padding:10px 0;
        }
        
        .question-text {
            font-weight: 600;
            color: var(--primary-dark);
            flex: 1;
            font-size: 1em;
            margin-bottom:0;
        }
        
        .question-meta {
            display: flex;
            gap: 12px;
            margin-top: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.875em;
            background-color: #092635;
            padding: 4px 8px;
            border-radius: var(--radius);
            color:white;
          
        }
        
        .meta-value {
            font-weight: 600;
            color:white;
        }
        
        .answer-section {
        
            margin-top: 12px;
            width: 100%;
            font-size: 1em;
            color:#1b4242;
            text-align:justify;
            border: 1px solid #e9ecef;
            padding:10px;
            border-radius:5px;
            
        }
        
        /* File Uploads */
        .file-uploads {
            margin-top: 12px;
            width: 100%;
        }
        
        .file-list {
            list-style: none;
            margin-top: 8px;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        
        .file-link {
            color: var(--secondary);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .file-link:hover {
            color: var(--primary);
        }

        .navbar {
            display:flex;
            justify-content:space-between;
            background-color: #092635;
          
            padding: 20px;
        
            box-shadow: var(--shadow);
        }
        
        /* Quiz Code */
        #quiz-code {
            display: none;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                grid-column: 1;
                margin-top: 30px;
            }
            
            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .action-btn {
                flex: 1 1 180px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .quiz-nav-tabs {
                flex-wrap: wrap;
            }
            
            .quiz-tab-btn {
                flex: 1;
                text-align: center;
                min-width: 120px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .question-header {
                flex-direction: column;
            }
            
            .question-meta {
                flex-direction: column;
                gap: 8px;
            }
        }




/* Modern Minimal Sidebar */
.sidebar {
    position: relative;
    width: 280px;
    padding: 25px;
    display: flex;
    flex-direction: column;
    min-height: 700px; /* Or use 100vh if you want full viewport height */
    height: 100%;
    background: white;
    border-right: 1px solid #eaeef2;
}

/* Optional: If you want the leaderboard to stay visible when scrolling */
.sidebar-content {
    flex: 1;
    overflow-y: auto; /* Allows scrolling for main content */
    padding-bottom: 20px; /* Prevents content from hiding behind leaderboard */
}



.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    background: transparent;
    color: #092635;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 14px;
    font-weight: 500;
    border: 1px solid #eaeef2;
}

.action-btn:hover {
    background: #f8fafc;
    transform: none;
    border-color: #d0d7de;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    text-decoration:none;
}

.action-btn i {
    width: 18px;
    text-align: center;
    color: #1b4242;
}

.action-btn.delete {
    color: #9e2a2b;
    border-color: #f3d7d7;

}

.action-btn.delete:hover {
    background: #fef2f2;
    border-color: #f3d7d7;
    text-decoration:none;
}

.action-btn.delete i {
    color: #9e2a2b;
}

/* Leaderboard Section - Fixed at bottom */
.leaderboard-section {
    margin-top: auto; /* This pushes it to the bottom */
    padding-top: 25px;
    padding-bottom: 20px; /* Added bottom padding */
    border-top: 1px solid #eaeef2;
    position: sticky;
    bottom: 0;
    background: white; /* Ensures it stays visible when scrolling */
    z-index: 1; /* Keeps it above other content */
}
.leaderboard-header {
    text-align: center;
    margin-bottom: 15px;
}

.leaderboard-title {
    font-size: 16px;
    font-weight: 600;
    color: #092635;
    margin-bottom: 8px;
    letter-spacing: 0.2px;
}

.leaderboard-header p {
    font-size: 12px;
    color: #64748b;
    margin-bottom: 18px;
    line-height: 1.5;
}

.leaderboard-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #1b4242;
    transition: all 0.25s ease;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #eaeef2;
    background:rgb(254, 254, 254);
}

.leaderboard-link:hover {
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transform: none;
    border-color: #d0d7de;
    text-decoration:none;
    color:#1b4242;
}

.leaderboard-img {
    width: 48px;
    height: 48px;
    margin-bottom: 10px;
    filter: none;
}

.leaderboard-link span {
    font-size: 13px;
    font-weight: 500;
    color: #1b4242;
}

/* Quiz code styling */
#quiz-code {
    color: #64748b;
    font-size: 11px;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 6px;
    margin-top: 12px;
    border: 1px solid #eaeef2;
    font-family: monospace;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        min-height: auto;
        padding: 20px;
        border-right: none;
        border-bottom: 1px solid #eaeef2;
    }
    
    .action-buttons {
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .action-btn {
        flex: 1 1 45%;
        font-size: 13px;
        padding: 10px 12px;
    }
    
    .leaderboard-section {
        margin-top: 30px;
        padding-top: 25px;
    }
}



    </style>
</head>
<body>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const backLink = document.getElementById('back-link');
    const urlParams = new URLSearchParams(window.location.search);
    const quizId = urlParams.get('quiz_id'); // Get the quiz_id from the URL

    // Fetch quiz data from the PHP script
    fetch("fetch_updated_quizzes.php")
        .then(response => {
            if (!response.ok) {
                throw new Error("Network response was not ok");
            }
            return response.json();
        })
        .then(data => {
            console.log("Fetched Data:", data); // Debugging: Check what data is being returned

            if (data.error) {
                console.error("Error:", data.error);
                return;
            }

            let pageToNavigate = "defaultPage.php"; // Default fallback page
            let quizStatus = null;

            // Check the status of the quiz based on its quiz_id
            if (quizId) {
                if (data.upcoming.some(quiz => quiz.quiz_id == quizId)) {
                    quizStatus = "upcoming";
                    pageToNavigate = "AcademAI-Library-Upcoming-Card.php";
                } else if (data.ongoing.some(quiz => quiz.quiz_id == quizId)) {
                    quizStatus = "ongoing";
                    pageToNavigate = "AcademAI-Library-Running-Card.php";
                } else if (data.completed.some(quiz => quiz.quiz_id == quizId)) {
                    quizStatus = "completed";
                    pageToNavigate = "AcademAI-Library-Completed-Card.php";
                }
            }

            console.log("Quiz Status:", quizStatus); // Debugging: Check the quiz status
            console.log("Page to navigate:", pageToNavigate); // Debugging: Check if the right page is selected

            // Set the back link based on the quiz status
            if (quizStatus) {
                backLink.href = pageToNavigate;
            } else {
                // Fallback: Use the referrer or default page
                if (document.referrer.includes("AcademAI-Library-Upcoming-Card.php")) {
                    backLink.href = "AcademAI-Library-Upcoming-Card.php";
                } else if (document.referrer.includes("AcademAI-Library-Running-Card.php")) {
                    backLink.href = "AcademAI-Library-Running-Card.php";
                } else if (document.referrer.includes("AcademAI-Library-Completed-Card.php")) {
                    backLink.href = "AcademAI-Library-Completed-Card.php";
                } else {
                    backLink.href = pageToNavigate; // Default fallback
                }
            }

            console.log("Back link href set to:", backLink.href); // Debugging: Verify the link
        })
        .catch(error => {
            console.error("Error fetching quizzes:", error);
            // Set a default back link in case of an error
            backLink.href = "defaultPage.php";
        });
});
</script>

<!-- Header with Back Button and User Profile -->
<div class="header">
<a href = "" id="back-link"class="back-btn">
            <i class="fa-solid fa-chevron-left"></i>
            </a>   
            <div class="header-right">  
                <div class="user-profile">
                <img src="<?php echo htmlspecialchars($quiz['photo_path']); ?>" alt="Creator Profile Picture"class="profile-pic" >
                    <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($quiz['first_name'] . ' ' . $quiz['middle_name'] . ' ' . $quiz['last_name']); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($quiz['email']); ?></span>
                      
                    </div>
                </div>
            </div>
        </div>
        <!-- Header with Back Button and User Profile -->
    <div class="container">
        
        <!-- Sidebar with Action Buttons -->
        <?php if($_SESSION["creation_id"] == $quiz["creation_id"]): ?>
            <div class="sidebar">
                <div class="action-buttons">
                    <a href="#" id="library-completed-edit-modalBtn" class="action-btn" data-bs-toggle="modal" data-bs-target="#edit-card-modal">
                        <i class="fas fa-edit"></i> Edit Quiz
                    </a>
                    
                    <a href="#" class="action-btn delete" data-bs-toggle="modal" data-bs-target="#delete-card-modal">
                        <i class="fas fa-trash-alt"></i> Delete Quiz
                    </a>
                    
                    <a href="#" id="copyQuizCode" class="action-btn">
                        <i class="fas fa-link"></i> Copy Quiz Code
                    </a>

                    
                    <a href="#" class="action-btn" data-bs-toggle="modal" data-bs-target="#reuse-card-modal">
                        <i class="fas fa-recycle"></i> Reuse Quiz
                    </a>
                    
                    <p id="quiz-code" style="display: none;">
                        <?php echo isset($quiz['quiz_code']) ? htmlspecialchars($quiz['quiz_code']) : 'NO_CODE'; ?>
                    </p>
                </div>
        <?php endif; ?>

                <!-- Leaderboard Section -->
                <div class="leaderboard-section">
                        <div class="leaderboard-header">
                            <h2 class="leaderboard-title">Leaderboard</h2>
                            <p class = "leaderboard-header p ">Top performers will be displayed here once the quiz has submissions.</p>
                            <a href="AcademAI-Library-Leaderboard.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" class="leaderboard-link">
                                <img src="../img/trophy.gif" class="leaderboard-img" alt="Leaderboard">
                                <span>View Full Leaderboard</span>
                            </a>
                        </div>
                    </div>

          
               
 
            </div>







    <div class="main-content">
       
            
            <!-- Quiz Navigation Tabs -->
            <div class="quiz-nav-tabs">
                <button class="quiz-tab-btn active" data-tab="quiz-info">  <i class="fas fa-lightbulb"></i> Quiz Information</button>
                <button class="quiz-tab-btn" data-tab="essay-questions"><i class="fas fa-feather-alt"></i> Essay Questions</button>
                
            </div>
            
            <!-- Quiz Info Tab -->
            <div id="quiz-info" class="quiz-tab-content active">
                <div class="quiz-card">
                <div class = "essay-header">
                <h2 class="card-title">Quiz Details</h2>
                </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <p class="info-label"><i class="fas fa-heading"></i> Title:</p>
                            <p class="info-value"><?php echo htmlspecialchars($quiz['title']); ?></p>
                        </div>
                        
                        <div class="info-item">
                            <p class="info-label"><i class="fas fa-book"></i> Subject:</p>
                            <p class="info-value"><?php echo htmlspecialchars($quiz['subject']); ?></p>
                        </div>
                        
                        <div class="info-item">
                            <p class="info-label"><i class="fas fa-align-left"></i> Description:</p>
                            <p class="info-value"><?php echo htmlspecialchars($quiz['description']); ?></p>
                        </div>
                        
                        <!-- Restriction Setting -->
                       
                                <div class="restriction-setting">
                                    <p class="restriction-text">
                                        <?php if ($quiz['is_active'] == 1): ?>
                                            <i class="fas fa-lock"></i> Restricted: Quiz will auto-close at <?php echo htmlspecialchars($quiz['end_time']); ?> on <?php echo htmlspecialchars($quiz['end_date']); ?>
                                        <?php else: ?>
                                            <i class="fas fa-unlock"></i>Unrestricted: This quiz will remain open even after the deadline.
                                        <?php endif; ?>
                                    </p>
                                </div>



                                <!-- HTML Section -->
                            <div class="setting-container">
                            <p class="info-label"><i class="fas fa-sliders-h"></i> Quiz Submission Settings :</p>
                                <div class="setting-item">
                                    <div class="file-submission-setting">
                                        <p class="file-submission-text">
                                            <?php if (isset($quiz['allow_file_submission']) && $quiz['allow_file_submission']): ?>
                                                <i class="fas fa-file-upload" style="color: #4CAF50;"></i> File submissions are allowed for this quiz.
                                            <?php else: ?>
                                                <i class="fas fa-file-excel" style="color: #f44336;"></i> File submissions are not allowed for this quiz.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

<style>
.file-submission-setting {
    display: flex;
    align-items: center;
    margin: 10px 0;
}

.file-submission-text {
    margin: 0;
    padding: 8px 12px;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  
    font-size: 0.9rem;
    color: #092635;

}

.file-submission-text i {
   
    font-size: 0.9rem;

    margin-right: 8px;
}
.setting-container {
    margin-bottom: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border-radius: 5px;
    padding: 10px;
    transition: all 0.3s ease;
}
</style>

                        
                        <div class="info-item">
                            <p class="info-label"><i class="fas fa-calendar-day"></i> Start Date:</p>
                            <p class="info-value"><?php echo htmlspecialchars($quiz['start_date']); ?> at <?php echo htmlspecialchars($quiz['start_time']); ?></p>
                        </div>
                        
                        <div class="info-item">
                            <p class="info-label"><i class="fas fa-calendar-times"></i> End Date:</p>
                            <p class="info-value"><?php echo htmlspecialchars($quiz['end_date']); ?> at <?php echo htmlspecialchars($quiz['end_time']); ?></p>
                        </div>
                        
                        <div class="info-item">
                            <p class="info-label"><i class="fas fa-star"></i> Total Points:</p>
                            <p class="info-value"><?php echo htmlspecialchars($quiz['quiz_total_points_essay']); ?></p>
                        </div>
                    </div>
                    
                  
                </div>
            </div>
            
            <!-- Essay Questions Tab -->
            <div id="essay-questions" class="quiz-tab-content">
                <div class="quiz-card">

                <div class = "essay-header">
                <h2 class="card-title">
                 Essay Questions
                </h2>
                <?php if (!empty($essayQuestions)): ?>
                    <?php $firstEssay = reset($essayQuestions); ?>
                    <!-- Button to trigger modal -->
                    <button type="button" class="btn see-rubric-btn" 
                            data-bs-toggle="modal" 
                            data-bs-target="#rubricModal"
                            data-rubric-id="<?php echo $firstEssay['rubric_id']; ?>"
                            data-quiz-id="<?php echo $quiz_id; ?>">
                        <i class="fas fa-table-list"></i> See Rubric
                    </button>
                <?php endif; ?>
                </div>


                    <div class="essay-container">
                        <?php foreach ($essayQuestions as $index => $essay): ?>

                            <div class="essay-question">
                                <div class="question-header">
                                    <p class="question-text"> <i class="fas fa-question-circle"></i>
                                        Question <?php echo isset($essay['id']) ? htmlspecialchars($essay['id']) : ($index + 1); ?> 
                                    </p>
                                    <div class="question-meta">
                                    <div class="meta-item">
                                        <span>Points:</span>
                                        <span class="meta-value"><?php echo htmlspecialchars($essay['points_per_item']); ?></span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <span>Word Limit:</span>
                                        <span class="meta-value"><?php echo htmlspecialchars($essay['min_words']); ?>-<?php echo htmlspecialchars($essay['max_words']); ?></span>
                                    </div>
                                </div>
                                </div>

                                <div>
                                <?php echo htmlspecialchars($essay['question']); ?>
                                </div>
                                
                                
                                <div class="answer-section">
                                    <?php
                                    $concatenated_answers = $essay['answer'];
                                    $answers = explode('|', $concatenated_answers);
                                    
                                    foreach ($answers as $answerIndex => $answer) {
                                        echo '<p>' . htmlspecialchars(trim($answer)) . '</p>';
                                    }
                                    ?>
                                </div>
                                
                                <?php if (!empty($essay['file_name']) && !empty($essay['file_upload'])): ?>
                                    <div class="file-uploads">
                                        <p class="info-label"><i class="fas fa-paperclip"></i> Attached Files:</p>
                                        <ul class="file-list">
                                            <?php
                                            $fileNames = explode(',', $essay['file_name']);
                                            $fileUploads = explode(',', $essay['file_upload']);
                                            
                                            foreach ($fileNames as $fileIndex => $fileName) {
                                                $fileData = $fileUploads[$fileIndex] ?? '';
                                                $fileMimeType = mime_content_type_from_name($fileName);
                                                $base64Data = base64_encode($fileData);
                                                $downloadUrl = "data:$fileMimeType;base64,$base64Data";
                                                ?>
                                                <li class="file-item">
                                                    <i class="fas fa-file"></i>
                                                    <a href="<?= $downloadUrl ?>" download="<?= htmlspecialchars($fileName) ?>" class="file-link">
                                                        <?= htmlspecialchars($fileName) ?>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                
     
       


                                            </div>
                                            </div>
                                            </div>








    
    <!-- Modals -->
    <!-- Edit Quiz Modal -->
    <div class="modal fade" id="edit-card-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Edit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to edit this quiz?</p>
                </div>
                <div class="modal-footer">
                    <a href="AcademAI-Edit-Quiz-Essay.php?quiz_id=<?php echo $quiz_id; ?>" class="modal-btn confirm-btn">Yes</a>
                    <button type="button" class="modal-btn cancel-btn" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Quiz Modal -->
    <div class="modal fade" id="delete-card-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this quiz?</p>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="delete-quiz-id" value="<?php echo $quiz_id; ?>">
                    <button type="button" class="modal-btn confirm-btn" id="confirm-delete-btn">Yes</button>
                    <button type="button" class="modal-btn cancel-btn" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reuse Quiz Modal -->
    <div class="modal fade" id="reuse-card-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Reuse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reuse this quiz?</p>
                </div>
                <div class="modal-footer">
                    <a href="create-set-quiz.php?quiz_id=<?php echo $quiz_id; ?>" class="modal-btn confirm-btn">Yes</a>
                    <button type="button" class="modal-btn cancel-btn" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>




    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme Toggle
            const themeToggle = document.getElementById('themeToggle');
            const icon = themeToggle.querySelector('i');
            
            // Check for saved theme preference or use preferred color scheme
            const savedTheme = localStorage.getItem('theme') || 
                              (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            
            // Apply the saved theme
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                icon.classList.replace('fa-moon', 'fa-sun');
            }
            
            // Toggle theme
            themeToggle.addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
                
                if (document.body.classList.contains('dark-mode')) {
                    icon.classList.replace('fa-moon', 'fa-sun');
                    localStorage.setItem('theme', 'dark');
                } else {
                    icon.classList.replace('fa-sun', 'fa-moon');
                    localStorage.setItem('theme', 'light');
                }
            });
            
            // Tab functionality
            const tabBtns = document.querySelectorAll('.quiz-tab-btn');
            const tabContents = document.querySelectorAll('.quiz-tab-content');
            
            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all buttons and contents
                    tabBtns.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Back link functionality
            const backLink = document.getElementById('back-link');
            const urlParams = new URLSearchParams(window.location.search);
            const quizId = urlParams.get('quiz_id');
            
            fetch("fetch_updated_quizzes.php")
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.json();
                })
                .then(data => {
                    let pageToNavigate = "AcademAI-Library-Upcoming-Card.php"; // Default
                    
                    if (quizId) {
                        if (data.upcoming.some(quiz => quiz.quiz_id == quizId)) {
                            pageToNavigate = "AcademAI-Library-Upcoming-Card.php";
                        } else if (data.ongoing.some(quiz => quiz.quiz_id == quizId)) {
                            pageToNavigate = "AcademAI-Library-Running-Card.php";
                        } else if (data.completed.some(quiz => quiz.quiz_id == quizId)) {
                            pageToNavigate = "AcademAI-Library-Completed-Card.php";
                        }
                    }
                    
                    backLink.href = pageToNavigate;
                })
                .catch(error => {
                    console.error("Error fetching quizzes:", error);
                    backLink.href = "AcademAI-Library-Upcoming-Card.php";
                });
            
            // Copy quiz code functionality
            document.addEventListener("click", function(event) {
                if (event.target.id === "copyQuizCode" || event.target.closest("#copyQuizCode")) {
                    event.preventDefault();
                    
                    let quizCodeElement = document.querySelector("#quiz-code");
                    if (!quizCodeElement) {
                        alert("Quiz code not found!");
                        return;
                    }
                    
                    let quizCode = quizCodeElement.textContent.trim();
                    if (!quizCode || quizCode === "NO_CODE") {
                        alert("No quiz code available to copy.");
                        return;
                    }
                    
                    // Use Clipboard API
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(quizCode)
                            .then(() => showCopySuccess(quizCode))
                            .catch(err => alert("Failed to copy quiz code: " + err.message));
                    } else {
                        // Fallback for older browsers
                        let textArea = document.createElement("textarea");
                        textArea.value = quizCode;
                        document.body.appendChild(textArea);
                        textArea.select();
                        try {
                            document.execCommand("copy");
                            showCopySuccess(quizCode);
                        } catch (err) {
                            alert("Failed to copy quiz code (fallback). Error: " + err.message);
                        }
                        document.body.removeChild(textArea);
                    }
                }
            });
            
            // Success Message Function
            function showCopySuccess(quizCode) {
                const successMessage = document.createElement("div");
                successMessage.textContent = `Copied quiz code: ${quizCode}`;
                successMessage.style.position = "fixed";
                successMessage.style.bottom = "20px";
                successMessage.style.left = "50%";
                successMessage.style.transform = "translateX(-50%)";
                successMessage.style.backgroundColor = "#1b4332";
                successMessage.style.color = "white";
                successMessage.style.padding = "10px 20px";
                successMessage.style.borderRadius = "var(--radius)";
                successMessage.style.zIndex = "9999";
                successMessage.style.boxShadow = "var(--shadow)";
                
                document.body.appendChild(successMessage);
                setTimeout(() => {
                    successMessage.style.opacity = "0";
                    successMessage.style.transition = "opacity 0.5s ease-out";
                    setTimeout(() => successMessage.remove(), 500);
                }, 2000);
            }
            
            // Delete quiz functionality
            const confirmDeleteBtn = document.getElementById("confirm-delete-btn");
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener("click", function() {
                    const quizId = document.getElementById("delete-quiz-id").value;
                    
                    fetch("delete_quiz.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: "quiz_id=" + quizId,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showDeleteSuccess();
                            setTimeout(function() {
                                window.location.href = "AcademAI-Library-Upcoming-Card.php";
                            }, 2000);
                        } else {
                            alert("Failed to delete quiz: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("An error occurred while deleting the quiz.");
                    });
                });
            }
            
            function showDeleteSuccess() {
                const successMessage = document.createElement("div");
                successMessage.textContent = "Quiz deleted successfully!";
                successMessage.style.position = "fixed";
                successMessage.style.bottom = "20px";
                successMessage.style.left = "50%";
                successMessage.style.transform = "translateX(-50%)";
                successMessage.style.backgroundColor = "#1b4332";
                successMessage.style.color = "white";
                successMessage.style.padding = "10px 20px";
                successMessage.style.borderRadius = "var(--radius)";
                successMessage.style.zIndex = "9999";
                successMessage.style.boxShadow = "var(--shadow)";
                
                document.body.appendChild(successMessage);
                setTimeout(() => {
                    successMessage.style.opacity = "0";
                    successMessage.style.transition = "opacity 0.5s ease-out";
                    setTimeout(() => successMessage.remove(), 500);
                }, 1500);
            }
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all tab buttons and tab contents
    const tabBtns = document.querySelectorAll('.quiz-tab-btn');
    const tabContents = document.querySelectorAll('.quiz-tab-content');
    
    // Add click event listeners to each tab button
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabBtns.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>





<script>
   document.addEventListener("click", function (event) {
    if (event.target.id === "copyQuizCode" || event.target.closest("#copyQuizCode")) {
        event.preventDefault(); // Prevent default anchor behavior

        let quizCodeElement = document.querySelector("#quiz-code");
        if (!quizCodeElement) {
            alert("Quiz code not found!");
            return;
        }

        let quizCode = quizCodeElement.textContent.trim();
        if (!quizCode || quizCode === "NO_CODE") {
            alert("No quiz code available to copy.");
            return;
        }

        // Use Clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(quizCode)
                .then(() => showCopySuccess(quizCode))
                .catch(err => alert("Failed to copy quiz code: " + err.message));
        } else {
            // Fallback for older browsers
            let textArea = document.createElement("textarea");
            textArea.value = quizCode;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand("copy");
                showCopySuccess(quizCode);
            } catch (err) {
                alert("Failed to copy quiz code (fallback). Error: " + err.message);
            }
            document.body.removeChild(textArea);
        }
    }
});

// Success Message Function
function showCopySuccess(quizCode) {
    const successMessage = document.createElement("div");
    successMessage.textContent = `Successfully copied quiz code ${quizCode} to clipboard!`;
    successMessage.style.position = "fixed";
    successMessage.style.bottom = "20px";
    successMessage.style.left = "50%";
    successMessage.style.transform = "translateX(-50%)";
    successMessage.style.backgroundColor = "#1b4332";

    successMessage.style.color = "white";
    successMessage.style.padding = "10px 20px";
    successMessage.style.borderRadius = "5px";
    successMessage.style.zIndex = "9999";
    successMessage.style.fontSize = '18px';
    document.body.appendChild(successMessage);
    setTimeout(() => {
        successMessage.style.opacity = "0";
        successMessage.style.transition = "opacity 0.5s ease-out";
        setTimeout(() => successMessage.remove(), 500);
    }, 2000);
}

</script>











<style>
   .confirm-btn,
    .cancel-btn {
        background-color: #092635;
        color: white;
        border: none;
        border-radius: 5px;
        text-decoration: none;
        padding: 0.5em 1em; /* more scalable than px */
        width: 100%;        /* make it fill the container */
        max-width: 100px;   /* limit width on bigger screens */
        text-align: center;
        box-sizing: border-box; /* ensures padding doesn't break layout */
        transition: background-color 0.3s ease;
    }

.cancel-btn {
    background-color: #1b4242;
}

.cancel-btn:hover,
.confirm-btn:hover {
    background-color: #5C8374;
    color: white;
    text-decoration: none;
}

</style>


<style>

#rubric-modal {
    margin:0;
}


/* Modal content box */
#rubric-modal-content {
    width: 100vw;
    max-width: none;
    height: 100%;
    margin: 0;
}

/* Close button (X) */
.close-modal {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close-modal:hover {
    color: #000;
}

.rubric-close {
    background-color: #092635;
    color: white;
    padding: 10px 30px;
    border:none;
    border-radius:5px;
    
}

.rubric-close:hover {
    background-color:#5c8374
}

/* Loading spinner (optional) */
.loading-spinner {
    text-align: center;
    padding: 20px;
}

.fa-spinner {
    font-size: 24px;
}
</style>

<!-- Full-screen Bootstrap Modal -->
<div class="modal fade" id="rubricModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen" id = "rubric-modal">
        <div class="modal-content" id ="rubric-modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rubric Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="rubricContent">
                <!-- Content will be loaded here via AJAX -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class=" rubric-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rubricModal = new bootstrap.Modal(document.getElementById('rubricModal'));
    
    // When modal is about to show
    document.getElementById('rubricModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const rubricId = button.getAttribute('data-rubric-id');
        const quizId = button.getAttribute('data-quiz-id');
        
        // Show loading spinner
        document.getElementById('rubricContent').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        // Fetch content via AJAX
        fetch(`AcademAI-See-Essay-Rubric.php?rubric_id=${rubricId}&quiz_id=${quizId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('rubricContent').innerHTML = data;
            })
            .catch(error => {
                document.getElementById('rubricContent').innerHTML = `
                    <div class="alert alert-danger">
                        Error loading rubric: ${error.message}
                    </div>
                `;
            });
    });
    
    // Reset content when modal closes
    document.getElementById('rubricModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('rubricContent').innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    });
});
</script>
</body>
</html>