<?php
// Include the database connection file
require_once('../include/extension_links.php');
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['creation_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$host = 'localhost';
$dbname = 'academaidb';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch(Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Default avatar path
$default_avatar = '../img/default-avatar.png';

// Get current user data
$user = [];
$stmt = $conn->prepare("SELECT * FROM academai WHERE creation_id = ?");
$stmt->bind_param("i", $_SESSION['creation_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Set default avatar if no photo exists
    if (empty($user['photo_path'])) {
        $user['photo_path'] = $default_avatar;
    }
} else {
    $_SESSION['error_message'] = "User not found!";
    header("Location: login.php");
    exit();
}

// Get quizzes created by this user
$created_quizzes = [];
$stmt = $conn->prepare("SELECT * FROM quizzes WHERE creation_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['creation_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $created_quizzes[] = $row;
}

// Get quizzes joined by this user
$joined_quizzes = [];
$stmt = $conn->prepare("SELECT q.* FROM quizzes q 
                       JOIN quiz_participation qp ON q.quiz_id = qp.quiz_id 
                       WHERE qp.user_id = ? 
                       ORDER BY qp.join_date DESC");
$stmt->bind_param("i", $_SESSION['creation_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $joined_quizzes[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Initialize with existing values
    $first_name = $user['first_name'];
    $middle_name = $user['middle_name'];
    $last_name = $user['last_name'];
    $email = $user['email'];
    $password = $user['password'];
    $photo_path = $user['photo_path'];

    // Update with posted values
    if (!empty($_POST['first_name'])) $first_name = $_POST['first_name'];
    if (!empty($_POST['middle_name'])) $middle_name = $_POST['middle_name'];
    if (!empty($_POST['last_name'])) $last_name = $_POST['last_name'];
    if (!empty($_POST['email'])) $email = $_POST['email'];
    
    // Handle password change
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    
    // Handle photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/profiles/";
        $file_extension = pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $new_filename = "user_" . $_SESSION['creation_id'] . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $check = getimagesize($_FILES["profile_photo"]["tmp_name"]);
            if ($check !== false && move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                // Delete old photo if it's not the default avatar
                if ($photo_path != $default_avatar && file_exists($photo_path)) {
                    @unlink($photo_path);
                }
                $photo_path = $target_file;
            } else {
                $_SESSION['error_message'] = "Failed to upload image.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid file type. Please upload JPG, JPEG, PNG, or GIF.";
        }
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE academai SET 
        first_name = ?, 
        middle_name = ?, 
        last_name = ?, 
        email = ?, 
        password = ?,
        photo_path = ?
        WHERE creation_id = ?");
    $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $email, $password, $photo_path, $_SESSION['creation_id']);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: profile-settings.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/profile-setting.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"> 
    <title>My Profile</title>
 
</head>
<body>
    
    
   
    <div class = "profile">
    <div class="profile-container ">
   
    <div class="profile-container-inside ">
   
        
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        
        <form action="profile-settings.php" method="POST" enctype="multipart/form-data">

        <div class="col-4 arrow-section">
        <a href="AcademAI-Quiz-Room.php" id="back-link">
            <i class="fa-solid fa-arrow-left" style="color:#9EC8B9;"></i>
        </a>
    </div>

        <h2 style="text-align: END; color: #5C8374;">
    <i class="fas fa-cogs" style="margin-right: 8px;"></i> My Profile Settings
</h2>



        <div class = "profile-sec">
        <div class = "col-6 profile-picture">

      

        <?php
        // At the top of your PHP file, make sure the default avatar path is correct
        $default_avatar = '../img/default-avatar.jpg'; // Changed to .jpg
        ?>

        <div class="form-group">
            <div class="photo-preview">
                <?php
                // Verify the profile picture
                $profilePicture = $default_avatar; // Start with default
                
                // Check if user has a valid custom profile picture
                if (!empty($user['photo_path']) && 
                    trim($user['photo_path']) !== '' && 
                    $user['photo_path'] !== 'img/default-avatar.jpg' && 
                    file_exists($user['photo_path'])) {
                    $profilePicture = $user['photo_path'];
                }
                ?>
                <img src="<?php echo htmlspecialchars($profilePicture); ?>" 
                    id="profile-preview" 
                    alt="Profile Picture"
                    onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($default_avatar); ?>'">
            </div>
            <label for="profile_photo" class="upload-btn"><i class="fas fa-camera"></i> Choose Photo</label>
            <input type="file" name="profile_photo" id="profile_photo" accept="image/*" onchange="previewImage(this)">
        </div>
        </div>




            <div class = "profile-info col-6">

               
                
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                    </div>

               
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" id="password" placeholder="Enter new password" 
                                minlength="8" oninput="validatePassword()">
                            <small style="color: #666;">Minimum 8 characters</small>
                            <small id="password-error" style="color: red; display: none;">Password must be at least 8 characters</small>
                        </div>
                        
                        <div class="form-group" style="text-align: end; margin-top: 25px;">
                            <button type="submit" class="save-btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                </div>


            
            </div>
            </div>


          
        </form>
        
        <!-- Quizzes Created Section -->

        <div class="quizzes-section-below">
            <div class="quizzes-section">
                <div class = "quizzesivejoined " class="collapsed" onclick="toggleSection(this, 'created-quizzes')">
                        <div>
                            <h4>
                            <i class="fas fa-list-ul"></i> My Created Quizzes
                        </div>
                        

                        <div>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                </div>
            </div>

            <div id="created-quizzes" class="quiz-list collapsed">
    <?php if (!empty($created_quizzes)): ?>
        <?php foreach ($created_quizzes as $quiz): ?>
            <div class="quiz-card" onclick="window.location.href='AcademAI-Library-Upcoming-View-Card.php?quiz_id=<?php echo $quiz['quiz_id']; ?>'">
                <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                <div class="quiz-meta">
                    <strong>Subject:</strong> <?php echo htmlspecialchars($quiz['subject']); ?>
                </div>
                <div class="quiz-meta">
                    <strong>Code:</strong> <?php echo htmlspecialchars($quiz['quiz_code']); ?>
                </div>
                <div class="quiz-meta">
                    <strong>Created:</strong> <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?>
                </div>
                <?php if ($quiz['start_date'] && $quiz['end_date']): ?>
                    <div class="quiz-meta">
                        <strong>Active:</strong> <?php echo date('M d', strtotime($quiz['start_date'])) . ' - ' . date('M d, Y', strtotime($quiz['end_date'])); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-quizzes">You haven't created any quizzes yet.</p>
    <?php endif; ?>
</div>
        
        
        <!-- Quizzes Joined Section -->
        <div class="quizzes-section">
            <div class = "quizzesivejoined " class="collapsed" onclick="toggleSection(this, 'joined-quizzes')">
                <div>
                    <h4>
                        <i class="fas fa-list-ul"></i> Joined Quiz List
                </div>
                <div>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
                </h3>
            </div>

            <div id="joined-quizzes" class="quiz-list collapsed">
                <?php if (!empty($joined_quizzes)): ?>
                    <?php foreach ($joined_quizzes as $quiz): ?>
                        <div class="quiz-card">
                            <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                            <div class="quiz-meta">
                                <strong>Subject:</strong> <?php echo htmlspecialchars($quiz['subject']); ?>
                            </div>
                            <div class="quiz-meta">
                                <strong>Code:</strong> <?php echo htmlspecialchars($quiz['quiz_code']); ?>
                            </div>
                            <div class="quiz-meta">
                                <strong>Created:</strong> <?php echo date('M d, Y', strtotime($quiz['created_at'])); ?>
                            </div>
                            <?php if ($quiz['start_date'] && $quiz['end_date']): ?>
                                <div class="quiz-meta">
                                    <strong>Active:</strong> <?php echo date('M d', strtotime($quiz['start_date'])) . ' - ' . date('M d, Y', strtotime($quiz['end_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-quizzes">You haven't joined any quizzes yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
 

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function toggleSection(header, sectionId) {
            header.classList.toggle('collapsed');
            const section = document.getElementById(sectionId);
            section.classList.toggle('collapsed');
            section.classList.toggle('expanded');
        }
    </script>




<script>
function validatePassword() {
    const password = document.getElementById('password');
    const errorElement = document.getElementById('password-error');
    const submitBtn = document.getElementById('submit-btn');
    
    if (password.value.length > 0 && password.value.length < 8) {
        errorElement.style.display = 'block';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
        submitBtn.style.cursor = 'not-allowed';
    } else {
        errorElement.style.display = 'none';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }
}

// Add event listener when page loads
document.addEventListener('DOMContentLoaded', function() {
    validatePassword(); // Check initial state
    document.getElementById('password').addEventListener('input', validatePassword);
});
</script>


</body>
</html>