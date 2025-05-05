<?php
// Start session
session_start();

// Include the database connection file
require_once('../include/extension_links.php');
include ("../classes/connection.php"); // Include your database connection here

// Instantiate the Database class and get the connection
$db = new Database();
$conn = $db->connect();

// Check if the creation_id is set in the session
if (!isset($_SESSION['creation_id'])) {
    header('Location: login.php'); // Redirect if not logged in
    exit();
}

$creation_id = $_SESSION['creation_id'];

// Prepare SQL query to fetch user information including photo_path
$query = "SELECT CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name, email, photo_path FROM academai WHERE creation_id = :creation_id";

try {
    // Prepare the query
    $stmt = $conn->prepare($query);
    // Bind the creation_id parameter
    $stmt->bindParam(':creation_id', $creation_id, PDO::PARAM_INT);
    // Execute the query
    $stmt->execute();

    // Fetch user data
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $user['full_name'];
        $userEmail = $user['email'];
        
        // Handle profile picture path
        $default_avatar = '../img/default-avatar.jpg';
        $profile_pic = $default_avatar; // Default fallback
        
        if (!empty($user['photo_path'])) {
            // Try different possible paths
            $possiblePaths = [
                '../uploads/profile/' . basename($user['photo_path']),
                '../' . $user['photo_path'],
                'uploads/profile/' . basename($user['photo_path']),
                $user['photo_path']
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $profile_pic = $path;
                    break;
                }
            }
        }
    } else {
        $userName = "Guest";
        $userEmail = "N/A";
        $profile_pic = '../img/default-avatar.jpg';
    }
} catch (PDOException $e) {
    // Log the error and show a generic message
    error_log("Query failed: " . $e->getMessage(), 3, 'errors.log');
    die("A query error occurred. Please try again later.");
}
?>

<?php
// Check if quiz_code is passed in the URL
if (isset($_GET['quiz_code'])) {
    $quizCode = htmlspecialchars($_GET['quiz_code']);
} else {
    $quizCode = '';
}
?>






<!DOCTYPE html>
<html lang="en"> <!-- Use the appropriate language code -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/academAI-view- creation-quiz.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"> 
   
    <title>Document</title>
</head>
<body>


    <form id="quizForm" action="../tools/create-quiz.php"  method="POST" enctype="multipart/form-data">
    
    <div class="mcq-container">
        <div class=" useranswer">

    
     <!-- Header with Back Button and User Profile -->
     <div class="header">
            <a href="AcademAI-Quiz-Room.php" class="back-btn">
            <i class="fa-solid fa-chevron-left"></i>
            </a>   
            <div class="header-right">  
                <div class="user-profile">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" class="profile-pic" onerror="this.onerror=null; this.src='../img/default-avatar.jpg'">    
                    <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($userEmail); ?></span>
                      
                    </div>
                </div>
            </div>
        </div>
        <!-- Header with Back Button and User Profile -->
  


<div class="divide-content"> 
<div class="author-descript col-6">
        <div class="author-info ">  
            <div class="d-grid">
                <p class="title-quiz">Quiz Title:</p>
                <textarea id="quizTitle" name="title"></textarea>
            </div>
        </div>
        <div class="d-grid">
            <p class="subject">Subject:</p>
            <textarea id="quizSubject" name="subject"></textarea>
        </div>  
        <div class="d-grid desc">
            <p class="descrip">Description:</p>
            <textarea id="quizDescription" name="quizDescription"></textarea>
        </div> 

        <div class="setting-container">
            <p class="descrip">Quiz Submission Settings</p>

            <div class = "toggle">
                    <p class="note">
        <em><strong>Note:</strong> If this option remains disabled, participants will not be permitted to upload or submit files as part of their quiz responses.</em>
        </p>

            <div class="setting-item">
                <label class="toggle-switch">
                    <input type="checkbox" id="fileSubmissionToggle" name="allow_file_submission">
                    <span class="slider"></span>
                </label>
                <span id="toggleText" style="margin-left: 10px;">Do not allow participants to submit files in the quiz</span>
            </div>
            <div id="statusMessage" class="status-message"></div>
        </div>
        </div>
          
        
        
     </div>


                   
   
        <div class= "rigihtiside col-6">  
        
      
        <div class = "date-section">
   
            <div class="date-descript"></div>
            <div class="date-descript2"></div>
        </div>
      

        <script>
            document.getElementById('fileSubmissionToggle').addEventListener('change', function() {
                const toggleText = document.getElementById('toggleText');
                if (this.checked) {
                    toggleText.textContent = "Allow participants to submit files in the quiz";
                } else {
                    toggleText.textContent = "Do not allow participants to submit files in the quiz";
                }
            });
        </script>

     
        
                         <div class="quiz-restriction-setting">
                        <fieldset>
                            <legend class="descrip">Quiz Availability Settings:</legend>
                            
                            <div class="quiz-restriction-setting-1">
                                <div class="restriction">
                                    <div class="restriction-box d-flex">
                                       <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="restriction" id="randomized-checkbox-restriction">
                                        <label class="form-check-label restriction-title" for="randomized-checkbox-restriction">
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
   

        </div>

        <script>
    // Save quiz data to localStorage whenever the user types in the fields
    document.getElementById('quizTitle').addEventListener('input', function() {
        localStorage.setItem('quizTitle', this.value);
    });
    document.getElementById('quizSubject').addEventListener('input', function() {
        localStorage.setItem('quizSubject', this.value);
    });
    document.getElementById('quizDescription').addEventListener('input', function() {
        localStorage.setItem('quizDescription', this.value);
    });

    // On page load, retrieve the saved data from localStorage and populate the fields
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('quizTitle')) {
            document.getElementById('quizTitle').value = localStorage.getItem('quizTitle');
        }
        if (localStorage.getItem('quizSubject')) {
            document.getElementById('quizSubject').value = localStorage.getItem('quizSubject');
        }
        if (localStorage.getItem('quizDescription')) {
            document.getElementById('quizDescription').value = localStorage.getItem('quizDescription');
        }
    });
