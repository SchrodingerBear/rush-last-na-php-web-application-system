    <?php
    require_once('../include/extension_links.php');
    include('../classes/connection.php');
    session_start();

    // Assuming the user's creation_id is stored in the session
    if (isset($_SESSION['creation_id'])) {
        $creation_id = $_SESSION['creation_id'];

        // Connect to the database
        $db = new Database();
        $conn = $db->connect();

        // Fetch subjects for this specific user
        $sql = "SELECT subject_id, title FROM subjects WHERE creation_id = :creation_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':creation_id', $creation_id, PDO::PARAM_INT);
        $stmt->execute();
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "User not logged in.";
        $subjects = [];
    }


    // Get current user info
$current_user_id = $_SESSION['creation_id'];
$stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, photo_path FROM academai WHERE creation_id = ?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $full_name = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
    $email = $user['email'];
    $photo_path = $user['photo_path'] ? '../' . $user['photo_path'] : '../img/default-avatar.jpg';
} else {
    $full_name = "User";
    $email = "user@example.com";
    $photo_path = '../img/default-avatar.jpg';
}
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"> 
        <link rel="stylesheet" href="../css/rubric.css">
        <title>Criteria Setting</title>
    </head>
    <body>
             <!-- Header with Back Button and User Profile -->
      <div class="header">
            <a href="AcademAI-View- Creation-Quiz.php" class="back-btn">
            <i class="fa-solid fa-chevron-left"></i>
            </a>   
            <div class="header-right">  
                <div class="user-profile">
                <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="User" class="profile-pic" onerror="this.onerror=null; this.src='../img/default-avatar.jpg'">    
                    <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($email); ?></span>
                      
                    </div>
                </div>
            </div>
        </div>
        <!-- Header with Back Button and User Profile -->
        <div class="essay-criteria-setting-container-2">
       


            <form id="quizFormrubric" action="../tools/create-quiz.php" method="POST">
    
            <div class="subject-container-2"> 
                <div class="btn-group btn-group-choose-rubric">
                <button type="button" class="btn btn-choose-rubric">Choose Rubric</button>
                <button type="button" class="btn dropdown-choose-rubric-btn dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>

                <ul id="rubric-dropdown" class="dropdown-menu dropdown-choose-rubric" static>
                    <?php if (isset($subjects) && count($subjects) > 0): ?>
                        <?php foreach ($subjects as $subject): ?>
                            <li>
                                <a class="dropdown-item" href="#" id="dropdown-rubric-items"
                                data-subject-id="<?= htmlspecialchars($subject['subject_id']); ?>" 
                                data-title="<?= htmlspecialchars($subject['title']); ?>">
                                    <?= htmlspecialchars($subject['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="#">No subjects found</a></li>
                        <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li>
                        <a class="dropdown-item create-new" href="create-new-rubric-inside.php">
                            <i class="fas fa-plus-circle me-2"></i>Create New Rubric
                        </a>
                    </li>
                </ul>
                </div>
        
        



                <div class = "no-subject-selected">
                <img src = "../img/book.gif" class = "book-gif" style = "display:none;">
                <h1 class="subject-title-1" style = "display:none">Please select the rubric criteria to apply to your essay.</h1>
                
                        </div>
                
                    <div class = "subject-container-1">
                    <h1 class="subject-title" id="selected-subject-title"></h1>
                <div class="criteria-table-container" style ="display:none;">
                    <table class=" table table-hover ">
    
                        <thead class="criteria-heading" id="criteria-heading">
                            <!-- Headers will be dynamically added here -->
                        </thead>
               
                        



                        <tbody id="criteria-table-body" class="predefined-criteria">
                            <!-- Rows will be added here dynamically -->
                        </tbody>
                    </table>

                
                    
                        

                </div>  

                        <!-- Add the OK button below the table -->
                        <div class="ok-button-container">

    <button class="ok-criteria" id="okButton" style = "display:none" >
    Apply Rubric <i class="fa-solid fa-clipboard-check"></i></button> <!-- Hide by default -->
    </div>
                </div>   
                
        

            </div>
    
        </div>
    

        </div>


    </form>

  

<script>
document.addEventListener("DOMContentLoaded", function() {
    const dropdownItems = document.querySelectorAll('#rubric-dropdown .dropdown-item');
    const criteriaTableBody = document.getElementById('criteria-table-body');
    const selectedSubjectTitle = document.getElementById('selected-subject-title');
    const criteriaTableContainer = document.querySelector('.criteria-table-container');
    const criteriaHeading = document.getElementById('criteria-heading');
    const okButton = document.getElementById('okButton');
    const bookGif = document.querySelector('.book-gif');
    const pleaseSelect = document.querySelector('.subject-title-1');

    // Initially hide the OK button, table container, and table heading
    okButton.style.display = 'none';
    criteriaTableContainer.style.display = 'none';
    criteriaHeading.style.display = 'none';
    bookGif.style.display = 'block';
    pleaseSelect.style.display = 'block';

    // Load the selected subject and table visibility state from localStorage
    const savedSubjectId = localStorage.getItem('selectedSubjectId');
    const savedSubjectTitle = localStorage.getItem('selectedSubjectTitle');
    const savedCriteriaData = localStorage.getItem('criteriaData');
    const isTableVisible = localStorage.getItem('isTableVisible') === 'true';

    // Check if a subject is already selected from localStorage
    if (savedSubjectId && savedSubjectTitle) {
        selectedSubjectTitle.textContent = savedSubjectTitle;
        loadCriteria(savedSubjectId, savedCriteriaData);
    }

    // Set the table visibility based on saved state
    if (isTableVisible) {
        criteriaTableContainer.style.display = 'block';
        criteriaHeading.style.display = 'table-header-group';
    } else {
        criteriaTableContainer.style.display = 'none';
        criteriaHeading.style.display = 'none';
    }

    
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Skip handling if this is the "Create New Rubric" link
            if (this.getAttribute('href') === 'create-new-rubric-inside.php') {
                return; // Let the default link behavior happen
            }
            
            e.preventDefault();
            const subjectId = this.getAttribute('data-subject-id');
            const subjectTitle = this.getAttribute('data-title');

            // Update subject title and store it in local storage
            selectedSubjectTitle.textContent = subjectTitle;
            localStorage.setItem('selectedSubjectId', subjectId);
            localStorage.setItem('selectedSubjectTitle', subjectTitle);

            // Show the table container and heading if a subject is selected
            criteriaTableContainer.style.display = 'block';
            criteriaHeading.style.display = 'table-header-group';

            // Save the table visibility state to localStorage
            localStorage.setItem('isTableVisible', 'true');

            // Fetch criteria for the selected subject
            loadCriteria(subjectId);
        });
    });

    // Function to load criteria based on the subject
    function loadCriteria(subjectId, storedData = null) {
        if (storedData) {
            displayCriteria(JSON.parse(storedData));
        } else {
            fetch(`fetch_criteria.php?subject_id=${subjectId}`)
                .then(response => response.json())
                .then(data => {
                    criteriaTableBody.innerHTML = ''; // Clear existing table content

                    if (!data || data.length === 0) {
                        criteriaTableBody.innerHTML = '<tr><td colspan="6">No criteria found for this subject.</td></tr>';
                        okButton.style.display = 'none';
                        bookGif.style.display = 'block';
                        pleaseSelect.style.display = 'block';
                    } else {
                        localStorage.setItem('criteriaData', JSON.stringify(data));
                        displayCriteria(data);
                    }
                })
                .catch(error => {
                    console.error('Error fetching criteria:', error);
                    criteriaTableBody.innerHTML = '<tr><td colspan="6">Error loading criteria.</td></tr>';
                    okButton.style.display = 'none';
                    bookGif.style.display = 'block';
                    pleaseSelect.style.display = 'block';
                });
        }
    }

    function displayCriteria(data) {
    criteriaTableBody.innerHTML = ''; // Clear existing table content
    
    if (!data || !data.rows || data.rows.length === 0) {
        criteriaTableBody.innerHTML = '<tr><td colspan="6">No criteria found for this subject.</td></tr>';
        okButton.style.display = 'none';
        bookGif.style.display = 'block';
        pleaseSelect.style.display = 'block';
    } else {
        // Clear existing headers
        criteriaHeading.innerHTML = '';

        // Create a new header row
        const headerRow = document.createElement('tr');

        // Add the "Criteria" header
        const criteriaHeader = document.createElement('th');
        criteriaHeader.scope = 'col';
        criteriaHeader.textContent = 'Criteria';
        headerRow.appendChild(criteriaHeader);

        // Add headers for each level
        data.headers.forEach(header => {
            const levelHeader = document.createElement('th');
            levelHeader.scope = 'col';

            // Create a div for the level name
            const levelNameDiv = document.createElement('div');
            levelNameDiv.className = 'd-flex justify-content-center';
            levelNameDiv.textContent = header;

            // Create a div for the percentage (if applicable)
            const percentageDiv = document.createElement('div');
            percentageDiv.className = 'd-flex align-items-center justify-content-center py-2';
            const percentageSpan = document.createElement('span');
            percentageSpan.className = 'px-2';

            percentageDiv.appendChild(percentageSpan);

            // Append the divs to the header
            levelHeader.appendChild(levelNameDiv);
            levelHeader.appendChild(percentageDiv);

            headerRow.appendChild(levelHeader);
        });

       

        // Append the header row to the table heading
        criteriaHeading.appendChild(headerRow);

        // Create table rows from the data
        data.rows.forEach(row => {
            const tableRow = document.createElement('tr');
            
            // Add criteria name
            const criteriaCell = document.createElement('td');
            criteriaCell.textContent = row.criteria;
            tableRow.appendChild(criteriaCell);
            
            // Add cells for each level
            row.cells.forEach(cellContent => {
                const cell = document.createElement('td');
                cell.textContent = cellContent || ''; // Use empty string if cell content is empty
                tableRow.appendChild(cell);
            });
            
            criteriaTableBody.appendChild(tableRow);
        });

        okButton.style.display = 'block';
        bookGif.style.display = 'none';
        pleaseSelect.style.display = 'none';
    }
}
    // Add event listener for the OK button
    okButton.addEventListener('click', function(e) {
        e.preventDefault(); // Prevent any default action
        window.location.href = './AcademAI-View-%20Creation-Quiz.php'; // Adjust path if needed
    });

    // Hide the table heading when the dropdown item is not clicked
    if (!localStorage.getItem('selectedSubjectId')) {
        criteriaHeading.style.display = 'none'; // Ensure the heading is hidden when no subject is selected
    }
});
</script>







    </body>
    </html>

    <style>


