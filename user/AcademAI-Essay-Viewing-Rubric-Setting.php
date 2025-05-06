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
?>
 <?php
    // Include the sidebar
    require_once '../include/new-academai-sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrics Editor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/essay_rubric_setting-1.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
   
    <div class = "essay-container">
    <div class="essay-criteria-setting-container">
    <div class ="d-flex align-items-center">
    <i class="fas fa-cogs"></i>  <h1 style =  "  color: #1b4242; margin-bottom: 10px; letter-spacing:5;   font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif; ">Rubrics Setting</h1>
</div>
<!-- Add this right after the "Rubrics Setting" heading -->
<div class="rubric-dimensions-controls">
    <div class="dimension-control">
        <label for="rowCount">Rows:</label>
        <div class="counter-controls">
            <button class="counter-btn" id="decreaseRowBtn"><i class="fas fa-minus"></i></button>
            <input type="number" id="rowCount" min="2" max="8" value="4" class="counter-input">
            <button class="counter-btn" id="increaseRowBtn"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    
    <div class="dimension-control">
        <label for="columnCount">Columns:</label>
        <div class="counter-controls">
            <button class="counter-btn" id="decreaseColBtn"><i class="fas fa-minus"></i></button>
            <input type="number" id="columnCount" min="2" max="5" value="5" class="counter-input">
            <button class="counter-btn" id="increaseColBtn"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    
    <div class="dimension-control">
        <button id="applyDimensionsBtn" class="button green-button"><i class="fas fa-check"></i> Apply</button>
    </div>
</div>

<!-- Add this CSS to your existing stylesheet or inline -->
<style>
.rubric-dimensions-controls {
    display: flex;
    gap: 20px;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.dimension-control {
    display: flex;
    align-items: center;
    gap: 10px;
}

.dimension-control label {
    font-weight: 500;
    color: #1b4242;
    margin-bottom: 0;
}

.counter-controls {
    display: flex;
    align-items: center;
    border: 1px solid #ced4da;
    border-radius: 5px;
    overflow: hidden;
}

.counter-btn {
    background-color: #e9ecef;
    border: none;
    color: #495057;
    cursor: pointer;
    height: 32px;
    width: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.2s;
}

.counter-btn:hover {
    background-color: #dee2e6;
}

.counter-input {
    width: 50px;
    text-align: center;
    border: none;
    border-left: 1px solid #ced4da;
    border-right: 1px solid #ced4da;
    height: 32px;
    padding: 0 5px;
}

.counter-input:focus {
    outline: none;
}

#applyDimensionsBtn {
    padding: 6px 15px;
    font-size: 0.9em;
}
</style>
<!-- Rubric Title with Label -->
<div style="margin-bottom: 15px; display: none;" id="titleContainer">
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
      width: 60%;
      font-size: 1em;
      min-height: 60px;
    "
    name="title"
  />
</div>

<!-- Rubric Description with Label -->
<div style="margin-bottom: 15px; display: none;" id="descriptionContainer">
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
      width: 60%;
      resize: vertical;
      font-size: 1em;
      min-height: 60px;
    "
    name="description"
  ></textarea>
</div>




        <div class="con">
        <div class="actions">
         
        <button id="saveNewBtn" class="button orange-button" data-bs-toggle="modal" data-bs-target="#saveRubricModal">
  <i class="fas fa-save"></i> Save as New Rubric
</button>

            <button id="updateRubricBtn" class="button purple-button" disabled><i class="fas fa-sync-alt"></i> Update Rubric</button>
            <button id="viewRubricsBtn" class="button"><i class="fas fa-list"></i> Rubrics List</button>
        </div>
        <br>
        <div id="tableContainer" class="table-responsive-wrapper ">
  <div class="table-responsive-inner">
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
</div>

        
        <!-- Save New Rubric Modal -->
        <div class="modal " id="saveRubricModal">
            <div class="modal-dialog" style="max-width: 700px; width: 100%;">
                <div class="modal-content" id="rubricmodalcontent">

                <div class="modal-header">
                    <h5 class="modal-title" id="saveRubricModalLabel">Confirm Save New Rubric</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeSaveModal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                    <label for="rubricTitle" class="form-label">Rubric Title:</label>
                    <input type="text" class="form-control" id="rubricTitle">
                    </div>
                    <div class="form-group mb-3">
                    <label for="rubricDescription" class="form-label">Description (optional):</label>
                    <textarea class="form-control" id="rubricDescription" rows="3" style="width: 100%;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="confirmSaveBtn" class="btn button"><i class="fas fa-save"></i> Save Rubric</button>
                
                </div>
                </div>
            </div>
            </div>




        
        
        <!-- Rubrics List Modal -->
        <div id="rubricsListModal" class="modal">
        <div class="modal-dialog" style="max-width: 1300px; width: 100%;">
            <div class="modal-content" id="contentofrubriclist ">
            <div class="modal-header">
                    <h5 class="modal-title" id="saveRubricModalLabel">Saved Rubrics Overview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeListModal"></button>
                </div>

                <div id="rubricsListContainer" style = "padding:1em;">
                    <table id="rubricsList">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rubrics will be loaded here -->
                        </tbody>


                    </table>
                </div>
            </div> 
        </div>
        </div>

        <div class = "add-section">
                <button id="addRowBtn" class="button"><i class="fas fa-plus"></i> Add Row</button>
                <button id="addColumnBtn" class="button blue-button"><i class="fas fa-columns"></i> Add Column</button>
