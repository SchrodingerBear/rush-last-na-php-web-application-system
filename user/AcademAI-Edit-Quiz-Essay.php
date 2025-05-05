
<?php
session_start();
require_once('../include/extension_links.php');
include("../classes/connection.php");

if (!isset($_GET['quiz_id'])) {
    echo "<script>alert('Quiz ID not provided'); window.location.href='AcademAI-Quiz-Room.php';</script>";
    exit();
}

if (!is_numeric($_GET['quiz_id'])) {
    echo "<script>alert('Invalid Quiz ID format'); window.location.href='AcademAI-Quiz-Room.php';</script>";
    exit();
}

$quiz_id = $_GET['quiz_id'];
$db = new Database();
$conn = $db->connect();

try {
    // Fetch quiz details
    $query = "SELECT * FROM quizzes WHERE quiz_id = :quiz_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':quiz_id', $quiz_id);
    $stmt->execute();
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch essay questions
    $query = "SELECT * FROM essay_questions WHERE quiz_id = :quiz_id ORDER BY essay_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':quiz_id', $quiz_id);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user info
    $creation_id = $quiz['creation_id'];
    $query = "SELECT CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name, email ,photo_path  FROM academai WHERE creation_id = :creation_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':creation_id', $creation_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

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

    

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Edit Quiz</title>
   


</head>
<body>
    <form id="quizForm" action="../tools/update-quiz.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="quiz_id" value="<?php echo htmlspecialchars($quiz_id); ?>">

        <div class="mcq-container">
           
        
      

                <!-- Header with Back Button and User Profile -->
            <div class="header">
            <a href="#" id="back-link" class="back-btn" onclick="window.location.href='AcademAI-Library-Upcoming-View-Card.php?quiz_id=<?php echo $_GET['quiz_id']; ?>'">
    <i class="fa-solid fa-chevron-left"></i>
</a>

            <div class="header-right">  
                <div class="user-profile">
                <img src="<?php echo htmlspecialchars($user['photo_path']); ?>" alt="Creator Profile Picture"class="profile-pic" >
                    <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                      
                    </div>
                </div>
            </div>
        </div>
        <!-- Header with Back Button and User Profile -->

                <div class="divide-content">

  <div class="quiz-settings-column"> 
  

    <div class="quiz-form-tabs">
    <div class="title-box">
                                    <p class="title">
                                    Total Quiz Points: </p>
                                    <input type="text" id="totalPoints" name="quiz_total_points_essay" value="<?php echo htmlspecialchars($quiz['quiz_total_points_essay']); ?>" readonly>
                                </div>

    <div class="tab-buttons">
        <button type="button" class="tab-btn active" data-tab="quiz-details"><i class="fa-solid fa-book" style="margin-right: 5px;"></i>
        Quiz Details</button>
        <button type="button" class="tab-btn" data-tab="date-time"><i class="fa-solid fa-calendar-days" style="margin-right: 5px;"></i>
        Date & Time</button>
      
    </div>

    <div class="tab-content active" id="quiz-details-tab">
        <div class="author-info">
            <div class="d-grid">
                <p class="title-quiz"><i class="fas fa-heading"></i> Title:</p>
                <textarea id="quizTitle" name="title" required><?php echo htmlspecialchars($quiz['title']); ?></textarea>
            </div>
        </div>
        <div class="d-grid">
            <p class="subject"><i class="fas fa-book"></i> Subject:</p>
            <textarea id="quizSubject" name="subject" required><?php echo htmlspecialchars($quiz['subject']); ?></textarea>
        </div>
        <div class="d-grid desc">
            <p class="descrip"><i class="fas fa-align-left"></i> Description:</p>
            <textarea id="quizDescription" name="description" ><?php echo htmlspecialchars($quiz['description']); ?></textarea>
        </div>



        <div class="setting-container">
            <p class="descrip">Quiz Submission Settings</p>
            <div class="toggle">
                <p class="note">
                    <em><strong>Note:</strong> If this option remains disabled, participants will not be permitted to upload or submit files as part of their quiz responses.</em>
                </p>

                <div class="setting-item">
                    <label class="toggle-switch">
                        <input type="checkbox" id="fileSubmissionToggle" name="allow_file_submission" 
                            <?php echo ($quiz['allow_file_submission'] ?? 0) ? 'checked' : ''; ?> class="visually-hidden">
                        <span class="slider">
                            <span class="toggle-status">
                                <?php echo ($quiz['allow_file_submission'] ?? 0) ? '' : ''; ?>
                            </span>
                        </span>
                        <span class="toggle-text">
                            <?php echo ($quiz['allow_file_submission'] ?? 0) ? 
                                'File submissions enabled' : 
                                'File submissions disabled'; ?>
                        </span>
                    </label>
                </div>
            </div>
        </div>

<style>
/* Hide checkbox but keep it accessible */
.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Toggle switch styles */
.toggle-switch {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}

.slider {
    position: relative;
    width: 60px;
    height: 30px;
    background: #e0e0e0;
    transition: all 0.3s ease;
    border-radius: 34px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 2px;
    bottom: 2px;
    background: white;
    transition: all 0.3s ease;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

#fileSubmissionToggle:checked + .slider {
    background: #092635;
}

#fileSubmissionToggle:checked + .slider:before {
    transform: translateX(30px);
}