</script>


<script>
   document.addEventListener('DOMContentLoaded', function () {
    // Function to load date and time data from localStorage and display it
    function loadDateTime() {
        // Directly get values from localStorage
        const startDate = localStorage.getItem('startDate') || '';
        const startTime = localStorage.getItem('startTime') || '';
        const endDate = localStorage.getItem('endDate') || '';
        const endTime = localStorage.getItem('endTime') || '';

        // Create HTML structure for start and end dates and times (except end time)
        const htmlContent1 = `
            <div class="date-info">
                <div class="d-grid date-information date">
                    <label class="startdate" for="start-date">Start Date:</label>
                    <input type="date" id="start-date" name="startDate" class="the-startdate" value="${startDate}" >
                </div>
                <div class="d-grid date-information time">
                    <label class="starttime" for="start-time">Start Time:</label>
                    <input type="time" id="start-time" name="startTime" class="the-starttime" value="${startTime}" >
                </div>
            </div>
        `;

        // Create HTML structure for the end time separately
        const htmlContent2 = `
            <div class="d-grid date-information date">
                <label class="enddate" for="end-date">End Date:</label>
                <input type="date" id="end-date" name="endDate" class="the-enddate" value="${endDate}" >
            </div>
            <div class="d-grid date-information time">
                <label class="endtime" for="end-time">End Time:</label>
                <input type="time" id="end-time" name="endTime" class="the-endtime" value="${endTime}" >
            </div>
        `;

        // Insert the first part of the HTML content into the first container
        const displayContainer1 = document.querySelector('.date-descript'); // Ensure this element exists
        if (displayContainer1) {
            displayContainer1.innerHTML = htmlContent1;
        }

        // Insert the second part of the HTML content into the second container
        const displayContainer2 = document.querySelector('.date-descript2'); // Ensure this element exists
        if (displayContainer2) {
            displayContainer2.innerHTML = htmlContent2;
        }
    }

    // Function to save the date and time to localStorage whenever the user changes them
    function saveDateTime() {
        const startDate = document.getElementById('start-date').value;
        const startTime = document.getElementById('start-time').value;
        const endDate = document.getElementById('end-date').value;
        const endTime = document.getElementById('end-time').value;

        // Save the values to localStorage
        localStorage.setItem('startDate', startDate);
        localStorage.setItem('startTime', startTime);
        localStorage.setItem('endDate', endDate);
        localStorage.setItem('endTime', endTime);
    }

    // Function to validate if the end date and time are later than the start date and time
    function showModal(modalId, message) {
        const modal = document.getElementById(modalId);
        const modalMessage = modal.querySelector('.modal-message'); // Ensure modal has an element to display the message

        if (modal) {
            if (modalMessage) {
                modalMessage.textContent = message;
            }
            modal.style.display = 'block'; // Ensure modal is visible
        } else {
            console.error(`Modal with ID '${modalId}' not found.`);
        }
    }

    // Function to validate if the end date and time are later than the start date and time
    function validateEndDateTime() {
        const startDateInput = document.getElementById('start-date');
        const startTimeInput = document.getElementById('start-time');
        const endDateInput = document.getElementById('end-date');
        const endTimeInput = document.getElementById('end-time');

        const startDate = new Date(`${startDateInput.value}T${startTimeInput.value}`);
        const endDate = new Date(`${endDateInput.value}T${endTimeInput.value}`);
        const currentDate = new Date(); // Get current date and time

        // Prevent the user from setting a start date and time before the current date and time
            if (startDateInput.value && startTimeInput.value) {
                if (startDate < currentDate) {
                    alert("Start date and time cannot be before the current time.");
                    startDateInput.value = '';
                    startTimeInput.value = '';
                    return;
                }
            }

            // Only check if both dates and times are selected
            if (startDateInput.value && startTimeInput.value && endDateInput.value && endTimeInput.value) {
                if (endDate <= startDate) {
                    alert("End date and time must be later than the start date and time.");
                    endDateInput.value = '';
                    endTimeInput.value = '';
                }
            }

    }

    // Load date and time data when the page loads
    loadDateTime();

    // Add event listeners for validation
    const endDateInput = document.getElementById('end-date');
    const endTimeInput = document.getElementById('end-time');
    const startDateInput = document.getElementById('start-date'); // Added event listener for start date
    const startTimeInput = document.getElementById('start-time'); // Added event listener for start time

    if (endDateInput && endTimeInput) {
        endDateInput.addEventListener('change', function () {
            validateEndDateTime();
            saveDateTime(); // Save values to localStorage whenever changed
        });
        endTimeInput.addEventListener('change', function () {
            validateEndDateTime();
            saveDateTime(); // Save values to localStorage whenever changed
        });
    }

    if (startDateInput && startTimeInput) {
        startDateInput.addEventListener('change', function () {
            validateEndDateTime();
            saveDateTime(); // Save values to localStorage whenever changed
        });
        startTimeInput.addEventListener('change', function () {
            validateEndDateTime();
            saveDateTime(); // Save values to localStorage whenever changed
        });
    }

});