</div>


    </div>    </div>
    </div>
    







    
    <script>
        // Global variables
        let currentRubricId = null;
        let isEditing = false;
        
        // Initial data setup
        const initialHeaders = [
            "Needs Improvement (1)",
            "Good (2)",
            "Excellent (3)",
            "Satisfactory (4)",
            "Very Satisfactory (5)",
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
                    if (index >= 1 && initialHeaders.length > 3 && index === initialHeaders.length - 2) { // Show delete button only for the rightmost grading column if more than 1 grading column exists
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
            if (initialRows.length >= 8) {
            Swal.fire({
                icon: 'error',
                title: 'Limit Reached',
                text: 'You cannot add more than 8 rows.'
            });
            return;
            }

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
            Swal.fire({
                icon: 'error',
                title: 'Limit Reached',
                text: 'You cannot add more than 5 grading columns.'
            });
            return;
            }

            const newLevelNumber = 6 - initialHeaders.length;

            const newHeaderNames = ["Very Satisfactory (5)", "Satisfactory (4)", "Excellent (3)"];
            const newHeaderName = newHeaderNames[newLevelNumber - 1] || `New Level (${newLevelNumber})`;
            initialHeaders.splice(initialHeaders.length - 1, 0, newHeaderName);

            // Add new cell to each row
            initialRows.forEach(row => {
            row.cells.splice(row.cells.length - 1, 0, "");
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
        
        // Save new rubric to database
        function saveNewRubric() {
            const modal = document.getElementById('saveRubricModal');
            modal.style.display = 'block';
        }
        
        // Confirm save rubric
        function confirmSaveRubric() {
            if (!validateWeights()) {
        return; // Stop if weights are invalid
    }
            const title = document.getElementById('rubricTitle').value.trim();
            const description = document.getElementById('rubricDescription').value.trim();
            
            if (!title) {
                alert('Please enter a title for your rubric.');
                return;
            }
            
            const rubricData = getRubricData();
            
            // Send data to server
            fetch('save_rubric.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save_new',
                    title: title,
                    description: description,
                    data: rubricData
                })
            })
            .then(response => response.json())
            .then(data => {
    if (data.success) {
        currentRubricId = data.rubric_id;
        document.getElementById('updateRubricBtn').disabled = false;
        
        // Update the title display
        const titleElement = document.getElementById('currentRubricTitle');
        titleElement.textContent = 'Current Rubric: ' + title;
        titleElement.style.display = 'block';
        
        // Reset form
        document.getElementById('rubricTitle').value = '';
        document.getElementById('rubricDescription').value = '';
        
        // Hide modal first
        $('#saveRubricModal').modal('hide');
        
        // Remove backdrop manually if needed
        $('.modal-backdrop').remove();
        
        // Reset body class
        document.body.classList.remove('modal-open');
        
        // Show alert after modal is fully hidden
        setTimeout(() => {
            Swal.fire({
            icon: 'success',
            title: 'Rubric Saved',
            text: 'Rubric saved successfully!'
            });
        }, 200);
    } else {
        alert('Error saving rubric: ' + data.message);
    }
});
        }
        
        // Update existing rubric
        function updateRubric() {
            if (!validateWeights()) {
        return; // Stop if weights are invalid
    }

            if (!currentRubricId) {
                alert('No rubric selected for update. Please save as new or load a rubric first.');
                return;
            }

            const title = document.getElementById('currentRubricTitle').value.trim();
            const description = document.getElementById('currentRubricDescription').value.trim();
            
            if (!title) {
                alert('Please enter a title for your rubric.');
                return;
            }

            const rubricData = getRubricData();
            
            // Send data to server
            fetch('save_rubric.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    rubric_id: currentRubricId,
                    title: title,
                    description: description,
                    data: rubricData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rubric Updated',
                        text: 'Rubric updated successfully!'
                    });
                } else {
                    alert('Error updating rubric: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating rubric. Please try again.');
            });
        }
        
        // Load rubrics list
        function loadRubricsList() {
            const modal = document.getElementById('rubricsListModal');
            const tbody = document.querySelector('#rubricsList tbody');
            tbody.innerHTML = '<tr><td colspan="5">Loading rubrics...</td></tr>';
            modal.style.display = 'block';
            
            // Fetch rubrics from server
            fetch('get_rubrics.php')
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = '';
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5">No rubrics found. Create and save a new rubric to get started.</td></tr>';
                    return;
                }
                
                data.forEach(rubric => {
                    const tr = document.createElement('tr');
                    
                    const tdTitle = document.createElement('td');
                    tdTitle.textContent = rubric.title;
                    tr.appendChild(tdTitle);
                    
                    const tdDescription = document.createElement('td');
                    tdDescription.textContent = rubric.description || 'No description';
                    tr.appendChild(tdDescription);
                    
                    const tdCreated = document.createElement('td');
                    tdCreated.textContent = new Date(rubric.created_at).toLocaleString();
                    tr.appendChild(tdCreated);
                    
                    const tdUpdated = document.createElement('td');
                    tdUpdated.textContent = new Date(rubric.updated_at).toLocaleString();
                    tr.appendChild(tdUpdated);
                    
                    const tdActions = document.createElement('td');
                    
                    const loadAction = document.createElement('span');
                    loadAction.className = 'rubric-action';
                    loadAction.textContent = 'Load';
                    loadAction.onclick = function() {
                        loadRubric(rubric.id);
                    };
                    tdActions.appendChild(loadAction);
                    
                    const deleteAction = document.createElement('span');
                    deleteAction.className = 'rubric-action';
                    deleteAction.textContent = 'Delete';
                    deleteAction.onclick = function() {
                        showDeleteModal(rubric.id);
                    };

                    tdActions.appendChild(deleteAction);
                    
                    tr.appendChild(tdActions);
                    tbody.appendChild(tr);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                tbody.innerHTML = '<tr><td colspan="5">Error loading rubrics. Please try again.</td></tr>';
            });
        }
        
        // Load a specific rubric
        function loadRubric(rubricId) {
            fetch(`get_rubric.php?id=${rubricId}`)
            .then(response => {
                // Check if response is OK
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Rubric data received:", data); // Debug line
                
                if (data.success) {
                    const rubricData = data.rubric;
                    
                    // Make sure the data structure is correct
                    if (!rubricData || !rubricData.headers || !rubricData.rows) {
                        throw new Error('Invalid rubric data structure');
                    }
                    
                    // Clear existing arrays before populating them
                    initialHeaders.length = 0;
                    initialRows.length = 0;
                    
                    // Copy headers and rows from the fetched data
                    rubricData.headers.forEach(header => initialHeaders.push(header));
                    rubricData.rows.forEach(row => initialRows.push(row));
                    
                    // Update current rubric ID
                    currentRubricId = rubricId;
                    document.getElementById('updateRubricBtn').disabled = false;
                    
                    // Display the current rubric title and description with labels
                    const titleContainer = document.getElementById('titleContainer');
                    const titleElement = document.getElementById('currentRubricTitle');
                    const descriptionContainer = document.getElementById('descriptionContainer');
                    const descriptionElement = document.getElementById('currentRubricDescription');

                    // Set values and show elements with labels
                    titleElement.value = data.title;
                    descriptionElement.value = data.description;

                    // Show the containers (which include both labels and fields)
                    titleContainer.style.display = 'block';
                    descriptionContainer.style.display = 'block';

                    // Refresh table
                    refreshTable();
                    
                    // Close modal
                    document.getElementById('rubricsListModal').style.display = 'none';
                } else {
                    alert('Error loading rubric: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('Error loading rubric: ' + error.message);
            });
        }
        
        // Delete a rubric
        function deleteRubric(rubricId) {
            fetch('delete_rubric.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    rubric_id: rubricId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload rubrics list
                    loadRubricsList();
                    
                    // If deleted current rubric, reset
                    if (currentRubricId === rubricId) {
                        currentRubricId = null;
                        document.getElementById('updateRubricBtn').disabled = true;
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Rubric Deleted',
                        text: 'Rubric deleted successfully!'
                    });
                } else {
                    alert('Error deleting rubric: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting rubric. Please try again.');
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
        Swal.fire({
            icon: 'error',
            title: 'Invalid Total Weight',
            text: `Total weight must be exactly 100% (Current: ${totalWeight.toFixed(2)}%)`
        });
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
        document.getElementById('saveNewBtn').addEventListener('click', saveNewRubric);
        document.getElementById('updateRubricBtn').addEventListener('click', updateRubric);
        document.getElementById('viewRubricsBtn').addEventListener('click', loadRubricsList);
        document.getElementById('confirmSaveBtn').addEventListener('click', confirmSaveRubric);
        
        // Modal close buttons
        document.getElementById('closeSaveModal').addEventListener('click', function() {
            document.getElementById('saveRubricModal').style.display = 'none';
        });
        
        document.getElementById('closeListModal').addEventListener('click', function() {
            document.getElementById('rubricsListModal').style.display = 'none';
        });
        
        // Close modals when clicking outside of them
        window.addEventListener('click', function(event) {
            const saveModal = document.getElementById('saveRubricModal');
            const listModal = document.getElementById('rubricsListModal');
            
            if (event.target === saveModal) {
                saveModal.style.display = 'none';
            }
            
            if (event.target === listModal) {
                listModal.style.display = 'none';
            }
        });
        
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

<script>
    // Add this code to your existing script section
document.addEventListener('DOMContentLoaded', function() {
    // Add Auto-Generate button to the actions div
    const actionsDiv = document.querySelector('.actions');
    const autoGenerateBtn = document.createElement('button');
    autoGenerateBtn.id = 'autoGenerateBtn';
    autoGenerateBtn.className = 'button green-button';
    autoGenerateBtn.innerHTML = '<i class="fas fa-magic"></i> Auto Generate Rubrics';
    actionsDiv.prepend(autoGenerateBtn);
    
    // Add event listener
    autoGenerateBtn.addEventListener('click', showAutoGenerateModal);
});

// Auto Generate Modal
function showAutoGenerateModal() {
    // Create modal if it doesn't exist
    if (!document.getElementById('autoGenerateModal')) {
        createAutoGenerateModal();
    }
    
    // Show the modal
    document.getElementById('autoGenerateModal').style.display = 'block';
}

function createAutoGenerateModal() {
    const modalHTML = `
    <div class="modal" id="autoGenerateModal">
        <div class="modal-dialog" style="max-width: 700px; width: 100%;">
            <div class="modal-content" id="autoGenerateModalContent">
                <div class="modal-header">
                    <h5 class="modal-title">Auto Generate Rubrics</h5>
                    <button type="button" class="btn-close" onclick="closeAutoGenerateModal()" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="subjectPrompt" class="form-label">Subject/Topic:</label>
                        <input type="text" class="form-control" id="subjectPrompt" placeholder="e.g., Literary analysis essay on The Great Gatsby">
                    </div>
                    <div class="form-group mb-3">
                        <label for="levelPrompt" class="form-label">Education Level:</label>
                        <select class="form-control" id="levelPrompt">
                            <option value="elementary">Elementary School</option>
                            <option value="middle">Middle School</option>
                            <option value="high" selected>High School</option>
                            <option value="college">College</option>
                            <option value="undergraduate">Undergraduate</option>
                            <option value="graduate">Graduate</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="additionalCriteria" class="form-label">Additional Criteria (optional):</label>
                        <textarea class="form-control" id="additionalCriteria" rows="3" style="width: 100%;" placeholder="Add specific requirements or focuses for this rubric"></textarea>
                    </div>
                    <div id="generationStatus" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                        </div>
                        <p class="text-center mt-2">Generating rubrics... please wait</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="generateRubricsBtn" class="btn button green-button" onclick="generateRubrics()">
                        <i class="fas fa-magic"></i> Generate
                    </button>
                    <button class="btn button" onclick="closeAutoGenerateModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Append modal to body
    const modalContainer = document.createElement('div');
    modalContainer.innerHTML = modalHTML;
    document.body.appendChild(modalContainer);
}

function closeAutoGenerateModal() {
    document.getElementById('autoGenerateModal').style.display = 'none';
    
    // Reset status
    const statusEl = document.getElementById('generationStatus');
    if (statusEl) statusEl.style.display = 'none';
    
    // Re-enable generate button
    const generateBtn = document.getElementById('generateRubricsBtn');
    if (generateBtn) {
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-magic"></i> Generate';
    }
}

// Updated generateRubrics function to use the PHP proxy and handle the response correctly
async function generateRubrics() {
    const subject = document.getElementById('subjectPrompt').value.trim();
    const level = document.getElementById('levelPrompt').value;
    const additionalCriteria = document.getElementById('additionalCriteria').value.trim();
    
    if (!subject) {
        alert('Please enter a subject or topic for the rubric.');
        return;
    }
    
    // Confirm with user that this will replace current rubric data
    const confirmGenerate = confirm('This will replace your current rubric data. Continue?');
    if (!confirmGenerate) {
        alert('Rubric generation cancelled.');
        return;
    }
    
    // Show status and disable button
    document.getElementById('generationStatus').style.display = 'block';
    const generateBtn = document.getElementById('generateRubricsBtn');
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    // Construct prompt
    const prompt = constructRubricPrompt(subject, level, additionalCriteria);
    
    try {
        // Call the PHP proxy instead of directly calling the Flask API
        const response = await fetch('api_proxy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                essay: prompt,
                rubrics_criteria: "auto-generate"
            })
        });
        
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.evaluation) {
            // Check if evaluation is already a JSON object
            let rubricData;
            if (typeof data.evaluation === 'object') {
                rubricData = data.evaluation;
            } else {
                // Try to parse the evaluation text as JSON
                try {
                    // Try to extract JSON from text response
                    const jsonMatch = data.evaluation.match(/\{[\s\S]*"headers"[\s\S]*"rows"[\s\S]*\}/);
                    const jsonStr = jsonMatch ? jsonMatch[0] : data.evaluation;
                    rubricData = JSON.parse(jsonStr);
                } catch (error) {
                    console.error('Failed to parse JSON from API response:', error);
                    console.log('Raw response:', data.evaluation);
                    throw new Error('Could not parse the rubric data from API response');
                }
            }
            
            // Process the rubric data
            processGeneratedRubrics(rubricData);
            
            // Close the modal
            closeAutoGenerateModal();
            
            // Show success message
            alert('Rubrics generated successfully!');
        } else if (data.error) {
            throw new Error(data.error);
        } else {
            throw new Error('No evaluation data received');
        }
    } catch (error) {
        console.error('Error generating rubrics:', error);
        alert(`Failed to generate rubrics: ${error.message}`);
        
        // Reset UI
        document.getElementById('generationStatus').style.display = 'none';
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-magic"></i> Generate';
    }
}

function constructRubricPrompt(subject, level, additionalCriteria) {
    let levelText = '';
    switch (level) {
        case 'elementary': levelText = 'elementary school'; break;
        case 'middle': levelText = 'middle school'; break;
        case 'high': levelText = 'high school'; break;
        case 'college': levelText = 'college'; break;
        case 'undergraduate': levelText = 'undergraduate college'; break;
        case 'graduate': levelText = 'graduate school'; break;
    }
    
    // Base prompt
    let prompt = `Create a detailed academic rubric for a ${levelText} ${subject} assessment with exactly 4 criteria rows and 4 scoring levels plus a weight column.
    
Format the response as a structured JSON object with this exact format:
{
  "headers": ["Needs Improvement (1)", "Good (2)", "Excellent (3)", "Satisfactory (4)", "Very Satisfactory (5)","Weight %"],
  "rows": [
    {
      "criteria": "CRITERION NAME 1",
      "cells": ["DETAILED DESCRIPTION FOR ADVANCED", "DETAILED DESCRIPTION FOR PROFICIENT", "DETAILED DESCRIPTION FOR NEEDS IMPROVEMENT", "DETAILED DESCRIPTION FOR WARNING", "25"]
    },
    // Additional rows following same pattern
  ]
}

The criteria should be detailed and specific to ${subject}. Each cell should contain a comprehensive description (25-35 words) of what that performance level looks like for that specific criterion. Each criterion must have a weight percentage assigned, and all weights must sum to exactly 100%.`;

    // Add additional criteria if provided
    if (additionalCriteria) {
        prompt += `\n\nAdditional requirements: ${additionalCriteria}`;
    }
    
    // Add limits and formatting requirements
    prompt += `\n\nIMPORTANT: 
1. Limit the response to exactly 4 criteria rows.
2. Each criterion must have detailed descriptions for all 4 performance levels plus a weight percentage.
3. Make sure all weight percentages sum to exactly 100%.
4. Format the output as a valid JSON object with no extra text before or after.
5. Do not use markdown code blocks or any other formatting - just return the raw JSON.`;
    
    return prompt;
}

function processGeneratedRubrics(rubricData) {
    try {
        // Validate structure
        if (!rubricData.headers || !rubricData.rows || !Array.isArray(rubricData.rows)) {
            throw new Error('Invalid rubric data structure');
        }

        // Clear existing arrays
        initialHeaders.length = 0;
        initialRows.length = 0;

        // Determine the maximum number of columns dynamically
        const MAX_COLUMNS = Math.min(rubricData.headers.length, 6); // Limit to 6 columns max: 5 levels + 1 weight column

        // Copy and adjust headers dynamically
        rubricData.headers.slice(0, MAX_COLUMNS).forEach((header, index) => {
            if (MAX_COLUMNS === 6) {
            // Rename levels if there are 5 grading levels
            const renamedHeaders = [
                "Needs Improvement (1)",
                "Good (2)",
                "Excellent (3)",
                "Satisfactory (4)",
                "Very Satisfactory (5)",
                "Weight %"
            ];
            initialHeaders.push(renamedHeaders[index]);
            } else if (MAX_COLUMNS === 5) {
            // Remove the highest level for 4 grading levels
            const renamedHeaders = [
                "Needs Improvement (1)",
                "Good (2)",
                "Excellent (3)",
                "Satisfactory (4)",
                "Weight %"
            ];
            initialHeaders.push(renamedHeaders[index]);
            } else if (MAX_COLUMNS === 4) {
            // Remove the two highest levels for 3 grading levels
            const renamedHeaders = [
                "Needs Improvement (1)",
                "Good (2)",
                "Excellent (3)",
                "Weight %"
            ];
            initialHeaders.push(renamedHeaders[index]);
            } else if (MAX_COLUMNS === 3) {
            // Remove the three highest levels for 2 grading levels
            const renamedHeaders = [
                "Needs Improvement (1)",
                "Good (2)",
                "Weight %"
            ];
            initialHeaders.push(renamedHeaders[index]);
            } else {
            initialHeaders.push(header);
            }
        });

        // Copy and limit rows
        rubricData.rows.forEach(row => {
            if (row.criteria && row.cells && Array.isArray(row.cells)) {
                const limitedCells = row.cells.slice(0, MAX_COLUMNS);
                initialRows.push({
                    criteria: row.criteria,
                    cells: limitedCells
                });
            }
        });

        // Ensure weights sum to 100%
        let totalWeight = 0;
        initialRows.forEach(row => {
            const weight = parseFloat(row.cells[row.cells.length - 1]) || 0;
            totalWeight += weight;
        });

        if (Math.abs(totalWeight - 100) > 0.01) {
            // Normalize weights
            initialRows.forEach(row => {
                const index = row.cells.length - 1;
                const weight = parseFloat(row.cells[index]) || 0;
                row.cells[index] = (weight / totalWeight * 100).toFixed(0);
            });
        }

        // Refresh the table
        refreshTable();

        // Reset current rubric ID since this is a new unsaved rubric
        currentRubricId = null;
        document.getElementById('updateRubricBtn').disabled = true;

        // Hide title and description containers
        const titleContainer = document.getElementById('titleContainer');
        const descriptionContainer = document.getElementById('descriptionContainer');
        if (titleContainer) titleContainer.style.display = 'none';
        if (descriptionContainer) descriptionContainer.style.display = 'none';

    } catch (error) {
        console.error('Error processing rubric data:', error);
        throw error;
    }
}

function constructRubricPrompt(subject, level, additionalCriteria) {
    let levelText = '';
    switch (level) {
        case 'elementary': levelText = 'elementary school'; break;
        case 'middle': levelText = 'middle school'; break;
        case 'high': levelText = 'high school'; break;
        case 'undergraduate': levelText = 'undergraduate college'; break;
        case 'graduate': levelText = 'graduate school'; break;
    }
    
    // Base prompt
    let prompt = `Create a detailed academic rubric for a ${levelText} ${subject} assessment with exactly 4 criteria rows and 4 scoring levels plus a weight column. 
    
Format the response as a structured JSON object with this exact format:
{
    "headers": ["Needs Improvement (1)", "Good (2)", "Excellent (3)", "Satisfactory (4)", "Very Satisfactory (5)","Weight %"],
  "rows": [
    {
      "criteria": "CRITERION NAME 1",
      "cells": ["DETAILED DESCRIPTION FOR ADVANCED", "DETAILED DESCRIPTION FOR PROFICIENT", "DETAILED DESCRIPTION FOR NEEDS IMPROVEMENT", "DETAILED DESCRIPTION FOR WARNING", "25"]
    },
    // Additional rows following same pattern
  ]
}

The criteria should be detailed and specific to ${subject}. Each cell should contain a comprehensive description (25-35 words) of what that performance level looks like for that specific criterion. Each criterion must have a weight percentage assigned, and all weights must sum to exactly 100%.`;

    // Add additional criteria if provided
    if (additionalCriteria) {
        prompt += `\n\nAdditional requirements: ${additionalCriteria}`;
    }
    
    // Add limits and formatting requirements
    prompt += `\n\nIMPORTANT: Limit the response to exactly 4 criteria rows. Each criterion must have detailed descriptions for all 4 performance levels plus a weight percentage. Make sure all weight percentages sum to exactly 100%. Format the output as a valid JSON object with no extra text before or after.`;
    
    return prompt;
}

// Add to your existing script section

// Initialize counters with default values
document.addEventListener('DOMContentLoaded', function() {
    // Set up event listeners for row counter
    document.getElementById('decreaseRowBtn').addEventListener('click', function() {
        const rowCountInput = document.getElementById('rowCount');
        const currentValue = parseInt(rowCountInput.value, 10);
        if (currentValue > parseInt(rowCountInput.min, 10)) {
            rowCountInput.value = currentValue - 1;
        }
    });
    
    document.getElementById('increaseRowBtn').addEventListener('click', function() {
        const rowCountInput = document.getElementById('rowCount');
        const currentValue = parseInt(rowCountInput.value, 10);
        if (currentValue < parseInt(rowCountInput.max, 10)) {
            rowCountInput.value = currentValue + 1;
        }
    });
    
    // Set up event listeners for column counter
    document.getElementById('decreaseColBtn').addEventListener('click', function() {
        const colCountInput = document.getElementById('columnCount');
        const currentValue = parseInt(colCountInput.value, 10);
        if (currentValue > parseInt(colCountInput.min, 10)) {
            colCountInput.value = currentValue - 1;
        }
    });
    
    document.getElementById('increaseColBtn').addEventListener('click', function() {
        const colCountInput = document.getElementById('columnCount');
        const currentValue = parseInt(colCountInput.value, 10);
        if (currentValue < parseInt(colCountInput.max, 10)) {
            colCountInput.value = currentValue + 1;
        }
    });
    
    // Apply dimensions button
    document.getElementById('applyDimensionsBtn').addEventListener('click', applyDimensions);
});

// Function to apply new dimensions
function applyDimensions() {
    const rowCount = parseInt(document.getElementById('rowCount').value, 10);
    const colCount = parseInt(document.getElementById('columnCount').value, 10);
    
    // Validate inputs
    if (isNaN(rowCount) || rowCount < 2 || rowCount > 8) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Row Count',
            text: 'Row count must be between 2 and 8'
        });
        return;
    }
    
    if (isNaN(colCount) || colCount < 2 || colCount > 5) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Column Count',
            text: 'Column count must be between 2 and 5'
        });
        return;
    }
    
    // Confirm with user
    Swal.fire({
        title: `Change dimensions to ${rowCount} rows and ${colCount} columns?`,
        text: "This will reset your current rubric data.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Reset the rubric with new dimensions
            resetRubricWithDimensions(rowCount, colCount);
            Swal.fire({
                icon: 'success',
                title: 'Dimensions Applied',
                text: `Rubric has been reset to ${rowCount} rows and ${colCount} columns.`
            });
        }
    });
}

// Function to reset rubric with custom dimensions
function resetRubricWithDimensions(rowCount, colCount) {
    // Clear existing arrays
    initialHeaders.length = 0;
    initialRows.length = 0;
    
    // Generate new headers based on column count
    const headerNames = [
        "Needs Improvement (1)",
        "Good (2)",
        "Excellent (3)",
        "Satisfactory (4)",
        "Very Satisfactory (5)"
    ];
    
    for (let i = 0; i < colCount; i++) {
        initialHeaders.push(headerNames[i] || `Level ${i + 1} (${i + 1})`);
    }
    initialHeaders.push("Weight %"); // Always add Weight % as the last column
    
    // Generate new rows based on row count
    const defaultWeight = Math.floor(100 / rowCount);
    let remainingWeight = 100 - (defaultWeight * rowCount);
    
    for (let i = 0; i < rowCount; i++) {
        const row = {
            criteria: `Criteria ${i + 1}`,
            cells: []
        };
        
        // Add empty cells for each column
        for (let j = 0; j < colCount; j++) {
            row.cells.push("");
        }
        
        // Add weight
        let rowWeight = defaultWeight;
        if (remainingWeight > 0) {
            rowWeight += 1;
            remainingWeight -= 1;
        }
        row.cells.push(rowWeight.toString());
        
        initialRows.push(row);
    }
    
    // Refresh the table
    refreshTable();
    
    // Reset current rubric ID since this is a new unsaved rubric
    currentRubricId = null;
    document.getElementById('updateRubricBtn').disabled = true;
    
    // Hide title and description if they were showing
    const titleContainer = document.getElementById('titleContainer');
    const descriptionContainer = document.getElementById('descriptionContainer');
    if (titleContainer) titleContainer.style.display = 'none';
    if (descriptionContainer) descriptionContainer.style.display = 'none';
}

// Update the generateRubrics function to pass row and column counts
async function generateRubrics() {
    const subject = document.getElementById('subjectPrompt').value.trim();
    const level = document.getElementById('levelPrompt').value;
    const additionalCriteria = document.getElementById('additionalCriteria').value.trim();
    const rowCount = parseInt(document.getElementById('rowCount').value, 10);
    const columnCount = parseInt(document.getElementById('columnCount').value, 10);
    
    if (!subject) {
        alert('Please enter a subject or topic for the rubric.');
        return;
    }
    
    // Confirm with user that this will replace current rubric data
    Swal.fire({
        title: 'This will replace your current rubric data. Continue?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, replace it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
    });
    
    // Show status and disable button
    document.getElementById('generationStatus').style.display = 'block';
    const generateBtn = document.getElementById('generateRubricsBtn');
    generateBtn.disabled = true;
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    // Construct prompt
    const prompt = constructRubricPrompt(subject, level, additionalCriteria);
    
    try {
        const response = await fetch('api_proxy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                essay: prompt,
                rubrics_criteria: "auto-generate",
                row_count: initialRows.length,
                column_count: initialHeaders.length - 1
            })
        });
        
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.evaluation) {
            // Process the response
            let rubricData;
            if (typeof data.evaluation === 'object') {
            rubricData = data.evaluation;
            } else {
            // Try to parse the evaluation text as JSON
            try {
                const jsonMatch = data.evaluation.match(/\{[\s\S]*"headers"[\s\S]*"rows"[\s\S]*\}/);
                const jsonStr = jsonMatch ? jsonMatch[0] : data.evaluation;
                rubricData = JSON.parse(jsonStr);
            } catch (error) {
                console.error('Failed to parse JSON from API response:', error);
                throw new Error('Could not parse the rubric data from API response');
            }
            }
            
            // Process the rubric data
            processGeneratedRubrics(rubricData);
            
            // Close the modal
            closeAutoGenerateModal();
            
            // Show success message using Swal
            Swal.fire({
            icon: 'success',
            title: 'Rubrics Generated',
            text: 'Rubrics have been generated successfully!'
            });
        } else if (data.error) {
            throw new Error(data.error);
        } else {
            throw new Error('No evaluation data received');
        }
    } catch (error) {
        console.error('Error generating rubrics:', error);
        alert(`Failed to generate rubrics: ${error.message}`);
        
        // Reset UI
        document.getElementById('generationStatus').style.display = 'none';
        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="fas fa-magic"></i> Generate';
    }
}

// Update the constructRubricPrompt function to include the row and column counts
function constructRubricPrompt(subject, level, additionalCriteria) {
    let levelText = '';
    switch (level) {
        case 'elementary': levelText = 'elementary school'; break;
        case 'middle': levelText = 'middle school'; break;
        case 'high': levelText = 'high school'; break;
        case 'college': levelText = 'college'; break;
        case 'undergraduate': levelText = 'undergraduate college'; break;
        case 'graduate': levelText = 'graduate school'; break;
    }
    
    const rowCount = parseInt(document.getElementById('rowCount').value, 10);
    const columnCount = parseInt(document.getElementById('columnCount').value, 10);
    
    // Base prompt
    let prompt = `Create a detailed academic rubric for a ${levelText} ${subject} assessment with exactly ${rowCount} criteria rows and ${columnCount} scoring levels plus a weight column.
    
The criteria should be detailed and specific to ${subject}. Each cell should contain a comprehensive description (25-35 words) of what that performance level looks like for that specific criterion. Each criterion must have a weight percentage assigned, and all weights must sum to exactly 100%.`;

    // Add additional criteria if provided
    if (additionalCriteria) {
        prompt += `\n\nAdditional requirements: ${additionalCriteria}`;
    }
    
    return prompt;
}
window.closeAutoGenerateModal = closeAutoGenerateModal;
window.generateRubrics = generateRubrics;
</script>

</div>
</body>
</html>