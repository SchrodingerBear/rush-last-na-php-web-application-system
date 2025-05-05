
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/academAI-view- creation-quiz.css">

    <title></title>
</head>
<body>
    
</body>
</html>
<?php

// Include the database connection file
require_once('../include/extension_links.php');

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Create database instance and establish connection
$db = new Database();
$conn = $db->connect();

// Handle form submissions
if (isset($_POST['submit-quiz-creation'])) {
    // Collect and sanitize inputs
    $title = trim($_POST['title']);
    $description = trim($_POST['quizDescription']);
    $subject = trim($_POST['subject']);
    $quiz_total_points_essay = intval($_POST['quiz_total_points_essay']);
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
     

    // Check if the restriction checkbox is checked
    $isActive = isset($_POST['restriction']) ? 1 : 0;

    // Get the file submission setting (1 if checked, 0 otherwise)
    $allowFileSubmission = isset($_POST['allow_file_submission']) ? 1 : 0;

    // Store inputs in session
    $_SESSION['quizTitle'] = $title;
    $_SESSION['quizDescription'] = $description;
    $_SESSION['quizSubject'] = $subject;

    // Check if session has creation_id
    if (!isset($_SESSION['creation_id'])) {
        echo "<script>alert('User is not logged in. Please log in to continue.');</script>";
        return;
    }

    $creationId = $_SESSION['creation_id'];

    // Generate a unique quiz code
    $quizCode = generateQuizCode();

$sql = "INSERT INTO quizzes (title, subject, description, start_date, end_date, start_time, end_time, creation_id, quiz_total_points_essay, is_active, quiz_code, allow_file_submission)
        VALUES (:title, :subject, :description, :start_date, :end_date, :start_time, :end_time, :creation_id, :quiz_total_points_essay, :is_active, :quiz_code, :allow_file_submission)";

    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);
