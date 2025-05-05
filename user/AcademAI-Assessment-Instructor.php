
<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<?php
    require_once('../include/extension_links.php');
    $answer_id= $_GET["answer_id"];
    $quiz_id= $_GET["quiz_id"];
?>


<?php
require_once('../include/extension_links.php');
include('../classes/connection.php');
session_start();

// Check if rubric_id is present in the URL
if (isset($_GET['rubric_id'])) {
    $rubric_id = $_GET['rubric_id'];

    // Connect to the database
    $db = new Database();
    $conn = $db->connect();

    try {
        // Step 1: Find the subject_id associated with this rubric_id
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

            // Step 2: Get criteria related to this subject_id
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
        $quiz_id = null;
    }
}

?>
<?php
// Correct evaluation fetch order
$stmt = $conn->prepare("SELECT * FROM essay_evaluations WHERE answer_id = :answer_id");
$stmt->bindParam(':answer_id', $answer_id, PDO::PARAM_INT);
$stmt->execute();
$evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$totalWeightedScore = 0;
$totalWeights = 0;
$equivalentPoints = 0;
$overallScore = 0;
$generalAssessment = [];
$existing_comment = '';

if (!empty($evaluations)) {
    $evaluationData = json_decode($evaluations[0]['evaluation_data'], true);
    $existing_comment = $evaluations[0]['teacher_comment'] ?? '';

    if (isset($evaluationData['evaluation']['evaluation'])) {
        $evaluationJson = str_replace(["```json\n", "\n```"], "", $evaluationData['evaluation']['evaluation']);
        $parsedEvaluation = json_decode($evaluationJson, true);

        if ($parsedEvaluation) {
            // Calculate from criteria scores if available
            if (isset($parsedEvaluation['criteria_scores'])) {
                foreach ($parsedEvaluation['criteria_scores'] as $criteria) {
                    $weight = $criteria['weight'] ?? 1;
                    $score = $criteria['score'] ?? 0;
                    $totalWeightedScore += $score * $weight;
                    $totalWeights += $weight;
                }
                
                if ($totalWeights > 0) {
                    $overallScore = number_format($totalWeightedScore / $totalWeights, 2);
                }
            }
            
            // Fallback to stored score if calculation not possible
            if ($overallScore == 0 && isset($parsedEvaluation['overall_weighted_score'])) {
                $overallScore = number_format($parsedEvaluation['overall_weighted_score'], 2);
            }

            $generalAssessment = $parsedEvaluation['general_assessment'] ?? [];
        }
    }

    // Get points per item fresh from database
    $stmt1 = $conn->prepare("SELECT points_per_item FROM essay_questions WHERE quiz_id = :quiz_id");
    $stmt1->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
    $stmt1->execute();
    $quiz_data = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    $equivalentPoints = number_format(($overallScore / 100) * ($quiz_data['points_per_item'] ?? 0), 2);
}
?>

<?php
// At the top of your PHP script, make sure you're getting all required parameters
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : '';
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$quiz_taker_id = isset($_GET['quiz_taker_id']) ? $_GET['quiz_taker_id'] : '';


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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/assessment.css">   
    <title></title>
</head>
<body>

<div class="asessment-1">


<!-- Header with Back Button and User Profile -->
<div class="header">

<a href="<?php echo 'AcademAI-Instructors-View-Student-Essay.php?' . 
              'quiz_id=' . urlencode($quiz_id) . '&' . 
              'student_id=' . urlencode($student_id) . '&' . 
              'quiz_taker_id=' . urlencode($quiz_taker_id); ?>" 
   class="back-btn">
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
        // Get the rubric data
        $rubricQuery = "SELECT data, id FROM rubrics WHERE subject_id = :rubric_id";
        $rubricStmt = $conn->prepare($rubricQuery);
        $rubricStmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
        $rubricStmt->execute();
        $rubricData = $rubricStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rubricData) {
            $criteriaData = json_decode($rubricData['data'], true);
            
            if ($criteriaData && isset($criteriaData['headers']) && isset($criteriaData['rows'])): ?>
                <table class="table table-hover">
                    <thead class="criteria-heading" id="criteria-heading">
                        <tr>
                            <th scope="col">Criteria</th>
                            <?php foreach ($criteriaData['headers'] as $header): ?>
                                <th scope="col"><?php echo htmlspecialchars($header); ?></th>
                            <?php endforeach; ?>
                            <th scope="col">Score</th>
                        </tr>
                    </thead>



                    <tbody id="criteria-table-body" class="predefined-criteria">
    <?php 
foreach ($criteriaData['rows'] as $index => $row):  
        $criterionName = trim($row['criteria']);
        $weight = $criteria[$index]['weight'] ?? 1;

        $score = 'N/A';
        
        if ($parsedEvaluation && !empty($parsedEvaluation['criteria_scores'])) {
            // Improved normalization for comparison
            $normalizedCriterion = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($criterionName));
            
            foreach ($parsedEvaluation['criteria_scores'] as $evalKey => $evalData) {
                // Normalize evaluation key the same way
                $normalizedEvalKey = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($evalKey));
                
                // Check for exact match first
                if ($normalizedEvalKey === $normalizedCriterion) {
                    $score = isset($evalData['score']) ? number_format(floatval($evalData['score']), 1) : 'N/A';
                    break;
                }
                
                // Additional check for contained matches (e.g., "grammar" vs "grammar & spelling")
                if (strpos($normalizedEvalKey, $normalizedCriterion) !== false || 
                    strpos($normalizedCriterion, $normalizedEvalKey) !== false) {
                    $score = isset($evalData['score']) ? number_format(floatval($evalData['score']), 1) : 'N/A';
                    break;
                }
            }
        }
    ?>