/* Status text inside toggle */
.toggle-status {
    position: absolute;
    color: white;
    font-weight: bold;
    font-size: 10px;
    line-height: 30px;
    width: 100%;
    text-align: center;
    user-select: none;
    
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}

.toggle-text {
    margin-left: 10px;
    font-weight: 500;
    color: #333;
}

/* Focus styles for accessibility */
#fileSubmissionToggle:focus + .slider {
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.3);
}
</style>

<script>
document.getElementById('fileSubmissionToggle').addEventListener('change', function() {
    const slider = this.nextElementSibling;
    const status = slider.querySelector('.toggle-status');
    const text = slider.nextElementSibling;
    
    if (this.checked) {
        status.textContent = '';
        slider.style.backgroundColor = '#092635';
        text.textContent = 'File submissions enabled';
    } else {
        status.textContent = '';
        slider.style.backgroundColor = '#e0e0e0';
        text.textContent = 'File submissions disabled';
    }
});
</script>

    <div class="quiz-restriction-setting">
    <fieldset>
        <legend class="descrip"><i class="fas fa-cog"></i> Quiz Availability Settings:</legend>
        
        <div class="quiz-restriction-setting-1">
            <div class="restriction">
                <div class="restriction-box d-flex">
                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               name="is_active" 
                               id="quiz-active" 
                               <?php echo $quiz['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label restriction-title" for="quiz-active">
                            Restriction: Automatically close quiz after due date/time
                        </label>
                    </div>
                </div>
            </div>

            <div class="note-setting">
                <p><em><strong>Note:</strong> When enabled, the quiz will automatically close and become unavailable once the end date/time is reached.</em></p>
            </div>
        </div>
    </fieldset>
</div>
                  
</div>

    <div class="tab-content" id="date-time-tab">
        <div class="date-info">
            <div class="date-left">
                <div class="d-grid date-information date">
                    <label class="start-date" for="start-date">Start Date:</label>
                    <input type="date" id="start-date" name="startDate" value="<?php echo htmlspecialchars($quiz['start_date']); ?>" required>
                </div>
                <div class="d-grid date-information time">
                    <label class="starttime" for="start-time">Start Time:</label>
                    <input type="time" id="start-time" name="startTime" value="<?php echo htmlspecialchars($quiz['start_time']); ?>" required>
                </div>
            </div>

            <div class="date-right">
                <div class="d-grid date-information date">
                    <label class="enddate" for="end-date">End Date:</label>
                    <input type="date" id="end-date" name="endDate" value="<?php echo htmlspecialchars($quiz['end_date']); ?>" required>
                </div>
                <div class="d-grid date-information time">
                    <label class="endtime" for="end-time">End Time:</label>
                    <input type="time" id="end-time" name="endTime" value="<?php echo htmlspecialchars($quiz['end_time']); ?>" required>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start-date');
    const startTime = document.getElementById('start-time');
    const endDate = document.getElementById('end-date');
    const endTime = document.getElementById('end-time');
    
    // Check all datetime inputs when they change
    [startDate, startTime, endDate, endTime].forEach(input => {
        input.addEventListener('change', validateDates);
    });
    
    function validateDates() {
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value + 'T' + (startTime.value || '00:00'));
            const end = new Date(endDate.value + 'T' + (endTime.value || '00:00'));
            
            if (end < start) {
                alert('Error: End date/time cannot be earlier than start date/time');
                // Optional: Reset to start date/time
                endDate.value = startDate.value;
                endTime.value = startTime.value;
            }
        }
    }
});
</script>

<style>
.quiz-form-tabs {
    margin-bottom: 20px;
}

.tab-buttons {
    display: flex;
    border-bottom: 1px solid #ddd;
    margin-bottom: 15px;
}

.tab-btn {
    padding: 10px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 600;
    color: #555;
  
    transition: all 0.3s;
    font-size:1.1em;
}

