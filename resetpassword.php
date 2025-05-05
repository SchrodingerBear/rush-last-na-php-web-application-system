<?php
// Strict error reporting and session configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Start session with strict settings
ini_set('session.use_strict_mode', 1);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
session_start();

// Check if user is verified for password reset
if (!isset($_SESSION['verified_for_reset']) || !$_SESSION['verified_for_reset'] || !isset($_SESSION['reset_email'])) {
    header('Location: forgotpassword.php');
    exit();
}

require_once 'classes/account.class.php';

$error = '';
$success = '';

if (isset($_POST['reset_password'])) {
    if (empty($_POST['password']) || empty($_POST['confirm_password'])) {
        $error = "Both password fields are required";
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = "Passwords do not match";
    } else {
        $password = trim($_POST['password']);
        
        // Basic password strength check
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long";
        } else {
            $account = new Account();
            $account->email = $_SESSION['reset_email'];
            
            if ($account->updatePassword($password)) {
                $success = "Password updated successfully!";
                
                // Clear reset session
                unset($_SESSION['verified_for_reset']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_code']);
                
                // Redirect to login after 3 seconds
                header("Refresh: 3; url=login.php");
            } else {
                $error = "Failed to update password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $title = 'AcademAI | Reset Password';
    require_once('include/academAI-extension-links-II.php');
    ?>
    <link rel="stylesheet" href="css/academAI-signup-login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>AcademAI | Reset Password</title>  
    <link rel="icon" href="img/light-logo-img.png" type="image/x-icon">
</head>

<body class="login-page">
    <?php require_once 'include/academAI-nav-bar-user.php'; ?>
    
    <main>
        <section class="login-section">
            <div class="login">      
                <div class="login-left p-5 col-6">
                    <h1 class="h2 brand-color text-center mb-3">CREATE NEW PASSWORD</h1>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!$success): ?>
                        <form action="" method="post">
                            <div class="mb-3 inputfield">
                                <input placeholder="New Password" type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="mb-3 inputfield">
                                <input placeholder="Confirm New Password" type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="mb-4 inputfield">
                                <button type="submit" name="reset_password" class="btn-log-in">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <p class="dont-have-account">Remember your password? <a href="login.php" class="brand-color-sign-in-here">Login here</a></p>
                    </div>
                </div>
                <div class="pic col-6"></div>
            </div>
        </section>
    </main>

    <script type="text/javascript">
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
        
        // Password strength indicator (optional)
        document.getElementById('password').addEventListener('input', function() {
            let password = this.value;
            let strengthText = '';
            let strengthClass = '';
            
            if (password.length === 0) {
                strengthText = '';
            } else if (password.length < 8) {
                strengthText = 'Weak';
                strengthClass = 'text-danger';
            } else if (password.length < 12) {
                strengthText = 'Medium';
                strengthClass = 'text-warning';
            } else {
                strengthText = 'Strong';
                strengthClass = 'text-success';
            }
            
            let indicator = document.getElementById('password-strength');
            if (indicator) {
                indicator.textContent = strengthText;
                indicator.className = strengthClass;
            }
        });
    </script>
</body>
</html>