<?php

    session_start();

    if(isset($_SESSION['user']) && $_SESSION['user'] == 'user'){
        header('location: index.php');
    }


    require_once 'classes/user.class.php';
    require_once 'tools/functions.php';

    if(isset($_POST['save'])){
        $user = new Academai();
        //sanitize
        $user->first_name = htmlentities($_POST['first_name']);
        $user->middle_name = htmlentities($_POST['middle_name']);
        $user->last_name = htmlentities($_POST['last_name']);
        $user->email = htmlentities($_POST['email']);
        $user->password = htmlentities($_POST['password']);

        //validate inputs of the users
        if (validate_field($user->first_name) && 
        validate_field($user->middle_name) &&
        validate_field($user->last_name) &&
        validate_field($user->email) &&
        validate_field($user->password) && validate_password($user->password)){
            //proceed with saving
            if($user->add()){
                header('location: signup.php');
                $message = 'You successfully created an account!';
            }else{
                echo 'Something went wrong in creating your account.';
            }
        }
    }

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/landing_page.css">
    <?php
      require_once('include/extension_links.php');
    ?>
   <title>Academai</title>  
   <link rel="icon" href="img/light-logo-img.png" type="image/x-icon">
</head>

  <?php
    require_once('include/academAI-nav-bar-user.php');
  ?>

<body class="academAI-landing-page">

