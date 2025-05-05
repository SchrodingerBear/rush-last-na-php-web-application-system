<?php
require_once('../include/extension_links.php');
include('../classes/connection.php');

// Place at the VERY TOP of every page
session_start();



// ===== ADD THIS SECTION RIGHT AFTER session_start() ===== //
// Handle participant removal if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_participant'])) {
    // Verify required parameters
    if (!isset($_POST['quiz_id']) || !isset($_POST['user_id']) || !isset($_SESSION['creation_id'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'error' => 'Missing parameters']));
    }

    $quiz_id = $_POST['quiz_id'];
    $user_id = $_POST['user_id'];

    try {
        $db = new Database();
        $conn = $db->connect();
        
        // First verify the current user is the quiz owner
        $query = "SELECT creation_id FROM quizzes WHERE quiz_id = :quiz_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->execute();
        $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$quiz || $quiz['creation_id'] != $_SESSION['creation_id']) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'Forbidden']));
        }
        
        // Delete the participation record
        $query = "DELETE FROM quiz_participation WHERE quiz_id = :quiz_id AND user_id = :user_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        error_log("Error removing participant: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
// ===== END OF ADDED SECTION ===== //

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
  $participant['missed'] = true; // Add this for JavaScript compatibility
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
   

<!-- Remove Participant Modal -->
<div class="modal fade" id="remove-participant-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Removal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove this participant?</p>
            </div>
            <div class="modal-footer">
                <input type="hidden" id="remove-quiz-id">
                <input type="hidden" id="remove-user-id">
                <button type="button" class="modal-btn confirm-btn" id="confirm-remove-btn">Remove</button>
                <button type="button" class="modal-btn cancel-btn" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>





<script>
// Initialize modal when DOM is loaded
// Initialize modal when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const removeModal = new bootstrap.Modal(document.getElementById('remove-participant-modal'));
    const confirmRemoveBtn = document.getElementById('confirm-remove-btn');
    let currentRemoval = {};

    // Enhanced click handler with better debugging
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-btn')) {
            const button = e.target.closest('.remove-btn');
            
            // Debug the button element
            console.log("Remove button attributes:", {
                quizId: button.dataset.quizId,
                userId: button.dataset.userId,
                html: button.outerHTML
            });

            const quizId = button.dataset.quizId;
            const userId = button.dataset.userId;
            
            if (!userId || userId === 'undefined') {
                console.error("Invalid user ID detected", {
                    buttonHTML: button.outerHTML,
                    allAttributes: button.getAttributeNames().map(name => ({
                        name,
                        value: button.getAttribute(name)
                    }))
                });
                alert('Error: Could not identify participant');
                return;
            }

            currentRemoval = {
                quizId,
                userId,
                buttonElement: button
            };
            
            removeModal.show();
        }
    
    });

    // Handle modal confirmation
// Handle modal confirmation
confirmRemoveBtn.addEventListener('click', async function() {
    removeModal.hide();
    
    const { quizId, userId, buttonElement } = currentRemoval;
    const originalText = buttonElement.innerHTML;
    
    try {
        buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...';
        buttonElement.disabled = true;

        const response = await fetch('../tools/remove_participant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `quiz_id=${encodeURIComponent(quizId)}&user_id=${encodeURIComponent(userId)}`
        });

        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || `Server error: ${response.status}`);
        }

        // Remove from UI
        const participantRow = buttonElement.closest('tr, .status-besides, .participant-row');
        if (participantRow) {
            participantRow.remove();
            
            // Update count
            const countElement = document.getElementById('attendee-count');
            if (countElement) {
                const currentCount = parseInt(countElement.textContent) || 0;
                countElement.textContent = Math.max(0, currentCount - 1);
            }
        }
        
        // Simple success alert
        alert('Participant was successfully removed!');
        
    } catch (error) {
        console.error('Removal failed:', error);
        alert(`Error: ${error.message}\n\nPlease check console for details`);
    } finally {
        buttonElement.innerHTML = originalText;
        buttonElement.disabled = false;
    }

});
    // Initialize participant list
    fetchParticipants();
});

