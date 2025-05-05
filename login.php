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

// Debug session status
if (session_status() !== PHP_SESSION_ACTIVE) {
    die('Session initialization failed. Check server configuration.');
}

// Force logout if requested
if (isset($_GET['force_logout'])) {
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

// Enhanced session validation
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Verify all required session variables
    if (isset($_SESSION['user']) && isset($_SESSION['creation_id'])) {
        header('Location: user/AcademAI-Quiz-Room.php');
        exit();
    } else {
        // Clear incomplete session
        session_unset();
        session_destroy();
    }
}

require_once 'classes/account.class.php';

$error = '';

if (isset($_POST['login'])) {
    // Validate inputs
    if (empty($_POST['email']) || empty($_POST['password'])) {
        $error = "Email and password are required";
    } else {
        $user = new Account();
        $user->email = htmlentities(trim($_POST['email']));
        $user->password = trim($_POST['password']); // Don't htmlentities password
        
        if ($user->sign_in_customer()) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            $_SESSION['user'] = 'student';
            $_SESSION['logged_in'] = true;
            $_SESSION['creation_id'] = $user->getUserCreationId();
            $_SESSION['login_success'] = "You've successfully logged in, welcome back!";
            $_SESSION['last_activity'] = time();
            
            // Secure redirect
            header('Location: user/AcademAI-Quiz-Room.php?success=1');
            exit();
        } else {
            $error = "Invalid email or password.";
            // Security: Delay response on failure
            sleep(1);
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<?php
    $title = 'AcademAI | Login';
    require_once('include/academAI-extension-links-II.php');
?>
<link rel="stylesheet" href="css/academAI-signup-login.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<title>New Login Page Name</title>

<title>Academai | Login</title>  
   <link rel="icon" href="img/light-logo-img.png" type="image/x-icon">
<body class="login-page">
<?php
    require_once 'include/academAI-nav-bar-user.php';
?>
<main>
    <section class="login-section">
            <div class="login">      
                <div class="login-left p-5 col-6">
                    <h1 class="h2 brand-color text-center mb-3">LOG IN</h1>
                    <form action="" method="post">
                        <div class="mb-3 inputfield">
                            <input placeholder="Email" type="email" class="form-control" id="email" name="email" value="<?php if(isset($_POST['email'])){ echo $_POST['email']; } ?>">
                        </div>
                        <div class="mb-3 inputfield">
                            <input placeholder="Password" type="password" class="form-control" id="password" name="password" value="<?php if(isset($_POST['password'])){ echo $_POST['password']; } ?>">
                            <p class="forgotpass">
    <a href="forgotpassword.php">Forgot your password?</a>
</p>

                        </div>
                        <div class="mb-4 inputfield">
                            <button type="submit" name="login" class="btn-log-in">Log In</button>
                        </div>
                        <?php if(isset($_POST['login']) && $error): ?>
                            <p class="text-danger text-center"><?= $error ?></p>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <p class="dont-have-account">Don't have an account?<a href="signup.php" class="brand-color-sign-in-here">Sign in here</a></p>
                            
                        </div>
            </div>
            <div class="pic col-6">

</div>
          
        </div>
    </section>
</main>

<!-- Success Modal -->
<div class="modal fade" id="login-success-modal" tabindex="-1" aria-labelledby="loginSuccessModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginSuccessModalLabel">Login Successful</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php if (isset($_SESSION['login_success'])) {
            echo $_SESSION['login_success'];
            unset($_SESSION['login_success']); // Clear the success message
        } ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



<script type="text/javascript">
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>

</body>
</html>


