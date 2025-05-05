<?php
// Strict error reporting at the very top (nothing before this)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration before starting
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Enable if using HTTPS

// Start session
session_start();

// Set timeout duration (30 minutes)
$timeout_duration = 1800;

// Validate all required session variables
$required_session_vars = ['logged_in', 'user', 'creation_id', 'last_activity'];

foreach ($required_session_vars as $var) {
    if (!isset($_SESSION[$var])) {
        session_unset();
        session_destroy();
        header('Location: login.php?error=session_invalid');
        exit();
    }
}

// Check authentication status
if ($_SESSION['logged_in'] !== true) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=not_logged_in');
    exit();
}

// Check session timeout
if ((time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?error=session_expired');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Regenerate session ID periodically for security
if (!isset($_SESSION['created_time'])) {
    $_SESSION['created_time'] = time();
} elseif (time() - $_SESSION['created_time'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created_time'] = time();
}

// Only now include other files after session validation
require_once('../include/extension_links.php');

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/quiz_room.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"> 
    <script src="https://cdn.tailwindcss.com"></script> 
    <title>Quiz Room</title>
    <style>
       /* Main layout structure */
       body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        /* Content area below navbar */
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-top: 30px; /* Height of your navbar */
            padding: 20px;
        }

     
        
        /* Quiz room container */
        .quiz-room-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* Card styling */
        .card {
            width: 100%;
            max-width: 1200px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: row!important;
        }
        
        @media (min-width: 1024px) {
            .card {
                flex-direction: row;
            }
        }
        
        /* Panel styling */
        .panel-left {
            width: 100%;
            padding: 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
            border-right: 1px solid #e0e0e0;
        }
        
        .panel-right {
            width: 100%;
            padding: 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: white;
        }


   
        
        
        /* Modal styles - keep your existing modal styles */
        .modal {
            /* Your existing modal styles */
        }
    </style>
</head>
<body>

<!-- Include your sidebar/navigation -->
<?php require_once '../include/new-academai-sidebar.php'; ?>

<!-- Main content area -->
<div class="content-wrapper">
    <!-- Display error message if any -->
    <?php
    if (isset($_SESSION['error_message'])) {
        echo "<div class='alert alert-danger mt-3'>" . $_SESSION['error_message'] . "</div>";
        unset($_SESSION['error_message']);
    }
    ?>

    <!-- Quiz room content -->
    <div class="quiz-room-container">
        <!-- Bigger, Animated Card Container -->
        <div class="card">
            <!-- Left Panel - Welcome Content -->
            <div class="panel-left">
                <div class="w-full max-w-xl mx-auto">
                    <h2 class="text-5xl font-bold text-[#5C8374] mb-2">🧠 Quiz Portal</h2>
                    <!-- Decorative Line -->
                    <div class="w-full h-1 bg-[#5C8374] rounded mb-6"></div>
                    <p class="text-[#5C8374] text-xl leading-relaxed">
                        Create and join quizzes with ease. Fast. Clean. Simple.
                    </p>
                </div>
            </div>
            
            <!-- Right Panel - Form Content -->
            <div class="panel-right">
                <div class="mb-10">
                    <h3 class="text-3xl font-semibold text-[#1b4242] mb-3 animate-heading">Welcome!</h3>
                    <p class="text-[#5C8374] text-lg">Choose an option to get started:</p>
                </div>

                <!-- Form to enter quiz code -->
                <form method="POST" action="../tools/join_quiz.php" class="space-y-5 mb-6">
                    <input
                        type="text"
                        name="quiz_code"
                        placeholder="🔍 Enter Quiz Code"
                        required
                        class="w-full px-5 py-3 border border-[#dcdcdc] rounded-[5px] bg-white text-[#092635] text-lg transition-all"
                    />

                    <!-- Join Quiz Card Button -->
                    <button type="submit" class="join flex items-center justify-center bg-[#1b4242] hover:bg-[#5C8374] text-white w-full py-3 rounded-[5px] text-xl font-semibold transition-all duration-300 transform hover:scale-105">
                        Join Quiz Card
                    </button>

                    <!-- Divider with Text -->
                    <div class="flex items-center my-6">
                        <div class="flex-grow border-t border-gray-300"></div>
                        <span class="mx-4 text-gray-500 font-medium">or</span>
                        <div class="flex-grow border-t border-gray-300"></div>
                    </div>

                    <!-- Create Quiz Button with Icon -->
                    <button class="create flex items-center justify-center bg-[#092635] hover:bg-[#5C8374] text-white w-full py-3 rounded-[5px] text-xl font-semibold transition-all duration-300 transform hover:scale-105">
                        <a href="AcademAI-View- Creation-Quiz.php" class="block text-white">
                            Create Quiz Card
                        </a>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- All your modal HTML remains unchanged -->
<!-- Upcoming Modal -->
<div id="upcoming-card-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="half-image">
            <img src="../img/modal/modal-9.gif" alt="First Image" style="width: 100%; height: 100%;">
        </div>
       
        <div class="submit-content">
        <p class="submit-text">This quiz will be available on </p>
            <div class="yes-btn-modal">
            <a href="#" id="yes-btn" class="yes-btn">Yes</a>
            <a href="AcademAI-Quiz-Room.php" class="cancel-btn">Cancel</a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Upcoming Modal Styling - Consistent with other modals */
    #upcoming-card-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        font-family: 'Inter', sans-serif;
    }

    #upcoming-card-modal .modal-content {
        position: relative;
        background-color: white;
        margin: 5% auto;
        padding: 20px;
        width: 60%;
        max-width: 400px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        font-family: 'Inter', sans-serif;
        animation: modalFadeIn 0.4s ease-out;
    }

    #upcoming-card-modal .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.5em;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
        font-family: 'Inter', sans-serif;
    }

    #upcoming-card-modal .close:hover {
        color: #333;
    }

    #upcoming-card-modal .half-image {
        width: 100%;
        height: 220px;
        overflow: hidden;
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f8f9fa;
    }

    #upcoming-card-modal .half-image img {
        object-fit: contain;
        max-width: 80%;
        max-height: 100%;
    }

    #upcoming-card-modal .submit-content {
        padding: 0 20px;
    }

    #upcoming-card-modal .submit-text {
        margin: 20px 0;
        font-size: 18px;
        color: #333;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
    }

    #upcoming-card-modal .yes-btn-modal {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 25px;
    }

    #upcoming-card-modal .yes-btn,
    #upcoming-card-modal .cancel-btn {
        padding: 10px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        font-family: 'Inter', sans-serif;
        font-size: 0.875em;
        border: none;
        cursor: pointer;
    }

    #upcoming-card-modal .yes-btn {
        background-color: #092635;
        color: white;
        width:95px;
    }

    #upcoming-card-modal .cancel-btn {
        background-color: rgb(236, 236, 236);
        color: #333;
    }

    #upcoming-card-modal .yes-btn:hover {
        background-color: #1b4242;
    }

    #upcoming-card-modal .cancel-btn:hover {
        background-color: #ddd;
    }

    /* Animation */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive adjustments */
    @media screen and (max-width: 768px) {
        #upcoming-card-modal .modal-content {
            width: 80%;
            margin: 20% auto;
        }
        
        #upcoming-card-modal .half-image {
            height: 150px;
        }
        
        #upcoming-card-modal .yes-btn-modal {
            flex-direction: column;
            gap: 10px;
        }
        
        #upcoming-card-modal .yes-btn,
        #upcoming-card-modal .cancel-btn {
            width: 100%;
        }
    }
