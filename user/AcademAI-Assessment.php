<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header('Location: ../login.php');
    exit();
}

require_once('../include/extension_links.php');
include('../classes/connection.php');

// Get parameters from URL
$answer_id = $_GET["answer_id"] ?? null;
$quiz_id = $_GET["quiz_id"] ?? null;
$rubric_id = $_GET['rubric_id'] ?? null;

// Connect to the database
$db = new Database();
$conn = $db->connect();

// Get current user info
$current_user_id = $_SESSION['creation_id'];
$stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, photo_path FROM academai WHERE creation_id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $full_name = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
    $email = $user['email'];
    $photo_path = $user['photo_path'] ? '../' . $user['photo_path'] : '../img/default-avatar.jpg';
} else {
    $full_name = "User";
    $email = "user@example.com";
    $photo_path = '../img/default-avatar.jpg';
}

// Check if rubric_id is present
if ($rubric_id) {
    try {
        // Find the subject_id associated with this rubric_id
        $subjectQuery = "SELECT DISTINCT c.subject_id 
                         FROM criteria c
                         INNER JOIN essay_questions eq ON c.subject_id = eq.rubric_id
                         WHERE eq.rubric_id = :rubric_id";

        $subjectStmt = $conn->prepare($subjectQuery);
        $subjectStmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
        $subjectStmt->execute();
        $subjectResult = $subjectStmt->fetch(PDO::FETCH_ASSOC);

        if ($subjectResult) {
            $subject_id = $subjectResult['subject_id'];

            // Get criteria related to this subject_id
            $criteriaQuery = "SELECT criteria_name, advanced_text, proficient_text, 
                                     needs_improvement_text, warning_text, weight 
                              FROM criteria 
                              WHERE subject_id = :subject_id";

            $criteriaStmt = $conn->prepare($criteriaQuery);
            $criteriaStmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            $criteriaStmt->execute();
            $criteria = $criteriaStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $criteria = [];
    }
}

// Fetch evaluations
$evaluations = [];
$teacher_comment = '';
if ($answer_id) {
    $stmt = $conn->prepare("SELECT * FROM essay_evaluations WHERE answer_id = :answer_id");
    $stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);
    $stmt->execute();
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($evaluations)) {
        $teacher_comment = $evaluations[0]['teacher_comment'] ?? '';
    }
}

// Get quiz details
$quiz_details = [];
if ($quiz_id) {
    $stmt = $conn->prepare("SELECT * FROM `essay_questions` WHERE quiz_id = :quiz_id");
    $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $stmt->execute();
    $quiz_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Process evaluation data
$evaluationData = null;
$parsedEvaluation = null;
$overallScore = null;

if (!empty($evaluations)) {
    foreach ($evaluations as $evaluation) {
        $jsonString = $evaluation["evaluation_data"];
        $data = json_decode($jsonString, true);
        
        if (isset($data["evaluation"]["evaluation"])) {
            $evaluationJson = str_replace(["```json\n", "\n```"], "", $data["evaluation"]["evaluation"]);
            $parsedEvaluation = json_decode($evaluationJson, true);
            
            if ($parsedEvaluation) {
                $overallScore = $parsedEvaluation["overall_weighted_score"];
                $generalAssessment = $parsedEvaluation["general_assessment"];
            }
        }
        break;
    }
}
// Get question number from URL
$question_number = $_GET['question_number'] ?? 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/assessment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Assessment</title>
</head>
<body>


      <!-- Header with Back Button and User Profile -->
      <div class="header">
            <a href="<?php echo 'AcademAI-user(learners)-view-quiz-answer-1.php' . ($quiz_id ? '?quiz_id=' . urlencode($quiz_id) : ''); ?>" class="back-btn">
            <i class="fa-solid fa-chevron-left"></i>
            </a>   
            <div class="header-right">  
                <div class="user-profile">
                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="User" class="profile-pic" onerror="this.onerror=null; this.src='../img/default-avatar.jpg'">    
                    <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                      
                    </div>
                </div>
            </div>
        </div>
        <!-- Header with Back Button and User Profile -->


<div class="asessment-1">


<div class="header-question">
    <div class="question-info-header">
        <div class="question-marker">
        <h2>
            Detailed Assessment
        </h2>
          
        </div>
        <span class="question-badge">
                <i class="fas fa-question-circle"></i> Question <?php echo htmlspecialchars($question_number); ?>
            </span>
       
    </div>
</div>

     


<?php
$stmt1 = $conn->prepare("SELECT * FROM `essay_questions` WHERE quiz_id = :quiz_id");
$stmt1->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
$stmt1->execute();
$result = $stmt1->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM essay_evaluations WHERE answer_id = :answer_id");
$stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);
$stmt->execute();
$evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables to store evaluation data
$evaluationData = null;
$parsedEvaluation = null;
$overallScore = null;

