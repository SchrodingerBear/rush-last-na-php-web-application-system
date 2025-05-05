<?php
require_once 'classes/user.class.php';
require_once 'tools/functions.php';

$verificationSuccess = false;
$verificationError = false;
$expiredLink = false;

if (isset($_GET['code'])) {
    $verificationCode = htmlentities($_GET['code']);
    
    try {
        $student = new Academai();
        $user = $student->getUserByVerificationCode($verificationCode);
        
        if ($user) {
            // Check if verification link has expired
            $currentDateTime = date('Y-m-d H:i:s');
            if ($currentDateTime > $user['verification_expiry']) {
                $expiredLink = true;
            } else {
                // Mark user as verified
                if ($student->verifyUser($user['id'])) {
                    $verificationSuccess = true;
                } else {
                    $verificationError = true;
                }
            }
        } else {
            $verificationError = true;
        }
    } catch (PDOException $e) {
        $verificationError = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>AcademAI | Email Verification</title>
    <?php require_once('include/academAI-extension-links-II.php'); ?>
    <link rel="icon" href="img/light-logo-img.png" type="image/x-icon">
    <link rel="stylesheet" href="css/academAI-signup-login.css">
</head>
<body>
    <?php require_once('include/academAI-nav-bar-user.php'); ?>
    
    <main>
        <section class="verification-section">
            <div class="container">
                <?php if ($verificationSuccess): ?>
                    <div class="verification-success">
                        <h2>Email Verified Successfully!</h2>
                        <p>Your email address has been verified. You can now log in to your account.</p>
                        <a href="login.php" class="btn-login">Go to Login</a>
                    </div>
                <?php elseif ($verificationError): ?>
                    <div class="verification-error">
                        <h2>Verification Failed</h2>
                        <p>Invalid verification link. Please try again or contact support.</p>
                    </div>
                <?php elseif ($expiredLink): ?>
                    <div class="verification-expired">
                        <h2>Link Expired</h2>
                        <p>This verification link has expired. Please request a new one.</p>
                        <a href="resend-verification.php" class="btn-resend">Resend Verification Email</a>
                    </div>
                <?php else: ?>
                    <div class="verification-default">
                        <h2>Email Verification</h2>
                        <p>Please check your email for the verification link.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>