.tab-btn.active {
    color: #5c8374;
    border-bottom-color: #5c8374;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Keep your existing styles for the form elements */
.author-info, .d-grid, .date-info {
    /* Your existing styles */
}
.date-left, .date-right {
    display: inline-block;
    width: 48%;
    vertical-align: top;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and tabs
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show corresponding tab
            const tabId = this.getAttribute('data-tab') + '-tab';
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>


















<div class="quiz-info-essay">
    <div class="rubric-selection">
        <div class="d-flex align-items-center rubricsection">
            <img src="../img/list.gif" alt="No Essay Icon" class="gif-rubric">

            <p class="title">Rubric: </p>
            
            <?php 
// First, get the rubric ID and title for the entire quiz (assuming it's stored in $quizData)
$currentRubricId = $quizData['rubric_id'] ?? '';
$currentRubricTitle = 'No rubric selected';

if (!empty($currentRubricId)) {
    $stmt = $conn->prepare("SELECT title FROM subjects WHERE subject_id = :subject_id");
    $stmt->bindParam(':subject_id', $currentRubricId);
    $stmt->execute();
    $currentRubric = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($currentRubric) {
        $currentRubricTitle = $currentRubric['title'];
    }
}
?>

<!-- Display current rubric (ONCE, outside the questions loop) -->

            <?php if (!empty($questions)): ?>
                <?php 
                // Get the rubric ID from the first question (all questions share same rubric)
                $firstQuestion = reset($questions);
                $currentRubricId = $firstQuestion['rubric_id'] ?? '';
                ?>
                
                <!-- Rubric Selection Dropdown -->
                <div class="rubric-selection">
                    <select name="rubric_id" class="rubric-select" required>
                        <option value="">Select Rubric...</option>
                        <?php
                        // Fetch all available rubrics
                        $rubricQuery = "SELECT subject_id, title FROM subjects WHERE creation_id = :creation_id";
                        $stmt = $conn->prepare($rubricQuery);
                        $stmt->bindParam(':creation_id', $_SESSION['creation_id']);
                        $stmt->execute();
                        $rubrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($rubrics as $rubric) {
                            $selected = ($currentRubricId == $rubric['subject_id']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($rubric['subject_id']) . "' $selected>" . 
                                htmlspecialchars($rubric['title']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    
    </div>
</div>

               <div class="add-section">
                               

                           
                            </div>

                            </div>


                             <!-- RIGHT COLUMN - Questions Section -->
                        <div class="questions-column"> 
                        <div class="questions-column-inside">    
                            <div class="essay-container">
                                <?php foreach ($questions as $index => $question): ?>
                                <div class="typequestionall-essay" data-question-id="<?php echo $question['essay_id']; ?>">
                                    <div class="typequestionhere-essay">
                                        <div class="input-wrapper dropdownminute">
                                            <div class="input-box-essay">
                                            <span class="number"><i class="fas fa-question-circle"></i> Question <?php echo $index + 1; ?></span>
                                                <textarea class="question-essay" name="questions[]" required><?php echo htmlspecialchars($question['question']); ?></textarea>
                                                <input type="hidden" name="question_ids[]" value="<?php echo $question['essay_id']; ?>">
                                               
                                                <span class="delete-icon-essay"><i class="fas fa-trash"></i></span>
                                            </div>
                                        </div>
                                        <div class=" typequestionhere">
                                            <div class="input-wrapper dropdownminute" id="p-mx-min">
                                                <input type="number" min="1" class="points-essay" name="points[]" value="<?php echo htmlspecialchars($question['points_per_item']); ?>" required>
                                                <div class="span-label">Points</div>
                                            </div>
                                            <div class="input-wrapper dropdownminute">
                                                <input type="number" class="minimum-per" name="min_words[]" value="<?php echo htmlspecialchars($question['min_words']); ?>" required>
                                                <div class="span-label">Minimum Words</div>
                                            </div>
                                            <div class="input-wrapper dropdownminute">
                                                <input type="number" class="maximum-per" name="max_words[]" value="<?php echo htmlspecialchars($question['max_words']); ?>" required>
                                                <div class="span-label">Maximum Words</div>
                                            </div>
                                        </div>
                                        <div class="input-wrapper dropdownminute">
                                      
            <div class="input-box">
                <?php
                // Check if this question has answers
                if (!empty($question['answer'])) {
                    $answers = explode('|', $question['answer']);
                    
                    foreach ($answers as $answerIndex => $answer) {
                        echo '<div class="answer-container">';
                        echo '<textarea class="answer-of-essay" name="answers['.$question['essay_id'].'][]" placeholder="Enter your answer">';
                        echo htmlspecialchars(trim($answer));
                        echo '</textarea>';
                        echo '<span class="delete-answer" data-question-id="'.$question['essay_id'].'"><i class="fas fa-times"></i></span>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="answer-container">';
                    echo '<textarea class="answer-of-essay" name="answers['.$question['essay_id'].'][]" placeholder="Enter your answer"></textarea>';
                    echo '<span class="delete-answer" data-question-id="'.$question['essay_id'].'"><i class="fas fa-times"></i></span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>                                 <div class="buttonanswer">
                            <button type="button" class="add-answer-btn-essay"><i class="fas fa-plus"></i></button>
                        </div>
                                                <div class="orupload">
                                                <div class="divider">OR</div>

                                                <div class="form-group fileupload">
            <label class="colcontrol-label">
                <span id="attachment-note-<?php echo $index; ?>" name="file-essay">Attachment: You can upload one file here</span>
            </label>

            <!-- Show Existing File (If Available) -->
            <div class="existing-files">
                <?php 
                if (!empty($question['file_name']) && !empty($question['file_upload'])) {
                    $file_content = base64_encode($question['file_upload']);
                    
                    echo '<div class="current-file">';
                    echo '<p>Current File:</p>';
                    echo '<a href="#" class="download-file" data-content="'.htmlspecialchars($file_content).'" 
                        download="'.htmlspecialchars($question['file_name']).'">';
                    echo htmlspecialchars($question['file_name']);
                    echo '</a>';
                    echo '<span class="delete-file" data-question-id="'.$question['essay_id'].'">';
                    echo '<i class="fas fa-times"></i>';
                    echo '</span>';
                    echo '<input type="hidden" name="existing_file['.$question['essay_id'].']" value="1">';
                    echo '</div>';
                } else {
                    echo '<p>No file uploaded yet.</p>';
                    echo '<input type="hidden" name="existing_file['.$question['essay_id'].']" value="0">';
                }
                ?>
            </div>

    <div class="file-input-wrapper">
        <button id="upload-btn-<?php echo $index; ?>" type="button"><i class="fa-solid fa-upload" style="margin-right: 5px;"></i>Upload File</button>
        <input id="file-input-<?php echo $index; ?>" 
               name="file-essay-<?php echo $question['essay_id']; ?>" 
               type="file" 
               class="file" 
               style="display: none;"
               accept=".pdf,.doc,.docx">
    </div>
    <p id="file-list-<?php echo $index; ?>" class="file-info">
        <?php if (!empty($question['file_name'])): ?>
            <span class="file-status">Existing file will be replaced</span>
        <?php endif; ?>
        <span id="file-preview-<?php echo $index; ?>"></span>
    </p>
    
  
<script>
// File upload button click handler
document.getElementById('upload-btn-<?php echo $index; ?>').addEventListener('click', function() {
    document.getElementById('file-input-<?php echo $index; ?>').click();
});

// File input change handler (SINGLE FILE)
document.getElementById('file-input-<?php echo $index; ?>').addEventListener('change', function(e) {
    const filePreview = document.getElementById('file-preview-<?php echo $index; ?>');
    filePreview.innerHTML = "";
    
    if (this.files && this.files[0]) {
        const fileSize = (this.files[0].size / 1024).toFixed(2);
        filePreview.innerHTML = 'Selected: ' + this.files[0].name + ' (' + fileSize + ' KB)';
        
        // If there's a delete checkbox, uncheck it when new file is selected
        const deleteCheckbox = document.getElementById('delete-file-<?php echo $question['essay_id']; ?>');
        if (deleteCheckbox) {
            deleteCheckbox.checked = false;
        }
    }
});

// File download handler
document.querySelector('.download-file')?.addEventListener('click', function(e) {
    e.preventDefault();
    const content = this.getAttribute('data-content');
    const fileName = this.getAttribute('download');
    
    // Create download link
    const link = document.createElement('a');
    link.href = 'data:application/octet-stream;base64,' + content;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
</script>

                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            </div>
                            </div>
                            </div>
                            <div class="addquestion"><i class="fas fa-plus-circle"></i> Add Question</div>
                         
                           

                        </div>
                      
                       
                    </div>
                              
                  
                </div>

            </div>
           
        </div>

        <div class="button-container">
        <button type="button" class="back-button" onclick="window.location.href='AcademAI-Library-Upcoming-View-Card.php?quiz_id=<?php echo $_GET['quiz_id']; ?>'">
    <span class="material-icons">arrow_back</span> Back
</button>

<!-- Update Quiz Button -->
<button type="button" id="show-update-modal" class = "show-update-modal">
    <i class="fas fa-sync-alt"></i> Update Quiz
</button>









<script>
document.addEventListener('DOMContentLoaded', function () {
  if (localStorage.getItem('quizUpdated') === 'true') {
    document.getElementById('update-success-alert').style.display = 'block';
    localStorage.removeItem('quizUpdated');
  }
});
</script>



        </div>
   



    
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.essay-container');
    const addQuestionBtn = document.querySelector('.addquestion');
    let questionCount = <?php echo count($questions); ?>;

    // Add new question
    addQuestionBtn.addEventListener('click', function() {
        questionCount++;
        const newQuestion = createQuestionElement(questionCount);
        container.appendChild(newQuestion);
        updateTotalPoints();
        initializeFileUpload(questionCount);
    });

    // Delete question
    container.addEventListener('click', function(e) {
        if (e.target.closest('.delete-icon-essay')) {
            const questionDiv = e.target.closest('.typequestionall-essay');
            questionDiv.remove();
            questionCount--;
            updateQuestionNumbers();
            updateTotalPoints();
        }
    });

    // Add answer field
    container.addEventListener('click', function(e) {
        if (e.target.closest('.add-answer-btn-essay')) {
            const questionDiv = e.target.closest('.typequestionall-essay');
            const answersContainer = questionDiv.querySelector('.answers-container') || 
                                   createAnswersContainer(questionDiv);
            
            const newAnswer = document.createElement('div');
            newAnswer.className = 'input-wrapper dropdownminute';
            newAnswer.innerHTML = `
                <div class="input-box">
                    <textarea class="answer-essay" name="answers[${questionDiv.dataset.questionId || 'new'}][]" required></textarea>
                    <span class="delete-answer"><i class="fas fa-times"></i></span>
                </div>
            `;
            answersContainer.appendChild(newAnswer);
        }
    });

    // Delete answer field - Fixed version
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-answer')) {
            const deleteBtn = e.target.closest('.delete-answer');
            const answerContainer = deleteBtn.closest('.input-wrapper.dropdownminute');
            
            // Only remove if it's not the last answer in its container
            const parentContainer = answerContainer.parentElement;
            const allAnswers = parentContainer.querySelectorAll('.input-wrapper.dropdownminute');
            
            if (allAnswers.length > 1) {
                answerContainer.remove();
            } else {
                // If it's the last answer, just clear the content
                const textarea = answerContainer.querySelector('textarea');
                if (textarea) {
                    textarea.value = '';
                }
            }
        }
    });

    function createQuestionElement(number) {
        const div = document.createElement('div');
        div.className = 'typequestionall-essay';
        div.dataset.questionId = 'new-' + number;
        div.innerHTML = `
            <div class="typequestionhere-essay">
                <div class="input-wrapper dropdownminute">
                    <div class="input-box-essay">
                        <span class="number"><i class="fas fa-question-circle"></i>  Question ${number}</span>
                        <textarea class="question-essay" name="questions[]" required></textarea>
                        <input type="hidden" name="question_ids[]" value="new">
                        <span class="delete-icon-essay"><i class="fas fa-trash"></i></span>
                    </div>
                </div>
                <div class="typequestionhere">
                    <div class="input-wrapper dropdownminute" id="p-mx-min">
                        <input type="number" class="points-essay" name="points[]" required>
                        <div class="span-label">Points</div>
                    </div>
                    <div class="input-wrapper dropdownminute">
                        <input type="number" class="minimum-per" name="min_words[]" required>
                        <div class="span-label">Minimum Words</div>
                    </div>
                    <div class="input-wrapper dropdownminute">
                        <input type="number" class="maximum-per" name="max_words[]" required>
                        <div class="span-label">Maximum Words</div>
                    </div>
                </div>

               <div class="answers-container">
    <div class="input-wrapper dropdownminute">
        <div class="input-box">
            <div class="answer-container">
                <textarea class="answer-essay" name="answers_new[0][]" required></textarea>
                <span class="delete-answer"><i class="fas fa-times"></i></span>
            </div>
        </div>
    </div>
</div>
                <div class="buttonanswer">
                    <button type="button" class="add-answer-btn-essay"><i class="fas fa-plus"></i></button>
                </div>
                
                 <div class="divider">OR</div>
                
             <div class="form-group fileupload">
    <label class="colcontrol-label">  
        <span id="attachment-note-new-${number}" name="file-essay">Attachment: You can upload your file here</span>
    </label>
    <div class="file-input-wrapper">
        <button id="upload-btn-new-${number}" type="button"> 
            <i class="fa-solid fa-upload" style="margin-right: 5px;"></i>Upload File
        </button>
        <input id="file-input-new-${number}" 
               name="file-essay-new-${number}" 
               type="file" 
               class="file" 
               style="display: none;"
               accept=".pdf,.doc,.docx">
    </div>
    <p id="file-list-new-${number}" class="file-info"></p>
</div>
                   
            </div>
        `;
        return div;
    }

    function createAnswersContainer(questionDiv) {
        const container = document.createElement('div');
        container.className = 'answers-container';
        const typeQuestionHere = questionDiv.querySelector('.typequestionhere');
        typeQuestionHere.parentNode.insertBefore(container, typeQuestionHere.nextSibling);
        return container;
    }

    function updateQuestionNumbers() {
        const questions = container.querySelectorAll('.typequestionall-essay');
        questions.forEach((question, index) => {
            question.querySelector('.number').textContent = index + 1;
        });
    }

    function updateTotalPoints() {
        const pointsInputs = document.querySelectorAll('.points-essay');
        let total = 0;
        pointsInputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        document.getElementById('totalPoints').value = total;
    }

    function initializeFileUpload(index) {
    const uploadBtn = document.getElementById(`upload-btn-new-${index}`);
    const fileInput = document.getElementById(`file-input-new-${index}`);
    const fileList = document.getElementById(`file-list-new-${index}`);

    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileSize = (file.size / 1024).toFixed(2); // Size in KB
                fileList.innerHTML = `Selected: ${file.name} (${fileSize} KB)`;
            } else {
                fileList.innerHTML = '';
            }
        });
    }
}

    // Initialize file upload for existing questions
    document.querySelectorAll('.typequestionall-essay').forEach((question, index) => {
        const questionId = question.dataset.questionId || index + 1;
        initializeFileUpload(questionId);
    });

    // Add event listener for points changes
    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('points-essay')) {
            updateTotalPoints();
        }
    });
});
</script>