</style>

<!-- Running Modal -->
<div id="running-card-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="half-image">
            <img src="../img/modal/modal-12.gif" alt="First Image" style="width: 100%; height: 100%;">
        </div>
       
        <div class="submit-content">
        <p class="submit-text">This quiz is already on-going</p>
            <div class="yes-btn-modal">
                <a href="#" id="yes-btn-running" class="yes-btn-running" data-quiz-id="">Yes</a>
                <a href="AcademAI-Quiz-Room.php" class="cancel-btn">Cancel</a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Running Modal Styling - Consistent with not-taken-card-modal */
    #running-card-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        font-family: 'Inter', sans-serif;
    }

    #running-card-modal .modal-content {
        position: relative;
        background-color: white;
        margin: 5% auto;
        padding: 20px;
        width: 60%;
        max-width: 400px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        font-family: 'Inter', sans-serif;
        animation: modalFadeIn 0.4s ease-out;
    }

    #running-card-modal .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.5em;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
        font-family: 'Inter', sans-serif;
    }

    #running-card-modal .close:hover {
        color: #333;
    }

    #running-card-modal .half-image {
        width: 100%;
        height: 220px;
        overflow: hidden;
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f8f9fa;
    }

    #running-card-modal .half-image img {
        object-fit: contain;
        max-width: 80%;
        max-height: 100%;
    }

    #running-card-modal .submit-content {
        padding: 0 20px;
    }

    #running-card-modal .submit-text {
        margin: 20px 0;
        font-size: 18px;
        color: #333;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
    }

    #running-card-modal .yes-btn-modal {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 25px;
    }

    #running-card-modal .yes-btn-running,
    #running-card-modal .cancel-btn {
        padding: 10px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        font-family: 'Inter', sans-serif;
        font-size: 0.875em;
        border: none;
        cursor: pointer;
    }

    #running-card-modal .yes-btn-running {
        background-color: #092635;
        color: white;
        width:95px;
    }

    #running-card-modal .cancel-btn {
        background-color: rgb(236, 236, 236);
        color: #333;
    }

    #running-card-modal .yes-btn-running:hover {
        background-color: #1b4242;
    }

    #running-card-modal .cancel-btn:hover {
        background-color: #ddd;
    }

    /* Animation */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive adjustments */
    @media screen and (max-width: 768px) {
        #running-card-modal .modal-content {
            width: 80%;
            margin: 20% auto;
        }
        
        #running-card-modal .half-image {
            height: 150px;
        }
        
        #running-card-modal .yes-btn-modal {
            flex-direction: column;
            gap: 10px;
        }
        
        #running-card-modal .yes-btn-running,
        #running-card-modal .cancel-btn {
            width: 100%;
        }
    }
