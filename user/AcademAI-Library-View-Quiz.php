<?php
    require_once('../include/extension_links.php');
?>


<?php
// Database connection
$host = 'localhost'; // Database host
$dbname = 'academaidb'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get the quiz_id from the URL
if (isset($_GET['quiz_id'])) {
    $quiz_id = $_GET['quiz_id'];

    // Fetch quiz details
    $quizQuery = "SELECT q.*, a.first_name, a.last_name, a.email 
                  FROM quizzes q 
                  JOIN academai a ON q.creation_id = a.creation_id 
                  WHERE q.quiz_id = :quiz_id";
    $quizStmt = $pdo->prepare($quizQuery);
    $quizStmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $quizStmt->execute();
    $quiz = $quizStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch essay questions for the quiz
    $essayQuery = "SELECT * FROM essay_questions WHERE quiz_id = :quiz_id";
    $essayStmt = $pdo->prepare($essayQuery);
    $essayStmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $essayStmt->execute();
    $essayQuestions = $essayStmt->fetchAll(PDO::FETCH_ASSOC);

    // Debugging: Check if data is fetched correctly
    if (!$quiz) {
        die("Quiz not found.");
    }
    if (empty($essayQuestions)) {
        die("No essay questions found for this quiz.");
    }
} else {
    die("Quiz ID not provided.");
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/academAI-library-upcoming-view-card.css">
    <title>Document</title>
</head>
<body>


<div class="mcq">

    <a href="AcademAI-Library-Upcoming-Card.php">
    <i class="fa-solid fa-arrow-left" style="color:#9EC8B9;"></i>
    </a>


    <div class="profile-student">
        <div class="profile-section">
            <img src="../img/www.jpg" class="profile-pic">
            <div class="profile-identity">
                <p class="profile-name"><?php echo htmlspecialchars($quiz['first_name'] . ' ' . $quiz['last_name']); ?></p>
                <p class="email"><?php echo htmlspecialchars($quiz['email']); ?></p>
            </div>
        </div>
        <a href="AcademAI-Completed-People-Join-LeaderBoard.php" class="custom-leaderboard-link">
            <i class="fas fa-trophy animated custom-leaderboard-icon">Leaderboard</i>
        </a>
    </div>

    <div class="divide-content">
        <div class="author-descript">
            <div class="author-info">
                <div class="d-flex">
                    <p class="title-quiz">Quiz Title:</p>
                    <p class="the-title-quiz"><?php echo htmlspecialchars($quiz['title']); ?></p>
                </div>
                <div class="d-flex">
                    <p class="descrip">Description:</p>
                    <p class="the-descrip"><?php echo htmlspecialchars($quiz['description']); ?></p>
                </div>
                <div class="d-flex">
                    <p class="subject">Subject:</p>
                    <p class="the-subject"><?php echo htmlspecialchars($quiz['subject']); ?></p>
                </div>
            </div>
        </div>

        <div class="date-descript">
            <div class="date-info">
                <div class="d-flex">
                    <p class="startdate">Start Date:</p>
                    <p class="the-startdate"><?php echo htmlspecialchars($quiz['start_date']); ?></p>
                </div>
                <div class="d-flex">
                    <p class="starttime">Start Time:</p>
                    <p class="the-starttime"><?php echo htmlspecialchars($quiz['start_time']); ?></p>
                </div>
                <div class="d-flex">
                    <p class="enddate">End Date:</p>
                    <p class="the-enddate"><?php echo htmlspecialchars($quiz['end_date']); ?></p>
                </div>
            </div>
        </div>

        <div class="etc-info-3">
            <div class="d-flex">
                <p class="endtime">End Time:</p>
                <p class="the-endtime"><?php echo htmlspecialchars($quiz['end_time']); ?></p>
            </div>
            <div class="restriction">
                <input type="checkbox" class="form-check-input" id="randomized-checkbox-restriction" checked>
                <p class="restriction-title">Restriction:</p>
                <p class="restriction-titles">Close quiz after due time</p>
            </div>
        </div>
    </div>

    <div class="col d-flex quiz-taker-section">
        <div class="col-6 green-section"
        ></div>
        <div class="col-6 quiz-points">
    <!-- Display the quiz total points -->
        <h3 class="total-points">Quiz Total Points: <?php echo htmlspecialchars($quiz['quiz_total_points_essay']); ?></h3>
        </div>
    </div>
     

        

              
            

    <div class="container useranswer">
    <div class="displayanswer">
        <div class="edit-delete-sections">
            <div class="edit-delete-leaderboard-section">
                <a href="#" id="library-completed-edit-modalBtn" class="edit" data-bs-toggle="modal" data-bs-target="#edit-card-modal">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="#" class="delete"><i class="fas fa-trash-alt"></i></a>
                <a href="#" class="copy-link"><i class="fa-solid fa-link"></i></a>
                <a href="#" class="recycle"><i class="fa-solid fa-recycle"></i></a>
            </div>
        </div>



    
<!-- Loop through essay questions and answers -->
<?php foreach ($essayQuestions as $index => $essay): ?>
    <div class="divide-question">
        <div class="container-fluid typequestionall-essay">
            <div class="container-fluid typequestion-essay">
                <div class="input-wrapper dropdownminute">
                    <div class="input-box-essay">
                        <input type="text" id="question-essay" placeholder="Discuss the causes and consequences of the French Revolution." name="question" value="<?php echo htmlspecialchars($essay['question']); ?>">
                        <!-- Check if 'id' key exists before using it -->
                        <span class="number-essay"><?php echo isset($essay['id']) ? htmlspecialchars($essay['id']) : ($index + 1); ?>.</span>
                        <span class="rubric-icon"><a href="AcademAI-Set-Essay-Rubric.php" class="rubric-link">Set Rubrics</a></span>
                    </div>
                </div>
                <div class="input-wrapper dropdownminute">
                    <div class="input-box">
                        <?php
                        // Assuming $essay['answer'] contains the concatenated answers from the database
                        $concatenated_answers = $essay['answer'];

                        // Split the concatenated answers into an array using the delimiter '|'
                        $answers = explode('|', $concatenated_answers);

                        // Loop through each answer and display it in a separate input box
                        foreach ($answers as $index => $answer) {
                            echo '<input type="text" id="answer-essay-' . $index . '" name="answer-essay[]" value="' . htmlspecialchars(trim($answer)) . '" placeholder="Enter your answer">';
                        }
                        ?>
                    </div>
                </div>

                <div class="container typequestionhere-essay">
                    <div class="input-wrapper dropdownminute-essay">
                        <div class="dropdown">
                            <!-- Updated: Use $essay['time_limit'] for each question -->
                            <input type="number" id="time-limit-essay-<?php echo $index; ?>" name="time-limit" value="<?php echo htmlspecialchars($essay['time_limit']); ?>" min="0">
                            <select name="time-limit-unit" id="time-limit-unit-essay-<?php echo $index; ?>">
                                <option value="minutes">minutes</option>
                                <option value="seconds">seconds</option>
                            </select>
                        </div>
                    </div>

                    <!-- Updated: Use $essay['points_per_item'] for each question -->
                    <div class="input-wrappe dropdownminute">
                        <input type="number" id="points-essay-<?php echo $index; ?>" name="points" min="0" value="<?php echo htmlspecialchars($essay['points_per_item']); ?>">
                    </div>
                </div>

                <!-- Updated: Use $essay['min_words'] and $essay['max_words'] for each question -->
                <div class="word-limit-per">
                    <input type="number" id="minimum-per-<?php echo $index; ?>" placeholder="minimum word limit" name="limit-word" min="1" value="<?php echo htmlspecialchars($essay['min_words']); ?>" required>
                    <input type="number" id="maximum-per-<?php echo $index; ?>" placeholder="maximum word limit" name="limit-word" min="1" value="<?php echo htmlspecialchars($essay['max_words']); ?>" required>
                </div>

                <div class="orupload">
                    <h3>or</h3>
                </div>
                <div class="form-group fileupload">
                    <label class="colcontrol-label">
                        Attachment(s) You can upload your files here
                    </label>
                    <div class="file-input-wrapper">
                        <button id="upload-btn">Upload File</button>
                        <input id="input-2" name="input2[]" type="file" class="file" multiple>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
    </div>
</div>
</div>
</div>
</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Polling every 30 seconds
    setInterval(fetchQuizzes, 30000);
    
    fetchQuizzes(); // Fetch immediately on page load

    function fetchQuizzes() {
        fetch("fetch_updated_quizzes.php")
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error("Error:", data.error);
                    return;
                }
                updateUI(data);
            })
            .catch(error => console.error("Error fetching quizzes:", error));
    }

    function updateUI(data) {
        updateQuizContainer(".upcoming-quizzes", data.upcoming);
        updateQuizContainer(".ongoing-quizzes", data.ongoing);
        updateQuizContainer(".completed-quizzes", data.completed);
    }

    function updateQuizContainer(selector, quizzes) {
        const container = document.querySelector(selector);
        if (!container) return;

        container.innerHTML = ""; // Clear existing content

        quizzes.forEach(quiz => {
            const card = createCard(quiz);
            container.appendChild(card);
        });
    }

    function createCard(quiz) {
        const cardDiv = document.createElement("div");
        cardDiv.classList.add("card");

        cardDiv.innerHTML = `
            <h2>${quiz.title}</h2>
            <p>${quiz.subject}</p>
            <p>Start: ${quiz.start_date} ${quiz.start_time} - End: ${quiz.end_date} ${quiz.end_time}</p>
        `;

        return cardDiv;
    }
});


</script>

<!-- Modal -->
<div class="modal fade" id="edit-card-modal"  data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
  <div class="modal-content">
            <a href="AcademAI-Library-Completed-View-Card.php" class="close">&times;</a>
        <div class="half-image">
            <img src="../img/modal/modal-2.jpg" alt="First Image" style="width: 100%; height: 100%;">
        </div>
        <img src="../img/modal/modal-1.jpg" alt="Circle Image" class="center-image"> <!-- Second Image (Circle Image) -->
        <form action="#">
        
        </form>
        <div class="submit-content">
            <p class="submit-text">Are you sure you want to edit this quiz?</p> <!-- Text -->
            <div class="yes-btn-section">
          <a href="AcademAI-Edit-Quiz-Essay.php" class="yes-btn">Yes</a> <!-- Yes Button -->
          <a href="AcademAI-Library-Completed-View-Card.php" class="cancel-btn">No</a> <!-- Cancel Button -->
            </div>
        </div>
</div>
        </div>
    </div>
</body>
</html>