<!-- Modal -->
<div class="modal fade" id="leave--modal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
      <h5 class="modal-title">Confirm Update</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <p>Are you sure you want to update this quiz?</p>
      </div>
      
      <div class="modal-footer">
        <!-- Yes: triggers form submission -->
        <button type="submit" class="update-button" id="confirm-update-btn">Yes</button>

        <!-- Cancel -->
        <button type="button" class="btn nodelete" data-bs-dismiss="modal">Cancel</button>
      </div>

    </div>
  </div>
</div>


</form>























<style>
    

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
</style>

<style>
 /* Base Styles */
:root {
    --primary-dark: #092635;
    --secondary-dark: #1b4242;
    --accent-color: #5C8374;
    --light-color: #f8f9fa;
    --text-color: #333;
    --border-radius: 5px;
    --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --transition: all 0.3s ease;
}

body {
    font-family: 'Inter', sans-serif;
    line-height: 1.6;
    color: var(--text-color);
    margin: 0;
    padding: 0;
}

/* Main Form Layout */
.divide-content {
  
    margin: 0 auto;
    display: grid
;
    grid-template-columns: 1.2fr 1.5fr;
    gap:10px;
    justify-content: center;


}
  


/* Left Column - Settings */
.quiz-settings-column {
    display: flex;
    flex-direction: column;
}

