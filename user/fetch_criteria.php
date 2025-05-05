<?php
include('../classes/connection.php');
header('Content-Type: application/json');

if (isset($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    
    // Connect to the database
    $db = new Database();
    $conn = $db->connect();
    
    // Fetch criteria for the given subject_id
    $sql = "SELECT data FROM rubrics WHERE subject_id = :subject_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['data'])) {
        // If data is already stored as a JSON string in the database
        echo $result['data'];
    } else {
        // If you need to build the JSON structure from individual rows
        // This is an example - adjust based on your database structure
        $sql = "SELECT criteria_name, advanced_text, proficient_text, needs_improvement_text, warning_text, weight 
                FROM rubric_criteria 
                WHERE subject_id = :subject_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
        $stmt->execute();
        $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $output = [
            'headers' => ['Advanced (5)', 'Proficient (4)', 'Needs Improvement (3)', 'Warning (2)', 'Weight %'],
            'rows' => []
        ];
        
        foreach ($criteria as $criterion) {
            $output['rows'][] = [
                'criteria' => $criterion['criteria_name'],
                'cells' => [
                    $criterion['advanced_text'],
                    $criterion['proficient_text'],
                    $criterion['needs_improvement_text'],
                    $criterion['warning_text'],
                    $criterion['weight']
                ]
            ];
        }
        
        echo json_encode($output);
    }
} else {
    echo json_encode([
        'headers' => [],
        'rows' => []
    ]);
}
?>