$stmt->bindParam(':title', $title);
$stmt->bindParam(':subject', $subject);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':start_date', $startDate);
$stmt->bindParam(':end_date', $endDate);
$stmt->bindParam(':start_time', $startTime);
$stmt->bindParam(':end_time', $endTime);
$stmt->bindParam(':creation_id', $creationId);
$stmt->bindParam(':quiz_total_points_essay', $quiz_total_points_essay);
$stmt->bindParam(':is_active', $isActive);
$stmt->bindParam(':quiz_code', $quizCode);
$stmt->bindParam(':allow_file_submission', $allowFileSubmission, PDO::PARAM_INT); // Add this line



    if ($stmt->execute()) {
        // Get the last inserted quiz_id
        $quizId = $conn->lastInsertId();
    
        try {
            insertEssayQuestions($conn, $quizId);
    
            // Generate the modal content
            echo "
            <div class='modal modal-successful fade' id='successful-message-modal' data-bs-backdrop='static' data-bs-keyboard='false' tabindex='-1' aria-labelledby='staticBackdropLabel' aria-hidden='true'>
                <div class='modal-dialog'>
                    <div class='modal-content-successful'>
                        <div class='half-image'>
                            <img src='../img/modal/modal-22.gif' alt='First Image' style='width: 100%; height: 100%;'>
                        </div>
            
                        <div class='submit-content-successful'>
                            <p class='submit-text-successfully'>You have successfully created the quiz!</p> <!-- Text -->
            
                            <div class='yes-no'>
                                <div class='quiz-link' id='quiz-code'>Quiz Code: $quizCode</div> <!-- Display Quiz Code -->
                              
                            </div>
            
                            <div class='ok'>
                                <a href='../user/AcademAI-Library-Upcoming-View-Card.php?quiz_id=$quizId' class='ok-btn'>View Quiz</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            // Show the modal after successful quiz creation
            var modal = new bootstrap.Modal(document.getElementById('successful-message-modal'));
            modal.show();
            
            // Function to display a styled success message
            function showCopySuccess(quizCode) {
                const successMessage = document.createElement('div');
                successMessage.textContent = 'Successfully copied ' + quizCode + ' to clipboard!';
                successMessage.style.position = 'fixed';
                successMessage.style.bottom = '20px';
                successMessage.style.left = '50%';
                successMessage.style.transform = 'translateX(-50%)';
                successMessage.style.backgroundColor = '#092635';
                successMessage.style.color = 'white';
                successMessage.style.padding = '10px 20px';
                successMessage.style.borderRadius = '5px';
                successMessage.style.zIndex = '9999';
                successMessage.style.opacity = '1'; // Start fully visible
                successMessage.style.transition = 'opacity 0.5s ease-out'; // Smooth fade-out transition
            
                document.body.appendChild(successMessage);
            
                // Fade out and remove the message after 2 seconds
                setTimeout(() => {
                    successMessage.style.opacity = '0';
                    setTimeout(() => successMessage.remove(), 500); // Remove after fade-out
                }, 2000);
            }
            
            // Close the modal when clicking the OK button
            document.querySelector('.ok-btn').addEventListener('click', function() {
                // Close the modal
                modal.hide();
            });
            
            // Global event listener for copying the quiz code
            document.addEventListener('click', function (event) {
                if (event.target.id === 'copyQuizCode' || event.target.closest('#copyQuizCode')) {
                    event.preventDefault(); // Prevent default anchor behavior
            
                    let quizCodeElement = document.querySelector('#quiz-code');
                    if (!quizCodeElement) {
                        alert('Quiz code element not found!');
                        return;
                    }
            
                    let quizCode = quizCodeElement.textContent.replace('Quiz Code: ', '').trim();
                    console.log('Extracted Quiz Code:', quizCode); // Debugging
            
                    if (!quizCode || quizCode === 'NO_CODE') {
                        alert('No quiz code available to copy.');
                        return;
                    }
            
                    // Use Clipboard API if available
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(quizCode)
                            .then(() => {
                                console.log('Quiz code copied to clipboard using Clipboard API:', quizCode); // Debugging
                                showCopySuccess(quizCode); // Show success message
                            })
                            .catch(err => {
                                console.error('Failed to copy quiz code using Clipboard API:', err); // Debugging
                                fallbackCopyText(quizCode); // Use fallback method
                            });
                    } else {
                        // Fallback for older browsers
                        fallbackCopyText(quizCode);
                    }
                }
            });
            
            // Fallback method using document.execCommand('copy')
            function fallbackCopyText(text) {
                let textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed'; // Ensure it's not visible
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();
                textArea.setSelectionRange(0, 99999); // For mobile devices
            
                try {
                    let isCopied = document.execCommand('copy');
                    if (isCopied) {
                        console.log('Quiz code copied to clipboard using fallback method:', text); // Debugging
                        showCopySuccess(text); // Show success message
                    } else {
                        console.error('Failed to copy quiz code using fallback method.');
                        alert('Failed to copy quiz code. Please try again.');
                    }
                } catch (err) {
                    console.error('Error during fallback copy:', err); // Debugging
                    alert('Failed to copy quiz code. Please try again.');
                } finally {
                    document.body.removeChild(textArea);
                }
            }
            </script>
            ";
            // Ensure the script terminates here after modal display
            exit;
        
        } catch (Exception $e) {
            // Handle errors during essay question insertion
            echo "<script>alert('Error: {$e->getMessage()}');</script>";
            exit; // Ensure script terminates after displaying the error
        }
    }
}
        

function generateQuizCode() {
    // Generate a random 6-character string
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // Characters to use for the code
    $code = ''; // Initialize the code variable

    // Loop to generate 6 random characters
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }

    // Insert a hyphen in the middle (after the first 3 characters)
    $code = substr($code, 0, 3) . '-' . substr($code, 3);

    return $code; // Return the 6-character code with a hyphen
}
header('Content-Type: application/json');