</script>







    
       
        
         
        </div>
      
       
        </div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
    // Check if there's a saved state for the checkbox in localStorage
    const restrictionCheckbox = document.getElementById('randomized-checkbox-restriction');
    const savedState = localStorage.getItem('quizRestrictionState');
    
    if (savedState === 'true') {
        restrictionCheckbox.checked = true;
    } else if (savedState === 'false') {
        restrictionCheckbox.checked = false;
    }

    // Event listener to save the checkbox state in localStorage
    restrictionCheckbox.addEventListener('change', function() {
        localStorage.setItem('quizRestrictionState', restrictionCheckbox.checked);
    });
});

</script>
<!-- Submit Quiz Modal -->

<div class="modal fade"  id="create-card-modal"  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
      <h5 class="modal-title">Confirm Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <p>Are you sure you want to create this quiz?</p>
      </div>
      
      <div class="modal-footer">
        <!-- Yes: triggers form submission -->
        <button type="submit" name="submit-quiz-creation" value="Create Quiz" id = "update-button"class="yes-btn">Yes</button>

        <!-- Cancel -->
        <button type="button" class="btn nodelete" data-bs-dismiss="modal">Cancel</button>
      </div>

    </div>
  </div>
</div>





<div class = " quiz-form-section d-flex">
<div class="displayanswer-essay col-6">
<div class="quiz-info-essay">
                <div class="title-box">
                    <p class="title">Total Points: </p>
                    <label for="totalPoints-essay"></label>
                    <input type="text" id="totalPoints" name="quiz_total_points_essay" class="points-multiple" readonly>
                </div>
            </div>
<div class="displayanswer-essay-inside">
           
            <div class="separate">
    
        
             
                <div class=" numberitem">
                    <div class="input-group number-of-quiz">
                        <label for="num-quiz" >Number of Item/s:</label>
                        <input type="number" id="num-quiz" name="num-quiz" min="1" required>
                    </div>
                    <div class="input-group num-per-point">
                    <div class = "d-grid" style = "margin-bottom:0;">
                        <label for="num-points">Point/s per Item:</label>
                        <p><em><strong>Note:</strong>This will apply to all the items</em></p>
                        </div>
                        <input type="number" id="num-points" name="num-points" >
                       
                  </div>
                    <div class="input-group word-limit-count">
                    <div class = "d-grid" style = "margin-bottom:0;">
                    <label for="limit-word" >Word Limit:</label>
                    <p><em><strong>Note:</strong>This will apply to all the items</em></p>
                    </div>
                    <div class="d-flex align-items-center" style = "gap:5px;">
                        <label for="minimum" >Minimum:</label>
                        <input type="number" id="minimum" name="limit-word" >
                        <label for="maximum" style="color:#092635;">Maximum:</label>
                        <input type="number" id="maximum" name="limit-word" >
                    </div>
                    </div>
                </div>
                </div>
          
            <div class="rubric-label-main">
                        <div class="row row-label-rubric">
                            <div class="col please-choose">
                           
                                <p class="rubric-label">Select a rubric type for the essays:</p>
                                <a href="AcademAI-Set-Essay-Rubric.php">
                                <button type="button" class="rubric-link"> <img src="../img/list.gif" alt="No Essay Icon" class="gif-rubric">Set Rubrics</button>
                                </a>
                            </div>
                            <div class="row chosen-rubric-row">
                            <div class="col rubric-btn">
                            <!-- Title of the selected rubric will be displayed here -->
                            <p id="rubric-title-display">Chosen Rubric: </p>
                            
                           
                        </div>
                        </div>
    




 <script>
