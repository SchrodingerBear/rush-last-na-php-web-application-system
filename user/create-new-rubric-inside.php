<?php
// Start session and check authentication
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Prevent caching of sensitive pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include required files
require_once('../include/extension_links.php');
require_once("../tools/add-new-subject-criteria.php");

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Add database connection
require_once('../classes/connection.php'); // Adjust path as needed
 // Connect to the database
 $db = new Database();
 $conn = $db->connect();

// Check if connection was successful
if (!$conn) {
    die("Database connection failed");
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
    <title>Rubrics Editor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/create-new-rubric-inside.css">
   
</head>
<body>
                 <!-- Header with Back Button and User Profile -->
            <div class="header">
            <a href="AcademAI-Set-Essay-Rubric.php" class="back-btn">
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
   
    <div class = "essay-container">
    <div class="essay-criteria-setting-container">
    <!-- Sidebar for buttons -->
    <div class="sidebar">
    <div class="actions">
        <button id="addRowBtn" class="button"><i class="fas fa-plus"></i> Add Row</button>
        <button id="addColumnBtn" class="button blue-button"><i class="fas fa-columns"></i> Add Column</button>
        <button id="saveRubricBtn" class="button purple-button" ><i class="fas fa-save"></i> Save Rubric</button>
        </div>

</div>




        <div class="con">
        
 <!-- Main content area -->
 <div class="main-content">


        <div class = "rubric-section">
<!-- Rubric Title with Label -->
<div style="margin-bottom: 15px;" id="titleContainer">
  <label for="currentRubricTitle" style="display: block; margin-bottom: 5px; font-weight: 500; color: #092635; font-family: 'Inter', sans-serif;">
    Title:
  </label>
  <input 
    type="text" 
    id="currentRubricTitle" 
    style="
      color: #092635;
      padding: 5px;
      box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);
      border-radius: 5px;
      font-family: 'Inter', sans-serif;
      border: 1px solid #ccc;
      width: 50%;
      font-size: 1em;
      min-height: 60px;
    "
    name="title"
  />
</div>

<!-- Rubric Description with Label -->
<div style="margin-bottom: 15px;" id="descriptionContainer">
  <label for="currentRubricDescription" style="display: block; margin-bottom: 5px; font-weight: 500; color: #092635; font-family: 'Inter', sans-serif;">
    Description:
  </label>
  <textarea 
    id="currentRubricDescription" 
    style="
      color: #092635;
      padding: 5px;
      box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.2);
      border-radius: 5px;
      font-family: 'Inter', sans-serif;
      border: 1px solid #ccc;
      width: 50%;
      resize: vertical;
      font-size: 1em;
      min-height: 60px;
    "
    name="description"
  ></textarea>
