<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criteria and Rubrics Manager</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            position: relative;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: none;
            overflow: hidden;
            min-height: 40px;
            transition: all 0.3s ease;
            font-family: Arial, sans-serif;
        }
        textarea:focus {
            min-height: 100px;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
            background-color: #fff;
            z-index: 10;
            position: relative;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="number"] {
            width: 60px;
        }
        .btn {
            padding: 8px 12px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .btn-secondary {
            background-color: #2196F3;
            color: white;
        }
        .controls {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        .total-weight {
            text-align: right;
            font-weight: bold;
            margin: 10px 0;
        }
        .error {
            color: red;
            font-size: 0.9em;
        }
        .level-title {
            font-weight: normal;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .level-header {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .level-number {
            font-weight: bold;
        }
        .criteria-name {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Criteria and Rubrics Manager</h1>
        
        <div class="controls">
            <button id="addRowBtn" class="btn btn-primary">Add Row</button>
            <button id="removeRowBtn" class="btn btn-danger">Remove Row</button>
            <button id="addColBtn" class="btn btn-primary">Add Column</button>
            <button id="removeColBtn" class="btn btn-danger">Remove Column</button>
        </div>
        
        <div class="total-weight">Total Weight: <span id="totalWeight">0</span>%</div>
        
        <table id="rubricTable">
            <thead>
                <tr>
                    <th style="width: 200px;">Criteria</th>
                    <th style="width: 80px;">Weight (%)</th>
                    <!-- Levels will be added dynamically here -->
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be added dynamically here -->
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rubricTable = document.getElementById('rubricTable');
            const addRowBtn = document.getElementById('addRowBtn');
            const removeRowBtn = document.getElementById('removeRowBtn');
            const addColBtn = document.getElementById('addColBtn');
            const removeColBtn = document.getElementById('removeColBtn');
            const totalWeightSpan = document.getElementById('totalWeight');
            
            let levelCount = 5; // Default number of levels (5,4,3,2,1)
            let criteriaCount = 0;
            
            // Initialize the table with default levels and one criteria row
            initializeTable();
            
            // Add event listeners
            addRowBtn.addEventListener('click', addCriteriaRow);
            removeRowBtn.addEventListener('click', removeCriteriaRow);
            addColBtn.addEventListener('click', addLevelColumn);
            removeColBtn.addEventListener('click', removeLevelColumn);
            
            // Initialize the table with default values
            function initializeTable() {
                // Add level columns (5,4,3,2,1)
                const headerRow = rubricTable.querySelector('thead tr');
                
                // Clear existing level columns (except Criteria and Weight)
                while (headerRow.children.length > 2) {
                    headerRow.removeChild(headerRow.lastChild);
                }
                
                // Add new level columns
                for (let i = levelCount; i >= 1; i--) {
                    const th = document.createElement('th');
                    th.innerHTML = `
                        <div class="level-header">
                            <span class="level-number">Level ${i}</span>
                            <input type="text" class="level-title" value="${getDefaultLevelTitle(i)}" data-level="${i}">
                        </div>
                    `;
                    headerRow.appendChild(th);
                }
                
                // Add one initial criteria row
                addCriteriaRow();
                
                // Update total weight
                updateTotalWeight();
            }
            
            // Get default title for each level
            function getDefaultLevelTitle(level) {
                const titles = {
                    5: "Excellent",
                    4: "Proficient",
                    3: "Adequate",
                    2: "Developing",
                    1: "Beginning"
                };
                return titles[level] || "Level " + level;
            }
            
            // Add a new criteria row
            function addCriteriaRow() {
                const tbody = rubricTable.querySelector('tbody');
                const row = document.createElement('tr');
                
                // Criteria name cell
                const criteriaCell = document.createElement('td');
                criteriaCell.innerHTML = '<input type="text" class="criteria-name" placeholder="Enter criteria" value="Criteria ' + (criteriaCount + 1) + '">';
                row.appendChild(criteriaCell);
                
                // Weight cell
                const weightCell = document.createElement('td');
                weightCell.innerHTML = '<input type="number" class="criteria-weight" min="0" max="100" value="20">';
                weightCell.querySelector('input').addEventListener('input', updateTotalWeight);
                row.appendChild(weightCell);
                
                // Level cells (using textarea instead of input)
                for (let i = levelCount; i >= 1; i--) {
                    const levelCell = document.createElement('td');
                    levelCell.innerHTML = '<textarea class="level-description" placeholder="Description for level ' + i + '">Description for level ' + i + '</textarea>';
                    
                    // Auto-resize textarea when content changes
                    const textarea = levelCell.querySelector('textarea');
                    textarea.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    });
                    
                    // Trigger input event to set initial height
                    textarea.dispatchEvent(new Event('input'));
                    
                    row.appendChild(levelCell);
                }
                
                tbody.appendChild(row);
                criteriaCount++;
                
                // Update total weight
                updateTotalWeight();
            }
            
            // Remove the last criteria row
            function removeCriteriaRow() {
                const tbody = rubricTable.querySelector('tbody');
                if (tbody.rows.length > 1) {
                    tbody.removeChild(tbody.lastChild);
                    criteriaCount--;
                } else if (tbody.rows.length === 1) {
                    // Reset the single remaining row instead of removing it
                    const inputs = tbody.rows[0].querySelectorAll('input, textarea');
                    inputs[0].value = "Criteria 1";
                    inputs[1].value = "20";
                    for (let i = 2; i < inputs.length; i++) {
                        inputs[i].value = "Description for level " + (levelCount - (i - 2));
                        // Trigger resize for textareas
                        if (inputs[i].tagName === 'TEXTAREA') {
                            inputs[i].dispatchEvent(new Event('input'));
                        }
                    }
                }
                
                // Update total weight
                updateTotalWeight();
            }
            
            // Add a new level column (if less than 5)
            function addLevelColumn() {
                if (levelCount >= 5) {
                    alert("Maximum of 5 levels allowed");
                    return;
                }
                
                levelCount++;
                const newLevel = levelCount;
                
                // Add to header
                const headerRow = rubricTable.querySelector('thead tr');
                const newHeaderCell = document.createElement('th');
                newHeaderCell.innerHTML = `
                    <div class="level-header">
                        <span class="level-number">Level ${newLevel}</span>
                        <input type="text" class="level-title" value="${getDefaultLevelTitle(newLevel)}" data-level="${newLevel}">
                    </div>
                `;
                headerRow.insertBefore(newHeaderCell, headerRow.children[2]);
                
                // Add to each row
                const rows = rubricTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const newCell = document.createElement('td');
                    newCell.innerHTML = '<textarea class="level-description" placeholder="Description for level ' + newLevel + '">Description for level ' + newLevel + '</textarea>';
                    
                    // Auto-resize textarea
                    const textarea = newCell.querySelector('textarea');
                    textarea.addEventListener('input', function() {
                        this.style.height = 'auto';
                        this.style.height = (this.scrollHeight) + 'px';
                    });
                    textarea.dispatchEvent(new Event('input'));
                    
                    row.insertBefore(newCell, row.children[2]);
                });
            }
            
            // Remove the last level column (if more than 1)
            function removeLevelColumn() {
                if (levelCount <= 1) {
                    alert("Minimum of 1 level required");
                    return;
                }
                
                // Remove from header
                const headerRow = rubricTable.querySelector('thead tr');
                headerRow.removeChild(headerRow.children[2]);
                
                // Remove from each row
                const rows = rubricTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    row.removeChild(row.children[2]);
                });
                
                levelCount--;
            }
            
            // Update the total weight display
            function updateTotalWeight() {
                const weightInputs = document.querySelectorAll('.criteria-weight');
                let total = 0;
                
                weightInputs.forEach(input => {
                    const value = parseFloat(input.value) || 0;
                    total += value;
                });
                
                totalWeightSpan.textContent = total.toFixed(1);
                
                // Highlight if total is not 100%
                if (total === 100) {
                    totalWeightSpan.style.color = 'green';
                } else {
                    totalWeightSpan.style.color = 'red';
                }
            }
            
            // Delegate events for dynamically added elements
            rubricTable.addEventListener('input', function(e) {
                if (e.target.classList.contains('criteria-weight')) {
                    updateTotalWeight();
                }
            });
        });
    </script>
</body>
</html>