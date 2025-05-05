<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rubrics Editor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            position: relative;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .editable {
            min-width: 100px;
            min-height: 50px;
        }
        
        textarea {
            width: 100%;
            min-height: 60px;
            resize: vertical;
            border: none;
            background: transparent;
        }
        
        textarea:focus {
            outline: 2px solid #4d90fe;
            background: white;
        }
        
        input[type="text"] {
            width: 100%;
            border: none;
            background: transparent;
            text-align: center;
        }
        
        input[type="text"]:focus {
            outline: 2px solid #4d90fe;
            background: white;
        }
        
        .delete-btn {
            position: absolute;
            color: red;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            background-color: #ffeeee;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            border-radius: 10px;
        }
        
        .delete-col-btn {
            right: 5px;
            top: 5px;
        }
        
        .delete-row-btn {
            right: 5px;
            top: 5px;
        }
        
        .delete-btn:hover {
            background-color: #ffcccc;
        }
        
        .fixed-header {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        
        .blue-button {
            background-color: #008CBA;
        }
        
        .orange-button {
            background-color: #FF9800;
        }
        
        .purple-button {
            background-color: #9C27B0;
        }
        
        .actions {
            margin-top: 20px;
        }
        
        .save-load {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 700px;
            border-radius: 5px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        #rubricsList {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
        }
        
        #rubricsList th, #rubricsList td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        #rubricsList th {
            background-color: #f2f2f2;
        }
        
        #rubricsList tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        #rubricsList tr:hover {
            background-color: #f1f1f1;
        }
        
        .rubric-action {
            cursor: pointer;
            color: #0066cc;
            margin-right: 10px;
        }
        
        .rubric-action:hover {
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Rubrics Editor</h1>
    <h2 id="currentRubricTitle" style="color: #008CBA; margin-top: -10px; margin-bottom: 20px; font-style: italic; display: none;"></h2>
    
    <div class="actions">
        <button id="addRowBtn" class="button">Add Row</button>
        <button id="addColumnBtn" class="button blue-button">Add Column</button>
        <button id="saveNewBtn" class="button orange-button">Save as New Rubric</button>
        <button id="updateRubricBtn" class="button purple-button" disabled>Update Rubric</button>
        <button id="viewRubricsBtn" class="button">Rubrics List</button>
    </div>
    
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
    
    <!-- Save New Rubric Modal -->
    <div id="saveRubricModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeSaveModal">&times;</span>
            <h2>Save Rubric</h2>
            <div class="form-group">
                <label for="rubricTitle">Rubric Title:</label>
                <input type="text" id="rubricTitle" placeholder="Enter a title for this rubric">
            </div>
            <div class="form-group">
                <label for="rubricDescription">Description (optional):</label>
                <textarea id="rubricDescription" rows="3" style="width: 100%;" placeholder="Enter a description for this rubric"></textarea>
            </div>
            <button id="confirmSaveBtn" class="button">Save Rubric</button>
        </div>
    </div>
    
    <!-- Rubrics List Modal -->
    <div id="rubricsListModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeListModal">&times;</span>
            <h2>Your Rubrics</h2>
            <div id="rubricsListContainer">
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
        
        // Save new rubric to database
        function saveNewRubric() {
            const modal = document.getElementById('saveRubricModal');
            modal.style.display = 'block';
        }
        
        // Confirm save rubric
        function confirmSaveRubric() {
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
    
    document.getElementById('saveRubricModal').style.display = 'none';
    
    // Reset form
    document.getElementById('rubricTitle').value = '';
    document.getElementById('rubricDescription').value = '';
    
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
        
        // Update existing rubric
        function updateRubric() {
            if (!currentRubricId) {
                alert('No rubric selected for update. Please save as new or load a rubric first.');
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
                    data: rubricData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rubric updated successfully!');
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
                        if (confirm('Are you sure you want to delete this rubric?')) {
                            deleteRubric(rubric.id);
                        }
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
            
            // Display the current rubric title
            const titleElement = document.getElementById('currentRubricTitle');
            titleElement.textContent = 'Current Rubric: ' + data.title;
            titleElement.style.display = 'block';
            
            // Refresh table
            refreshTable();
            
            // Close modal
            document.getElementById('rubricsListModal').style.display = 'none';
            
         //   alert('Rubric loaded successfully!');
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
                    
                    alert('Rubric deleted successfully!');
                } else {
                    alert('Error deleting rubric: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting rubric. Please try again.');
            });
        }
        
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
</body>
</html>