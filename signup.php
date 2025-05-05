<?php
require_once 'classes/user.class.php';
require_once 'tools/functions.php';

$accountCreated = false;
$duplicateEmailError = false;
$passwordMismatchError = false;

if (isset($_POST['save'])) {
    $student = new Academai();
    // sanitize
    $student->first_name = htmlentities($_POST['first_name']);
    $student->middle_name = htmlentities($_POST['middle_name']);
    $student->last_name = htmlentities($_POST['last_name']);
    $student->email = htmlentities($_POST['email']);
    $student->password = htmlentities($_POST['password']);
    $confirmPassword = htmlentities($_POST['confirmpassword']);

    // validate inputs
    if (validate_field($student->first_name) && 
        validate_field($student->middle_name) &&
        validate_field($student->last_name) &&
        validate_field($student->email) &&
        validate_field($student->password) &&
        validate_password($student->password) &&
        $student->password === $confirmPassword) {

        try {
            // Generate verification code and expiry
            $verificationCode = md5(uniqid(rand(), true));
            $verificationExpiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Set verification fields
            $student->is_verified = false;
            $student->verification_code = $verificationCode;
            $student->verification_expiry = $verificationExpiry;
            
            // proceed with saving
            if ($student->add()) {
                // Send verification email
                if (sendVerificationEmail($student->email, $verificationCode)) {
                    $accountCreated = true;
                } else {
                    throw new Exception("Failed to send verification email");
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $duplicateEmailError = true;
            } else {
                echo 'Something went wrong: ' . $e->getMessage();
            }
        }
    } else {
        if ($student->password !== $confirmPassword) {
            $passwordMismatchError = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <?php    
        $title = 'AcademAI | Sign Up';
        require_once('include/academAI-extension-links-II.php');
    ?>
    <title>Academai | Signup</title>  
    <link rel="icon" href="img/light-logo-img.png" type="image/x-icon">
    <link rel="stylesheet" href="css/academAI-signup-login.css">

<body class="signup-page" >
    <?php
        require_once('include/academAI-nav-bar-user.php');
    ?>
    <main>
        <section class="signup-section">
                <div class="row row-signup">     
                        <div class="signup-left p-3">
                        
                            <form action="" method="post" class = "signupsignup">
                                <div class=" picleft col-6">





                                </div>

                                <div class="rightsignup  col-6">
                                <h1 class="h2 brand-color text-center mb-3">SIGN UP</h1>

                                <div class="mb-3 mt-4">
                                    <input placeholder="First Name" type="text" class="form-control" id="first_name" name="first_name" value="<?php if(isset($_POST['first_name'])){ echo $_POST['first_name']; } ?>">
                                    <?php
                                        if(isset($_POST['first_name']) && !validate_field($_POST['first_name'])){
                                    ?>
                                            <p class="text-danger my-1">Please enter a valid first name</p>
                                    <?php
                                        }
                                    ?>
                                </div>
                                <div class="mb-3 mt-4">
                                    <input placeholder="Middle Name" type="text" class="form-control" id="middle_name" name="middle_name" value="<?php if(isset($_POST['middle_name'])){ echo $_POST['middle_name']; } ?>">
                                    <?php
                                        if(isset($_POST['middle_name']) && !validate_field($_POST['middle_name'])){
                                    ?>
                                            <p class="text-danger my-1">Please enter a valid middle name</p>
                                    <?php
                                        }
                                    ?>
                                </div>
                                <div class="mb-3 mt-4">
                                    <input placeholder="Last Name" type="text" class="form-control" id="last_name" name="last_name" value="<?php if(isset($_POST['last_name'])){ echo $_POST['last_name']; } ?>">
                                    <?php
                                        if(isset($_POST['last_name']) && !validate_field($_POST['last_name'])){
                                    ?>
                                            <p class="text-danger my-1">Please enter a valid last name</p>
                                    <?php
                                        }
                                    ?>
                                </div>

                                <div class="mb-3 mt-4">
                                    <input placeholder="Email" type="email" class="form-control" id="email" name="email" value="<?php if(isset($_POST['email'])){ echo $_POST['email']; } ?>">
                                    <?php
                                        if(isset($_POST['email']) && !validate_field($_POST['email'])){
                                    ?>
                                            <p class="text-danger my-1">Please enter a valid email</p>
                                    <?php
                                        }
                                    ?>
                                </div>
                                


                                <div class="mb-3 mt-4">
                                    <input placeholder="Password" type="password" class="form-control" id="password" name="password" value="<?php if(isset($_POST['password'])){ echo $_POST['password']; } ?>">
                                    <?php
                                        if(isset($_POST['password']) && validate_password($_POST['password']) !== "success"){
                                    ?>
                                            <p class="text-danger my-1"><?= validate_password($_POST['password']) ?></p>
                                    <?php
                                        }
                                    ?>
                                </div>



                                    
                                <div class="mb-3 mt-4">
                                <input placeholder="Confirm Password" type="password" class="form-control" id="confirmpassword" name="confirmpassword" value="<?php if(isset($_POST['confirmpassword'])){ echo $_POST['confirmpassword']; } ?>">
                                <?php
                                    if (isset($_POST['password']) && isset($_POST['confirmpassword']) && $passwordMismatchError) {
                                ?>
                                    <p class="text-danger text-danger my-1">Your confirm password didn't match</p>
                                <?php
                                    }
                                ?>
                                </div>

                                
                                <div class="mb-3 mt-5">
                                    <button type="submit" name="save" class="btn-create-account">Create account</button>
                                </div>
                       
                            <div class="text-center mb-5">
                                <p class="already-have-account">Already have an account? <a href="login.php" class="brand-color-log-in">Log in here</a></p>
                            </div>
                            

                        </div>
                    </div>
                    </div>
                    
                
         
          
        </section>
        </div>
    </main>

 <!-- Success Modal -->
 <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-content-sucessfully-created">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-sucessful-modal">
                    Your account has been created successfully! Please check your email for the verification link to activate your account.
                </div>
                <div class="modal-footer modal-footer-sucessfully-created">
                    <a href="login.php" class=" btn-go-to-login">Go to Login</a>
                </div>
            </div>
        </div>
    </div>

       <!-- Duplicate Email Error Modal -->
       <div class="modal fade" id="duplicateEmailModal" tabindex="-1" aria-labelledby="duplicateEmailModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-content-duplicate-email">
                <div class="modal-header">
                </div>
                <div class="modal-body modal-body-duplicate-email">
                    The email you entered is already in use. Please try another one.
                </div>
                <div class="modal-footer">
                    <button type="button" class="OK-btn-error-email" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>


    <!-- JavaScript to trigger modals -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($accountCreated) { ?>
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        <?php } elseif ($duplicateEmailError) { ?>
            var duplicateEmailModal = new bootstrap.Modal(document.getElementById('duplicateEmailModal'));
            duplicateEmailModal.show();
        <?php } ?>
    </script>
</body>
</html>


<style>
    /* AcademAI Signup Page CSS */
.signup-page {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.signup-section {
    padding: 2rem 0;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-grow: 1;
}

.row-signup {
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    max-width: 1400px;
    width: 100%;
    margin: 0 auto;
    display: flex;
    min-height: 600px;
}

.signup-left {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    
}

.picleft {
    background: linear-gradient(rgba(9, 38, 53, 0.8), rgba(27, 66, 66, 0.8)),
                url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
    background-size: cover;
    background-position: center;
 
    border-radius: 12px 0 0 12px;
}

.rightsignup {
    padding: 2.5rem;
    width: 100%;
}

.signupsignup {
    width: 100%;
}

.brand-color {
    color: #092635;
    font-weight: 700;
}

.brand-color-log-in {
    color: #1B4242;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.brand-color-log-in:hover {
    color: #5C8374;
    text-decoration: underline;
}

h1.h2 {
    font-size: 2rem;
    margin-bottom: 1.5rem !important;
    color: #092635;
}

.form-control {
    height: 50px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 0 15px;
    font-size: 16px;
    transition: all 0.3s ease;
    box-shadow: none;
}

.form-control:focus {
    border-color: #5C8374;
    box-shadow: 0 0 0 3px rgba(92, 131, 116, 0.1);
}

.btn-create-account {
    background-color: #1B4242;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 50px;
}

.btn-create-account:hover {
    background-color: #092635;
    transform: translateY(-2px);
}

.btn-create-account:active {
    transform: translateY(0);
}

.already-have-account {
    color: #6c757d;
    font-size: 15px;
    margin-top: 1.5rem;
}

.text-danger {
    font-size: 14px;
    margin-top: 5px;
    color: #dc3545;
}

/* Responsive adjustments */
@media (min-width: 768px) {
    .picleft {
        display: block;
        flex: 1;
    }
    
    .rightsignup {
        flex: 1;
        padding: 3rem;
    }
    
    .signup-left {
        padding: 3rem;
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.signup-section {
    animation: fadeIn 0.6s ease-out;
}

/* Input placeholder styling */
::placeholder {
    color: #adb5bd;
    opacity: 1;
}

/* Form group spacing */
.mb-3 {
    margin-bottom: 1.25rem !important;
}

.mt-4 {
    margin-top: 1.5rem !important;
}

.mt-5 {
    margin-top: 2.5rem !important;
}

/* Error message styling */
.text-danger.my-1 {
    color: #dc3545;
    font-size: 0.875rem;
}

.signup-page .navigation-bar {
    background-color: #092635 !important;
    padding: 1.5rem 0;
}

/* Additional color palette elements */
.pic-content {
    color: white;
    padding: 2rem;
}

.pic-content h2 {
    color: #9EC8B9;
    margin-bottom: 1rem;
}

.pic-content p {
    color: rgba(255, 255, 255, 0.9);
}
</style>