if (!empty($evaluations)) {
    foreach ($evaluations as $evaluation) {
        $jsonString = $evaluation["evaluation_data"];
        $data = json_decode($jsonString, true);
        
        // Extract the JSON string from the evaluation field
        $evaluationJson = str_replace(["```json\n", "\n```"], "", $data["evaluation"]["evaluation"]);
        
        // Decode the clean JSON string
        $parsedEvaluation = json_decode($evaluationJson, true);
        
        if ($parsedEvaluation) {
            $overallScore = $parsedEvaluation["overall_weighted_score"];
            $generalAssessment = $parsedEvaluation["general_assessment"];
        }
        
        // We only need one evaluation
        break;
    }
}
?>

        <div class="essay-criteria-setting-container"> 
       
    <div class="rubric">
    <div class="rubric-table">
        <?php 
        if (isset($_GET['rubric_id'])) {
            $rubric_id = $_GET['rubric_id'];
            
            // Connect to the database
            $db = new Database();
            $conn = $db->connect();
            
            try {
        // Get the rubric data directly using the rubric_id from the URL
$rubricQuery = "SELECT data, id FROM rubrics WHERE subject_id = :rubric_id";
//echo $rubric_id;
$rubricStmt = $conn->prepare($rubricQuery);
$rubricStmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
$rubricStmt->execute();
$rubricData = $rubricStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($rubricData) {
                    //$subject_id = $rubricData['subject_id'];
                    $criteriaDatas = json_decode($rubricData['data'], true);
                    
                    if ($criteriaDatas && isset($criteriaDatas['headers']) && isset($criteriaDatas['rows'])):
                    ?>
                        <table class="table table-hover">
                            <thead class="criteria-heading" id="criteria-heading">
                                <tr>
                                    <th scope="col">Criteria</th>
                                    <?php foreach ($criteriaDatas['headers'] as $header): ?>
                                        <th scope="col"><?php echo htmlspecialchars($header); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody id="criteria-table-body" class="predefined-criteria">
                                <?php foreach ($criteriaDatas['rows'] as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['criteria']); ?></td>
                                        <?php foreach ($row['cells'] as $cell): ?>
                                            <td><?php echo htmlspecialchars($cell); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Invalid rubric data format.</p>
                    <?php endif;
                } else {
                    echo "<p>No rubric found with the specified ID.</p>";
                }
            } catch (Exception $e) {
                echo "<p>Error loading rubric: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p>No rubric ID specified.</p>";
        }
        ?>
    </div>
</div>
            </div>
        </div>
    </div>









    <div class="feedback-container">
    <style>
        /* Navigation Bar Styling */
        .nav-bar {
            display: flex;
            justify-content:flex-start;
            padding: 10px;
            margin-bottom: 20px;
            margin-top:50px;
            border-bottom:2px solid #092635;
          
        }

        .nav-bar a {

            font-size:1.2em;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            color: #1b4242 !important;

            cursor: pointer;
            padding: 10px 20px; /* Add padding for better click area */
            transition: background-color 0.3s ease; /* Smooth transition */
        }

        .nav-bar a:hover {
            color:#5c8374 !important;
        }

        /* Active navigation link style */
        .nav-bar a.active {
            background-color: #092635;/* Background color for active link */
            color: white !important; /* Text color for active link */
        }

        /* Content Section Styling */
        .content-section {
            display: none; /* Initially hide all sections */
            margin-top: 20px;
        }

        /* Show the active section */
        .content-section.active {
            display: block;
        }
    </style>
</head>
<body>







<div class="asess">

   

    <!-- Navigation Bar -->
    <div class="nav-bar">
        <a id="nav-system-assessment" onclick="showSection('system-assessment', this)">System Assessment</a>
        <a id="nav-ai-report" onclick="showSection('ai-report', this)">AI Report</a>
        <a id="nav-plagiarism-report" onclick="showSection('plagiarism-report', this)">Plagiarism Report</a>
    </div>

    <!-- System Assessment Section -->
    <div id="system-assessment" class="content-section active">
    <div class="assessment">
        <?php if ($parsedEvaluation && isset($parsedEvaluation["criteria_scores"])): ?>
            <?php foreach ($parsedEvaluation["criteria_scores"] as $criteriaName => $criteriaData): ?>
                <div class="assessment-details">
                    <div class="asset">
                        <div class="assess-title col-2"> 
                            
                            <p class="rubrics">
                                <?php 
                                echo htmlspecialchars($criteriaName) . " -<br> Score: " . htmlspecialchars($criteriaData["score"]) . "%"; 

                                // Initialize level
                                $level = '';

                                // Use regex to extract the level from the feedback
                                if (preg_match('/✅\s+Why\s+(\w[\w\s]*\w):/i', $criteriaData["feedback"], $matches)) {
                                    $level = trim($matches[1]);
                                }
                                
// Compare header and level
$levelNumber = null;
if (isset($criteriaDatas['headers']) && is_array($criteriaDatas['headers'])) {
    foreach ($criteriaDatas['headers'] as $index => $header) {
        if (strcasecmp($header, $level) === 0) {
            $levelNumber = $index + 1; // Add 1 to make it human-readable (1-based index)
            break;
        }
    }
}

echo "<br>Level: " . htmlspecialchars($level);
if ($levelNumber !== null) {
    echo " (" . htmlspecialchars($levelNumber) . ")";
}
?>
</p>
</div>

                        
<div class="assess-feedback col-5"> 
    <p class="rubrics-explanation"><strong>Evaluation:</strong></p>
    <?php 
    // Convert line break placeholder to actual <br> tags
    $criteriaData["feedback"] = str_replace("**", "<br>", $criteriaData["feedback"]);
    echo $criteriaData["feedback"]; 
    ?>
</div>


                        <?php if (isset($criteriaData["suggestions"]) && !empty($criteriaData["suggestions"])): ?>
                            <div class="feedback-suggestion col-5">
                                <p class="feedback-title" style="color:#1b4242;"><strong>Suggestions for Improvement:</strong></p>
                                <ul class="suggestion-list" style="color:#1b4242;">
                                    <?php foreach ($criteriaData["suggestions"] as $suggestion): ?>
                                        <li><?php echo htmlspecialchars($suggestion); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Display general assessment -->
            <?php if ($generalAssessment): ?>
                <div class="assessment-details general-assessment">
                    <div class="asset">
                    <div class = "assess-t col-2">
                        <p class="rubrics">General Assessment</p>
                        </div>

                        <?php if (isset($generalAssessment["strengths"]) && !empty($generalAssessment["strengths"])): ?>
                            <div class = "assess-feedback col-5">
                                <p class="feedback-title" ><strong>📋 General Assessment and Feedback:</strong></p>
                                <ul class="assessment-list"  style="color: #1b4242;">
                                    <?php foreach ($generalAssessment["strengths"] as $strength): ?>
                                        <li><?php echo htmlspecialchars($strength); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($generalAssessment["areas_for_improvement"]) && !empty($generalAssessment["areas_for_improvement"])): ?>
                            <div class="feedback-suggestion col-5 improvements">
                                <p class="feedback-title" style="color: #1b4242;"><strong>✨ Needs Improvement / Suggestions for Improvement:</strong></p>
                                <ul class="assessment-list"  style="color: #1b4242;">
                                    <?php foreach ($generalAssessment["areas_for_improvement"] as $improvement): ?>
                                        <li><?php echo htmlspecialchars($improvement); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="assessment-details">
                <p>No evaluation data found for this answer.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

    <!-- AI Report Section -->
  <!-- AI Report Section -->