// Single fetchParticipants function
// Single fetchParticipants function (fixed version)
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
                    
                    // FIXED: Properly set both data attributes
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
                        <div class="ACTION-BTN">
                            <button type="button" class="remove-btn" 
                                    data-quiz-id="${quizId}" 
                                    data-user-id="${participant.creation_id}">
                                <i class="fas fa-minus-circle"></i> Remove
                            </button>  
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

// Back link functionality (keep your existing implementation)
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
    
    

<script>
document.addEventListener("DOMContentLoaded", function() {
    const backLink = document.getElementById('back-link');
    const urlParams = new URLSearchParams(window.location.search);
    const quizId = urlParams.get('quiz_id'); // Get only quiz_id from URL

    // Fetch quiz data
    fetch("fetch_updated_quizzes.php")
        .then(response => {
            if (!response.ok) throw new Error("Network response was not ok");
            return response.json();
        })
        .then(data => {
            let targetPage = "AcademAI-Library-Leaderboard.php"; // Default
            
            if (quizId) {
                if (data.upcoming?.some(quiz => quiz.quiz_id == quizId)) {
                    targetPage = "AcademAI-Library-Upcoming-Card.php";
                } else if (data.ongoing?.some(quiz => quiz.quiz_id == quizId)) {
                    targetPage = "AcademAI-Library-Running-Card.php";
                } else if (data.completed?.some(quiz => quiz.quiz_id == quizId)) {
                    targetPage = "AcademAI-Library-Completed-Card.php";
                }
            }

            // Set href with only quiz_id parameter
            backLink.href = quizId ? `${targetPage}?quiz_id=${encodeURIComponent(quizId)}` : targetPage;
        })
        .catch(error => {
            console.error("Error:", error);
            backLink.href = "AcademAI-Library-Leaderboard.php";
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
<!-- Header with Back Button and User Profile -->


<div class=" first-con">


       
    <div class="con-host">
        <div class="name">
            <div class = "d-flex align-items-center profile">
            <i class =  "fa-solid fa-chalkboard-teacher"> </i>
            <h2>Hosted by:</h2>
            <p><?php echo $owner_name; ?></p>
            </div>

<!-- Dashboard section with icon -->
<div class="dashboard ">
<a href="AcademAI-Library-Leaderboard.php?quiz_id=<?php echo htmlspecialchars($quiz_id); ?>" class="custom-leaderboard-link">
        <img src="../img/trophy.gif" class="trophy">
        <p class="Leaderboard">LEADERBOARD</p>
    </a>
</div>

        </div>

        <div class="attendees">
            <div class="status-name">
                <div class="number-attendees ">
      
                    <h3>Quiz Attendees:</h3>
                    <h3 id="attendee-count"><?php echo count($participants); ?></h3>
                </div>
                
                <div class="status">
           
                    <h3>Status</h3>
                </div>

                <div class="date-completed ">
             
                    <h3>Joined On</h3>
                </div>


                  
            <div class="action ">
                <h2 class="action-removal">Action</h2>
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
                <div class="ACTION-BTN">
                <button type="button" class="remove-btn" 
        data-quiz-id="<?php echo $quiz_id; ?>" 
        data-user-id="${participant.creation_id}">
    <i class="fas fa-minus-circle"></i> Remove
</button>
    
    </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No participants found for this quiz.</div>
    <?php endif; ?>
</div>
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
/* Add to your existing styles */
.missed-section {
    margin-top: 2rem;
    border-top: 2px solid #e74c3c;
}

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

/* [Rest of your existing styles remain the same] */
</style>





<style>
/* Base Styles with Inter Font */
:root {
    --primary: #4a6bff;
    --secondary: #10b981;
    --warning: #f59e0b;
    --text-dark: #1f2937;
    --text-medium: #4b5563;
    --text-light: #6b7280;
    --bg-light: #f9fafb;
    --border-light: #e5e7eb;
}

.first-con {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--text-dark);
    max-width: 100%;
    margin: 0;
    background-color: white;
}

/* Load Inter font from Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.con-host {
    background-color: white;
    padding: 0;
}



/* Remove Button */
.remove-btn {
    background-color:#9EC8B9;
    color:#fff;
    font-size: 0.875em;
    border:none;
    padding:10 20px;
}

.number-attendees  {
    display:flex;

}
/* Header Section - Compact */
.name {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-light);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.profile {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.profile i {
    font-size: 1.25rem;
    color:white;
    background-color:#5C8374;
    padding: 0.75rem;
    border-radius: 50%;
}

.profile h2 {
    font-size: 1.2em;
    font-weight: 500;
    color: #1b4242;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom:0;
}

.profile p {
    font-size: 1.2em;
    font-weight: 600;
    color: #1b4242;
    margin: 0;
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
  justify-content: center;
  text-decoration:none;
 
}


.custom-leaderboard-link:hover {
  transform: translateY(-5px);
  text-decoration:none;
  color: #1b4242;
  
}

.trophy {
  width: 40px;
  height: 30px;
  margin-right: 0.75rem;
}

.Leaderboard {
  margin: 0;
  font-size: 1.2em;
}

/* Attendees Section - Space Efficient */
.attendees {
    margin-top: 0;
}

.status-name {
    display: flex;
    background-color: var(--bg-light);
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--border-light);
   
}

.number-attendees, .status, .date-completed, .action {
    flex: 1;
}

.action {
    text-align: right;
    padding-right: 1rem;
}

/* Attendees List - Compact */
#attendees-list {
    max-height: 60vh;
    overflow-y: auto;

    
}





.status-besides {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-light);
    transition: background-color 0.2s ease;
}

.status-besides:hover {
    background-color: var(--bg-light);
 
   

 
   
}


.status {
    text-align:center;
    margin-right:5%;
}
.date-completed h3  {
    text-align:end;
    margin-right:15%;
}
.student-name, .status-item-not-2, .date-joined {
    flex: 1;
}

.student-name {
    font-weight: 500;
    color: var(--text-dark);
    font-size: 1em;
}

.date-joined p {
    color: var(--text-light);
    font-size: 1em;
    margin: 0;
}

/* Modern Status Badges with Icons */
.completed {
    color: var(--secondary);
    font-weight: 600;
    background: rgba(16, 185, 129, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    font-size: 0.875em;
    width: fit-content;
    text-align:center;
    justify-content:center;
}

.completed:before {
    content: "✓";
    margin-right: 0.5rem;
    font-weight: bold;
    font-size: 0.65rem;
}

.pending {
    color: var(--warning);
    font-weight: 600;
    background: rgba(245, 158, 11, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    font-size: 0.875em;
    width: fit-content;
}

.pending:before {
    content: "↻";
    margin-right: 0.5rem;
    animation: spin 2s linear infinite;
    font-size: 0.65rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Compact Scrollbar */
#attendees-list::-webkit-scrollbar {
    width: 4px;
}

#attendees-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#attendees-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.number-attendees h3, .status h3 , .date-completed h3 , .action-removal {
    color:#1b4242;
    font-size:1.2em;
    font-weight:600;

}



/* Responsive Adjustments */
@media (max-width: 768px) {
    .status-name {
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
    }
    
    .status-besides {
        flex-wrap: wrap;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
    }
    
    .student-name, .status-item-not-2, .date-joined {
        flex: 100%;
    }
    
    .status-item-not-2 {
        order: 3;
    }
    
    .date-joined {
        order: 2;
    }
}
</style>

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
/* Add this to your existing CSS */
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
</style>
</body>
</html>



