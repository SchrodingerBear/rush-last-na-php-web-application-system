<?php
// File: api_proxy.php
// This file serves as a proxy for the Flask API to avoid CORS issues

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers to allow CORS
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Only POST requests are allowed']);
    exit;
}

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate request data
if (!$data || !isset($data['essay']) || !isset($data['rubrics_criteria'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Missing required fields: essay and/or rubrics_criteria']);
    exit;
}

// Add row and column count parameters if provided
if (isset($data['row_count']) && is_numeric($data['row_count'])) {
    $data['essay'] .= "\nrow_count: " . $data['row_count'];
}

if (isset($data['column_count']) && is_numeric($data['column_count'])) {
    $data['essay'] .= "\ncolumn_count: " . $data['column_count'];
}

// Set up cURL request to the Flask API
$ch = curl_init('https://olraceirdna.pythonanywhere.com/evaluate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set timeout to 30 seconds

// Execute the cURL request
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'API request failed: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

// Check if response is valid JSON
$responseData = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // If not valid JSON, try to extract JSON from the text
    preg_match('/\{[\s\S]*"headers"[\s\S]*"rows"[\s\S]*\}/', $response, $matches);
    if (!empty($matches[0])) {
        $jsonStr = $matches[0];
        // Try to parse the extracted JSON
        $extractedJson = json_decode($jsonStr, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Return the extracted and parsed JSON
            echo json_encode(['evaluation' => $extractedJson]);
            exit;
        }
    }
    
    // If extraction failed too, return original response
    http_response_code($statusCode);
    echo $response;
} else {
    // Response is already valid JSON, return it
    http_response_code($statusCode);
    echo $response;
}
?>