<div id="ai-report" class="content-section">
    <div class="assessment">
        <?php if (isset($data["ai_detection"]) && !empty($data["ai_detection"])): ?>
            <div class="assessment-details-ai">
                <p class="rubrics-ai">AI Detection Analysis</p>
                <div class="ai-score-container">
                    <div class="ai-score-chart">
                        <div class="ai-meter">
                            <div class="ai-portion" style="width: <?php echo htmlspecialchars($data["ai_detection"]["ai_probability"] * 100); ?>%;">
                                <span class="ai-label">AI: <?php echo htmlspecialchars($data["ai_detection"]["ai_probability"] * 100); ?>%</span>
                            </div>
                            <div class="human-portion" style="width: <?php echo htmlspecialchars($data["ai_detection"]["human_probability"] * 100); ?>%;">
                                <span class="human-label">Human: <?php echo htmlspecialchars($data["ai_detection"]["human_probability"] * 100); ?>%</span>
                            </div>
                        </div>
                    
                        <div class="ai-explanation">
                            <br>
                    <h4>Detailed Explanation:</h4>
                    <?php if (isset($data["ai_detection"]["explanation"])): ?>
                        <div class="ai-meter">
<?php
echo nl2br(htmlspecialchars(
    preg_replace([
        '/```json/',       // Remove opening code block
        '/```/',           // Remove closing code block
        '/,+/',            // Remove extra commas (1 or more)
        '/\bJSON\b/',      // Remove the word JSON
        '/\b[A-Z]{3,}\b/'  // Remove all-caps words (3 or more letters)
    ], '', $data["ai_detection"]["explanation"])
));
?>
</div>
                    <?php else: ?>
                        <div class="ai-summary">
                            <p>No explanation found.</p>
                        </div>
                    <?php endif; ?>

                    </div>
                    </div>
                </div>
                
                <div class="ai-explanation">
                    <h4>What does this mean?</h5>
                    <p>This analysis estimates the probability that the text was generated by AI versus written by a human. A higher AI percentage suggests the content may have been created or heavily assisted by AI tools like ChatGPT or similar models.</p>
                    
                    <?php if ($data["ai_detection"]["ai_probability"] > 70): ?>
                        <div class="ai-warning">
                            <p><strong>Note:</strong> This content shows a high probability of AI generation. If this work was submitted as original human work, please review your institution's policies on AI-assisted writing.</p>
                        </div>
                    <?php elseif ($data["ai_detection"]["ai_probability"] > 40): ?>
                        <div class="ai-caution">
                            <p><strong>Note:</strong> This content shows moderate indicators of AI assistance. The writing may contain sections created with AI help.</p>
                        </div>
                    <?php else: ?>
                        <div class="ai-ok">
                          
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="assessment-details-ai">
                <p class="rubrics-ai">AI Detection Analysis</p>
                <p class="ai-unavailable">AI analysis data is not available for this submission.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

    <!-- Plagiarism Report Section -->