document.addEventListener("DOMContentLoaded", function () {
    const rubricTitleDisplay = document.getElementById('rubric-title-display');
    const savedSubjectTitle = localStorage.getItem('selectedSubjectTitle');
    const savedSubjectId = localStorage.getItem('selectedSubjectId');
    const form = document.querySelector("#quizForm"); // Ensure your form has this ID
    const modal = document.getElementById("invalid-select-rubric-modal"); // Get the modal
    const closeModal = document.getElementById("alert-close"); // Close button

    if (savedSubjectTitle && savedSubjectId) {
        rubricTitleDisplay.textContent = `Chosen Rubric: ${savedSubjectTitle}`;

        // Add the rubric_id as a hidden input field in the form
        const rubricIdInput = document.createElement("input");
        rubricIdInput.type = "hidden";
        rubricIdInput.name = "rubric_id";
        rubricIdInput.value = savedSubjectId;
        form.appendChild(rubricIdInput);
    }

    // Prevent form submission if no rubric is selected
    form.addEventListener("submit", function (event) {
        if (!savedSubjectTitle || !savedSubjectId) {
            event.preventDefault(); // Stops form submission
            modal.style.display = "block"; // Show the modal
        }
    });

    // Close modal when clicking the close button
    closeModal.addEventListener("click", function () {
        modal.style.display = "none";
    });

    // Close modal when clicking outside the modal content
    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
});
</script>












                     </div>
                    </div>
                </div>
            </div>


     


<!-- Container for dynamically generated quiz forms -->
 <div class="col-6 essay-container">
<div id="quiz-form-container"> <div id="no-essay-message" >
    <img src="../img/online-lesson.gif" alt="No Essay Icon" class="gif-no-quiz">
    <p  class="no-essay-txt">Please enter the number of essay questions.</p>
</div>
</div>


<div class="add-section">
        <div class="addquestion">Add Essay Form</div>
</div>
</div>