function insertEssayQuestions($conn, $quizId)
{
    $alertMessages = [];

    // Check if essay questions are submitted
    if (!empty($_POST['question-essay'])) {
        $questions = $_POST['question-essay']; // Array of essay questions
        $points = $_POST['points-essay'] ?? []; // Array of points per question
        $min_words = $_POST['min-essay-min'] ?? []; // Array of minimum word limits
        $max_words = $_POST['max-essay-max'] ?? []; // Array of maximum word limits
        $answers = $_POST['answer-essay'] ?? []; // 2D array of answers for each question
        $rubric_id = $_POST['rubric_id'] ?? null; // Rubric ID (if applicable)

        // Loop through each essay question
        foreach ($questions as $index => $questionText) {
            // Skip if the question text is empty
            if (empty(trim($questionText))) {
                $alertMessages[] = "Question text is empty for question index: $index";
                continue;
            }

            // Get points, min_words, and max_words for the current question
            // Ensure these values are treated as single values, not arrays
            $points_per_item = $points[$index] ?? 0; // Points for the question
            $min_word_limit = $min_words[$index] ?? 0; // Minimum word limit for the question
            $max_word_limit = $max_words[$index] ?? 0; // Maximum word limit for the question

            // Get answers for the current question
            $question_answers = $answers[$index] ?? [];

            // Handle file uploads for this question
            $file_upload_content = null;
            $file_name = null;
            $file_input_name = "file-essay-{$index}";

            // Check if files are uploaded for this question
            if (!empty($_FILES[$file_input_name]['name'][0])) {
                $file_names = [];
                $file_uploads = [];

                // Loop through each uploaded file
                foreach ($_FILES[$file_input_name]['name'] as $fileIndex => $fileName) {
                    if (!empty($fileName)) {
                        $tmp_file = $_FILES[$file_input_name]['tmp_name'][$fileIndex];
                        $file_content = file_get_contents($tmp_file);

                        if ($file_content === false) {
                            $alertMessages[] = "Failed to read file for question: $questionText";
                            continue;
                        }

                        // Store file content and name
                        $file_uploads[] = base64_encode($file_content);
                        $file_names[] = $fileName;
                    }
                }

                // Concatenate file names and uploads with a delimiter (e.g., comma)
                $file_name = implode(',', $file_names);
                $file_upload_content = implode(',', $file_uploads);
            }

            // Concatenate answers into a single string with a delimiter (e.g., |)
            $concatenated_answers = implode('|', $question_answers);

            // Insert essay question into the database
            $sql_essay = "INSERT INTO essay_questions 
                           (quiz_id, question, min_words, max_words, num_quiz, points_per_item, rubric_id, answer, file_upload, file_name)
                           VALUES 
                           (:quiz_id, :question, :min_words, :max_words, :num_quiz, :points_per_item, :rubric_id, :answer, :file_upload, :file_name)";
            $stmt_essay = $conn->prepare($sql_essay);

            // Calculate the question number
            $num_quiz = $index + 1;

            // Bind parameters and execute
            $stmt_essay->bindParam(':quiz_id', $quizId, PDO::PARAM_INT);
            $stmt_essay->bindParam(':question', $questionText, PDO::PARAM_STR);
            $stmt_essay->bindParam(':min_words', $min_word_limit, PDO::PARAM_INT);
            $stmt_essay->bindParam(':max_words', $max_word_limit, PDO::PARAM_INT);
            $stmt_essay->bindParam(':num_quiz', $num_quiz, PDO::PARAM_INT);
            $stmt_essay->bindParam(':points_per_item', $points_per_item, PDO::PARAM_INT);
            $stmt_essay->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
            $stmt_essay->bindParam(':answer', $concatenated_answers, PDO::PARAM_STR);
            $stmt_essay->bindParam(':file_upload', $file_upload_content, PDO::PARAM_LOB); // Store binary data
            $stmt_essay->bindParam(':file_name', $file_name, PDO::PARAM_STR); // Store file name

            try {
                $stmt_essay->execute();
          
            } catch (PDOException $e) {
                $alertMessages[] = "Error inserting essay question: " . $e->getMessage();
                echo "Error: " . $e->getMessage(); // Debugging statement
            }
        }
    } else {
        $alertMessages[] = "No essay questions found in the POST request.";
    }

    // Display alert messages if there are any errors
    if (!empty($alertMessages)) {
        echo "
            <script>
                window.onload = function() {
                    showModal();
                };
            </script>
        ";
    }
}
?>