<!-- Plagiarism Report Section -->
<div id="plagiarism-report" class="content-section">
    <div class="assessment">
        <?php 
                                    $sources = json_decode($evaluation['plagiarism_sources'], true);
                                    ?>
                                    <?php if ((isset($data["plagiarism"]["success"]) && $data["plagiarism"]["success"]) || (count($sources) > 0)): ?>
            <div class="assessment-details-plagiarize">
                <p class="rubrics-plagiariaze">Plagiarism Analysis</p>
                
                    <!-- Display successful plagiarism results -->
                        <div class="plagiarism-found">
                        <div class="plagiarism-found">
                            <p class="plagiarism-warning">Potential plagiarism detected. Parts of this submission match content from external sources.</p>
                            
                            <div class="plagiarism-summary">
                                <p style="margin-bottom:0;">
                                    <strong>Similarity Score:</strong> 
                                    <?php 
                                    if (isset($evaluation["plagiarism_score"])) {
                                        $plagiarism_score = $evaluation["plagiarism_score"] > 50 ? 100 : $evaluation["plagiarism_score"];
                                        echo htmlspecialchars($plagiarism_score) . '%';
                                    } else {
                                        echo 'Not available';
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <div class="plagiarism-other-sources">
                                <p class="plagiarized-works-database">Sources found in online databases:</p>
                                <ol class="source-list">
                                    <?php 
                                    if (is_array($sources)) {
                                        foreach ($sources as $source) {
                                            echo '<li>';
                                            echo '<p><strong>Matched Parts:</strong> ' . htmlspecialchars(implode(' ', $source['matched_parts'])) . '</p>';
                                            echo '<p><strong>Similarity:</strong> ' . htmlspecialchars(number_format($source['similarity'], 2)) . '%</p>';
                                            echo '<p><strong>Source URL:</strong> <a href="' . htmlspecialchars($source['url']) . '" target="_blank">' . htmlspecialchars($source['url']) . '</a></p>';
                                            echo '</li>';
                                        }
                                    } else {
                                        echo '<li>No valid sources found.</li>';
                                    }
                                    ?>
                                </ol>
                            </div>
                        </div>
                  
         
            </div>
        <?php else: ?>
            <div class="assessment-details-plagiarize">
                <p class="rubrics-plagiariaze">Plagiarism Analysis</p>
                <p class="plagiarism-unavailable">No plagiarism detected.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="points-below flex flex-col md:flex-row items-center justify-between gap-4 bg-white shadow-[0_4px_20px_rgba(0,0,0,0.05)] rounded-xl p-6 mt-6 transform transition duration-300 hover:scale-105">
    <div class="weighted text-center md:text-left">
        <p class="text-gray-700 text-lg font-medium">
            Your Total Weighted Score: 
            <span style="color:#9EC8B9;">
                <?php echo $overallScore !== null ? htmlspecialchars($overallScore) : '0'; ?>%
            </span>
        </p>
    </div>
    <div class="points text-center md:text-right">
        <p class="text-gray-700 text-lg font-medium">
            Your Equivalent Points: 
            <span style=" color: #9EC8B9;">
                <?php echo ($overallScore / 100) * $result[0]["points_per_item"]; ?> Points
            </span>
        </p>
    </div>
</div>

<script>
    // Function to show the selected section and highlight the active nav link
    function showSection(sectionId, clickedLink) {
        // Hide all content sections
        document.querySelectorAll('.content-section').forEach(function(section) {
            section.classList.remove('active');
        });

        // Show the selected section
        document.getElementById(sectionId).classList.add('active');

        // Remove 'active' class from all navigation links
        document.querySelectorAll('.nav-bar a').forEach(function(link) {
            link.classList.remove('active');
        });

        // Add 'active' class to the clicked link
        clickedLink.classList.add('active');
    }

    // Show the System Assessment section by default
    document.addEventListener('DOMContentLoaded', function() {
        showSection('system-assessment', document.getElementById('nav-system-assessment'));
    });
</script>


           
<?php
// At the top after database connection
$stmt = $conn->prepare("SELECT * FROM essay_evaluations WHERE answer_id = :answer_id");
$stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);
$stmt->execute();
$evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$teacher_comment = !empty($evaluations) ? ($evaluations[0]['teacher_comment'] ?? '') : '';
?>

<!-- In your HTML -->
<div class="comments">
    <?php if (!empty(trim($teacher_comment))): ?>
        <h2>Quiz Creator Comment</h2>
        <div class="comment">
            <p class="comment-text"><?php echo htmlspecialchars($teacher_comment); ?></p>
            <div class="educators">
                <!-- ... instructor info ... -->
            </div>
        </div>
    <?php endif; ?>
</div>
    
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelector('.feedback-container').classList.add('show');
        document.querySelectorAll('.assessment, .comments').forEach(function(el) {
            el.classList.add('show');
        });
    });
</script>
</body>
</html>