</div>
        <br>
        <div id="tableContainer">
            <table id="rubricsTable">
                <thead>
                    <tr id="headerRow">
                        <th class="fixed-header">Criteria</th>
                        <!-- Headers will be inserted here -->
                    </tr>
                </thead>
                <tbody>
                    <!-- Rows will be inserted here -->
                </tbody>
            </table>
        </div>
  



        
        
       

    
 


     



    </div>    </div>
    </div>
    







    
    <script>
        // Global variables
        let currentRubricId = null;
        let isEditing = false;
        
        // Initial data setup
        const initialHeaders = [
            "Advanced (5)",
            "Proficient (4)",
            "Needs Improvement (3)",
            "Warning (2)",
            "Weight %"
        ];
        
        const initialRows = [
            {
                criteria: "Thesis Statement",
                cells: [
                    "",
                    "",
                    "",
                    "",
                    "25"
                ]
            },
            {
                criteria: "Use of Evidence & Research",
                cells: [
                    "",
                    "",
                    "",
                    "",
                    "25"
                ]
            },
            {
                criteria: "Organization & Structure",
                cells: [
                    "",
                    "",
                    "",
                    "",
                    "25"
                ]
            },
            {
                criteria: "Grammar, Mechanics & Style",
                cells: [
                     "",
                    "",
                    "",
                    "",
                    "25"
                ]
            }
        ];
        
        // Initialize the table
        function initializeTable() {
            const headerRow = document.getElementById('headerRow');
            const tableBody = document.querySelector('#rubricsTable tbody');
            
            // Clear existing content
            while (headerRow.children.length > 1) {
                headerRow.removeChild(headerRow.lastChild);
            }
            tableBody.innerHTML = '';
            
            // Add headers
            initialHeaders.forEach((header, index) => {
                const th = document.createElement('th');
                if (index === initialHeaders.length - 1) {
                    // Weight % header - fixed
                    th.textContent = header;
                    th.classList.add('fixed-header');
                } else {
                    // Editable headers
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = header;
               
            input.style.color = 'white'; 
                    input.addEventListener('change', function() {
                        // Store updated value
                        initialHeaders[index] = this.value;
                    });
                    th.appendChild(input);
                    
                    // Add delete button for columns except Weight %
                    if (initialHeaders.length > 3) { // Keep at least 1 grading column
                        const deleteBtn = document.createElement('span');
                        deleteBtn.className = 'delete-btn delete-col-btn';
                        deleteBtn.innerHTML = '✕';
                        deleteBtn.title = 'Delete column';
                        deleteBtn.onclick = function() {
                            deleteColumn(index);
                        };
                        th.appendChild(deleteBtn);
                    }
                }
                headerRow.appendChild(th);
            });
            
            // Add rows
            initialRows.forEach((row, rowIndex) => {
                addTableRow(row, rowIndex);
            });
        }
        
        // Add a table row
        function addTableRow(rowData, rowIndex) {
        
            const tableBody = document.querySelector('#rubricsTable tbody');
            const tr = document.createElement('tr');
            
            // Add criteria cell
            const tdCriteria = document.createElement('td');
            const criteriaInput = document.createElement('input');
            criteriaInput.type = 'text';
            criteriaInput.value = rowData.criteria;
            criteriaInput.addEventListener('change', function() {
                // Store updated value
                initialRows[rowIndex].criteria = this.value;
            });
            tdCriteria.appendChild(criteriaInput);
            tr.appendChild(tdCriteria);
            
            // Add other cells
            rowData.cells.forEach((cellText, cellIndex) => {

             
                const td = document.createElement('td');
                td.className = 'editable';
                
                if (cellIndex === rowData.cells.length - 1) {
                    // Weight % cell - input
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.value = cellText;
                    input.addEventListener('change', function() {
                        // Store updated value
                        initialRows[rowIndex].cells[cellIndex] = this.value;
                    });
                    td.appendChild(input);
                    
                    // Add delete button for the row in the last cell
                    const deleteBtn = document.createElement('span');
                    deleteBtn.className = 'delete-btn delete-row-btn';
                    deleteBtn.innerHTML = '✕';
                    deleteBtn.title = 'Delete row';
                    deleteBtn.onclick = function() {
                        deleteRow(rowIndex);
                    };
                    td.appendChild(deleteBtn);
                } else {
                    // Content cells - textarea
                    const textarea = document.createElement('textarea');
                    textarea.value = cellText;
                    textarea.addEventListener('change', function() {
                        // Store updated value
                        initialRows[rowIndex].cells[cellIndex] = this.value;
                    });
                    td.appendChild(textarea);
                }
                
                tr.appendChild(td);
            });
            
            tableBody.appendChild(tr);
        }
        
        // Add new row
        function addRow() {
            const newRow = {
                criteria: "New Criteria",
                cells: []
            };
            
            // Create cells for each column
            for (let i = 0; i < initialHeaders.length; i++) {
                if (i === initialHeaders.length - 1) {
                    newRow.cells.push("0"); // Weight % value
                } else {
                    newRow.cells.push("");
                }
            }
            
            initialRows.push(newRow);
            addTableRow(newRow, initialRows.length - 1);
        }
        
        // Delete row
        function deleteRow(rowIndex) {
            initialRows.splice(rowIndex, 1);
            refreshTable();
        }
        
        // Add new column
        function addColumn() {
            // Check if the number of columns (including Weight %) is already 6
        if (initialHeaders.length >= 6) {
            alert("You cannot add more than 5 grading columns.");
            return;
        }

            // Add new header before Weight %
            initialHeaders.splice(initialHeaders.length - 1, 0, "New Level");
            
            // Add new cell to each row
            initialRows.forEach(row => {
                row.cells.splice(row.cells.length - 1, 0, "Click to edit");
            });
            
            refreshTable();
        }
        
        // Delete column
        function deleteColumn(columnIndex) {
            // Ensure we don't delete the Weight % column or the last grading column
            if (columnIndex === initialHeaders.length - 1 || initialHeaders.length <= 3) {
                return;
            }
            
            // Remove header
            initialHeaders.splice(columnIndex, 1);
            
            // Remove corresponding cell from each row
            initialRows.forEach(row => {
                row.cells.splice(columnIndex, 1);
            });
            
            refreshTable();
        }
        
        // Refresh the entire table
        function refreshTable() {
            initializeTable();
        }
        
        // Get current rubric data
        function getRubricData() {
            return {
                headers: initialHeaders,
                rows: initialRows
            };
        }
        
        







