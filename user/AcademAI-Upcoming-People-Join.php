<?php
require_once('../include/extension_links.php');
include('../classes/connection.php');

// Place at the VERY TOP of every page
session_start();

// Default values for user profile
$full_name = "Unknown";
$email = "N/A";
$photo_path = '../img/default-avatar.jpg';

// Fetch current user details if logged in
if (isset($_SESSION['creation_id'])) {
    $db = new Database();
    $conn = $db->connect();
    
    try {
        $query = "SELECT first_name, middle_name, last_name, email, photo_path 
                  FROM academai 
                  WHERE creation_id = :creation_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':creation_id', $_SESSION['creation_id'], PDO::PARAM_INT);
        $stmt->execute();
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current_user) {
            $full_name = htmlspecialchars(
                trim($current_user['first_name'] . ' ' . 
                $current_user['middle_name'] . ' ' . 
                $current_user['last_name'])
            );
            $email = htmlspecialchars($current_user['email']);
            
            // Handle profile picture path
            if (!empty($current_user['photo_path'])) {
                $possible_paths = [
                    '../uploads/profile/' . basename($current_user['photo_path']),
                    '../' . $current_user['photo_path'],
                    'uploads/profile/' . basename($current_user['photo_path']),
                    $current_user['photo_path']
                ];
                
                foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                        $photo_path = $path;
                        break;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching user details: " . $e->getMessage());
    }
}

// Default values for quiz info
$owner_name = "Unknown";
$participants = [];
$quiz_end_date = null;
$quiz_title = "Unknown Quiz";

