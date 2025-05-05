<?php
// password_reset.php - Handles both forgot password and reset password functionality

require_once 'connection.php'; // Your database connection file
require_once 'tools/email_functions.php'; // Your email sending functions

class PasswordReset {
    protected $db;

    function __construct() {
        $this->db = new Database();
    }

    // Generate and send password reset token
    public function sendResetToken($email) {
        // Check if email exists
        if (!$this->emailExists($email)) {
            return ['status' => 'error', 'message' => 'Email not found'];
        }

        // Generate token and expiry (1 hour from now)
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in database
        $sql = "UPDATE academai SET 
                reset_token = :token,
                reset_expiry = :expiry
                WHERE email = :email";

        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':token', $token);
        $query->bindParam(':expiry', $expiry);
        $query->bindParam(':email', $email);

        if ($query->execute()) {
            // Send email with reset link
            $resetLink = "https://yourdomain.com/password_reset.php?token=$token";
            $subject = "Password Reset Request";
            $message = "
                <h2>Password Reset</h2>
                <p>We received a request to reset your password. Click the link below to proceed:</p>
                <p><a href='$resetLink'>Reset Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
            ";

            if (sendEmail($email, $subject, $message)) {
                return ['status' => 'success', 'message' => 'Reset link sent to your email'];
            } else {
                return ['status' => 'error', 'message' => 'Failed to send email'];
            }
        }

        return ['status' => 'error', 'message' => 'Database error'];
    }

    // Verify reset token
    public function verifyResetToken($token) {
        $sql = "SELECT email FROM academai 
                WHERE reset_token = :token 
                AND reset_expiry > NOW()";
        
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':token', $token);
        $query->execute();

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    // Update password
    public function updatePassword($token, $newPassword) {
        // Verify token first
        $user = $this->verifyResetToken($token);
        if (!$user) {
            return ['status' => 'error', 'message' => 'Invalid or expired token'];
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password and clear token
        $sql = "UPDATE academai SET 
                password = :password,
                reset_token = NULL,
                reset_expiry = NULL
                WHERE reset_token = :token";

        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':password', $hashedPassword);
        $query->bindParam(':token', $token);

        if ($query->execute()) {
            return ['status' => 'success', 'message' => 'Password updated successfully'];
        }

        return ['status' => 'error', 'message' => 'Failed to update password'];
    }

    // Check if email exists
    private function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM academai WHERE email = :email";
        $query = $this->db->connect()->prepare($sql);
        $query->bindParam(':email', $email);
        $query->execute();
        return $query->fetchColumn() > 0;
    }
}

// Start session
session_start();

// Initialize PasswordReset class
$passwordReset = new PasswordReset();

// Handle form submissions
$response = [];
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'request_reset':
            if (!empty($_POST['email'])) {
                $response = $passwordReset->sendResetToken($_POST['email']);
            } else {
                $response = ['status' => 'error', 'message' => 'Email is required'];
            }
            break;

        case 'reset_password':
            if (!empty($_POST['token']) && !empty($_POST['password']) && !empty($_POST['confirm_password'])) {
                if ($_POST['password'] !== $_POST['confirm_password']) {
                    $response = ['status' => 'error', 'message' => 'Passwords do not match'];
                } else {
                    $response = $passwordReset->updatePassword($_POST['token'], $_POST['password']);
                }
            } else {
                $response = ['status' => 'error', 'message' => 'All fields are required'];
            }
            break;
    }
}

// Check for token in URL
$token = $_GET['token'] ?? null;
$tokenValid = false;
$tokenEmail = '';

if ($token) {
    if ($user = $passwordReset->verifyResetToken($token)) {
        $tokenValid = true;
        $tokenEmail = $user['email'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .reset-container { max-width: 500px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .form-title { margin-bottom: 30px; text-align: center; }
        .btn-reset { width: 100%; padding: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <?php if (isset($response)): ?>
                <?php if ($response['status'] === 'success'): ?>
                    <div class="alert alert-success"><?= $response['message'] ?></div>
                <?php elseif ($response['status'] === 'error'): ?>
                    <div class="alert alert-danger"><?= $response['message'] ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!$token): ?>
                <!-- Request Password Reset Form -->
                <h2 class="form-title">Forgot Password</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="request_reset">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-reset">Send Reset Link</button>
                </form>
                <div class="text-center mt-3">
                    <a href="login.php">Back to Login</a>
                </div>

            <?php elseif ($tokenValid): ?>
                <!-- Reset Password Form -->
                <h2 class="form-title">Reset Password</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($tokenEmail) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary btn-reset">Reset Password</button>
                </form>

            <?php else: ?>
                <!-- Invalid Token Message -->
                <div class="alert alert-danger">
                    Invalid or expired password reset link. Please request a new one.
                </div>
                <div class="text-center mt-3">
                    <a href="password_reset.php">Request New Reset Link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>