<script>
document.addEventListener('DOMContentLoaded', function () {
    const addQuestionButton = document.querySelector('.addquestion');
    const container = document.getElementById('quiz-form-container');
    const numQuizInput = document.getElementById('num-quiz');
    let questionIndex = 0;

    const numPointsInput = document.getElementById('num-points');
    const minWordLimitInput = document.getElementById('minimum');
    const maxWordLimitInput = document.getElementById('maximum');

    // Load quizzes from localStorage
    function loadQuizzes() {
        const savedQuizzes = localStorage.getItem("quizzes");
        if (savedQuizzes) {
            container.innerHTML = savedQuizzes; // Restore the quiz items
        }
        updateQuizCount();
    }

    // Update and store quiz count
    function updateQuizCount() {
        const quizCount = container.querySelectorAll(".typequestionall-essay").length;
        numQuizInput.value = quizCount;
        localStorage.setItem("quizCount", quizCount); // Store count
        localStorage.setItem("quizzes", container.innerHTML); // Store quiz elements
    }

    // Observe changes in the container to keep count updated
    const observer = new MutationObserver(updateQuizCount);
    observer.observe(container, { childList: true, subtree: true });

    // Load quizzes when the page loads
    loadQuizzes();

    // Sync the input field with the number of quiz forms
    function syncQuizCount() {
        numQuizInput.value = container.childElementCount;
    }

    numQuizInput.addEventListener('input', function () {
        let numQuizzes = parseInt(this.value);

        // Ensure the input value is valid
        if (isNaN(numQuizzes) || numQuizzes < 0) return;

        // Clear existing quiz forms
        container.innerHTML = '';

        // Generate quiz forms based on the input number
        for (let i = 0; i < numQuizzes; i++) {
            createQuizForm(i);
        }

        questionIndex = numQuizzes;
        updateQuestionNumbers();
        updateTotalPoints(); // Update total points after generating new forms
        updateQuizCount(); // Update quiz count in localStorage
    });

    addQuestionButton.addEventListener('click', function () {
    createQuizForm(questionIndex);
    questionIndex++;
    updateQuestionNumbers();
    syncQuizCount(); // Update total number of quizzes in the input field
    updateTotalPoints(); // Update total points after adding a new form
    updateQuizCount(); // Update quiz count in localStorage
});

    // Function to calculate and update the total points
    function updateTotalPoints() {
        const pointsInputs = document.querySelectorAll('.points-essay');
        let totalPoints = 0;
        
        // Loop through all .points-essay inputs and sum their values
        pointsInputs.forEach(input => {
            totalPoints += parseInt(input.value) || 0; // Use 0 as default if input is empty or invalid
        });

        // Display the total points in the input with id 'totalPoints'
        document.getElementById('totalPoints').value = totalPoints;
    }

    // Add event listeners to all .points-essay inputs
    document.querySelectorAll('.points-essay').forEach(input => {
        input.addEventListener('input', updateTotalPoints);
    });

    // Call the function to initialize total points when the page loads
    window.addEventListener('load', updateTotalPoints);

    function createQuizForm(index) {
        const quizForm = document.createElement('div');
        quizForm.classList.add('container-fluid', 'typequestionall-essay');


        

        quizForm.innerHTML = `
            <div class="typequestionhere-essay">
                <div class="input-wrapper dropdownminute">
                    <div class="input-box-essay">
                         <span class="number"></span>
                        <textarea class="question-essay" id="question-${index}" placeholder="Please type your question here" name="question-essay[]" rows="3" ></textarea>
                        <span class="delete-icon-essay"><i class="fas fa-trash"></i></span>
                    </div>
                </div>
                <div class=" typequestionhere">
                    <div class="input-wrapper dropdownminute">
                        <input type="number" class="points-essay" id="points-${index}" placeholder="Points" name="points-essay[]" min="1" >
                           
                        </div>
                 <div class="input-wrapper dropdownminute" id ="word-limit">
                        <input type="number" class="minimum-per" id="minimum-per-${index}" placeholder="Minimum word limit" name="min-essay-min[]" min="1" >
                       
                    </div>
                          <div class="input-wrapper dropdownminute">
                        <input type="number" class="maximum-per" id="maximum-per-${index}" placeholder="Maximum word limit" name="max-essay-max[]" min="1">
                          
                        </div>
                    </div>     
                

                <div class="input-wrapper dropdownminute">
                    <div class="input-box">
                        <textarea class="answer-essay" id="answer-${index}" placeholder="Type your answer here" name="answer-essay[${index}][]" ></textarea>
                        <span class="delete-icon-essay-answer"><i class="fas fa-trash"></i></span>
                    </div>
                </div>
                <div class="buttonanswer">
                    <button type="button" class="add-answer-btn-essay"><i class="fas fa-plus"></i></button>
                </div>
                   <div class="divider">OR</div>
                <div class="form-group fileupload">
                    <div class="file-input-wrapper">
                        <button id="upload-btn-${index}" type="button">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload File
                            </button>
                        <input id="file-input-${index}" name="file-essay-${index}[]" type="file" class="file" multiple style="display: none;">
                    </div>
                    <p id="file-list-${index}"></p>
                </div>
            </div>
        `;

        container.appendChild(quizForm);

         // Update the fields in the new quiz form
        updateQuizFormFields(index);

        bindFileUploadEvents(index);
        bindDeleteQuizEvent(quizForm);
     

        const addAnswerButton = quizForm.querySelector('.add-answer-btn-essay');
        addAnswerButton.addEventListener('click', function () {
            const newAnswerWrapper = document.createElement('div');
            newAnswerWrapper.classList.add('input-wrapper', 'dropdownminute');

            newAnswerWrapper.innerHTML = `
                <div class="input-box">
                    <textarea class="answer-essay" placeholder="Type your answer here" name="answer-essay[${index}][]" ></textarea>
                    <span class="delete-icon-essay-answer"><i class="fas fa-trash"></i></span>
                </div>
            `;

            addAnswerButton.closest('.typequestionhere-essay').insertBefore(newAnswerWrapper, addAnswerButton.closest('.buttonanswer'));

            const deleteIcon = newAnswerWrapper.querySelector('.delete-icon-essay-answer');
            deleteIcon.addEventListener('click', function () {
                newAnswerWrapper.remove();
            });
            
        });

        // Add event listener to the points input field to update total points
        const pointsInput = quizForm.querySelector('.points-essay');
        pointsInput.addEventListener('input', updateTotalPoints);
    }

    // Function to bind file upload events
    // Function to bind file upload events with file type validation
function bindFileUploadEvents(index) {
    const uploadButton = document.getElementById(`upload-btn-${index}`);
    const fileInput = document.getElementById(`file-input-${index}`);
    const fileListDisplay = document.getElementById(`file-list-${index}`);

    // Valid file extensions
    const validExtensions = ['.doc', '.docx', '.pdf'];

    if (uploadButton && fileInput && fileListDisplay) {
        // Open file input dialog when button is clicked
        uploadButton.addEventListener('click', function () {
            fileInput.click();
        });

        // Validate file types when files are selected
        fileInput.addEventListener('change', function () {
            const fileList = Array.from(fileInput.files);
            const invalidFiles = [];

            // Check each selected file for valid extension
            fileList.forEach(file => {
                const fileName = file.name.toLowerCase();
                const fileExtension = fileName.substring(fileName.lastIndexOf('.'));

                if (!validExtensions.includes(fileExtension)) {
                    invalidFiles.push(file.name);
                }
            });

            // If invalid files are detected
            if (invalidFiles.length > 0) {
                alert('Invalid file types: ' + invalidFiles.join(', ') + '. Only .doc, .docx, and .pdf files are allowed.');
                fileInput.value = '';  // Clear the input field
                fileListDisplay.innerText = 'No files selected';  // Reset file list display
            } else {
                // Show the selected file names
                const fileNames = fileList.map(file => file.name).join(', ');
                fileListDisplay.innerText = fileNames || 'No files selected';
            }
        });
    }
}


    function bindDeleteQuizEvent(quizForm) {
        const deleteIcon = quizForm.querySelector('.delete-icon-essay');
        deleteIcon.addEventListener('click', function () {
            quizForm.remove();
            updateQuestionNumbers();
            syncQuizCount(); // Sync the count after removal
            updateTotalPoints(); // Update total points after removing a form
            updateQuizCount(); // Update quiz count in localStorage
        });
    }

        function updateQuestionNumbers() {
        const questionSpans = container.querySelectorAll('.number');
        questionSpans.forEach((span, index) => {
            span.innerHTML = `<i class="fas fa-question-circle"></i> Question ${index + 1}`;
        });
    }

    // Function to update the fields in each generated quiz form
    function updateQuizFormFields(index) {
        const pointsInput = document.getElementById(`points-${index}`);
        const minimumInput = document.getElementById(`minimum-per-${index}`);
        const maximumInput = document.getElementById(`maximum-per-${index}`);

     
        // Update the points field
        if (numPointsInput) {
            pointsInput.value = numPointsInput.value;
        }

        // Update the word limit fields
        if (minWordLimitInput && maxWordLimitInput) {
            minimumInput.value = minWordLimitInput.value;
            maximumInput.value = maxWordLimitInput.value;
        }
    }

    numPointsInput.addEventListener('input', function () {
        updateAllQuizForms();
        updateTotalPoints(); // Update total points

    });

    minWordLimitInput.addEventListener('input', function () {
        updateAllQuizForms();
    });

    maxWordLimitInput.addEventListener('input', function () {
        updateAllQuizForms();
    });

    // Function to update all quiz forms
    function updateAllQuizForms() {
        const quizForms = container.querySelectorAll('.typequestionall-essay');
        quizForms.forEach((quizForm, index) => {
            updateQuizFormFields(index);
        });
    }
});
</script>










