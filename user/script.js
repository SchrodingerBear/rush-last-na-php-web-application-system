document.addEventListener("DOMContentLoaded", function () {
    let selectedQuizId; // Declare selectedQuizId globally

    // Polling every 30 seconds to update quizzes
    setInterval(fetchQuizzes, 30000);
    fetchQuizzes(); // Fetch immediately on page load

    function fetchQuizzes() {
        fetch("fetch_updated_quizzes.php")
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error("Error:", data.error);
                    return;
                }
                updateUI(data);
            })
            .catch(error => console.error("Error fetching quizzes:", error));
    }

    function updateUI(data) {
        updateQuizContainer(".upcoming-quizzes", ".upcoming-pagination", data.upcoming);
        updateQuizContainer(".ongoing-quizzes", ".ongoing-pagination", data.ongoing);
        updateQuizContainer(".completed-quizzes", ".completed-pagination", data.completed);

        attachDeleteListeners(); // Reattach listeners after updating the UI
    }

    function updateQuizContainer(cardsSelector, paginationSelector, quizzes) {
        const cardsContainer = document.querySelector(cardsSelector);
        const paginationContainer = document.querySelector(paginationSelector);
        
        if (!cardsContainer || !paginationContainer) return;
    
        cardsContainer.innerHTML = "";
        paginationContainer.innerHTML = "";
    
        if (quizzes.length === 0) {
            cardsContainer.innerHTML = `
                <div class="no-quiz-section">
                    <p class="no-quiz-txt">No quizzes available</p>
                </div>
            `;
            return;
        }
    
        const quizzesPerPage = 4;
        const totalPages = Math.ceil(quizzes.length / quizzesPerPage);
    
        function displayQuizzesForPage(page) {
            cardsContainer.innerHTML = "";
    
            const startIndex = (page - 1) * quizzesPerPage;
            const endIndex = startIndex + quizzesPerPage;
            const quizzesToDisplay = quizzes.slice(startIndex, endIndex);
    
            quizzesToDisplay.forEach(quiz => {
                const card = createCard(quiz);
                cardsContainer.appendChild(card);
            });
    
            generatePaginationButtons(totalPages, page);
        }
    
        function generatePaginationButtons(totalPages, currentPage) {
            paginationContainer.innerHTML = "";
            const paginationDiv = document.createElement("div");
            paginationDiv.classList.add("pagination");
        
            // Previous Button
            const prevButton = document.createElement("button");
            prevButton.innerHTML = "&larr;";
            prevButton.classList.add("arrow-button");
            prevButton.disabled = currentPage === 1;
            prevButton.addEventListener("click", () => {
                if (currentPage > 1) displayQuizzesForPage(currentPage - 1);
            });
            paginationDiv.appendChild(prevButton);
        
            // Page Buttons
            if (currentPage > 1) {
                paginationDiv.appendChild(createPageButton(currentPage - 1));
            }
        
            paginationDiv.appendChild(createPageButton(currentPage, true));
        
            if (currentPage < totalPages) {
                paginationDiv.appendChild(createPageButton(currentPage + 1));
            }
        
            // Next Button
            const nextButton = document.createElement("button");
            nextButton.innerHTML = "&rarr;";
            nextButton.classList.add("arrow-button");
            nextButton.disabled = currentPage === totalPages;
            nextButton.addEventListener("click", () => {
                if (currentPage < totalPages) displayQuizzesForPage(currentPage + 1);
            });
            paginationDiv.appendChild(nextButton);
        
            paginationContainer.appendChild(paginationDiv);
        }
        
        function createPageButton(page, isActive = false) {
            const button = document.createElement("button");
            button.innerText = page;
            button.classList.add("page-button");
            if (isActive) button.classList.add("active");
            button.addEventListener("click", () => displayQuizzesForPage(page));
            return button;
        }

        displayQuizzesForPage(1);
    }








    function createCard(quiz) {
        // Function to convert 24-hour time to 12-hour format with AM/PM
        function formatTime(time24) {
            if (!time24) return ''; // Handle empty time
            
            // Split the time string into hours and minutes
            const [hours, minutes] = time24.split(':');
            const hourNum = parseInt(hours, 10);
            
            // Determine AM/PM
            const period = hourNum >= 12 ? 'PM' : 'AM';
            
            // Convert to 12-hour format
            let hour12 = hourNum % 12;
            hour12 = hour12 === 0 ? 12 : hour12; // Handle midnight (0 becomes 12)
            
            // Return formatted time (e.g., "6:00 PM")
            return `${hour12}:${minutes} ${period}`;
        }
    
        const cardDiv = document.createElement("div");
        cardDiv.classList.add("card");
        cardDiv.setAttribute("data-quiz-id", quiz.quiz_id);
    
        // Generate a consistent random image based on quiz_id
        const imageNum = (parseInt(quiz.quiz_id) % 9 ) + 1; // Always returns 1-8 based on quiz_id
        const imageSrc = `../img/cards/${imageNum}.jpg`;
    
        cardDiv.innerHTML = `
        <div class="quiz-card-container">
            <div class="card-menu">
                <button class="menu-toggle">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="menu-dropdown">
                    <button class="menu-item copy-icon" data-quiz-id="${quiz.quiz_id}">
                        <i class="fas fa-clone"></i>
                        <span>Copy Quiz ID</span>
                    </button>
                    <button class="menu-item close-icon" data-quiz-id="${quiz.quiz_id}" data-bs-toggle="modal" data-bs-target="#leave--modal">
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete</span>
                    </button>
                </div>
            </div>
            
                <div class="card-image-container">
                    <img src="${imageSrc}" alt="Card Image" class="card-image">
                    <div class="card-badge">${quiz.title}</div>
                </div>
                
                <div class="card-info">
                   <h2 class="card-title ">Subject: ${quiz.subject}</h2>
    
                    
                    <div class="people-joined">
                        <a href="AcademAI-Library-Upcoming-People-Join.php?quiz_id=${quiz.quiz_id}" class="people-link">
                            <i class="fas fa-users"></i> View Participants
                        </a>
                    </div>
                    
                    <div class="date-time-info">
                        <div class="time-row">
                            <span class="time-label">Start Date:</span>
                            <span class="time-value">${quiz.start_date}</span>
                            <span class="time-separator">•</span>
                            <span class="time-label">Start Time:</span>
                            <span class="time-value">${formatTime(quiz.start_time)}</span>
                        </div>
                        <div class="time-row">
                            <span class="time-label">End Date:</span>
                            <span class="time-value">${quiz.end_date}</span>
                            <span class="time-separator">•</span>
                            <span class="time-label">End Time:</span>
                            <span class="time-value">${formatTime(quiz.end_time)}</span>
                        </div>
                        <div class = "viewquiz">
                        <button type = "button" class = "viewquiz-btn"><a href="AcademAI-Library-Upcoming-View-Card.php?quiz_id=${quiz.quiz_id}" class="card-link no-underline hover:no-underline focus:no-underline active:no-underline visited:no-underline">
                        <i class="fas fa-book" title="View Quiz"></i>
                        View Quiz
                        </button></a>
                        </div>
                        </div> 
                        </div>
        `;
        
        // Menu toggle functionality remains unchanged
        const menuToggle = cardDiv.querySelector('.menu-toggle');
        const menuDropdown = cardDiv.querySelector('.menu-dropdown');
        
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            menuDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', () => {
            menuDropdown.classList.remove('show');
        });
    
        return cardDiv;
    }


    function attachDeleteListeners() {
        document.querySelectorAll(".close-icon").forEach(icon => {
            icon.addEventListener("click", function () {
                selectedQuizId = this.getAttribute("data-quiz-id");
                console.log("Selected Quiz ID:", selectedQuizId); // Debugging
            });
        });
    }

    // Dynamically insert the modal into the DOM
    const modalHTML = `
    <div class="modal fade" id="leave--modal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this quiz? 
                </div>
                <form id="deleteQuizForm">
                    <div class="modal-footer">
                      <button type="submit" class="btn" id="confirmDeleteQuiz">Yes</button>
                        <button type="button" class="btn nodelete " data-bs-dismiss="modal">Cancel</button> 
                    </div>
                </form>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML("beforeend", modalHTML);

    document.getElementById("confirmDeleteQuiz").addEventListener("click", function () {
        console.log("Deleting Quiz ID:", selectedQuizId); // Debugging
        if (!selectedQuizId) {
            alert("Quiz ID not found! Please select a quiz.");
            return;
        }

        fetch("../tools/delete-library-quiz.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `quiz_id=${selectedQuizId}`
        })
        .then(response => response.text()) // Get the raw response as text
        .then(text => {
            console.log("Raw Response:", text); // Debugging
            try {
                return JSON.parse(text); // Try parsing as JSON
            } catch (error) {
                throw new Error("Invalid JSON response: " + text);
            }
        })
        .then(data => {
            if (data.success) {
                alert("Quiz deleted successfully!");
                fetchQuizzes(); // Refresh the quiz list
            } else {
                alert("Failed to delete quiz: " + data.error);
            }
        })
        .catch(error => console.error("Error deleting quiz:", error));
    });















    // Event listeners for copying quiz code
    document.addEventListener("mouseover", function (event) {
        const copyBtn = event.target.closest(".copy-icon");
        if (copyBtn) {
            const tooltip = copyBtn.closest(".card").querySelector(".copy-tooltip");
            if (tooltip) {
                tooltip.style.display = "inline"; // Show "Copy" text
                tooltip.style.position = "absolute";
                tooltip.style.top = "100%"; // Position below the icon
                tooltip.style.left = "50%";
                tooltip.style.transform = "translateX(-50%)"; // Center tooltip
            }
        }
    });
    

    document.addEventListener("click", function (event) {
        if (event.target.classList.contains("copy-icon")) {
            let quizId = event.target.getAttribute("data-quiz-id");
            if (!quizId) {
                alert("Quiz ID not found!");
                return;
            }
    
            console.log("Fetching quiz code for quiz ID:", quizId); // Debugging
            fetchQuizCode(event, quizId);
        }
    });
    
    function fetchQuizCode(event, quizId) {
        fetch('get_quiz_code.php?quiz_id=' + quizId)  // Adjust path as needed
            .then(response => response.json())
            .then(data => {
                console.log(data);  // Check if data is returned as expected
                if (data.error) {
                    console.error("Error:", data.error);
                    alert("Error fetching quiz code: " + data.error);  // Show error in alert
                    return;
                }
    
                const quizCode = data.quiz_code;
                if (!quizCode) {
                    alert("Quiz code not found!");
                    return;
                }
    
                // Try to use Clipboard API
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(quizCode)
                        .then(() => {
                            // Create a success message element
                            const successMessage = document.createElement('div');
                            successMessage.classList.add('copy-success-message');
                            successMessage.textContent = `Successfully copied quiz code ${quizCode} to clipboard!`;
    
                            // Append the success message to the body
                            document.body.appendChild(successMessage);
    
                            // Style the success message
                            successMessage.style.position = 'fixed';
                            successMessage.style.bottom = '20px'; // Position it at the bottom
                            successMessage.style.left = '50%';
                            successMessage.style.transform = 'translateX(-50%)';
                            successMessage.style.backgroundColor = "#1b4332";
                            successMessage.style.color = 'white';
                            successMessage.style.padding = '10px 20px';
                            successMessage.style.borderRadius = '5px';
                            successMessage.style.zIndex = '9999';
    
                            // Hide the success message after 2 seconds
                            setTimeout(() => {
                                successMessage.style.opacity = '0';
                                successMessage.style.transition = 'opacity 0.5s ease-out';
                                setTimeout(() => {
                                    successMessage.remove(); // Remove from DOM after fade out
                                }, 500);
                            }, 2000);
                        })
                        .catch(err => {
                            console.error("Failed to copy quiz code:", err);
                            alert("Failed to copy quiz code. Error: " + err.message);  // Show specific error
                        });
                } else {
                    // Fallback for older browsers or unsupported environments
                    const textArea = document.createElement("textarea");
                    textArea.value = quizCode;
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        // Create a fallback success message element
                        const successMessage = document.createElement('div');
                        successMessage.classList.add('copy-success-message');
                        successMessage.textContent = `Successfully copied quiz code ${quizCode} to clipboard!`;
    
                        // Append the fallback success message to the body
                        document.body.appendChild(successMessage);
    
                        // Style the fallback success message
                        successMessage.style.position = 'fixed';
                        successMessage.style.bottom = '20px'; // Position it at the bottom
                        successMessage.style.left = '50%';
                        successMessage.style.transform = 'translateX(-50%)';
                        successMessage.style.backgroundColor = "#1b4332";
                        successMessage.style.color = 'white';
                        successMessage.style.padding = '10px 20px';
                        successMessage.style.borderRadius = '5px';
                        successMessage.style.zIndex = '9999';
                        successMessage.style.fontFamily = '"Times New Roman", Times, serif';
                        successMessage.style.fontSize = '18px';
    
                        // Hide the fallback success message after 2 seconds
                        setTimeout(() => {
                            successMessage.style.opacity = '0';
                            successMessage.style.transition = 'opacity 0.5s ease-out';
                            setTimeout(() => {
                                successMessage.remove(); // Remove from DOM after fade out
                            }, 500);
                        }, 2000);
    
                    } catch (err) {
                        console.error("Fallback copy failed:", err);
                        alert("Failed to copy quiz code (fallback). Error: " + err.message);  // Show fallback error
                    }
                    document.body.removeChild(textArea);
                }
            })
            .catch(err => {
                console.error("Error fetching quiz code:", err);
                alert("An error occurred while fetching the quiz code: " + err.message);  // Show specific error
            });
    }
});