<section class="videosection">
    <div class="video-overlay"></div>
    <video autoplay muted loop id="video-background">
        <source src="img/landingpage.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="container video-content"> <!-- Ensure container class is here -->
        <div class="row"> <!-- Row should be inside the container -->
            <div class="col-md-6">
                <div class="video-content-quotes">
                    <p class="video-content-qoutes-1">Don't be a CTRL C + CTRL V</p>
                    <p class="video-content-qoutes-2">BE ORIGINAL, BE AUTHENTICA</p>
                    <p class="video-content-qoutes-3">A truer form of self-respect and dignity transcends
                      beyond, embracing the uniqueness value in your self will generally
                      open the door to genuine creativity and self-expression.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="left-icon-section">
                    <div class="social-icons">
                        <div class="social-icons-line-above"></div>
                        <div class="social-icon">
                            <a href="https://www.facebook.com"><i class="fa-brands fa-facebook"></i></a>
                        </div>
                        <div class="social-icon">
                            <a href="https://www.tiktok.com"><i class="fa-brands fa-tiktok"></i></a>
                        </div>
                        <div class="social-icon">
                            <a href="https://www.instagram.com"><i class="fa-brands fa-instagram"></i></a>
                        </div>
                        <div class="social-icons-line-below"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<section class="slidersection">
      <div class="container slidersection-container">
           
          <div class="slide">
        
          <div class="item" style="background-image: url(img/index-slidersection/plagiarism-detection.jpg);">      
                    <div class="content slider-content">
                      <div class="name">Plagiarism Detection</div>
                      <div class="des">Utilizes advanced Open Artificial Intelligence (OpenAI) Large Language Model to detect 
                                       instances of plagiarism in submitted essays.
              </div>
                    <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image: url(img/index-slidersection/ai.jpg);">
                  <div class="content slider-content">
                      <div class="name">AI Generation Detection</div>
                      <div class="des">Detect AI generated content in submitted essays.</div>
                      <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image: url(img/index-slidersection/essay-grading.png);">
                  <div class="content slider-content">
                      <div class="name">Automated Essay Grading</div>
                      <div class="des">Implements automated grading mechanisms to assess essays against predetermined grading criteria and standards.</div>
                      <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image: url(img/index-slidersection/user-interface.jpg);">
                  <div class="content slider-content">
                      <div class="name">User-Friendly Interface</div>
                      <div class="des">Features an intuitive and user-friendly interface for easy submission of essays, viewing of assessment results, and accessing feedback.</div>
                      <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image:url(img/index-slidersection/rubric-alignment.jpg);">
                  <div class=" content slider-content">
                      <div class="name">Rubric Alignment Assessment</div>
                      <div class="des">The system will assess essays based on provided rubrics, analyzing criteria such as content relevance, organization, coherence, and citation adherence to ensure alignment with guidelines.</div>
                      <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image: url(img/index-slidersection/customizable-rubric.jpg);">
                  <div class=" content slider-content">
                      <div class="name">Customizable Rubric Criteria</div>
                      <div class="des">Allows educators to create and customize rubrics criteria </div>
                      <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image: url(img/index-slidersection/feedback.jpg);">
                  <div class=" content slider-content">
                      <div class="name">Feedback Generation</div>
                      <div class="des">Generates comprehensive feedback for students based on assessment results, highlighting strengths and areas for improvement in their essays. </div>
                      <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image: url(img/index-slidersection/mcq.jpg);">
                  <div class=" content slider-content">
                      <div class="name">Multiple Choice Question (MCQ)</div>
                      <div class="des">Facilitates the creation and administration of multiple-choice quizzes to complement essay assessments.</div>
                      <button>See More</button>
                  </div>
              </div>
              <div class="item" style="background-image: url(img/index-slidersection/identification.jpg);">
                  <div class=" content slider-content">
                      <div class="name">Identification Quiz Functionality</div>
                      <div class="des">Enables the creation of an identification type of  quizzes</div>
                      <button>See More</button>
                  </div>
              </div>
      </div>

        <div class="button">
              <button class="prev"><i class="fa-solid fa-arrow-left"></i></button>
              <button class="next"><i class="fa-solid fa-arrow-right"></i></button>
          </div>
      </div>
  </section>
  
  <section class="benefit-section">
    <div class="container-fluid benefit-container">
    <div class="row my-5 d-flex justify-content-center">
      <div class="col-3 benefit-line-1 mb-5"> </div>
      <div class="col-6 d-flex justify-content-center">
        <p class="benefit-title ">Perks of using our essay checker<p>
        </div>
        

      <div class="col-3  benefit-line-1 d-flex justify-content-center mb-5"> </div>
      </div>

        <div class="row"> 
            <div class="col-6 d-flex justify-content-evenly my-5"> 
                <img src="img/benefit-section/Promotes-Fairness.GIF" class="img-fluid img-fluid hidden" alt="Benefit Image" id="benefit-img-1"> <!-- Correct image extension to .webp -->
            </div>
           
            <div class="col-6 d-flex justify-content-start my-5"> 
            <div class="d-grid px-5 ">
            <p class="text-animation benefit-name">Promotes Fairness</p>
            <p class="text-animation benefit-des">Ensures equitable evaluation by impartially assessing all submissions based on the predefined criteria, thereby upholding fairness and
               consistency in the grading process. This approach fosters trust and transparency, ensuring that every student receives fair recognition for their efforts and achievements.</p>
            </div>
            </div>
        </div>
  


    <div class="row  my-5"> 
    <div class="col-6  my-5 ">
    <div class="d-grid academic-integrity">
        <p class="text-animation benefit-name">Enhances Academic Integrity</p>
        <p class="text-animation benefit-des">Detects instances of plagiarism and AI-generated content, ensuring authenticity and originality in student submissions.
        This comprehensive solution not only identifies instances of plagiarism and AI-generated content but also offers valuable insights and suggestions to promote authentic and original writing practices among students. 
        By fostering a culture of academic integrity, it supports the development of critical thinking and ethical writing habits essential for academic success and lifelong learning.
        </p>
    </div>

      </div>
      <div class="col-6 d-flex justify-content-around my-5 ">
      <img src="img/benefit-section/Academic-integrity.GIF" class="img-fluid img-fluid hidden" alt="Benefit Image"id="benefit-img-2"> <!-- Correct image extension to .webp -->
     
      </div>
    </div>

    <div class="row my-5 "> 
      <div class="col-6 d-flex justify-content-evenly my-5"> 
        <img src="img/benefit-section/Empower-Educator.GIF" class="img-fluid img-fluid hidden" alt="Benefit Image"id="benefit-img-3"> <!-- Correct image extension to .webp -->
      </div>
      <div class="col-6 d-flex justify-content-start my-5"> 
      <div class="d-grid  px-5">
            <p class="text-animation benefit-name">Empowers Educators</p>
            <p class="text-animation benefit-des">Equips instructors with powerful tools to effectively combat academic dishonesty, empowering them to maintain academic standards with confidence while also fostering
               a supportive learning environment. By providing insights into student performance and identifying potential areas of concern, these tools enable instructors to intervene proactively and offer guidance to students, 
               promoting academic integrity and ethical behavior throughout the educational process.</p>
            </div>
            </div>
    </div>

  </div>
</section>