<script>
    function saveQuizData() {
        const quizForms = document.querySelectorAll('.typequestionall-essay');
        const quizData = {
            numQuizzes: quizForms.length,
            quizForms: [],
            globalSettings: {
                points: document.getElementById('num-points').value || , // Default to 1 if empty
                minWords: document.getElementById('minimum').value || , // Default to 1 if empty
                maxWords: document.getElementById('maximum').value ||  // Default to 1 if empty
            }
        };

        quizForms.forEach((form, index) => {
            const question = form.querySelector('.question-essay').value;
            const points = form.querySelector('.points-essay').value;
            const minWordLimit = form.querySelector('.minimum-per').value;
            const maxWordLimit = form.querySelector('.maximum-per').value;

            // Get all answers related to this question
            const answers = Array.from(form.querySelectorAll('.answer-essay')).map(textarea => textarea.value);

            quizData.quizForms.push({
                question,
                points,
                minWordLimit,
                maxWordLimit,
                answers // Store multiple answers as an array
            });
        });

        localStorage.setItem('quizData', JSON.stringify(quizData));
    }

    function loadQuizData() {
        const quizData = JSON.parse(localStorage.getItem('quizData'));

        if (quizData) {
            // Restore global input values
            if (quizData.globalSettings) {
                document.getElementById('num-points').value = quizData.globalSettings.points;
                document.getElementById('minimum').value = quizData.globalSettings.minWords;
                document.getElementById('maximum').value = quizData.globalSettings.maxWords;
            }

            // Restore quiz forms
            if (quizData.quizForms && quizData.quizForms.length > 0) {
                const quizForms = document.querySelectorAll('.typequestionall-essay');

                quizForms.forEach((form, index) => {
                    if (quizData.quizForms[index]) {
                        form.querySelector('.question-essay').value = quizData.quizForms[index].question;
                        form.querySelector('.points-essay').value = quizData.quizForms[index].points;
                        form.querySelector('.minimum-per').value = quizData.quizForms[index].minWordLimit;
                        form.querySelector('.maximum-per').value = quizData.quizForms[index].maxWordLimit;

                        // Restore multiple answers
                        const answerFields = form.querySelectorAll('.answer-essay');
                        quizData.quizForms[index].answers.forEach((answer, i) => {
                            if (answerFields[i]) {
                                answerFields[i].value = answer;
                            }
                        });
                    }

                    // Re-attach event listeners for the "Add Answer" button
                    const addAnswerButton = form.querySelector('.add-answer-btn-essay');
                    if (addAnswerButton) {
                        addAnswerButton.addEventListener('click', function () {
                            const newAnswerWrapper = document.createElement('div');
                            newAnswerWrapper.classList.add('input-wrapper', 'dropdownminute');

                            newAnswerWrapper.innerHTML = `
                                <div class="input-box">
                                    <textarea class="answer-essay" placeholder="Type your answer here" name="answer-essay[${index}][]" required></textarea>
                                    <span class="delete-icon-essay-answer"><i class="fas fa-trash"></i></span>
                                </div>
                            `;

                            addAnswerButton.closest('.typequestionhere-essay').insertBefore(newAnswerWrapper, addAnswerButton.closest('.buttonanswer'));

                            // Attach event listener for the delete icon in the new answer
                            const deleteIcon = newAnswerWrapper.querySelector('.delete-icon-essay-answer');
                            deleteIcon.addEventListener('click', function () {
                                const answerWrappers = form.querySelectorAll('.input-wrapper.dropdownminute');
                                if (answerWrappers.length > 1) { // Ensure at least one answer box remains
                                    newAnswerWrapper.remove();
                                    saveQuizData(); // Save quiz data after deleting an answer
                                } else {
                                    alert("You must keep at least one answer box.");
                                }
                            });
                        });
                    }

                    // Re-attach event listeners for the delete icon in the question
                    const deleteIcon = form.querySelector('.delete-icon-essay');
                    if (deleteIcon) {
                        deleteIcon.addEventListener('click', function () {
                            form.remove();
                            updateQuestionNumbers(); // Update question numbers after deletion
                            saveQuizData(); // Save quiz data after deleting a question
                        });
                    }

                    // Attach event listeners to existing answer delete icons
                    const answerDeleteIcons = form.querySelectorAll('.delete-icon-essay-answer');
                    answerDeleteIcons.forEach(icon => {
                        icon.addEventListener('click', function () {
                            const answerWrappers = form.querySelectorAll('.input-wrapper.dropdownminute');
                            if (answerWrappers.length > 1) { // Ensure at least one answer box remains
                                const answerWrapper = icon.closest('.input-wrapper');
                                answerWrapper.remove();
                                saveQuizData(); // Save quiz data after deleting an answer
                            } else {
                                alert("You must keep at least one answer box.");
                            }
                        });
                    });
                });

                // Update question numbers after loading data
                updateQuestionNumbers();
                
            }
        }
    }

    // Function to update question numbers
    function updateQuestionNumbers() {
        const questionSpans = document.querySelectorAll('.typequestionall-essay .number');
        questionSpans.forEach((span, index) => {
            span.textContent = ` ${index + 1}`; // Update the number to reflect the current order
        });
    }

    // Auto-save when user types
    document.addEventListener('input', saveQuizData);

    window.addEventListener('load', function () {
    loadQuizData();
    updateAllQuizForms();
});
</script>











