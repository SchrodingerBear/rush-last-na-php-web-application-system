<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../include/extension_links.php');
include('../classes/connection.php');
session_start();

// Function to get ordinal suffix (1st, 2nd, 3rd, etc.)
function getOrdinalSuffix($num) {
    if ($num % 100 >= 11 && $num % 100 <= 13) {
        return $num . 'th';
    }
    switch ($num % 10) {
        case 1: return $num . 'st';
        case 2: return $num . 'nd';
        case 3: return $num . 'rd';
        default: return $num . 'th';
    }
}

// Default values
$owner_name = "Unknown";
$participants = [];
$quiz_id = null;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$total_pages = 1;
$quiz_total_points = 0;

if (isset($_GET['quiz_id'])) {
    $quiz_id = $_GET['quiz_id'];
    
    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        die("Database connection failed: " . print_r($db->getError(), true));
    }

    try {
        // 1. Fetch quiz creator details and total points
        $quiz_owner = [];
        try {
            $query = "SELECT a.first_name, a.middle_name, a.last_name, q.quiz_total_points_essay
                      FROM quizzes q
                      INNER JOIN academai a ON q.creation_id = a.creation_id
                      WHERE q.quiz_id = :quiz_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception("Failed to fetch quiz owner: " . implode(" ", $stmt->errorInfo()));
            }
            $quiz_owner = $stmt->fetch(PDO::FETCH_ASSOC);
            $quiz_total_points = $quiz_owner['quiz_total_points_essay'] ?? 0;
        } catch (Exception $e) {
            error_log("Quiz owner fetch error: " . $e->getMessage());
            $quiz_owner = false;
        }

        $owner_name = $quiz_owner ? htmlspecialchars(trim(
            $quiz_owner['first_name'] . ' ' . 
            ($quiz_owner['middle_name'] ?? '') . ' ' . 
            $quiz_owner['last_name']
        )) : "Unknown";

        // 2. Get all unique participants with their correct information
        $base_query = "SELECT DISTINCT
                        qp.quiz_taker_id,
                        a.creation_id as student_id,
                        a.first_name, 
                        COALESCE(a.middle_name, '') as middle_name,
                        a.last_name,
                        a.email
                      FROM quiz_participation qp
                      INNER JOIN academai a ON qp.user_id = a.creation_id
                      WHERE qp.quiz_id = :quiz_id
                      GROUP BY qp.quiz_taker_id, a.creation_id, a.first_name, a.middle_name, a.last_name, a.email";

        // Get total count of unique participants
        $count_query = "SELECT COUNT(DISTINCT qp.quiz_taker_id) as total 
                       FROM quiz_participation qp
                       WHERE qp.quiz_id = :quiz_id";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bindParam(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $count_stmt->execute();
        $total_participants = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $total_pages = max(1, ceil($total_participants / $per_page));

        // Get paginated participants
        $participants_query = "$base_query ORDER BY a.last_name, a.first_name LIMIT :offset, :per_page";
        $stmt = $conn->prepare($participants_query);
        $stmt->bindValue(':quiz_id', $quiz_id, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($current_page - 1) * $per_page, PDO::PARAM_INT);
        $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new Exception("Participant query failed: " . implode(" ", $stmt->errorInfo()));
        }
        
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Calculate scores for each participant
        foreach ($participants as $key => $participant) {
            try {
                // Get all answers with evaluation data for this quiz_taker_id
                $answers_query = "SELECT qa.answer_id, q.points_per_item, ee.evaluation_data
                                FROM quiz_answers qa
                                JOIN essay_questions q ON qa.question_id = q.essay_id
                                LEFT JOIN (
                                    SELECT answer_id, evaluation_data 
                                    FROM essay_evaluations 
                                    ORDER BY evaluation_date DESC
                                ) ee ON qa.answer_id = ee.answer_id
                                WHERE qa.quiz_taker_id = :quiz_taker_id
                                AND q.quiz_id = :quiz_id";
                
                $answers_stmt = $conn->prepare($answers_query);
                $answers_stmt->execute([
                    ':quiz_taker_id' => $participant['quiz_taker_id'],
                    ':quiz_id' => $quiz_id
                ]);
                
                $answers = $answers_stmt->fetchAll(PDO::FETCH_ASSOC);

                $totalPoints = 0;
                $totalPossiblePoints = 0;
                $evaluatedAnswers = 0;
                $totalAnswers = count($answers);
                
                foreach ($answers as $answer) {
                    $totalPossiblePoints += $answer['points_per_item'];
                    
                    if (empty($answer['evaluation_data'])) {
                        continue;
                    }
                    
                    $evaluatedAnswers++;
                    $data = json_decode($answer['evaluation_data'], true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue;
                    }

                    // Calculate score percentage
                    $percentage = 0;
                    if (isset($data['evaluation']['evaluation'])) {
                        $evalJson = str_replace(["```json\n", "\n```"], "", $data['evaluation']['evaluation']);
                        $parsedEval = json_decode($evalJson, true);
                        
                        if ($parsedEval) {
                            if (isset($parsedEval['overall_weighted_score'])) {
                                $percentage = $parsedEval['overall_weighted_score'];
                            } 
                            elseif (isset($parsedEval['scores'])) {
                                $percentage = (array_sum($parsedEval['scores']) / (count($parsedEval['scores']) * 100)) * 100;
                            }
                        }
                    }
                    
                    $totalPoints += ($percentage / 100) * $answer['points_per_item'];
                }

                // Store participant's score data
                $participants[$key]['overall_score'] = ($evaluatedAnswers > 0) ? round($totalPoints, 2) : 'Pending';
                $participants[$key]['total_possible_points'] = $totalPossiblePoints;
                $participants[$key]['has_evaluation'] = ($evaluatedAnswers > 0);
                $participants[$key]['evaluated_answers'] = $evaluatedAnswers;
                $participants[$key]['total_answers'] = $totalAnswers;
                $participants[$key]['completion_percentage'] = ($totalAnswers > 0) ? round(($evaluatedAnswers / $totalAnswers) * 100, 0) : 0;

            } catch (Exception $e) {
                error_log("Error processing participant ".$participant['student_id'].": ".$e->getMessage());
                $participants[$key]['overall_score'] = 'Error';
                $participants[$key]['total_possible_points'] = 0;
                $participants[$key]['has_evaluation'] = false;
                $participants[$key]['evaluated_answers'] = 0;
                $participants[$key]['total_answers'] = 0;
                $participants[$key]['completion_percentage'] = 0;
            }
        }

        // Store original positions before sorting
        $original_numbers = range(1, count($participants));
        
        // Sort participants by score (evaluated first, then by score descending)
        usort($participants, function($a, $b) {
            if ($a['has_evaluation'] === $b['has_evaluation']) {
                if ($a['overall_score'] === 'Pending' && $b['overall_score'] === 'Pending') {
                    return strcmp($a['last_name'], $b['last_name']); // Alphabetical sort for pending
                }
                if ($a['overall_score'] === 'Pending') return 1;
                if ($b['overall_score'] === 'Pending') return -1;
                return $b['overall_score'] <=> $a['overall_score'];
            }
            return $b['has_evaluation'] <=> $a['has_evaluation'];
        });

    } catch (Exception $e) {
        error_log("System error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        echo "<div class='alert alert-danger'>System error processing quiz results. Technical details have been logged.</div>";
    }
}

