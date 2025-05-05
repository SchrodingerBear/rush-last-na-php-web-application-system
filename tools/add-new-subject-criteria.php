<?php

// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once("../classes/connection.php");

// Create an instance of the Database class
$database = new Database();
$pdo = $database->connect(); // Call the connect method to establish the connection

if (!$pdo) {
    die("PDO connection not established.");
}

$creation_id = $_SESSION['creation_id']; // Use creation_id from the session

// Function to insert new criteria
function insertNewCriteria($pdo, $newCriteria) {
    try {
        $stmt = $pdo->prepare("INSERT INTO criteria 
            (criteria_name, advanced_text, proficient_text, needs_improvement_text, warning_text, weight, subject_id, creation_id)
            VALUES (:criteria_name, :advanced_text, :proficient_text, :needs_improvement_text, :warning_text, :weight, :subject_id, :creation_id)");

        $stmt->bindParam(':criteria_name', $newCriteria['criteria_name']);
        $stmt->bindParam(':advanced_text', $newCriteria['advanced_text']);
        $stmt->bindParam(':proficient_text', $newCriteria['proficient_text']);
        $stmt->bindParam(':needs_improvement_text', $newCriteria['needs_improvement_text']);
        $stmt->bindParam(':warning_text', $newCriteria['warning_text']);
        $stmt->bindParam(':weight', $newCriteria['weight']);
        $stmt->bindParam(':subject_id', $newCriteria['subject_id']);
        $stmt->bindParam(':creation_id', $newCriteria['creation_id']);

        if (!$stmt->execute()) {
            throw new Exception("Database error: " . implode(", ", $stmt->errorInfo()));
        }
    } catch (Exception $e) {
        echo "<script>alert('Error inserting new criteria: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}

// Function to update existing criteria
function updateCriteria($pdo, $criteriaData, $creationId) {
    try {
        foreach ($criteriaData as $criteria) {
            $stmt = $pdo->prepare("UPDATE criteria SET 
                criteria_name = :criteria_name, 
                advanced_text = :advanced_text, 
                proficient_text = :proficient_text, 
                needs_improvement_text = :needs_improvement_text, 
                warning_text = :warning_text, 
                weight = :weight 
                WHERE criteria_id = :criteria_id AND creation_id = :creation_id");

            $stmt->bindParam(':criteria_name', $criteria['criteria_name']);
            $stmt->bindParam(':advanced_text', $criteria['advanced_text']);
            $stmt->bindParam(':proficient_text', $criteria['proficient_text']);
            $stmt->bindParam(':needs_improvement_text', $criteria['needs_improvement_text']);
            $stmt->bindParam(':warning_text', $criteria['warning_text']);
            $stmt->bindParam(':weight', $criteria['weight']);
            $stmt->bindParam(':criteria_id', $criteria['criteria_id']);
            $stmt->bindParam(':creation_id', $creationId);

            if (!$stmt->execute()) {
                throw new Exception("Database error: " . implode(", ", $stmt->errorInfo()));
            }
        }
        
    } catch (Exception $e) {
        echo "<script>alert('Error updating data: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}

// Handle form submissions
if (isset($_POST["Submit"])) {
    // Collect and sanitize inputs
    $title = trim($_POST['title']);
    $num_criteria = trim($_POST['num_criteria']);
    
    // Initialize error messages
    $errorMessages = [];

    // Checking for empty fields
    if (empty($title)) {
        $errorMessages[] = 'Error: Subject Title field is empty.';
    }
    if (empty($num_criteria)) {
        $errorMessages[] = 'Error: Number of Criteria field is empty.';
    }

    // Show error messages if any
    if (!empty($errorMessages)) {
        echo "<script>alert('" . implode('\\n', $errorMessages) . "');</script>";
        echo "<script>window.history.back();</script>"; // Go back to the previous page
        exit; // Stop further execution
    }

    // Make sure num_criteria is an integer
    $num_criteria = intval($num_criteria);

    try {
        // Insert data into the subjects table
        $stmt = $pdo->prepare("INSERT INTO subjects (title, num_criteria, creation_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $num_criteria, $creation_id])) {
            $subject_id = $pdo->lastInsertId();

            // Insert default data into the criteria table for new criteria only
            for ($i = 0; $i < $num_criteria; $i++) {
                $stmt = $pdo->prepare("INSERT INTO criteria (subject_id, criteria_name, advanced_text, proficient_text, needs_improvement_text, warning_text, weight, creation_id) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$subject_id, "Criteria " . ($i + 1), '', '', '', '', 0, $creation_id]); // Default values
            }

            // Alert and redirect after successful insertion
            echo "<script>alert('Data added successfully!');</script>";
            echo "<script>window.location.href = '../user/AcademAI-Essay-Rubric-Setting.php';</script>"; // Redirect to the rubric setting page
        } else {
            $errorInfo = implode(", ", $stmt->errorInfo());
            echo "<script>alert('Error inserting subject: $errorInfo');</script>";
            echo "<script>window.history.back();</script>"; // Go back to the previous page
        }
    } catch (PDOException $e) {
        $errorMessage = $e->getMessage();
        echo "<script>alert('Database error: $errorMessage');</script>";
        echo "<script>window.history.back();</script>"; // Go back to the previous page
    }
}

if (isset($_POST["Save"])) {
    $subject_id = $_POST['subject_id'];
    $criteriaIds = $_POST['criteria_id'] ?? []; // Handle if criteria_id is not set
    $criteriaNames = $_POST['criteria_name'];
    $advancedTexts = $_POST['advanced_text'];
    $proficientTexts = $_POST['proficient_text'];
    $needsImprovementTexts = $_POST['needs_improvement_text'];
    $warningTexts = $_POST['warning_text'];
    $weights = $_POST['weight'];

    // Update the subject title
    $title = $_POST['title']; // Get the updated title from the form
    $updateTitleStmt = $pdo->prepare("UPDATE subjects SET title = :title WHERE subject_id = :subject_id AND creation_id = :creation_id");
    $updateTitleStmt->bindParam(':title', $title);
    $updateTitleStmt->bindParam(':subject_id', $subject_id);
    $updateTitleStmt->bindParam(':creation_id', $creation_id);
    $updateTitleStmt->execute();

    // Fetch existing criteria IDs from the database for this subject
    $stmt = $pdo->prepare("SELECT criteria_id FROM criteria WHERE subject_id = :subject_id AND creation_id = :creation_id");
    $stmt->bindParam(':subject_id', $subject_id);
    $stmt->bindParam(':creation_id', $creation_id);
    $stmt->execute();
    $existingCriteriaIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    // Determine which criteria to delete
    $criteriaToDelete = array_diff($existingCriteriaIds, $criteriaIds);
    
    // Delete the removed criteria
    if (!empty($criteriaToDelete)) {
        $deleteStmt = $pdo->prepare("DELETE FROM criteria WHERE criteria_id IN (" . implode(',', array_map('intval', $criteriaToDelete)) . ") AND creation_id = :creation_id");
        $deleteStmt->bindParam(':creation_id', $creation_id);
        $deleteStmt->execute();
    }

    // Prepare criteria data for update and insert
    $criteriaData = [];
    for ($i = 0; $i < count($criteriaNames); $i++) {
        if (isset($criteriaIds[$i]) && !empty($criteriaIds[$i])) {
            // Existing criteria
            $criteriaData[] = [
                'criteria_id' => $criteriaIds[$i],
                'criteria_name' => $criteriaNames[$i],
                'advanced_text' => $advancedTexts[$i],
                'proficient_text' => $proficientTexts[$i],
                'needs_improvement_text' => $needsImprovementTexts[$i],
                'warning_text' => $warningTexts[$i],
                'weight' => $weights[$i]
            ];
        } else {
            // New criteria (handle insert)
            $newCriteria = [
                'criteria_name' => $criteriaNames[$i],
                'advanced_text' => $advancedTexts[$i],
                'proficient_text' => $proficientTexts[$i],
                'needs_improvement_text' => $needsImprovementTexts[$i],
                'warning_text' => $warningTexts[$i],
                'weight' => $weights[$i],
                'subject_id' => $subject_id,
                'creation_id' => $creation_id
            ];
            insertNewCriteria($pdo, $newCriteria); // Call function to insert new criteria
        }
    }

    // Update existing criteria
    updateCriteria($pdo, $criteriaData, $creation_id);
    
    // Update total number of criteria in the subject
    $totalCriteria = count($criteriaNames); // Total criteria count after all updates/inserts
    $updateNumCriteriaStmt = $pdo->prepare("UPDATE subjects SET num_criteria = :num_criteria WHERE subject_id = :subject_id AND creation_id = :creation_id");
    $updateNumCriteriaStmt->bindParam(':num_criteria', $totalCriteria);
    $updateNumCriteriaStmt->bindParam(':subject_id', $subject_id);
    $updateNumCriteriaStmt->bindParam(':creation_id', $creation_id);
    $updateNumCriteriaStmt->execute();

    // Redirect with success alert
    echo "<script>alert('Data updated successfully!'); window.location.href = '../user/AcademAI-Essay-Viewing-Rubric-Setting.php';</script>";
}

// Fetch only relevant subjects and criteria for the current operation
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

$stmt = $pdo->prepare("SELECT subjects.subject_id, subjects.title, criteria.* FROM subjects 
                       LEFT JOIN criteria ON subjects.subject_id = criteria.subject_id 
                       WHERE subjects.creation_id = :creation_id AND (subjects.subject_id = :subject_id OR criteria.criteria_id IS NOT NULL)
                       ORDER BY subjects.subject_id DESC, criteria.criteria_id");
$stmt->bindParam(':creation_id', $creation_id);
$stmt->bindParam(':subject_id', $subject_id);
$stmt->execute();

// Grouping criteria by subject
$groupedCriteria = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $subject_id = $row['subject_id'];

    // If subject not yet added, initialize the structure
    if (!isset($groupedCriteria[$subject_id])) {
        $groupedCriteria[$subject_id] = [
            'title' => $row['title'],
            'criteria' => []
        ];
    }

    // Add criteria to the respective subject
    if ($row['criteria_id'] !== null) { // Only add criteria if it exists
        $groupedCriteria[$subject_id]['criteria'][] = [
            'criteria_id' => $row['criteria_id'],
            'criteria_name' => $row['criteria_name'],
            'advanced_text' => $row['advanced_text'],
            'proficient_text' => $row['proficient_text'],
            'needs_improvement_text' => $row['needs_improvement_text'],
            'warning_text' => $row['warning_text'],
            'weight' => $row['weight']
        ];
    }
}

// Output or use $groupedCriteria to display only relevant criteria for editing

function getCriteriaBySubjectId($subject_id) {
    // Assuming you have a database connection set up as $conn
    $stmt = $conn->prepare("SELECT * FROM criteria WHERE subject_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $criteria = [];
    while ($row = $result->fetch_assoc()) {
        $criteria[] = $row;
    }

    // Return criteria data
    return [
        'title' => getSubjectTitle($subject_id), // Replace with your actual function to get the subject title
        'criteria' => $criteria
    ];
}



?>