</form>






























</div>




    <div class="button-container">
    <button type="button" class="back-button" onclick="window.location.href='AcademAI-Quiz-Room.php'">
    <span class="material-icons">arrow_back</span> Back
</button>

    <button type="button" class="create-button">
  <i class="fas fa-paper-plane"></i> Submit Quiz
</button>

    </div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const quizFormContainer = document.getElementById('quiz-form-container');
    
    function checkEssayForms() {
        const essayForms = quizFormContainer.querySelectorAll('.typequestionall-essay');
        let noEssayMessage = document.getElementById('no-essay-message');

        // If message doesn't exist in DOM, create it
        if (!noEssayMessage) {
            noEssayMessage = document.createElement('div');
            noEssayMessage.id = 'no-essay-message';
            noEssayMessage.innerHTML = `
                <div style="text-align: center; margin-top: 10px;">
                     <img src="../img/online-lesson.gif" alt="No Essay Icon" class="gif-no-quiz">
                  <p  class="no-essay-txt">Please enter the number of essay questions.</p>
                </div>
            `;
            quizFormContainer.appendChild(noEssayMessage);
        }

        // Show message only if there are no essay forms
        noEssayMessage.style.display = (essayForms.length === 0) ? 'block' : 'none';
    }

    // Initial check on page load
    checkEssayForms();

    // Observe changes in the entire quiz form container
    const observer = new MutationObserver(checkEssayForms);
    observer.observe(quizFormContainer, { childList: true, subtree: true }); });
</script>



<script>
    document.addEventListener('DOMContentLoaded', function () {
    const quizFormContainer = document.getElementById('quiz-form-container');
    const addSection = document.querySelector('.add-section');

    function updateButtonVisibility() {
        const forms = quizFormContainer.querySelectorAll('.typequestionall-essay');

        // Hide the button if no forms exist
        if (forms.length === 0) {
            addSection.style.display = 'none';
        } else {
            addSection.style.display = 'block';
        }
    }

    // Initial check when page loads
    updateButtonVisibility();

    // Observe changes in #quiz-form-container to detect added/removed forms
    const observer = new MutationObserver(updateButtonVisibility);
    observer.observe(quizFormContainer, { childList: true, subtree: true });
    });

</script>


<script>
document.querySelector('.create-button').addEventListener('click', function(e) {
    e.preventDefault();

    const emptyFields = [];

    // ✅ Check if rubric is selected
    const savedSubjectTitle = localStorage.getItem('selectedSubjectTitle');
    const savedSubjectId = localStorage.getItem('selectedSubjectId');
    if (!savedSubjectTitle || !savedSubjectId) {
        emptyFields.push("Rubric selection");
    }

    // ✅ Fields to validate manually
    const fieldsToCheck = [
        { id: 'quizTitle', label: 'Quiz Title' },
        { id: 'quizSubject', label: 'Subject' },
        { id: 'start-date', label: 'Start Date' },
        { id: 'start-time', label: 'Start Time' },
        { id: 'end-date', label: 'End Date' },
        { id: 'end-time', label: 'End Time' }
    ];

    fieldsToCheck.forEach(({ id, label }) => {
        const el = document.getElementById(id);
        if (!el || !el.value.trim()) {
            emptyFields.push(label);
        }
    });

    // ✅ Check essay questions, points, and word limits
    const questionInputs = document.querySelectorAll('textarea[name="question-essay[]"]');
    const pointInputs = document.querySelectorAll('input[name="points-essay[]"]');
    const minWordInputs = document.querySelectorAll('input[name="min-essay-min[]"]');
    const maxWordInputs = document.querySelectorAll('input[name="max-essay-max[]"]');

    questionInputs.forEach((questionInput, index) => {
        const questionText = questionInput.value.trim();
        const pointValue = pointInputs[index]?.value.trim();
        const minWord = minWordInputs[index]?.value.trim();
        const maxWord = maxWordInputs[index]?.value.trim();

        if (!questionText) {
            emptyFields.push(`Essay question ${index + 1}`);
        }

        if (!pointValue) {
            emptyFields.push(`Points for question ${index + 1}`);
        }

        if (!minWord && !maxWord) {
            emptyFields.push(`Word limits for question ${index + 1}`);
        } else if (!minWord) {
            emptyFields.push(`Minimum word limit for question ${index + 1}`);
        } else if (!maxWord) {
            emptyFields.push(`Maximum word limit for question ${index + 1}`);
        }
    });

    // ✅ Show alert or open modal for missing fields
    if (emptyFields.length > 0) {
        let message = 'Cannot submit quiz. Please fill in the following required fields:\n\n';
        message += emptyFields.join('\n');
        alert(message);
    } else {
        const modal = new bootstrap.Modal(document.getElementById('create-card-modal'));
        modal.show();
    }
});
</script>