/* Right Column - Questions */
@keyframes floatUpDown {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

.add-answer-btn-essay {
    margin-top: 10px;
    margin-bottom: 0px;
    height: 50px;
    width: 50px;
    border: none;
    color: #092635;
    background-color: white;
    border-radius: 50%;
    

    /* ✨ Lighter shadow */
    box-shadow: 0 4px 0 #eee, 0 8px 16px rgba(0, 0, 0, 0.1);

    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    transform-style: preserve-3d;

    /* ✨ Floating animation */
    animation: floatUpDown 1.8s ease-in-out infinite;
}

.questions-column {
    display: flex;
    flex-direction: column;
    display: flex
;
    flex-direction: column;
    max-height: 140vh;
  
    padding:1em;
    marGin-top:50PX;
  
    border-radius:5px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    justify-content: center;
   
}

.questions-column-inside {

    max-height: 120vh;
    overflow-y: auto;
   
   
}


/* Tab System */
.quiz-form-tabs {
    background: white;
    box-shadow: var(--box-shadow);
    overflow: hidden;
    padding-TOP: 2em;
    border:1px solid rgb(221, 221, 221);
}

.tab-buttons {
    display: flex;
    border-bottom: 3px solid #092635;
}

.tab-btn {
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: #092635;
    transition: var(--transition);
    position: relative;
    font-size:1em;
}

.tab-btn.active {
    color:#fff;
    background-color: var(--primary-dark);
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 2px;
    background: var(--primary-dark);
}

.tab-content {
    padding: 1.5rem;
    display: none;
}

.tab-content.active {
    display: block;
}

/* Quiz Details Section */
.author-info, .d-grid {
    margin-bottom: 1.5rem;
}

.title-quiz, .subject, .descrip {
    font-weight: 600;
    color: var(--primary-dark);
    margin-bottom: 0.5rem;
    display: block;
    font-size:1em;
}

textarea {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    font-family: inherit;
    font-size: 0.95rem;
    transition: var(--transition);
    resize: vertical;
    min-height: 50px;
}

textarea:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(92, 131, 116, 0.1);
}

