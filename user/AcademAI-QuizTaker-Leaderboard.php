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
    // Default fallback page
    $target = 'AcademAI-Upcoming-People-Join.php';
    $params = [];
    
    // First try to get quiz_id from current URL
    $quiz_id = null;
    if (isset($_GET['quiz_id']) && is_numeric($_GET['quiz_id'])) {
        $quiz_id = (int)$_GET['quiz_id'];
    }
    // Then try session as fallback
    elseif (isset($_SESSION['original_referrer']['quiz_id']) && is_numeric($_SESSION['original_referrer']['quiz_id'])) {
        $quiz_id = (int)$_SESSION['original_referrer']['quiz_id'];
    }
    
    // Only add quiz_id if we have a valid one
    if ($quiz_id !== null && $quiz_id > 0) {
        $params['quiz_id'] = $quiz_id;
    }

    // Build the URL safely
    $url = $target;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
?>" class="back-btn">
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

<div class = "search">
<div class="search-container ">
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
</div>

<div class="LEADERBOARD">
    <div class="leaderboard-header">
        <div class="leaderboard-title">LEADERBOARD</div>
       
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
            echo '<span class="pending">----</span>';
        } elseif ($score === 'Error') {
            echo '<span class="error">Evaluation Error</span>';
        } else {
            echo number_format($score, 2) . ' / ' . $totalPossible;
            if ($participant['completion_percentage'] < 100) {
                echo ' <span class="completion-status">('.$participant['evaluated_answers'].'/'.$participant['total_answers'].' evaluated)</span>';
            }
        }
        
      
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


 <style>
    
</style>
</body>
</html>