if (isset($_GET['quiz_id'])) {
    $quiz_id = $_GET['quiz_id'];

    // Connect to the database
    $db = new Database();
    $conn = $db->connect();

    try {
        // Fetch basic quiz info including end date
        $query = "SELECT q.title, q.end_date, a.first_name, a.middle_name, a.last_name
                  FROM quizzes q
                  INNER JOIN academai a ON q.creation_id = a.creation_id
                  WHERE q.quiz_id = :quiz_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->execute();
        $quiz_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($quiz_info) {
            $quiz_title = htmlspecialchars($quiz_info['title']);
            $quiz_end_date = $quiz_info['end_date'];
            $owner_name = htmlspecialchars($quiz_info['first_name'] . ' ' . $quiz_info['middle_name'] . ' ' . $quiz_info['last_name']);
        }

        // Fetch all participants for the specific quiz
        $query = "SELECT a.creation_id, a.first_name, a.middle_name, a.last_name, qp.join_date, qp.status
                  FROM quiz_participation qp
                  INNER JOIN academai a ON qp.user_id = a.creation_id
                  WHERE qp.quiz_id = :quiz_id
                  ORDER BY 
                      CASE WHEN qp.status = 'completed' THEN 1 
                           WHEN qp.status = 'missed' THEN 2
                           ELSE 3 END,
                      qp.join_date DESC";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add missed status to participants who didn't complete before end date
        $current_date = date('Y-m-d H:i:s');
        if ($quiz_end_date && $current_date > $quiz_end_date) {
            foreach ($participants as &$participant) {
                if ($participant['status'] != 'completed') {
                    $participant['status'] = 'missed';
                }
            }
            unset($participant); // Break the reference
        }

    } catch (Exception $e) {
        // Set default values in case of error
        $owner_name = "Unknown";
        $participants = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/academAI-library-people-join.css">
    <title>Quiz Maestro Library</title>
</head>
<body>
 
<script>
document.addEventListener("DOMContentLoaded", function() {
    const backLink = document.getElementById('back-link');
    const urlParams = new URLSearchParams(window.location.search);
    const quizId = urlParams.get('quiz_id');

    // First check if student has completed this quiz
    fetch(`check_quiz_completion.php?quiz_id=${quizId}`)
        .then(response => response.json())
        .then(completionData => {
            if (completionData.completed) {
                // If student completed it, always go to Completed page
                backLink.href = `AcademAI-Activity-Completed-Card.php?quiz_id=${encodeURIComponent(quizId)}`;
                return;
            }
            
            // If not completed, check quiz status
            return fetch("fetch_updated_quizzes.php")
                .then(response => {
                    if (!response.ok) throw new Error("Network response was not ok");
                    return response.json();
                })
                .then(quizData => {
                    let targetPage = "AcademAI-Activity-Not-Taken-Card.php";
                    
                    if (quizId) {
                        if (quizData.upcoming?.some(quiz => quiz.quiz_id == quizId)) {
                            targetPage = "AcademAI-Activity-Upcoming-Card.php";
                        } else if (quizData.ongoing?.some(quiz => quiz.quiz_id == quizId)) {
                            targetPage = "AcademAI-Activity-Running-Card.php";
                        }
                        // Completed case already handled above
                    }
                    
                    backLink.href = quizId ? `${targetPage}?quiz_id=${encodeURIComponent(quizId)}` : targetPage;
                });
        })
        .catch(error => {
            console.error("Error:", error);
            backLink.href = quizId ? 
                `AcademAI-Activity-Completed-Card.php?quiz_id=${encodeURIComponent(quizId)}` : 
                "AcademAI-Activity-Not-Taken-Card.php";
        });
});
</script>

   
<!-- Header with Back Button and User Profile -->
<div class="header">
<a href="#" id="back-link" class="back-btn">
    <i class="fa-solid fa-chevron-left"></i>
</a>
    
    <div class="header-right">  
        <div class="user-profile">
            <img src="<?php echo htmlspecialchars($photo_path); ?>" 
                 alt="User" 
                 class="profile-pic" 
                 onerror="this.onerror=null; this.src='../img/default-avatar.jpg'">    
            <div class="user-info">
                <span class="user-name"><?php echo $full_name; ?></span>
                <span class="user-email"><?php echo $email; ?></span>
            </div>
        </div>
    </div>
</div>

<div class="first-con">
    <div class="con-host">
        <div class="name">
            <div class="d-flex align-items-center profile">
                <i class="fa-solid fa-chalkboard-teacher"></i>
                <h2>Hosted by:</h2>
                <p><?php echo $owner_name; ?></p>
            </div>

            <!-- Dashboard section with icon -->
            <div class="dashboard">
            <a href="AcademAI-QuizTaker-Leaderboard.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" class="custom-leaderboard-link">
        <img src="../img/trophy.gif" class="trophy">
        <p class="Leaderboard">LEADERBOARD</p>
    </a>
            </div>
        </div>

        <div class="attendees">
            <div class="status-name">
                <div class="number-attendees">
                    <h3>Quiz Attendees:</h3>
                    <h3 id="attendee-count"><?php echo count($participants); ?></h3>
                </div>
                
                <div class="status">
                    <h3>Status</h3>
                </div>

                <div class="date-completed">
                    <h3>Joined On</h3>
                </div>
            </div>

            <div id="attendees-list">
                <?php if (!empty($participants)): ?>
                    <?php foreach ($participants as $participant): 
                        // Determine status class and text
                        $status_class = '';
                        $status_text = '';
                        
                        switch ($participant['status']) {
                            case 'completed':
                                $status_class = 'completed';
                                $status_text = 'Completed';
                                break;
                            case 'missed':
                                $status_class = 'missed';
                                $status_text = 'Missed';
                                break;
                            default:
                                $status_class = 'pending';
                                $status_text = 'Not yet taken';
                                break;
                        }
                    ?>
                        <div class="status-besides">   
                            <div class="student-name">
                                <?php echo htmlspecialchars($participant['first_name'] . ' ' . $participant['middle_name'] . ' ' . $participant['last_name']); ?>
                            </div>
                            <div class="status-item-not-2">
                                <span id="still-not" class="<?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </div>   
                            <div class="date-joined">
                                <p><?php echo date("F j, Y h:i A", strtotime($participant['join_date'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">No participants found for this quiz.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize participant list
fetchParticipants();

// Single fetchParticipants function
function fetchParticipants() {
    let quizId = <?php echo json_encode($quiz_id); ?>;
    fetch(`fetch_participants.php?quiz_id=${quizId}`)
        .then(response => response.json())
        .then(data => {
            let attendeesDiv = document.getElementById('attendees-list');
            attendeesDiv.innerHTML = ''; // Clear old data

            if (data.length > 0) {
                let currentDate = new Date();
                let quizEnded = <?php echo ($quiz_end_date && date('Y-m-d H:i:s') > $quiz_end_date) ? 'true' : 'false'; ?>;
                
                data.forEach(participant => {
                    // Determine status
                    let statusClass, statusText;
                    
                    if (participant.status === 'completed') {
                        statusClass = 'completed';
                        statusText = 'Completed';
                    } else if (quizEnded || participant.missed) {
                        statusClass = 'missed';
                        statusText = 'Missed';
                    } else {
                        statusClass = 'pending';
                        statusText = 'Not yet taken';
                    }
                    
                    let participantDiv = document.createElement('div');
                    participantDiv.classList.add('status-besides');
                    
                    participantDiv.innerHTML = `
                        <div class="student-name">
                            ${participant.first_name} ${participant.middle_name} ${participant.last_name}
                        </div>
                        <div class="status-item-not-2">
                            <span id="still-not" class="${statusClass}">${statusText}</span>
                        </div>   
                        <div class="date-joined">
                            <p>${new Date(participant.join_date).toLocaleString()}</p>
                        </div>
                    `;
                    attendeesDiv.appendChild(participantDiv);
                });
            } else {
                attendeesDiv.innerHTML = "<div class='alert alert-info'>No participants found for this quiz.</div>";
            }

            document.getElementById('attendee-count').innerText = data.length;
        })
        .catch(error => console.error('Error fetching participants:', error));
}

// Auto-refresh every 5 seconds
setInterval(fetchParticipants, 5000);

// Back link functionality
document.addEventListener("DOMContentLoaded", function() {
    const backLink = document.getElementById('back-link');
    const urlParams = new URLSearchParams(window.location.search);
    const quizId = urlParams.get('quiz_id');

    fetch("fetch_updated_quizzes.php")
        .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then(data => {
            let targetPage = "AcademAI-Library-Leaderboard.php";
            
            if (quizId) {
                if (data.upcoming?.some(quiz => quiz.quiz_id == quizId)) {
                    targetPage = "AcademAI-Library-Upcoming-Card.php";
                } else if (data.ongoing?.some(quiz => quiz.quiz_id == quizId)) {
                    targetPage = "AcademAI-Library-Running-Card.php";
                } else if (data.completed?.some(quiz => quiz.quiz_id == quizId)) {
                    targetPage = "AcademAI-Library-Completed-Card.php";
                }
            }

            backLink.href = quizId ? `${targetPage}?quiz_id=${encodeURIComponent(quizId)}` : targetPage;
        })
        .catch(error => {
            console.error("Error:", error);
            backLink.href = "AcademAI-Library-Leaderboard.php";
        });
});
</script>


</body>
</html>

<style>


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
</style>


<style>
    /* Pagination Styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 2rem;
    padding: 1rem;
    flex-wrap: wrap;
}

.page-link {
    display: inline-block;
    padding: 8px 16px;
    background-color: #f3f4f6;
    color: #4b5563;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-weight: 500;
    min-width: 40px;
    text-align: center;
}

.page-link:hover {
    background-color: #e5e7eb;
    color: #1f2937;
}

.page-link.active {
    background-color: #4f46e5;
    color: white;
    font-weight: 600;
}

.page-link.first,
.page-link.last {
    background-color: #d1d5db;
}

.page-link.prev,
.page-link.next {
    background-color: #e5e7eb;
}

/* Responsive Pagination */
@media (max-width: 768px) {
    .pagination {
        gap: 4px;
    }
    
    .page-link {
        padding: 6px 12px;
        min-width: 32px;
    }
    
    .page-link.first,
    .page-link.last,
    .page-link.prev,
    .page-link.next {
        display: none;
    }
}
</style>
<style>
    body{
    margin:0px;
    font-family: 'Inter', sans-serif;
    background-color: #f8f9fa;
}
//* Modern Full-Space Layout with Custom Color Palette */
:root {
    --primary: #4F46E5;       /* Indigo */
    --primary-light: #EEF2FF; /* Light indigo */
    --secondary: #10B981;     /* Emerald */
    --warning: #F59E0B;       /* Amber */
    --text-dark: #1E293B;     /* Dark slate */
    --text-medium: #64748B;   /* Slate */
    --text-light: #94A3B8;    /* Light slate */
    --bg-light: #F8FAFC;      /* Lightest slate */
    --white: #FFFFFF;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
}

/* Full-Space Base Styles */
.first-con {
    padding: 0;
    width: 100%;
    min-height: 100vh;
    background: var(--bg-light);
    font-family: 'Inter', system-ui, sans-serif;
}

/* Header Container - Full Width */
.con-host {
    background: var(--white);
 
    margin-bottom: 2px;
    box-shadow: var(--shadow-sm);
    
}

/* Modern Host Profile Section */
.name {
    display: flex
;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-light);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    
    
}

.profile {
    display: flex
;
    align-items: center;
    gap: 1rem;
}

.profile i {
    font-size: 1.25rem;
    color: white;
    background-color: #5C8374;
    padding: 0.75rem;
    border-radius: 50%;
}
.name h2 {
    font-size: 1.2em;
    font-weight: 500;
    color: #1b4242;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom:0;
}


.name p {
    font-size: 1.2em;
    font-weight: 600;
    color: #1b4242;
    margin-bottom:0;
}

/* Leaderboard Button - Floating Action */
.dashboard {
  
    padding:0;
}

.custom-leaderboard-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #1b4242;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 5px;
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    justify-content: flex-end;
    text-decoration:none;
   
}

.custom-leaderboard-link:hover {
    transform: translateY(-5px);
    text-decoration:none;
    color:#1b4242!important;
}

.trophy {
    width: 40px;
    height: 30px;
    margin-right: 0.75rem;
}

.Leaderboard {
    margin-bottom: 0;
    font-size: 1.2em;
    
}

/* Attendees Section - Full Width Modern Table */
.attendees {
 
    width: 100%;
    box-shadow: var(--shadow-sm);
}

.status-name {
    display: grid;
    justify-content:center;
    grid-template-columns: 5fr 3fr 4fr;
    gap: 2rem;
    padding: 1.5rem 1em;
    margin-bottom: 1rem;
    max-width: 1600px;
    margin: 0 auto;
    box-shadow: 
        0 2px 6px rgba(0, 0, 0, 0.12), 
        0 4px 10px rgba(0, 0, 0, 0.10);
    border-bottom: 1px solid rgb(208, 208, 208);
    border-top: 1px solid  rgb(208, 208, 208);
    color:#ffff;
    justify-items:center;
    background-color:#9EC8B9;
   


}

.number-attendees  {
    display:flex;

}




.status-name h3 {
    margin: 0;
    font-size: 1.2em;
    color: var(--text-medium);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Attendees List - Card-Like Table */
#attendees-list {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
   
}

.status-besides {
    display: grid;
    grid-template-columns: 5fr 3fr 4fr;
    gap: 2rem;
    align-items: center;
    padding: 1.5rem 1em;
    box-shadow: 
        0 4px 6px rgba(0, 0, 0, 0.06), 
        0 2px 4px rgba(0, 0, 0, 0.04); 
    transition: all 0.3s ease;
    color: #092635;
    justify-items:center;
   
  
}

.status-besides:nth-child(odd) {
    background-color: #f9f9f9; /* Light gray for odd items */
}

.status-besides:nth-child(even) {
    background-color:rgb(237, 237, 237); /* Light gray for odd items */
}


.status-besides:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    background-color:rgb(220, 220, 220); /* Light gray for odd items */
    color:white;

}

.student-name {
    font-weight: 500;
    color: #092635;
    font-size: 1em;
   
}

.date-joined p {
    margin: 0;
    color:  #6b7280;
    font-size: 1em;
  
}

/* Modern Status Badges with Icons */
.missed {
    color: #e74c3c;
    font-weight: 600;
    background: rgba(231, 76, 60, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    font-size: 0.875em;
    width: fit-content;
}

.missed:before {
    content: "✗";
    margin-right: 0.5rem;
    font-weight: bold;
    font-size: 0.65rem;
}
.completed {
    color:  #10b981;
    font-weight: 600;
    background: rgba(16, 185, 129, 0.1);
    padding: 0.75rem 1.25rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    font-size: 0.875rem;
    width: fit-content;
}

.completed:before {
    content: "✓";
    margin-right: 0.5rem;
    font-weight: bold;
}

.pending {
    color: var(--warning);
    font-weight: 600;
    background: rgba(245, 158, 11, 0.1);
    padding: 0.75rem 1.25rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    font-size: 0.875rem;
    width: fit-content;
}

.pending:before {
    content: "↻";
    margin-right: 0.5rem;
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Empty State Design */
#attendees-list > p {
    text-align: center;
    color: var(--text-light);
    padding: 4rem;
    font-size: 1rem;
    background: var(--bg-light);
    border-radius: 16px;
    margin: 2rem auto;
    max-width: 1600px;
    box-shadow: var(--shadow-sm);
}

/* Responsive Breakpoints */


@media (max-width: 768px) {
    .status-name,
    .status-besides {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .status-name {
        display: none;
    }
    
    .status-besides {
        margin-bottom: 1rem;
    }
    
    .student-name::before {
        content: "Student:";
        font-weight: 600;
        color: var(--text-medium);
        margin-right: 0.75rem;
        min-width: 80px;
    }
    
    .status-item-not-2::before {
        content: "Status:";
        font-weight: 600;
        color: var(--text-medium);
        margin-right: 0.75rem;
        min-width: 80px;
    }
    
    .date-joined p::before {
        content: "Joined:";
        font-weight: 600;
        color: var(--text-medium);
        margin-right: 0.75rem;
        min-width: 80px;
    }
}

@media (max-width: 480px) {
    .name {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
    }
    
    .profile {
        flex-direction: row;
        width: 100%;
    }
    
    .custom-leaderboard-link {
        width: 100%;
    }
    
    #attendees-list > p {
        padding: 2rem 1rem;
    }
}
</style>