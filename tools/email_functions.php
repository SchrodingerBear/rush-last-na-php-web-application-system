<?php
// tools/email_functions.php
require_once 'vendor/autoload.php'; // If using PHPMailer

function sendVerificationEmail($email, $verificationCode) {
    // Create the verification link
    $verificationLink = "https://academai.xscpry.com/verify.php?code=" . urlencode($verificationCode);
    
    // Email subject and body
    $subject = "Verify Your AcademAI Account";
    $message = "
        <html>
        <head>
            <title>Verify Your AcademAI Account</title>
        </head>
        <body>
            <h2>Welcome to AcademAI!</h2>
            <p>Please click the link below to verify your email address:</p>
            <p><a href='$verificationLink'>Verify My Account</a></p>
            <p>If you didn't create an account with us, please ignore this email.</p>
        </body>
        </html>
    ";
    
    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: AcademAI <noreply@academai.xscpry.com>" . "\r\n";
    
    // Send the email
    return mail($email, $subject, $message, $headers);
}