// Update existing rubric or save new one
function updateRubric() {
    if (!validateWeights()) {
        return; // Stop if weights are invalid
    }

    // Get values from the visible title and description inputs
    const titleInput = document.getElementById('currentRubricTitle');
    const descInput = document.getElementById('currentRubricDescription');
    
    const title = titleInput.value.trim();
    const description = descInput.value.trim();
    
    if (!title) {
        alert('Please enter a title for your rubric.');
        return;
    }

    const rubricData = getRubricData();
    
    // Determine if we're creating new or updating existing
    const action = currentRubricId ? 'update' : 'save_new';
    const payload = {
        action: action,
        title: title,
        description: description,
        data: rubricData
    };
    
    if (currentRubricId) {
        payload.rubric_id = currentRubricId;
    }
    
    // Send data to server
    fetch('save_rubric.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (!currentRubricId) {
                currentRubricId = data.rubric_id;
                document.getElementById('saveRubricBtn').disabled = false;
            }
            alert('Rubric saved successfully!');
        } else {
            alert('Error saving rubric: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving rubric. Please try again.');
    });
}








    function validateWeights() {
    let totalWeight = 0;
    let hasInvalidWeights = false;
    const weightInputs = document.querySelectorAll('#rubricsTable tbody tr td:last-child input');
    
    // Check individual weights
    weightInputs.forEach(input => {
        const weight = parseFloat(input.value);
        input.style.border = ""; // Reset styling
        
        if (isNaN(weight)) {
            input.style.border = "1px solid red";
            hasInvalidWeights = true;
            alert("❌ Error: Weight must be a number (e.g., 25, 10.5)");
            return; // Exit early on first error
        }
        
        if (weight < 0) {
            input.style.border = "1px solid red";
            hasInvalidWeights = true;
            alert("❌ Error: Weight cannot be negative");
            return;
        }
        
        totalWeight += weight;
    });
    
    // Check total weight
    if (Math.abs(totalWeight - 100) > 0.01) {
        weightInputs.forEach(input => input.style.border = "1px solid red");
        alert(`🚨 Total weight must be exactly 100% (Current: ${totalWeight.toFixed(2)}%)`);
        return false;
    }
    
    return !hasInvalidWeights;
}

function updateWeightTotal() {
    let totalWeight = 0;
    const weightInputs = document.querySelectorAll('#rubricsTable tbody tr td:last-child input');
    
    weightInputs.forEach(input => {
        totalWeight += parseFloat(input.value) || 0;
    });
    
    const totalSpan = document.getElementById('currentWeightTotal');
    totalSpan.textContent = totalWeight.toFixed(2);
    
    // Change color if not 100%
    if (Math.abs(totalWeight - 100) > 0.01) {
        totalSpan.style.color = "red";
    } else {
        totalSpan.style.color = "green";
    }
}

// Call this whenever weights change
document.querySelector('#rubricsTable').addEventListener('input', function(e) {
    if (e.target.matches('td:last-child input')) {
        updateWeightTotal();
    }
});
        
        // Event listeners
        document.getElementById('addRowBtn').addEventListener('click', addRow);
        document.getElementById('addColumnBtn').addEventListener('click', addColumn);
        document.getElementById('saveRubricBtn').addEventListener('click', updateRubric);
      
        
   
        
        // Initialize table on page load
        document.addEventListener('DOMContentLoaded', initializeTable);
    </script>





<script>
    function showDeleteModal(rubricId) {
    const modal = document.getElementById('deleteModal');
    const confirmBtn = document.getElementById('confirmDelete');

    modal.style.display = 'block';

    // Set event listener for confirmation
    confirmBtn.onclick = function() {
        deleteRubric(rubricId);
        closeDeleteModal();
    };
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>


  <!-- Delete Rubric Modal -->

<div class="modal" tabindex="-1" id="deleteModal">
  <div class="modal-dialog">
    <div class="modal-content" id= "deleteModal-content">
      <div class="modal-header" id= "modal-header-delete-rubric">
      <h5 class="modal-title">Confirm Deletion</h5>

        <button type="button"onclick="closeDeleteModal()" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id = "modal-body-delete-rubric" >
        <p>Are you sure you want to delete this rubric?</p>
      </div>
      <div class="modal-footer">
      <button id="confirmDelete" class="confirm-btn">Delete</button>
      <button class="cancel-btn" id = "cancel-btn-delete"onclick="closeDeleteModal()">Cancel</button>
      </div>
    </div>
  </div>
</div>



</div>
</body>
</html>

<style>


/* Profile */

.header {
   display: flex;
   justify-content: space-between;
   align-items: center;
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