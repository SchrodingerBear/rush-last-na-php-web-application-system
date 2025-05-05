<?php
// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the session variable is set and user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo "Please log in.";
    exit;
}

require_once '../classes/account.class.php';

$user = new Account();

if (!isset($_SESSION['creation_id'])) {
    echo "Session ID not set.";
    exit;
}

$user->creation_id = $_SESSION['creation_id'];
$userDetails = $user->getUserDetails();

if ($userDetails) {
    $first_name = htmlspecialchars($userDetails['first_name']);
    $middle_name = htmlspecialchars($userDetails['middle_name']);
    $last_name = htmlspecialchars($userDetails['last_name']);
} else {
    $first_name = $middle_name = $last_name = "Unknown";
}
?>