<section class="how-it-works">
  <div class="container-fluid  how-it-works-container">
    <div class="row my-5 d-flex justify-content-center">
      <div class="col-4 how-it-works-line-1 d-flex justify-content-around mb-5"> </div>
      <div class="col-3 d-flex justify-content-center">
        <p class="how-it-works-title ">How it works<p>
        </div>
      <div class="col-4 how-it-works-line-2 mb-5"> </div>
    </div>

    <div class="row d-flex justify-start-center my-5">
      <div class="col-5 d-flex justify-start-center my-5">
        <img src="img/Submitting-of-essay.GIF" class="img-fluid img-fluid" alt="Benefit Image" id="how-it-works-img-1"> <!-- Correct image extension to .webp -->
        <img src="img/b.GIF" class="img-fluid img-fluid hidden-img" alt="Second Image" id="how-it-works-img-2">
        <img src="img/e.GIF" class="img-fluid img-fluid hidden-img" alt="Third Image" id="how-it-works-img-3">
      </div>
      <div class="col-1 d-grid justify-content-center my-1">
        <div class="line above"></div>
        <p class="number active" data-index="1">1</p>
        <p class="number" data-index="2">2</p>
        <p class="number" data-index="3">3</p>
        <div class="line down"></div>
      </div>

      <div class="col-6 d-grid justify-content-center my-5"> <!-- Ensure alignment -->
        <div class="how-it-works-input shadow-lg active" id="how-it-works-input" data-index="1"> <!-- Add active class to default input -->
          <p class="how-it-works-name">Submission of Essays</p>
          <p class="how-it-works-des"> Educators or students log in to the automated essay checker platform using their credentials. They navigate to the submission interface, where they can upload their essays. They may also provide additional information such as assignment details or rubrics.</p>
        </div>
        <div class="how-it-works-input shadow-lg" id="how-it-works-input" data-index="2">
            <p class="how-it-works-name">Automated Assessment</p>
          <p class="how-it-works-des"> Once the essays are submitted, the automated essay checker initiates the assessment process. It utilizes OpenAI's Large Language Model to analyze the content of the essays, evaluating criteria such as plagiarism, content relevance, organization, coherence, and citation adherence. The system generates assessment results, including if it is generated of plagiarized, feedback on essay quality, and overall scores based on predefined rubrics.</p>
        </div>
        <div class="how-it-works-input shadow-lg" id="how-it-works-input" data-index="3">
           <p class="how-it-works-name">Accessing Feedback and Results</p>
          <p class="how-it-works-des">Educators can view detailed reports and analysis for each essay, facilitating informed grading decisions and providing personalized feedback to students. Students can access their individual assessment results, including feedback on strengths, areas for improvement, and overall performance.</p>
        </div>
      </div>
    </div>
  </div>
</div>
</section>

