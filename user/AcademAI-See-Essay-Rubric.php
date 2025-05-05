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
        // Get the rubric data directly using the rubric_id
        $rubricQuery = "SELECT data, title FROM rubrics WHERE subject_id = :rubric_id";
        $rubricStmt = $conn->prepare($rubricQuery);
        $rubricStmt->bindParam(':rubric_id', $rubric_id, PDO::PARAM_INT);
        $rubricStmt->execute();
        $rubricData = $rubricStmt->fetch(PDO::FETCH_ASSOC);

        if ($rubricData) {
            // Decode the JSON data
            $criteriaData = json_decode($rubricData['data'], true);
            $subject_title = $rubricData['title'] ?? "Rubric";
            
            if ($criteriaData && isset($criteriaData['headers']) && isset($criteriaData['rows'])) {
                $headers = $criteriaData['headers'];
                $rows = $criteriaData['rows'];
            } else {
                $headers = [];
                $rows = [];
                $error = "Invalid rubric data format.";
            }
        } else {
            $error = "No rubric found with the specified ID.";
            $subject_title = "No Title Found";
        }
    } catch (Exception $e) {
        $error = "Error loading rubric: " . $e->getMessage();
        $subject_title = "Error";
    }
}
?>

<?php
$quiz_id = isset($_GET['quiz_id']) ? $_GET['quiz_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/essay_set_rubric_setting.css">
    <title>Essay Rubrics</title>
</head>
<body>


<div class="rubric" id="rubricseeessay">
    
    <div class="rubric-table">
        <h1 class="criteria-title"><?php echo htmlspecialchars($subject_title); ?></h1>
        
        <?php if (isset($error)): ?>
            <p><?php echo htmlspecialchars($error); ?></p>
        <?php elseif (!empty($headers) && !empty($rows)): ?>
            <table class="table table-hover table-bordered">
                <thead class="criteria-heading" id="criteria-heading">
                    <tr>
                        <th scope="col">Criteria</th>
                        <?php foreach ($headers as $header): ?>
                            <th scope="col"><?php echo htmlspecialchars($header); ?></th>
                        <?php endforeach; ?>
                       
                    </tr>
                </thead>
                <tbody id="criteria-table-body" class="predefined-criteria">
                    <?php foreach ($rows as $row): ?>
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
            <p>No rubrics found for this essay.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>