</style>

<!-- Done Modal -->
<div id="not-taken-card-modal" class="modal">
    <div class="modal-content" id ="modal-content-done">
        <span class="close">&times;</span>
        <div class="half-image">
            <img src="../img/modal/modal-7.gif" alt="First Image" style="width: 100%; height: 100%;">
        </div>
        
        <div id = "submit-content-done">
            <p class="submit-text">
            </p>
        </div>
        <div class="ok-btn-modal">
                <a href="AcademAI-Quiz-Room.php" id="OK-btn-running" class="OK-btn-running">OK</a>
        </div>
    </div>
</div>


<style>
    /* Import Inter font from Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    /* Modal Background */
    #not-taken-card-modal {
        display: none; /* Hidden by default */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent black */
        z-index: 1000; /* Make sure it appears on top */
        font-family: 'Inter', sans-serif;
    }

    /* Modal Content Box */
    #not-taken-card-modal .modal-content {
        position: relative;
        background-color: white;
        margin: 5% auto; /* Centered with top margin */
        padding: 20px;
        width: 60%;
        max-width: 400px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        font-family: 'Inter', sans-serif;
        height:400px;
    }

    /* Close Button */
    #not-taken-card-modal .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.5em;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
        font-family: 'Inter', sans-serif;
    }

    #not-taken-card-modal .close:hover {
        color: #333;
    }

    /* Half Image at Top */
    #not-taken-card-modal .half-image {
        width: 100%;
        height: 220px;
        overflow: hidden;
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #f8f9fa;
    }

    #not-taken-card-modal .half-image img {
        object-fit: contain;
        max-width: 80%;
        max-height: 100%;
    }

    /* Text Content */
    #not-taken-card-modal .submit-text {
        margin: 20px 0;
        font-size: 18px;
        color: #333;
        font-family: 'Inter', sans-serif;
        font-weight: 500; /* Medium weight for better readability */
    }

    /* Button Container */
    #not-taken-card-modal .ok-btn-modal {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    /* Button */
    #not-taken-card-modal .OK-btn-running {
        padding: 10px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600; /* Semi-bold for buttons */
        transition: all 0.3s;
        font-family: 'Inter', sans-serif;
        font-size: 0.875em;
        border: none;
        cursor: pointer;
        background-color: #092635;
        color: white;
        width:60%;
    }

    #not-taken-card-modal .OK-btn-running:hover {
        background-color: #1b4242;
    }

    /* Animation */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive adjustments */
    @media screen and (max-width: 768px) {
        #not-taken-card-modal .modal-content {
            width: 80%;
            margin: 20% auto;
        }
        
        #not-taken-card-modal .half-image {
            height: 150px;
        }
    }