<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get the input fields
    const minInput = document.getElementById('minimum');
    const maxInput = document.getElementById('maximum');
    const numPointsInput = document.getElementById('num-points');
    const submitButton = document.querySelector('.create-button');

    // Function to validate min and max fields
    function validateMinMax() {
        const minValue = parseInt(minInput.value);
        const maxValue = parseInt(maxInput.value);

      

        // Check for negative values or zero
        if (minValue <= 0 || maxValue <= 0) {
            alert("Values cannot be negative or zero.");
            return false;
        }

        // Check if min is greater than or equal to max
        if (minValue >= maxValue) {
            alert("Minimum value must be less than the maximum value.");
            return false;
        }

        return true;
    }

    // Function to handle input validation for num-points field (runs on input)
    function validateNumPoints() {
        const numPointsValue = parseInt(numPointsInput.value);

        // Check for zero or negative value
        if (numPointsValue <= 0) {
            alert("Point/s per Item cannot be zero or negative.");
            numPointsInput.value = '';
        }
    }

    // Event listener for submit button
    submitButton.addEventListener('click', function(e) {
        if (!validateMinMax()) {
            e.preventDefault(); // Prevent form submission if validation fails
        }
        // Form will submit if validation passes
    });

    // Event listener for points input (still validates on input)
    numPointsInput.addEventListener('input', function() {
        setTimeout(validateNumPoints, 0);
    });
});
</script>




<script>
document.addEventListener("DOMContentLoaded", function () {
    // Get elements
    const pointInputs = document.querySelectorAll('.points-essay');
    const minInputs = document.querySelectorAll('.minimum-per');
    const maxInputs = document.querySelectorAll('.maximum-per');
    const submitButton = document.querySelector('.create-button');

    // Validate points input (still on input)
    pointInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            const value = parseInt(input.value);
            if (value <= 0) {
                alert("Points cannot be zero or negative.");
                input.value = '';  // Clear the invalid input
            }
        });
    });

    // Validate min/max on button click
    submitButton.addEventListener('click', function(e) {
        let errorMessage = '';
        
        // First check all min inputs
        minInputs.forEach(function (input, index) {
            if (errorMessage) return; // Skip if we already have an error
            
            const minValue = parseInt(input.value);
            const maxValue = parseInt(maxInputs[index].value);

            // Check if empty
            if (isNaN(minValue)) {
                errorMessage = "Please enter a minimum word limit.";
                return;
            }

            // Check if valid number and > 0
            if (minValue <= 0) {
                errorMessage = "Minimum word limit cannot be zero or negative.";
                return;
            }
        });

        // Then check all max inputs (only if no error from min checks)
        if (!errorMessage) {
            maxInputs.forEach(function (input, index) {
                if (errorMessage) return; // Skip if we already have an error
                
                const maxValue = parseInt(input.value);
                const minValue = parseInt(minInputs[index].value);

                // Check if empty
                if (isNaN(maxValue)) {
                    errorMessage = "Please enter a maximum word limit.";
                    return;
                }

                // Check if valid number and > 0
                if (maxValue <= 0) {
                    errorMessage = "Maximum word limit cannot be zero or negative.";
                    return;
                }
            });
        }

        // Finally check min vs max relationships (only if no previous errors)
        if (!errorMessage) {
            minInputs.forEach(function (input, index) {
                if (errorMessage) return; // Skip if we already have an error
                
                const minValue = parseInt(input.value);
                const maxValue = parseInt(maxInputs[index].value);

                // Check if min >= max
                if (minValue >= maxValue) {
                    errorMessage = "Minimum word limit cannot be greater than or equal to maximum.";
                    return;
                }
            });
        }

        if (errorMessage) {
            alert(errorMessage);
            e.preventDefault(); // Prevent form submission
        }
    });
});
</script>





</body>
</html>