/* Quiz Restrictions */
.quiz-restriction-setting-1 {
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    background: white;
}

.quiz-restriction-setting legend {
    font-weight: 600;
  
    color: var(--primary-dark);
}

.form-check {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.form-check-input {
    margin-right: 0.5rem;
}

.form-check-label {
    cursor: pointer;
}

.note-setting {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

/* Date & Time Section */
.date-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.date-information {
    margin-bottom: 1rem;
}

.date-information label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--primary-dark);
}

.date-information input[type="date"],
.date-information input[type="time"] {
    width: 100%;
    padding: 0.7rem;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    font-family: inherit;
}

/* Rubric Selection */
.rubric-selection {
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    background: white;
    border:1px solid rgb(221, 221, 221);
    width:100%;
}

.rubricsection {
    gap: 1rem;
    align-items: center;
}

.rubricsection .title {
    font-weight: 600;
    color: var(--primary-dark);
    margin: 0;
}

.rubric-select {
    flex: 1;
    padding: 0.7rem;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    font-family: inherit;
    width:100%;
}

.gif-rubric {
    width: 50px;
    height: 50px;
}

/* Questions Section */
.essay-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.typequestionall-essay {
    background: white;
    border-radius: var(--border-radius);

    padding: 1.5rem;
    position: relative;
    border-TOP: 2px dashed rgb(229, 229, 229);
    
}

.typequestionhere-essay {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}



.number {
    font-weight: bold;
    min-width: 30px;
    border-radius: 5px;
    color: #092635;
    padding: 1em;
    margin-bottom:10px;
}

.question-essay {
    flex: 1;
    min-height: 100px;
}

