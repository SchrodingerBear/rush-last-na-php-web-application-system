<?php
session_start(); // Start the session

// Check if the endDate and endTime are set in the POST request
if (isset($_POST['endDate']) && isset($_POST['endTime'])) {
    // Sanitize and store the values in session variables
    $_SESSION['endDate'] = htmlspecialchars($_POST['endDate']);
    $_SESSION['endTime'] = htmlspecialchars($_POST['endTime']);
    
    // Optional: You can send a response back
    echo json_encode(['status' => 'success', 'message' => 'End Date and Time saved successfully.']);
} else {
    // Optional: Handle the case where values are not set
    echo json_encode(['status' => 'error', 'message' => 'End Date or Time not set.']);
}
?>