<section class="accordion-section">
  <div class="container-fluid">
    <div class="row">
      <div class="col-2"> 
        <div class="d-grid justify-content-end ">
          <div class="accordion-line-1"> </div>
          <p class="faq-title mt-4 mb-4">F</p>
          <p class="faq-title mb-4">A</p>
          <p class="faq-title mb-4">Q</p>
          <div class="accordion-line-2"> </div>
        </div>
      </div>
      <div class="col-10 d-flex justify-content-center"> 
  <div class="accordion" id="accordionPanelsStayOpenExample">
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
          What is the purpose of the automated essay checker?
          </button>
        </h2>
        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
          <div class="accordion-body">
            Our automated essay checker aims to streamline the essay assessment process, detect plagiarism, and promote academic integrity.
          </div>
        </div>
      </div>
      <img src="img/index-faq/faq-1.jpg" alt="Image 1" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
            How does the automated essay checker detect plagiarism?
          </button>
        </h2>
        <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse">
          <div class="accordion-body">
            Our system utilizes OpenAI's Large Language Model to compare submitted essays against a database of existing content, identifying similarities and instances of plagiarism.
          </div>
        </div>
      </div>
      <img src="img/index-faq/faq-2.jpg" alt="Image 2" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
          Can the automated essay checker differentiate between AI-generated content and original student work?
          </button>
        </h2>
        <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
          <div class="accordion-body">
          Yes, our system is equipped to identify AI-generated content and distinguish it from original student work using OpenAI's Large Language Model.
        </div>
        </div>
      </div>
      <img src="img/index-faq/faq-3.jpg" alt="Image 3" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFour" aria-expanded="false" aria-controls="panelsStayOpen-collapseFour">
          What criteria does the automated essay checker use to assess essays?
          </button>
        </h2>
        <div id="panelsStayOpen-collapseFour" class="accordion-collapse collapse">
          <div class="accordion-body">
          The automated essay checker analyzes various criteria such as content relevance, organization, coherence, citation adherence, and originality.Additionally, the professor can manipulate the criteria into his/her own preferences.
        </div>
        </div>
      </div>
      <img src="img/index-faq/faq-4.jpg" alt="Image 3" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseFive" aria-expanded="false" aria-controls="panelsStayOpen-collapseFive">
          How long does it take for the automated essay checker to provide feedback on submitted essays?
          </button>
        </h2>
        <div id="panelsStayOpen-collapseFive" class="accordion-collapse collapse">
          <div class="accordion-body">
          Feedback turnaround time may vary depending on factors such as essay length and system workload, but we strive to provide prompt feedback whenever possible.
        </div>
        </div>
      </div>
      <img src="img/index-faq/faq-5.jpg" alt="Image 3" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSix" aria-expanded="false" aria-controls="panelsStayOpen-collapseSix">
          Can instructors customize the rubrics used by the automated essay checker?
          </button>
        </h2>
        <div id="panelsStayOpen-collapseSix" class="accordion-collapse collapse">
          <div class="accordion-body">
           Yes, our system offers flexibility for instructors to create and customize rubrics.
        </div>
        </div>
      </div>
      <img src="img/index-faq/faq-6.jpg" alt="Image 3" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSeven" aria-expanded="false" aria-controls="panelsStayOpen-collapseSeven">
          Is the automated essay checker compatible with different file formats for essay submissions?
          </button>
        </h2>
        <div id="panelsStayOpen-collapseSeven" class="accordion-collapse collapse">
          <div class="accordion-body">
          Yes, our system supports various file formats such as DOCX and PDF for essay submissions, ensuring flexibility and ease of use for students and educators.
       </div>
        </div>
      </div>
      <img src="img/index-faq/faq-7.jpg" alt="Image 3" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseEight" aria-expanded="false" aria-controls="panelsStayOpen-collapseEight">
          How does the automated essay checker handle essays that contain images or graphs?
         </button>
        </h2>
        <div id="panelsStayOpen-collapseEight" class="accordion-collapse collapse">
          <div class="accordion-body">
          Our system primarily focuses on analyzing textual content, so images or graphs included in essays are not assessed.          
       </div>
        </div>
      </div>
      <img src="img/index-faq/faq-8.jpg" alt="Image 3" class="accordion-image">
    </div>
    <div class="accordion-item">
      <div class="accordion-content">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseNine" aria-expanded="false" aria-controls="panelsStayOpen-collapseNine">
          Can students collaborate on essays and submit them jointly through the automated essay checker?
         </button>
        </h2>
        <div id="panelsStayOpen-collapseNine" class="accordion-collapse collapse">
          <div class="accordion-body">
          Our system is designed to assess individual submissions, so collaborative essays should be submitted separately by each student. However, instructors may provide guidance on collaborative writing processes outside of the automated essay checker.          
       </div>
        </div>
      </div>
      <img src="img/index-faq/faq-9.jpg" alt="Image 3" class="accordion-image">
    </div>
  </div>
  </div>
</div>
</div>
</section>


<footer class="footer">
  <div class="container footers">
    <div class="row">
      <div class="col-12 text-center">
        <p>&copy; 2024 AcademAI. All rights reserved.</p>
      </div>
    </div>
  </div>
</footer>

