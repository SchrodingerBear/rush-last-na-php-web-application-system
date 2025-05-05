<?php
/**
 * Modified save_scores.php
 * 
 * This implementation calculates the overall score as a SUM of all criteria scores,
 * rather than an average or weighted average.
 */

// Start output buffering to prevent accidental output
ob_start();

// Set proper JSON header
header("Content-Type: application/json; charset=UTF-8");

// Ensure errors don't show in output but log them
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Clean any previous output to ensure clean JSON response
while (ob_get_level()) ob_end_clean();

try {
    // Log incoming request for debugging
    error_log("Score update request received: " . file_get_contents('php://input'));
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method", 405);
    }

    // Get input data
    $jsonInput = file_get_contents('php://input');
    if (empty($jsonInput)) {
        throw new Exception("No input data received", 400);
    }

    $input = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON format: " . json_last_error_msg(), 400);
    }

    // Validate required fields
    if (!isset($input['answer_id']) || !isset($input['scores']) || empty($input['scores'])) {
        throw new Exception("Missing required fields", 400);
    }

    // Database connection
    require_once('../classes/connection.php');
    $db = new Database();
    $conn = $db->connect();

    // Begin transaction for data consistency
    $conn->beginTransaction();

    // Fetch existing evaluation data
    $stmt = $conn->prepare("SELECT evaluation_data FROM essay_evaluations WHERE answer_id = :answer_id");
    $stmt->bindValue(':answer_id', $input['answer_id'], PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new Exception("Database query failed: " . implode(", ", $stmt->errorInfo()), 500);
    }

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        throw new Exception("Evaluation not found for answer_id: " . $input['answer_id'], 404);
    }

    // Decode evaluation data
    $evaluationData = json_decode($result['evaluation_data'], true);
    if (!$evaluationData || !isset($evaluationData['evaluation']['evaluation'])) {
        throw new Exception("Invalid evaluation format in database", 500);
    }

    // Process evaluation JSON - handle both formats that might exist
    $evaluationJson = $evaluationData['evaluation']['evaluation'];
    
    // Remove any markdown code blocks if present
    $evaluationJson = preg_replace(['/```json\n/', '/\n```/', '/```json/', '/```/'], '', $evaluationJson);
    
    // Attempt to decode cleaned JSON
    $parsedEvaluation = json_decode($evaluationJson, true);
    if (!$parsedEvaluation) {
        throw new Exception("Failed to parse evaluation JSON: " . json_last_error_msg(), 500);
    }

    // IMPROVEMENT: Clean up duplicate criteria entries
    $criteriaToKeep = [];
    foreach ($parsedEvaluation['criteria_scores'] as $key => $value) {
        // Keep only criteria with proper structure (feedback, suggestions)
        if (isset($value['feedback']) && isset($value['suggestions'])) {
            $criteriaToKeep[$key] = $value;
        }
    }
    
    // Replace with cleaned data
    $parsedEvaluation['criteria_scores'] = $criteriaToKeep;

    // Update scores with proper criteria matching
    $scoresUpdated = false;
    
    foreach ($input['scores'] as $inputScore) {
        if (!isset($inputScore['criterion']) || !isset($inputScore['score'])) {
            continue; // Skip invalid entries
        }
        
        $criterion = trim($inputScore['criterion']);
        $score = (float)$inputScore['score'];
        
        // IMPROVED CRITERIA MATCHING:
        // 1. Try exact match first
        $matchFound = false;
        
        if (isset($parsedEvaluation['criteria_scores'][$criterion])) {
            $parsedEvaluation['criteria_scores'][$criterion]['score'] = $score;
            $matchFound = true;
            $scoresUpdated = true;
            
            // Log successful match for debugging
            error_log("Exact match found for criterion: $criterion");
        } else {
            // 2. Try fuzzy match if exact match fails
            $bestMatch = null;
            $bestMatchScore = 0;
            
            foreach (array_keys($parsedEvaluation['criteria_scores']) as $existingCriterion) {
                // Normalize for comparison
                $normalizedExisting = strtolower(preg_replace('/[^a-z0-9]/i', '', $existingCriterion));
                $normalizedCriterion = strtolower(preg_replace('/[^a-z0-9]/i', '', $criterion));
                
                // Simple similarity check
                if (strpos($normalizedExisting, $normalizedCriterion) !== false ||
                    strpos($normalizedCriterion, $normalizedExisting) !== false) {
                    
                    // Basic similarity score (improve this for better matching)
                    $similarity = similar_text($normalizedExisting, $normalizedCriterion);
                    
                    if ($similarity > $bestMatchScore) {
                        $bestMatch = $existingCriterion;
                        $bestMatchScore = $similarity;
                    }
                }
            }
            
            if ($bestMatch) {
                $parsedEvaluation['criteria_scores'][$bestMatch]['score'] = $score;
                $matchFound = true;
                $scoresUpdated = true;
                
                // Log fuzzy match for debugging
                error_log("Fuzzy match found for criterion: $criterion -> $bestMatch");
            }
        }
        
        if (!$matchFound) {
            // Log unmatched criteria for debugging
            error_log("No match found for criterion: $criterion");
        }
    }
    
    // Only proceed if we actually have scores
    if (!$scoresUpdated) {
        throw new Exception("No criteria could be matched to update scores", 400);
    }

    // MODIFIED: Calculate overall score as the SUM of all criteria scores
    $totalScore = 0;
    
    foreach ($parsedEvaluation['criteria_scores'] as $criterion => $data) {
        if (isset($data['score'])) {
            $totalScore += (float)$data['score'];
        }
    }
    
    // Update the overall score with the SUM (not average)
    $parsedEvaluation['overall_weighted_score'] = number_format($totalScore, 2, '.', '');
    
    // Log the calculation for debugging
    error_log("Score calculation: SUM of all scores = $totalScore");

    // Update evaluation data with correctly formatted JSON
    // IMPORTANT: Restore the code blocks format that the system expects!
    $evaluationData['evaluation']['evaluation'] = "```json\n" . 
        json_encode($parsedEvaluation, JSON_PRETTY_PRINT) . 
        "\n```";

    // Save to database
    $updateStmt = $conn->prepare("UPDATE essay_evaluations SET 
                                  evaluation_data = :data,
                                  overall_score = :overall_score
                                  WHERE answer_id = :answer_id");
                                  
    $updateData = json_encode($evaluationData);
    $overallScoreForDb = $parsedEvaluation['overall_weighted_score'];
    
    $updateStmt->bindValue(':data', $updateData);
    $updateStmt->bindValue(':overall_score', $overallScoreForDb);
    $updateStmt->bindValue(':answer_id', $input['answer_id'], PDO::PARAM_INT);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update evaluation: " . implode(", ", $updateStmt->errorInfo()), 500);
    }

    // Commit the transaction
    $conn->commit();

    // Successful response
    echo json_encode([
        'success' => true,
        'total_score' => $parsedEvaluation['overall_weighted_score'],
        'message' => 'Scores updated successfully!'
    ]);

} catch (Throwable $e) {
    // Rollback transaction if needed
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Log error details
    error_log("Score Update Error: " . $e->getMessage() . " in " . $e->getFile() . " line " . $e->getLine());
    
    // Return clean error response
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} finally {
    // Ensure no other output
    exit();
}