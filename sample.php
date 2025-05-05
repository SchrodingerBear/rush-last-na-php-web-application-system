<?php
    session_start();

    if(!isset($_SESSION['user']) || (isset($_SESSION['user']) && $_SESSION['user'] != 'student')){
        header('location: index.php');
    }
?>

Logged In

<a class="vh-100 d-flex-col" href="user.logout.php">Logout</a>