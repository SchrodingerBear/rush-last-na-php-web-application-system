<?php
/**
 * JSON Evaluation Structure Fixer
 * 
 * This script corrects issues with evaluation JSON structure that prevent score updates
 * from being properly reflected in the UI and database.
 */

// Sample function to fix evaluation JSON data
function fixEvaluationJson($jsonString) {
    // Step 1: Extract and parse the evaluation JSON
    $pattern = '/```json\s*(.*?)\s*```/s';
    if (preg_match($pattern, $jsonString, $matches)) {
        $evaluationJson = $matches[1];
    } else {
        $evaluationJson = $jsonString;
    }
    
    $data = json_decode($evaluationJson, true);
    if (!$data) {
        return [
            'success' => false,
            'message' => 'Failed to parse JSON: ' . json_last_error_msg(),
            'original' => $jsonString
        ];
    }
    
    // Step 2: Remove duplicate criteria (ones without feedback)
    $criteriaToKeep = [];
    $duplicateCriteria = [];
    
    foreach ($data['criteria_scores'] as $key => $value) {
        // Keep only criteria with proper structure (feedback, suggestions)
        if (isset($value['feedback']) && isset($value['suggestions'])) {
            $criteriaToKeep[$key] = $value;
        } else {
            $duplicateCriteria[] = $key;
        }
    }
    
    // Step 3: Replace the criteria scores with cleaned data
    $data['criteria_scores'] = $criteriaToKeep;
    
    // Step 4: Recalculate the weighted score properly
    $totalWeightedScore = 0;
    $totalWeight = 0;
    
    foreach ($criteriaToKeep as $key => $criteria) {
        // Extract weight from key or use default
        $weight = 1;
        if (preg_match('/\(Weight:\s*(\d+(\.\d+)?)%\)/', $key, $weightMatch)) {
            $weight = floatval($weightMatch[1]) / 100;
        }
        
        $score = floatval($criteria['score']);
        $totalWeightedScore += $score * $weight;
        $totalWeight += $weight;
    }
    
    // Calculate new overall score
    if ($totalWeight > 0) {
        $data['overall_weighted_score'] = number_format($totalWeightedScore / $totalWeight, 2);
    }
    
    // Step 5: Format the fixed JSON correctly for the database
    $fixedJson = json_encode($data, JSON_PRETTY_PRINT);
    $formattedJson = "```json\n" . $fixedJson . "\n```";
    
    return [
        'success' => true,
        'message' => 'JSON structure fixed successfully',
        'removed_criteria' => $duplicateCriteria,
        'fixed_json' => $formattedJson,
        'new_score' => $data['overall_weighted_score']
    ];
}

// Function to debug the score update process
function debugScoreUpdate($evaluationJson, $submittedScores) {
    // Parse the evaluation JSON
    $pattern = '/```json\s*(.*?)\s*```/s';
    if (preg_match($pattern, $evaluationJson, $matches)) {
        $jsonContent = $matches[1];
    } else {
        $jsonContent = $evaluationJson;
    }
    
    $data = json_decode($jsonContent, true);
    if (!$data) {
        return [
            'success' => false,
            'message' => 'Failed to parse evaluation JSON: ' . json_last_error_msg()
        ];
    }
    
    // Map submitted scores to criteria
    $matchedScores = [];
    $unmatchedScores = [];
    
    foreach ($submittedScores as $score) {
        $criterion = $score['criterion'];
        $scoreValue = $score['score'];
        $weight = $score['weight'] ?? 1;
        
        $matched = false;
        
        // Try to find exact match
        if (isset($data['criteria_scores'][$criterion])) {
            $matchedScores[$criterion] = [
                'old_score' => $data['criteria_scores'][$criterion]['score'],
                'new_score' => $scoreValue,
                'matched_type' => 'exact'
            ];
            $matched = true;
        } else {
            // Try to find fuzzy match
            foreach (array_keys($data['criteria_scores']) as $existingCriterion) {
                // Normalize for comparison
                $normalizedExisting = strtolower(preg_replace('/[^a-z0-9]/i', '', $existingCriterion));
                $normalizedCriterion = strtolower(preg_replace('/[^a-z0-9]/i', '', $criterion));
                
                if (strpos($normalizedExisting, $normalizedCriterion) !== false ||
                    strpos($normalizedCriterion, $normalizedExisting) !== false) {
                    $matchedScores[$criterion] = [
                        'matched_to' => $existingCriterion,
                        'old_score' => $data['criteria_scores'][$existingCriterion]['score'],
                        'new_score' => $scoreValue,
                        'matched_type' => 'fuzzy'
                    ];
                    $matched = true;
                    break;
                }
            }
        }
        
        if (!$matched) {
            $unmatchedScores[] = [
                'criterion' => $criterion,
                'score' => $scoreValue,
                'weight' => $weight
            ];
        }
    }
    
    return [
        'success' => true,
        'matched_scores' => $matchedScores,
        'unmatched_scores' => $unmatchedScores,
        'total_criteria_in_json' => count($data['criteria_scores']),
        'total_submitted_scores' => count($submittedScores),
        'current_weighted_score' => $data['overall_weighted_score'] ?? 'not set'
    ];
}

/**
 * Instructions:
 * 
 * 1. To fix your evaluation JSON structure:
 *    - Add this code to a new PHP file named "fix_evaluation_json.php"
 *    - Modify save_scores.php to call fixEvaluationJson() before updating the database
 * 
 * 2. To debug score submission issues:
 *    - Use the debugScoreUpdate() function temporarily to log what's happening
 *    - Check if submitted criteria names match those in your JSON structure
 * 
 * 3. Key implementation steps:
 *    - Remove duplicate criteria entries in your JSON
 *    - Ensure proper calculation of weighted scores
 *    - Fix JSON formatting for database storage
 */