<!-- The static modal -->
<div id="myModal-login" class="modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="myModal-loginLabel" aria-hidden="true">
    <!-- Modal content -->
    <div class="containersignup">
        <div class="signin-signup">
            <form action="" method="post" class="sign-in-form">
                <img src="img/logo.png" alt="logo" id="AcademAiSignUp-logo">
                <h2 class="title">Sign in</h2>
                <div class="input-field-login">
                    <i class="fas fa-user"></i>
                    <input type="text" placeholder="Username">
                </div>
                <div class="input-field-login">
                    <i class="fas fa-lock"></i>
                    <input type="password" placeholder="Password">
                </div>
                <a href="user/AcademAI-Quiz-Room.php" class="log-in-btn">Login</a>
                <p class="account-text">Don't have an account? <a href="#" id="sign-up-btn2">Sign up</a></p>
            </form>

   <form action="" method="POST" class="sign-up-form">
    <h2 class="title">Sign up</h2>
    <div class="input-field-section">
        <div class="input-field">
            <i class="fas fa-user"></i>
            <input type="text" class="form-control" placeholder="First Name" id="first_name" name="first_name" value="<?php if(isset($_POST['first_name'])){ echo $_POST['first_name']; } ?>">
        </div>
        <?php 
            if(isset($_POST['first_name']) && !validate_field($_POST['first_name'])){ 
        ?>
            <p class="text-danger my-1">Please enter a valid first name</p>
        <?php 
              } 
        ?>
    </div>
    <div class="input-field-section">
        <div class="input-field">
            <i class="fas fa-user"></i>
            <input type="text" class="form-control" placeholder="Middle Name" id="middle_name" name="middle_name" value="<?php if(isset($_POST['last_name'])){ echo $_POST['middle_name']; } ?>">
        </div>
        <?php 
            if(isset($_POST['middle_name']) && !validate_field($_POST['middle_name'])){ 
        ?>
            <p class="text-danger my-1">Please enter a valid middle name</p>
        <?php 
              } 
        ?>
    </div>
    <div class="input-field-section">
        <div class="input-field">
            <i class="fas fa-user"></i>
            <input type="text" class="form-control" placeholder="Last Name" id="last_name" name="last_name" value="<?php if(isset($_POST['last_name'])){ echo $_POST['last_name']; } ?>">
        </div>
        <?php 
            if(isset($_POST['last_name']) && !validate_field($_POST['last_name'])){ 
        ?>
            <p class="text-danger my-1">Please enter a valid last name</p>
        <?php 
              } 
        ?>
    </div>
    <div class="input-field-section">
        <div class="input-field">
            <i class="fas fa-envelope"></i>
            <input type="email" class="form-control" placeholder="Email" id="email" name="email" value="<?php echo isset($inputValues['email']) ? $inputValues['email'] : ''; ?>">
        </div>
        <?php if (isset($errors['email'])): ?>
            <p class="text-danger my-1"><?php echo $errors['email']; ?></p>
        <?php endif; ?>
    </div>
    <div class="input-field-section">
        <div class="input-field">
            <i class="fas fa-lock"></i>
            <input type="password" class="form-control" placeholder="Password" id="password" name="password">
        </div>
        <?php if (isset($errors['password'])): ?>
            <p class="text-danger my-1"><?php echo $errors['password']; ?></p>
        <?php endif; ?>
    </div>
    <div class="input-field-section">
        <div class="input-field">
            <i class="fas fa-lock"></i>
            <input type="password" class="form-control" placeholder="Confirm Password" id="confirmpassword" name="confirmpassword" value="<?php echo isset($inputValues['confirmpassword']) ? $inputValues['confirmpassword'] : ''; ?>">
        </div>
        <?php if (isset($errors['confirmpassword'])): ?>
            <p class="text-danger my-1"><?php echo $errors['confirmpassword']; ?></p>
        <?php endif; ?>
    </div>
    <button type="submit" class="sign-up-btn" name="save" value="Sign Up">Sign Up</button>
    <p class="account-text">Already have an account? <a href="#" id="sign-in-btn">Sign in</a></p>
</form>
        </div>
      <div class="panels-container">
  <div class="panel left-panel">
  <i class="fa-solid fa-xmark fa-x left-panel-close close panel-close" data-bs-dismiss="modal"></i>

    <div class="content">
      <img src="img/sigin-img.gif" alt="" class="sigin-image">
      <h3>Already have an account here?</h3>
      <p>Sign in to get started. Enjoy the offer of our essay checker features to the fullest!</p>
      <button class="sign-in-btn" id="sign-in-btn">Sign Up</button>
    </div>
    <img src="signin.svg" alt="" class="image">
</div>
<div class="panel right-panel">
    <i class="fa-solid fa-xmark fa-x right-panel-close close panel-close" ></i>
    <div class="content">
      <img src="img/login-img.gif" alt="" class="login-image">
      <h3>Welcome to our platform! </h3>
      <p>Our AI essay checker not only detects plagiarism but also ensures authentic content. It's user-friendly and perfect for enhancing your writing. Sign up to experience it now!</p>
      <button class="sign-in-btn" id="sign-in-btn">Sign In</button>
    </div>
</div>


        </div>


       
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($errors)): ?>
    var myModal = new bootstrap.Modal(document.getElementById('myModal-signup'));
    myModal.show();
    <?php endif; ?>
});
</script>



<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>
<script src="js/main.js"></script>
     <!-- Scripts -->
     <script src="js/landing_page.js"></script>
  
</body>

</html>

