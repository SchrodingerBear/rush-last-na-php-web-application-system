<?php
    
    function validate_field($field){
        $field = htmlentities($field);
        if(strlen(trim($field))<1){
            return false;
        }else{
            return true;
        }
    }


    function sendVerificationEmail($email, $verificationCode) {
        // Load environment variables
        $fromEmail = getenv('FROM_EMAIL');
        $baseUrl = getenv('BASE_URL');
        
        // Create verification link
        $verificationLink = $baseUrl . "/verify.php?code=" . $verificationCode;
        
        // Email subject
        $subject = "AcademAI - Verify Your Email Address";
        
        // Email message
        $message = "
            <html>
            <head>
                <title>Email Verification</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .button { 
                        display: inline-block; 
                        padding: 10px 20px; 
                        background-color: #4CAF50; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 5px; 
                        margin: 20px 0;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Welcome to AcademAI!</h2>
                    <p>Thank you for registering. Please verify your email address to activate your account.</p>
                    <a href='$verificationLink' class='button'>Verify Email</a>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p><small>$verificationLink</small></p>
                    <p>This link will expire in 24 hours.</p>
                </div>
            </body>
            </html>
        ";
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: AcademAI <$fromEmail>" . "\r\n";
        $headers .= "Reply-To: $fromEmail" . "\r\n";
        
        // Send email
        return mail($email, $subject, $message, $headers);
    }


    function validate_email($email){
        // Check if the 'email' key exists in the $_POST array
        if (isset($email)) {
            $email = trim($email); // Trim whitespace
    
            // Check if the email is not empty
            if (empty($email)) {
                return false;
            } else {
                // Check if the email has a valid format
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return true; // Email is valid
                } else {
                    return false; // Email is not valid
                }
            }
        } else {
            return false; // 'email' key doesn't exist in $_POST
        }
    }
    
    

    function validate_password($password) {
        $password = htmlentities($password);
        
        if (strlen(trim($password)) < 1) {
            return "Password cannot be empty";
        } elseif (strlen($password) < 8) {
            return "Password must be at least 8 characters long";
        } else {
            return "success"; // Indicates successful validation
        }
    }    

    function validate_cpw($password, $cpassword){
        $pw = htmlentities($password);
        $cpw = htmlentities($cpassword);
        if(strcmp($pw, $cpw) == 0){
            return true;
        }else{
            return false;
        }
    }

?>