</style>

<!-- Invalid Code Modal -->
<div id="invalid-code-modal" class="modal">
    <div class="modal-content" id="alert-modal-invalidquizcode">
        <div class="loading-bar"></div>
        <span class="close" id="alert-close">&times;</span>
        <div class="submit-content" id="alert-message-failed">
            <div class="red"></div>
            <div class="failed-img-container">
                <img src="../img/modal/delete.png" class="failed-img">
            </div>
            <div class="text-container">
                <p class="alert-failed-TITLE">FAILED</p>
                <p class="submit-text" id="submit-text-alert-failed"></p>
            </div>
        </div>
        <div class="loading-bar bottom-bar"></div>
    </div>
</div>

<!-- Already Join Quiz Modal -->
<div id="invalid-joinquiz-modal" class="modal">
    <div class="modal-content" id="alert-modal-invalidquizcode">
        <div class="loading-bar"></div>
        <span class="close" id="alert-close-code-same">&times;</span>
        <div class="submit-content" id="alert-message-failed">
            <div class="red"></div>
            <div class="failed-img-container">
                <img src="../img/modal/delete.png" class="failed-img">
            </div>
            <div class="text-container">
                <p class="alert-failed-TITLE">Error</p>
                <p class="submit-text" id="submit-text-alert-failed">You already join the quiz</p>
            </div>
        </div>
        <div class="loading-bar bottom-bar"></div>
    </div>
</div>







<!-- All your JavaScript remains unchanged -->
<script>
function clearLocalStorageBeforeRedirect() {
    localStorage.removeItem("quizData");
    localStorage.removeItem("quizCount");
    localStorage.removeItem("quizzes");
    localStorage.removeItem("quizSettings");
    localStorage.removeItem('startDate');
    localStorage.removeItem('startTime');
    localStorage.removeItem('endDate');
    localStorage.removeItem('endTime');
    localStorage.removeItem('quizRestrictionState');
    localStorage.removeItem('quizTitle');
    localStorage.removeItem('quizSubject');
    localStorage.removeItem('quizDescription');
    localStorage.removeItem('num-points');
    localStorage.removeItem('minimum');
    localStorage.removeItem('maximum');
    localStorage.removeItem('selectedSubjectTitle');
    localStorage.removeItem('selectedSubjectId');
    window.location.href = 'AcademAI-View-%20Creation-Quiz.php';
}

document.querySelector('.create').addEventListener('click', clearLocalStorageBeforeRedirect);
</script>



<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form[action='../tools/join_quiz.php']");
    let currentQuizId = null;
    let activeModal = null;

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        const formData = new FormData(form);

        // Close any existing modal before making new request
        if (activeModal) {
            closeModal(activeModal);
            activeModal = null;
        }

       // Inside your existing script, find this fetch request:
fetch('../tools/join_quiz.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {  // <-- INSERT THE CODE RIGHT AFTER THIS LINE
    console.log("Response Data:", data);
    if (data.error) {
        if (data.error.includes("already joined")) {
            alert("You have already joined this quiz.");
        } else if (data.error.includes("quiz owner")) {
            alert("You cannot join your own quiz as the creator.");
        } else {
            alert(data.error); // Show the exact error message from server
        }
        return;
    }

    currentQuizId = data.quiz_id || null;

    if (data.already_joined) {
        alert("You have already joined this quiz.");
        return;
    }


            // Clear any previous modal before showing new one
            if (activeModal) {
                closeModal(activeModal);
            }

            if (data.status === 'upcoming') {
                showModal("upcoming-card-modal", `This quiz will be available on ${data.start_date} at ${data.start_time}. Do you want to join this quiz for now?`);
            } else if (data.status === 'running') {
                showModal("running-card-modal", "This quiz is currently running. Do you want to join?");
            } else if (data.status === 'done') {
                showModal("not-taken-card-modal", `The quiz you attempted to join is no longer active as it has expired on ${data.end_date} at ${data.end_time}.`);
            } else {
                console.error("Unexpected response:", data);
                alert("An error occurred. Please try again.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred. Please try again.");
        });
    });

    function showModal(modalId, message) {
        const modal = document.getElementById(modalId);
        const modalMessage = modal.querySelector(".submit-text");
        if (modalMessage) modalMessage.textContent = message;
        modal.style.display = "block";
        activeModal = modal;

        const closeButton = modal.querySelector(".close");
        closeButton.addEventListener("click", () => closeModal(modal));

        window.onclick = (event) => {
            if (event.target === modal) closeModal(modal);
        };
    }

    document.body.addEventListener("click", (event) => {
        if (event.target.classList.contains("yes-btn") || event.target.classList.contains("yes-btn-running")) {
            event.preventDefault();
            const modal = event.target.closest('.modal');
            const modalId = modal.id;

            if (!currentQuizId) {
                alert("Quiz ID is missing.");
                console.error("Quiz ID is missing. Cannot proceed.");
                return;
            }
            
            joinQuiz(currentQuizId, modalId);
        }
    });

    function joinQuiz(quizId, modalId) {
        $.ajax({
            url: '../tools/join_quiz.php',
            type: 'POST',
            data: {
                join_quiz: true,
                quiz_id: quizId
            },
            success: function (response) {
                console.log("Server response:", response);
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.success) {
                        const redirectPage = modalId === "upcoming-card-modal" 
                            ? "AcademAI-Activity-Upcoming-Card.php" 
                            : "AcademAI-Activity-Running-Card.php?quiz_id=" + quizId;
                        window.location.href = redirectPage;
                    } else {
                        // Always show the error message from server
                        alert(data.error || "You have already joined this quiz.");
                    }
                } catch (e) {
                    console.error("Error parsing response:", e);
                    // Check if raw response contains "already joined"
                    if (typeof response === 'string' && response.includes("already joined")) {
                        alert("You have already joined this quiz.");
                    } else {
                        alert("An error occurred while processing the response.");
                    }
                }
            },
            error: function (error) {
                console.error("AJAX error:", error);
                // Check if error response contains "already joined"
                if (error.responseText && error.responseText.includes("already joined")) {
                    alert("You have already joined this quiz.");
                } else {
                    alert("You have already joined this quiz.");
                }
            }
        });
    }

    function closeModal(modal) {
        modal.style.display = "none";
        activeModal = null;
    }
});

// Keep your existing modal close handlers
document.getElementById("alert-close").addEventListener("click", function() {
    let modal = document.getElementById("invalid-code-modal");
    modal.style.opacity = "0";
    setTimeout(() => {
        modal.style.display = "none"; 
    }, 300);
});

document.getElementById("alert-close-code-same").addEventListener("click", function() {
    let invalidModal = document.getElementById("invalid-joinquiz-modal");
    let runningModal = document.getElementById("running-card-modal");
    let upcomingModal = document.getElementById("upcoming-card-modal");

    invalidModal.style.opacity = "0";
    if (runningModal) runningModal.style.opacity = "0";
    if (upcomingModal) upcomingModal.style.opacity = "0";

    setTimeout(() => {
        invalidModal.style.display = "none"; 
        if (runningModal) runningModal.style.display = "none"; 
        if (upcomingModal) upcomingModal.style.display = "none"; 
    }, 300);
});
</script>

</body>
</html>