// Track referrer for back button
if (isset($_SERVER['HTTP_REFERER'])) {
    $referrer = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));
    
    if ($referrer === 'AcademAI-Library-Upcoming-View-Card.php' || 
        $referrer === 'AcademAI-Library-Upcoming-People-Join.php') {
        $_SESSION['original_referrer'] = [
            'page' => $referrer,
            'quiz_id' => $_GET['quiz_id'] ?? null
        ];
    }
}


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


// Default values for user profile


?>
<?php
// Add this to the existing PHP file

// Function to update quiz score and feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_score'])) {
    $quiz_taker_id = $_POST['quiz_taker_id'] ?? null;
    $quiz_id = $_POST['quiz_id'] ?? null;
    $new_score = $_POST['new_score'] ?? null;
    $feedback = $_POST['feedback'] ?? '';
    
    if ($quiz_taker_id && $quiz_id && $new_score !== null) {
        $db = new Database();
        $conn = $db->connect();
        
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // First, get all answers for this quiz taker
            $answers_query = "SELECT qa.answer_id, q.points_per_item 
                             FROM quiz_answers qa
                             JOIN essay_questions q ON qa.question_id = q.essay_id
                             WHERE qa.quiz_taker_id = :quiz_taker_id
                             AND q.quiz_id = :quiz_id";
            
            $answers_stmt = $conn->prepare($answers_query);
            $answers_stmt->execute([
                ':quiz_taker_id' => $quiz_taker_id,
                ':quiz_id' => $quiz_id
            ]);
            
            $answers = $answers_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($answers)) {
                $total_points = array_sum(array_column($answers, 'points_per_item'));
                
                // Calculate individual answer scores based on the new total score
                foreach ($answers as $index => $answer) {
                    $answer_proportion = $answer['points_per_item'] / $total_points;
                    $answer_score = $new_score * $answer_proportion;
                    $percentage = ($total_points > 0) ? ($answer_score / $answer['points_per_item']) * 100 : 0;
                    
                    // Create evaluation data
                    $eval_data = [
                        'evaluation' => [
                            'evaluation' => json_encode([
                                'overall_weighted_score' => $percentage,
                                'feedback' => $feedback,
                                'manual_adjustment' => true,
                                'adjusted_date' => date('Y-m-d H:i:s')
                            ])
                        ]
                    ];
                    
                    // Check if evaluation exists
                    $check_query = "SELECT evaluation_id FROM essay_evaluations WHERE answer_id = :answer_id";
                    $check_stmt = $conn->prepare($check_query);
                    $check_stmt->execute([':answer_id' => $answer['answer_id']]);
                    
                    if ($check_stmt->rowCount() > 0) {
                        // Update existing evaluation
                        $update_query = "UPDATE essay_evaluations 
                                         SET evaluation_data = :evaluation_data,
                                             evaluation_date = NOW()
                                         WHERE answer_id = :answer_id";
                        $update_stmt = $conn->prepare($update_query);
                        $update_stmt->execute([
                            ':evaluation_data' => json_encode($eval_data),
                            ':answer_id' => $answer['answer_id']
                        ]);
                    } else {
                        // Insert new evaluation
                        $insert_query = "INSERT INTO essay_evaluations 
                                        (answer_id, evaluation_data, evaluation_date)
                                        VALUES (:answer_id, :evaluation_data, NOW())";
                        $insert_stmt = $conn->prepare($insert_query);
                        $insert_stmt->execute([
                            ':answer_id' => $answer['answer_id'],
                            ':evaluation_data' => json_encode($eval_data)
                        ]);
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                // Set success message
                $_SESSION['message'] = "Score and feedback updated successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                throw new Exception("No answers found for this quiz taker.");
            }
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollBack();
            
            // Set error message
            $_SESSION['message'] = "Error updating score: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
        }
        
        // Redirect to refresh page and show message
        header("Location: " . $_SERVER['PHP_SELF'] . "?quiz_id=" . $quiz_id . (isset($_GET['page']) ? "&page=" . $_GET['page'] : ""));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="stylesheet" href="../css/academAI-student-leaderboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .pagination {
            display: inline-block;
            margin-top: 20px;
        }
        .pagination a {
            color: black;
            float: left;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        .pending {
            color: orange;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .scored {
            color: green;
            font-weight: bold;
        }
        .leaderboard-header {
         
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
      
        .score-display {
            font-weight: bold;
        }
        .completion-status {
            font-size: 0.8em;
            color: #666;
        }
        #leaderboardTable {
            position: relative;
        }
        #leaderboardTable th:nth-child(1),
        #leaderboardTable td:nth-child(1) {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 2;
        }
        #leaderboardTable th:nth-child(2),
        #leaderboardTable td:nth-child(2) {
            position: sticky;
            left: 40px;
            background-color: white;
            z-index: 2;
        }
        .rank-cell {
            font-weight: bold;
        }
        .best-rank {
            color: #4CAF50;
        }
        .worst-rank {
            color: #f44336;
        }
        @media (max-width: 768px) {
            .leaderboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
  

</head>
<body>


      
<!-- Header with Back Button and User Profile -->
<div class="header">
<a href="<?php
        $target = 'AcademAI-Library-Upcoming-View-Card.php';
        $params = [];
        
        if (isset($_SESSION['original_referrer'])) {
            $target = $_SESSION['original_referrer']['page'];
            if (!empty($_SESSION['original_referrer']['quiz_id'])) {
                $params[] = 'quiz_id=' . urlencode($_SESSION['original_referrer']['quiz_id']);
            }
        }

        echo $target . (!empty($params) ? '?' . implode('&', $params) : '');
    ?>" class = "back-btn">
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

<div class = "body">

<!-- Header with Back Button and User Profile -->
<div class = "search-library">
<div class="search-container col-6 ">
    <input type="text" id="searchInput" placeholder="Search..." onkeyup="searchTable()">
    <button type="button" id="searchButton"><i class="fas fa-search"></i></button>
    <div class="filter-container">
        <div class="dropdown">
            <button class="filter-button" id="scoreFilter">Score<i class="fa-solid fa-arrow-down-wide-short"></i></button>
            <div class="dropdown-content">
                <a href="#" onclick="sortTable('score', 'desc')">Highest to Lowest</a>
                <a href="#" onclick="sortTable('score', 'asc')">Lowest to Highest</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="filter-button" id="nameFilter">Name<i class="fa-solid fa-arrow-down-wide-short"></i></button>
            <div class="dropdown-content">
                <a href="#" onclick="sortTable('name', 'asc')">A-Z</a>
                <a href="#" onclick="sortTable('name', 'desc')">Z-A</a>
            </div>
        </div>
    </div>
</div>

<div class="export-buttons col-6">
  <!-- Export to Excel Button -->
  <button onclick="exportToExcel()" id="excel">
    <i class="fas fa-file-excel"></i> Export to Excel
  </button>

  <!-- Export to PDF Button -->
  <button onclick="exportToPDF()" class="pdf">
    <i class="fas fa-file-pdf"></i> Export to PDF
  </button>
</div>
</div>

<div class="LEADERBOARD">
    <div class="leaderboard-header">
        <div class="leaderboard-title"> LEADERBOARD</div>
    </div>
    

    
    <?php if (!$quiz_id): ?>
        <div class="alert alert-danger">No quiz selected. Please access this page through a valid quiz link.</div>
    <?php elseif (empty($participants)): ?>
        <div class="alert alert-info">No participants found for this quiz.</div>
    <?php else: ?>
        <div class="table-responsive leaderboard-table">
            <table class="table table-hover" id="leaderboardTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Rank</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Score</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <?php
// [Previous PHP code remains exactly the same until the table body]
?>

<tbody>
    <?php
    // First, sort a copy of participants by score for ranking
    $sorted_for_ranking = $participants;
    usort($sorted_for_ranking, function($a, $b) {
        if ($a['has_evaluation'] === $b['has_evaluation']) {
            if ($a['overall_score'] === 'Pending' && $b['overall_score'] === 'Pending') {
                return 0;
            }
            if ($a['overall_score'] === 'Pending') return 1;
            if ($b['overall_score'] === 'Pending') return -1;
            return $b['overall_score'] <=> $a['overall_score'];
        }
        return $b['has_evaluation'] <=> $a['has_evaluation'];
    });
    
    // Create a map of quiz_taker_id to rank
    $ranks = [];
    $current_rank = 1;
    $prev_score = null;
    
    foreach ($sorted_for_ranking as $key => $participant) {
        $current_score = ($participant['overall_score'] === 'Pending' || $participant['overall_score'] === 'Error') 
            ? -1 
            : $participant['overall_score'];
        
        if ($current_score !== $prev_score) {
            $current_rank = $key + 1;
        }
        
        $ranks[$participant['quiz_taker_id']] = $current_rank;
        $prev_score = $current_score;
    }
    
    // Display participants in original order with static # and dynamic rank
    foreach ($participants as $key => $participant) {
        $fullName = trim(htmlspecialchars(
            $participant['first_name'] . ' ' . 
            $participant['middle_name'] . ' ' . 
            $participant['last_name']
        ));
        $email = htmlspecialchars($participant['email'] ?? '');
        $score = $participant['overall_score'];
        $totalPossible = $participant['total_possible_points'];
        $student_id = htmlspecialchars($participant['student_id'] ?? '');
        $quiz_taker_id = htmlspecialchars($participant['quiz_taker_id'] ?? '');
        $score_class = ($score === 'Pending') ? 'pending' : (($score === 'Error') ? 'error' : 'scored');
        $rank_value = $ranks[$participant['quiz_taker_id']] ?? ($key + 1);
        
        // Determine rank class for styling
        $rank_class = '';
        if ($rank_value === 1 && $score !== 'Pending' && $score !== 'Error') {
            $rank_class = 'best-rank';
        } elseif ($rank_value === count($participants) && $score !== 'Pending' && $score !== 'Error') {
            $rank_class = 'worst-rank';
        }

        echo "
        <tr data-original-number='".($key + 1)."' data-score='".($score === 'Pending' ? -1 : ($score === 'Error' ? -2 : $score))."' 
            data-name='{$fullName}'>
            <td>".($key + 1)."</td>
            <td class='rank-cell {$rank_class}'>".getOrdinalSuffix($rank_value)."</td>
            <td>{$email}</td>
            <td>{$fullName}</td>
            <td class='{$score_class} score-display'>";
            
        if ($score === 'Pending') {
            echo '<span class="pending">Pending Evaluation</span>';
        } elseif ($score === 'Error') {
            echo '<span class="error">Evaluation Error</span>';
        } else {
            echo number_format($score, 2) . ' / ' . $totalPossible;
            if ($participant['completion_percentage'] < 100) {
                echo ' <span class="completion-status">('.$participant['evaluated_answers'].'/'.$participant['total_answers'].' evaluated)</span>';
            }
        }
        
        echo "</td>
        <td>
            <div class='gap'>
                <a href='AcademAI-Instructors-View-Student-Essay.php?student_id={$student_id}&quiz_taker_id={$quiz_taker_id}&quiz_id={$quiz_id}' class='btn eyebtn btn-sm mr-2'>
                    <i class='fas fa-eye'></i> View
                </a>";
    
    // Only show edit button for evaluated answers
    if ($score !== 'Pending' && $score !== 'Error') {
        // Get feedback from first evaluated answer
        $feedback = '';
        if ($participant['has_evaluation']) {
            try {
                $feedback_query = "SELECT ee.evaluation_data
                                FROM quiz_answers qa
                                JOIN essay_evaluations ee ON qa.answer_id = ee.answer_id
                                WHERE qa.quiz_taker_id = :quiz_taker_id
                                AND qa.quiz_id = :quiz_id
                                ORDER BY ee.evaluation_date DESC
                                LIMIT 1";
                
                $feedback_stmt = $conn->prepare($feedback_query);
                $feedback_stmt->execute([
                    ':quiz_taker_id' => $quiz_taker_id,
                    ':quiz_id' => $quiz_id
                ]);
                
                $eval_data = $feedback_stmt->fetchColumn();
                if ($eval_data) {
                    $data = json_decode($eval_data, true);
                    if ($data && isset($data['evaluation']['evaluation'])) {
                        $evalJson = str_replace(["```json\n", "\n```"], "", $data['evaluation']['evaluation']);
                        $parsedEval = json_decode($evalJson, true);
                        if ($parsedEval && isset($parsedEval['feedback'])) {
                            $feedback = htmlspecialchars($parsedEval['feedback']);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching feedback: " . $e->getMessage());
            }
        }
        
        echo "<a href='javascript:void(0);' class='btn btn-edit btn-sm mx-2' 
                  data-quiz-taker-id='{$quiz_taker_id}'
                  data-student-name='" . htmlspecialchars($fullName) . "'
                  data-current-score='" . ($score === 'Pending' ? 0 : $score) . "'
                  data-max-score='{$totalPossible}'
                  data-feedback='{$feedback}'>
                  <i class='fas fa-edit'></i> Edit
              </a>";
    }
    
    echo "<a href='#' class='btn delbtn btn-sm' onclick='confirmDelete({$student_id}, {$quiz_id}, {$quiz_taker_id})'>
            <i class='fas fa-trash-alt'></i> Delete
        </a>
    </div>
    </td>
    </tr>";
    }
    ?>
    
</tbody>

        </div>
       

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?quiz_id=<?= $quiz_id ?>&page=<?= $current_page - 1 ?>">&laquo; Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?quiz_id=<?= $quiz_id ?>&page=<?= $i ?>" <?= ($i == $current_page) ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="?quiz_id=<?= $quiz_id ?>&page=<?= $current_page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>

</table>

<script>
function getOrdinalSuffix(num) {
    if (num % 100 >= 11 && num % 100 <= 13) {
        return num + "th";
    }
    switch (num % 10) {
        case 1: return num + "st";
        case 2: return num + "nd";
        case 3: return num + "rd";
        default: return num + "th";
    }
}

function searchTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("leaderboardTable");
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        let found = false;
        const td = tr[i].getElementsByTagName("td");

        // Skip first two columns (# and Rank) when searching
        for (let j = 2; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }

        tr[i].style.display = found ? "" : "none";
    }
}

function sortTable(type, order = 'desc') {
    const table = document.getElementById("leaderboardTable");
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    // Sort the rows based on the specified type (score or name)
    rows.sort((a, b) => {
        if (type === 'score') {
            const getScore = (val) => {
                if (val.includes('Pending')) return -1;
                if (val.includes('Error')) return -2;
                return parseFloat(val.split('/')[0]);
            };
            const aScore = getScore(a.cells[4].textContent);
            const bScore = getScore(b.cells[4].textContent);
            return order === 'asc' ? aScore - bScore : bScore - aScore;
        } else if (type === 'name') {
            const aName = a.cells[3].textContent;
            const bName = b.cells[3].textContent;
            return order === 'asc'
                ? aName.localeCompare(bName)
                : bName.localeCompare(aName);
        }
        return 0;
    });

    // Rebuild the table with the sorted rows and updated ranks
    let currentRank = 1;
    let prevScore = null;
    let skipCount = 0;
    
    rows.forEach((row, index) => {
        // Keep the original number (student number) static in the first column
        // (no change needed here as we're not modifying the first column)
        
        // Calculate current score
        const currentScore = row.cells[4].textContent.includes('Pending') ? -1 : 
                           (row.cells[4].textContent.includes('Error')) ? -2 : 
                           parseFloat(row.cells[4].textContent.split('/')[0]);
        
        // Calculate rank - same scores get same rank, next score follows sequentially
        if (currentScore !== prevScore && prevScore !== null) {
            currentRank += skipCount;
            skipCount = 0;
        }
        
        // Update rank cell
        const rankCell = row.cells[1];
        rankCell.textContent = getOrdinalSuffix(currentRank);
        
        // Apply rank styling
        rankCell.className = 'rank-cell';
        if (currentRank === 1 && currentScore !== -1 && currentScore !== -2) {
            rankCell.classList.add('best-rank');
        } else if (index === rows.length - 1 && currentScore !== -1 && currentScore !== -2) {
            rankCell.classList.add('worst-rank');
        }
        
        // Track same scores
        if (currentScore === prevScore) {
            skipCount++;
        } else {
            prevScore = currentScore;
        }
        
        tbody.appendChild(row);  // Re-append the row in the correct order
    });
}

function confirmDelete(studentId, quizId, quizTakerId) {
    if (confirm('Are you sure you want to delete this submission?')) {
        window.location.href = 'delete_submission.php?student_id=' + studentId + '&quiz_id=' + quizId + '&quiz_taker_id=' + quizTakerId;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('#leaderboardTable tbody tr');
    let currentRank = 1;
    let prevScore = null;
    let sameScoreCount = 0;

    rows.forEach((row, index) => {
        const currentScore = row.cells[4].textContent.includes('Pending') ? -1 :
                              row.cells[4].textContent.includes('Error') ? -2 :
                              parseFloat(row.cells[4].textContent.split('/')[0]);

        if (currentScore !== prevScore && prevScore !== null) {
            currentRank += sameScoreCount;
            sameScoreCount = 0;
        }

        row.cells[1].textContent = getOrdinalSuffix(currentRank);
        row.cells[1].className = 'rank-cell';

        if (currentRank === 1 && currentScore >= 0) {
            row.cells[1].classList.add('best-rank');
        } else if (index === rows.length - 1 && currentScore >= 0) {
            row.cells[1].classList.add('worst-rank');
        }

        prevScore = currentScore;
        sameScoreCount++;
    });
});
</script>
</div>
</div>
</div>



<!-- Add this code before the closing </body> tag -->

<!-- Modal for editing scores -->
<div id="editScoreModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Student Score</h2>
        
        <form id="editScoreForm" method="POST" action="">
            <input type="hidden" name="update_score" value="1">
            <input type="hidden" id="modal_quiz_taker_id" name="quiz_taker_id">
            <input type="hidden" id="modal_quiz_id" name="quiz_id" value="<?php echo $quiz_id; ?>">
            
            <div class="form-group">
                <label for="student_name">Student:</label>
                <span id="modal_student_name" class="form-control-static"></span>
            </div>
            
            <div class="form-group">
                <label for="current_score">Current Score:</label>
                <span id="modal_current_score" class="form-control-static"></span>
            </div>
            
            <div class="form-group">
                <label for="new_score">New Score:</label>
                <input type="number" id="new_score" name="new_score" class="form-control" step="0.01" min="0" required>
                <span id="max_score_hint"></span>
            </div>
            
            <div class="form-group">
                <label for="feedback">Feedback Comments:</label>
                <textarea id="feedback" name="feedback" class="form-control" rows="4"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Success/Error message display -->
<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-<?php echo $_SESSION['message_type']; ?>" id="alert-message">
    <?php echo $_SESSION['message']; ?>
</div>
<script>
    setTimeout(function() {
        document.getElementById('alert-message').style.display = 'none';
    }, 5000);
</script>
<?php 
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
endif; 
?>
<style>
/* Add this to the existing CSS styles in the head section */

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 50%;
    max-width: 500px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-control-static {
    display: block;
    padding: 8px 0;
    font-weight: normal;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
}

.btn {
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    margin-left: 10px;
}

.btn-primary {
    background-color: #4CAF50;
    color: white;
}

.btn-secondary {
    background-color: #ccc;
    color: #333;
}

.btn-edit {
    background-color: #3498db;
    color: white;
}

.alert {
    padding: 15px;
    margin: 20px 0;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

/* Make the modal responsive */
@media (max-width: 768px) {
    .modal-content {
        width: 90%;
        margin: 10% auto;
    }
}
    </style>






<!-- Code for downloading the table -->
<!-- SheetJS for Excel -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<!-- jsPDF for PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>


<script>
function exportToExcel() {
    const table = document.getElementById("leaderboardTable");
    const data = [];

    // Extract headers
    const headers = Array.from(table.querySelectorAll("thead th")).map(th => th.innerText.trim());
    data.push(headers);

    // Extract rows
    table.querySelectorAll("tbody tr").forEach(row => {
        const rowData = Array.from(row.querySelectorAll("td")).map(td => td.innerText.trim());
        data.push(rowData);
    });

    const worksheet = XLSX.utils.aoa_to_sheet(data);

    // Force all cells as string to prevent Excel formatting issues
    for (const key in worksheet) {
        if (key[0] === '!') continue;
        worksheet[key].t = 's';
    }

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Leaderboard");

    XLSX.writeFile(workbook, "Leaderboard.xlsx");
}
</script>









<script>
async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    const table = document.getElementById("leaderboardTable");
    
    const headers = Array.from(table.querySelectorAll("thead th")).map(th => th.innerText.trim());
    const body = Array.from(table.querySelectorAll("tbody tr")).map(row =>
        Array.from(row.querySelectorAll("td")).map(td => td.innerText.trim())
    );

    doc.autoTable({
        head: [headers],
        body: body,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [22, 160, 133] },
        margin: { top: 20 }
    });

    doc.save("Leaderboard.pdf");
}
</script>


<script>
// Add this to the existing JavaScript or before the closing </body> tag

document.addEventListener('DOMContentLoaded', function() {
    // Get the modal
    const modal = document.getElementById("editScoreModal");
    
    // Get the <span> element that closes the modal
    const closeBtn = modal.querySelector(".close");
    const cancelBtn = modal.querySelector(".cancel-btn");
    
    // When the user clicks on <span> (x) or Cancel, close the modal
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }
    
    cancelBtn.onclick = function() {
        modal.style.display = "none";
    }
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
    
    // Add event listeners to all edit buttons
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            const quizTakerId = this.getAttribute('data-quiz-taker-id');
            const studentName = this.getAttribute('data-student-name');
            const currentScore = this.getAttribute('data-current-score');
            const maxScore = this.getAttribute('data-max-score');
            const feedback = this.getAttribute('data-feedback') || '';
            
            // Populate modal fields
            document.getElementById('modal_quiz_taker_id').value = quizTakerId;
            document.getElementById('modal_student_name').textContent = studentName;
            document.getElementById('modal_current_score').textContent = currentScore;
            document.getElementById('new_score').value = parseFloat(currentScore);
            document.getElementById('max_score_hint').textContent = `(Max: ${maxScore})`;
            document.getElementById('new_score').max = maxScore;
            document.getElementById('feedback').value = feedback;
            
            // Display the modal
            modal.style.display = "block";
        });
    });
    
    // Form validation
    document.getElementById('editScoreForm').addEventListener('submit', function(e) {
        const newScore = parseFloat(document.getElementById('new_score').value);
        const maxScore = parseFloat(document.getElementById('new_score').max);
        
        if (isNaN(newScore) || newScore < 0) {
            alert('Please enter a valid score greater than or equal to 0.');
            e.preventDefault();
        } else if (newScore > maxScore) {
            if (!confirm(`The score you entered (${newScore}) is greater than the maximum possible score (${maxScore}). Are you sure you want to continue?`)) {
                e.preventDefault();
            }
        }
    });
});

// Function to extract feedback from evaluation data
function extractFeedback(evaluationData) {
    try {
        if (!evaluationData) return '';
        
        const data = JSON.parse(evaluationData);
        if (!data.evaluation || !data.evaluation.evaluation) return '';
        
        const evalJson = data.evaluation.evaluation.replace(/```json\n|\n```/g, '');
        const parsed = JSON.parse(evalJson);
        
        return parsed.feedback || '';
    } catch (e) {
        console.error('Error parsing feedback:', e);
        return '';
    }
}
    </script>
</body>
</html>