/* Profile */

.header {
   display: flex;
   justify-content: space-between;
   align-items: center;
   margin-bottom: 60px;
   padding: 15px;
   background-color: #092635;
   box-shadow: 0 4px 6px -1px rgba(100, 100, 100, 0.3), 0 2px 4px -1px rgba(50, 50, 50, 0.2);
   color: #ffffff;
}

.header-right {
   display: flex;
   align-items: center;
   gap: 20px;
}

.user-profile {
   display: flex;
   align-items: center;
   gap: 12px;
   padding: 8px 16px;
}
.user-info {
   display: flex;
   flex-direction: column;
   max-width: 800px; /* or whatever fits your layout */
   word-wrap: break-word;
   overflow-wrap: break-word;
}

.user-name {
   font-size: 1em;
   font-weight: 700;

}
.user-email {
   font-style: italic;
   font-size:0.875em;
}


.user-name,
.user-email {
   white-space: normal;
   overflow-wrap: break-word;
   word-break: break-word;
}



.profile-pic {
   width: 48px;
   height: 48px;
   border-radius: 50%;
   object-fit: cover;
   border: 2px solid #5C8374;
}

.back-btn {
   display: inline-flex;
   align-items: center;
   gap: 5px;
   text-decoration: none;
   color: #ffffff;
   font-weight: 500;
   font-size:2em;
   transition: color 0.3s ease, transform 0.3s ease;
}                                                                          

.back-btn:hover {
   color: #ffffff;
   transform: translateX(-5px); /* move slightly to the left */
   text-decoration: none;
 }
 


/* Profile */ 
</style>