<tr data-weight="<?= htmlspecialchars($weight) ?>">
        <td><?php echo htmlspecialchars($criterionName); ?></td>
        <?php foreach ($row['cells'] as $cell): ?>
            <td><?php echo htmlspecialchars($cell); ?></td>
        <?php endforeach; ?>
        <td class="score-cell">
            <input type="number" 
                   value="<?php echo htmlspecialchars($score); ?>" 
                   step="0.1" 
                   min="1" 
                   max="<?php echo htmlspecialchars($cell); ?>">
        </td>
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

        <div class="edibtn-score mt-4" >
           
           
           <button type="button" class="edit-criteria-btn"><span class="material-icons">save</span>Save new Score</button>

           </div>

    </div>
</head>
<body>

<div class="asess">

   



 



<div class="points-below">
    <div class="weighted">
        <p>Total Weighted Score: <?php echo $overallScore !== null ? htmlspecialchars($overallScore) : '0'; ?>%</p>
    </div>
    <div class="points">
        <p>Equivalent Points: <?php echo ($overallScore / 100) * $result[0]["points_per_item"]; ?> Points</p>
    </div>
</div>




<!-- Move the score saving script AFTER the handleStatus function definition -->
<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener to the Save new Score button
    document.querySelector('.edit-criteria-btn').addEventListener('click', saveScores);
});

// Status message handler
function handleStatus(message, isSuccess) {
    // Create status element if it doesn't exist
    let statusElement = document.getElementById('comment-status');
    if (!statusElement) {
        statusElement = document.createElement('div');
        statusElement.id = 'comment-status';
        statusElement.style.padding = '10px';
        statusElement.style.marginTop = '10px';
        statusElement.style.borderRadius = '5px';
        document.querySelector('.edibtn-score').appendChild(statusElement);
    }
    
    statusElement.style.display = 'block';
    statusElement.style.backgroundColor = isSuccess ? '#d4edda' : '#f8d7da';
    statusElement.style.color = isSuccess ? '#155724' : '#721c24';
    statusElement.style.border = isSuccess ? '1px solid #c3e6cb' : '1px solid #f5c6cb';
    statusElement.textContent = message;
    
    if (isSuccess) {
        setTimeout(() => {
            statusElement.style.display = 'none';
        }, 3000);
    }
}

// Score saving functionality
async function saveScores() {
    try {
        const rows = document.querySelectorAll('#criteria-table-body tr');
        const scores = [];
        let isValid = true;
        
        // Clear previous validation errors
        rows.forEach(row => {
            const input = row.querySelector('input');
            input.style.border = '';
        });
        
        // Collect scores
        rows.forEach((row) => {
            const criterion = row.querySelector('td:first-child').textContent.trim();
            const input = row.querySelector('input');
            const weight = parseFloat(row.getAttribute('data-weight')) || 1;
         
            let score = parseFloat(input.value);
            
            // Validate score
            if (isNaN(score) || score < 1 || score > input.getAttribute('max')) {
                input.style.border = '2px solid red';
                handleStatus(`Invalid score for "${criterion}". Must be between 1-${input.getAttribute('max')}.`, false);
                isValid = false;
                return;
            }
            
            scores.push({
                criterion: criterion,
                score: score,
                weight: weight
            });
        });

        if (!isValid) return;
        
        // Get answer_id from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const answer_id = urlParams.get('answer_id');
        
        if (!answer_id) {
            handleStatus('Missing answer ID parameter', false);
            return;
        }

        // Display loading state
        handleStatus('Saving scores...', true);
        
        // Send data to server
        const response = await fetch('save_scores.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                answer_id: answer_id,
                scores: scores
            })
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server error: ${response.status} - ${errorText}`);
        }

        const result = await response.json();
        
        if (result.success) {
            // Update displayed total weighted score
            const weightedScoreElement = document.querySelector('.weighted p');
            location.reload();
            if (weightedScoreElement) {
                weightedScoreElement.textContent = `Total Weighted Score: ${result.total_score}%`;
            }
            
            // Update equivalent points if present
            const pointsElement = document.querySelector('.points p');
            if (pointsElement) {
                const pointsPerItem = document.querySelector('.points p').textContent.match(/\d+(\.\d+)?/);
                if (pointsPerItem && pointsPerItem[0]) {
                    const equivalentPoints = (parseFloat(result.total_score) / 100) * parseFloat(pointsPerItem[0]);
                    pointsElement.textContent = `Equivalent Points: ${equivalentPoints.toFixed(2)} Points`;
                }
            }
            
            handleStatus('Scores saved successfully!', true);
        } else {
            handleStatus(result.message || 'Failed to save scores', false);
        }
    } catch (error) {
        console.error('Error saving scores:', error);
        handleStatus('Error: ' + error.message, false);
    }
}
</script>

</body>
</html>