.delete-icon-essay {
    position: absolute;
    top: 50px;
    right: 10px;
    color: #dc3545;
    cursor: pointer;
  
    width: 24px;
    height: 24px;
    display: flex
;
    align-items: center;
    justify-content: center;
  
}

.delete-icon-essay:hover {
    transform: scale(1.1);
}

.typequestionhere {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.input-wrapper {
    position: relative;
}

.input-wrapper input[type="number"] {
    width: 100%;
    padding: 0.7rem;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    font-family: inherit;
}

.span-label {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 0.8rem;
}

/* Answers Section */
.answers-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.answer-container {
    position: relative;
}

.answer-of-essay {
    margin-top: 20px;
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    min-height: 150px;
    resize: vertical;

}

.delete-answer {
    position: absolute;
    top: 30px;
    right: 10px;
    color: #dc3545;
    cursor: pointer;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    
}

/* File Upload */
.fileupload {
    margin-top: 1.5rem;
    padding: 1.5rem;
    border-radius: var(--border-radius);

    background: white;
    display: flex
;
    flex-direction: column;
    align-items: center;
    border: 1px solid rgb(221, 221, 221);
}

.file-gif {
    width: 24px;
    height: 24px;
    vertical-align: middle;
    margin-right: 0.5rem;
}

.file-input-wrapper {
    margin-top: 1rem;
}

.file-input-wrapper button {
    background: #1B4242;
    color: white;
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.9rem;
}

.file-input-wrapper button:hover {
    background:  #5C8374;
}

.file-info {
    margin-top: 0.5rem;
    font-size: 0.85rem;
}

.file-status {
    color: var(--accent-color);
    font-weight: 500;
}

/* Add Question Button */
.add-section {
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
   
}
.addquestion {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ffff;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    padding: 0.8rem 1.2rem;
    border-radius: var(--border-radius);
    background: var(--primary-dark);
    box-shadow: var(--box-shadow);
    border: 1px solid rgb(221, 221, 221);
    justify-content: center;

    /* Sticky part */
    position: sticky;
    top: 20px; /* You can adjust this: 20px from top */
    z-index: 999; /* So it stays above other elements */
}

.addquestion:hover {
    color: #ffff;
    background:  #5C8374;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.15), 0 2px 4px -1px rgba(0, 0, 0, 0.1);
}

.addquestion i {
    font-size: 1.1rem;
}

.title-box {
    display: flex;
    align-items: center;
    background: white;
    border-bottom: 1px solid rgb(221, 221, 221);
    margin-bottom:10px;
    padding:0.5em;

  
 
}

.title-box .title {
    font-weight: 600;
    margin: 0;
    color: var(--primary-dark);
    font-size:1.2em;

}

.title-box input {
    font-weight: bold;
    text-align: center;
    border: none;
    background: transparent;
    width: 60px;
    color: var(--secondary-dark);
}

/* Button Container */
.button-container {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    grid-column: 1 / -1;
    padding:1em
}

.back-button, .show-update-modal {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--box-shadow);
}

.back-button {
    background: white;
    color: var(--primary-dark);
    border: 1px solid #e0e0e0;
}

.back-button:hover {
    background: #f8f9fa;
}

.show-update-modal {
    background: var(--primary-dark);
    color: white;
    border: none;
}

.show-update-modal:hover {
    background: var(--secondary-dark);
}

.toggle {
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    background: white;

}

.answer-essay  {
    margin-top: 20px;
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #e0e0e0;
    border-radius: var(--border-radius);
    min-height: 150px;
    resize: vertical;
}

.divider {
    display: flex
;
    align-items: center;
    margin: 1.5rem 0;
    color: var(--primary-dark);
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
.question-essay {
    margin-top:20px;
}
.note {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.5rem;
}
.quiz-restriction-setting {
    margin-top: 1rem;
}
.form-check-input:checked {
    background-color: #092635;
    border-color: #092635;
}
/* Responsive Adjustments */
@media (max-width: 1024px) {
    #quizForm {
        grid-template-columns: 1fr;
    }
    
    .date-info {
        grid-template-columns: 1fr;
    }
    
    .typequestionhere {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    #quizForm {
        padding: 1rem;
    }
    
    .button-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .back-button, .show-update-modal {
        width: 100%;
        justify-content: center;
    }
}

/* Animation for better UX */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.typequestionall-essay {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>


<style>
   .update-button,
    .nodelete {
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

.nodelete {
    background-color: #1b4242;
}

.update-button:hover,
.nodelete:hover {
    background-color: #5C8374;
    color: white;
    text-decoration: none;
}

</style>




<script>
document.addEventListener("DOMContentLoaded", function () {
    const updateButton = document.querySelector('.show-update-modal');
    const quizTitle = document.getElementById('quizTitle');
    const quizSubject = document.getElementById('quizSubject');
    const startDate = document.getElementById('start-date');
    const startTime = document.getElementById('start-time');
    const endDate = document.getElementById('end-date');
    const endTime = document.getElementById('end-time');
    const rubricSelect = document.querySelector('.rubric-select');

    updateButton.addEventListener('click', function (e) {
        e.preventDefault(); // Always prevent first to control flow
        let errorMessages = [];

        // Check main quiz fields
        if (!quizTitle.value.trim()) {
            errorMessages.push("Quiz title is required");
        }
        if (!quizSubject.value.trim()) {
            errorMessages.push("Quiz subject is required");
        }
        if (!startDate.value) {
            errorMessages.push("Start date is required");
        }
        if (!startTime.value) {
            errorMessages.push("Start time is required");
        }
        if (!endDate.value) {
            errorMessages.push("End date is required");
        }
        if (!endTime.value) {
            errorMessages.push("End time is required");
        }
        if (!rubricSelect.value) {
            errorMessages.push("Please select a rubric");
        }

        // Check each question fields
        const allQuestions = document.querySelectorAll('.typequestionall-essay');
        allQuestions.forEach(function (questionDiv, index) {
            const questionNumber = index + 1;
            const questionTextarea = questionDiv.querySelector('.question-essay');
            const pointsInput = questionDiv.querySelector('.points-essay');
            const minWordsInput = questionDiv.querySelector('.minimum-per');
            const maxWordsInput = questionDiv.querySelector('.maximum-per');

            if (!questionTextarea || questionTextarea.value.trim() === '') {
                errorMessages.push(`Question ${questionNumber}: Question text is required`);
            }
            if (!pointsInput || pointsInput.value.trim() === '') {
                errorMessages.push(`Question ${questionNumber}: Points are required`);
            }
            if (!minWordsInput || minWordsInput.value.trim() === '') {
                errorMessages.push(`Question ${questionNumber}: Minimum words are required`);
            }
            if (!maxWordsInput || maxWordsInput.value.trim() === '') {
                errorMessages.push(`Question ${questionNumber}: Maximum words are required`);
            }
        });

        // Check date/time validity
        if (startDate.value && endDate.value && startTime.value && endTime.value) {
            const startDateTime = new Date(`${startDate.value}T${startTime.value}`);
            const endDateTime = new Date(`${endDate.value}T${endTime.value}`);
            if (endDateTime <= startDateTime) {
                errorMessages.push("End date/time must be after start date/time");
            }
        }

        if (errorMessages.length > 0) {
            // If any errors, show alert
            alert("Please fix the following errors:\n\n" + errorMessages.join("\n"));
        } else {
            // If no errors, show modal
            const leaveModal = new bootstrap.Modal(document.getElementById('leave--modal'));
            leaveModal.show();
        }
    });
});
</script>








<script>
document.addEventListener('DOMContentLoaded', function() {
    // Real-time validation for number-only and positive numbers
    document.querySelectorAll('.points-essay, .minimum-per, .maximum-per')
      .forEach(input => {
        input.addEventListener('input', function() {
          validateInput(this);
        });
      });

    // When Update Quiz button is clicked
    document.getElementById('show-update-modal').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Only if all inputs are valid do we show the modal
        if (validateAllInputs()) {
            const leaveModal = new bootstrap.Modal(
              document.getElementById('leave--modal')
            );
            leaveModal.show();
        }
    });

    // Real-time input validation (only number and positive checking)
    function validateInput(input) {
        let value = input.value.trim();

        // Remove any non-digit characters
        value = value.replace(/[^0-9]/g, '');
        input.value = value;

        if (value === '') {
            return false;
        }

        if (parseInt(value) <= 0 || isNaN(value)) {
            input.value = '';
            alert(
              `${input.nextElementSibling ? input.nextElementSibling.textContent : 'Value'} must be a positive number`
            );
            return false;
        }

        return true;
    }

    // Final full validation on button click
    function validateAllInputs() {
        let isValid = true;
        
        document.querySelectorAll('.typequestionhere').forEach(group => {
            const pointsInput = group.querySelector('.points-essay');
            const minInput    = group.querySelector('.minimum-per');
            const maxInput    = group.querySelector('.maximum-per');

            // Validate points
            if (!pointsInput.value.trim() || parseInt(pointsInput.value) <= 0) {
                alert('Points must be a positive number');
                pointsInput.focus();
                isValid = false;
                return false;
            }

            // Validate minimum words
            if (!minInput.value.trim() || parseInt(minInput.value) <= 0) {
                alert('Minimum words must be a positive number');
                minInput.focus();
                isValid = false;
                return false;
            }

            // Validate maximum words
            if (!maxInput.value.trim() || parseInt(maxInput.value) <= 0) {
                alert('Maximum words must be a positive number');
                maxInput.focus();
                isValid = false;
                return false;
            }

            // Check: min must be less than max
            if (parseInt(minInput.value) >= parseInt(maxInput.value)) {
                alert('Minimum words must be less than maximum words');
                minInput.focus();
                isValid = false;
                return false;
            }
        });

        return isValid;
    }
});
</script>






\
</body>
</html>
