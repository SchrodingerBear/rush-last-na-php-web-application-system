document.addEventListener("DOMContentLoaded", () => {
    let currentQuizId = null;
    console.log("DOMContentLoaded event fired");

    // Function to check if a quiz is accessible
    function isQuizAccessible(quiz) {
        const now = new Date();
        const endDateTime = new Date(`${quiz.end_date}T${quiz.end_time}`);

        // If the quiz is restricted (is_active = 1) and the end date/time has passed, it's inaccessible
        if (quiz.is_active === 1 && now > endDateTime) {
            return false; // Quiz is missed
        }

        return true; // Quiz is accessible
    }

    // Function to fetch and display quizzes with pagination
    function fetchAndDisplayQuizzes(page = 1) {
        fetch(`../tools/Sorting-Of-Join-Quiz.php`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error("Error:", data.error);
                    return;
                }

                console.log("Quizzes fetched:", data);

                // Define the number of cards per page
                const cardsPerPage = 4;

                // Slice the quizzes array to display only 4 cards per page
                const startIndex = (page - 1) * cardsPerPage;
                const endIndex = startIndex + cardsPerPage;

                const runningQuizzes = data.runningQuizzes.slice(startIndex, endIndex);
                const upcomingQuizzes = data.upcomingQuizzes.slice(startIndex, endIndex);

                // Display quizzes for the current page
                displayCards(runningQuizzes, "running-card-container", page);
                displayCards(upcomingQuizzes, "upcoming-card-container", page);

                // Handle completed quizzes
                const completedContainer = document.getElementById("completed-card-container");
                if (completedContainer) {
                    if (data.completedQuizzes.length === 0) {
                        // Display "No quizzes found" message for completed quizzes
                        completedContainer.innerHTML = `
                            <div class="no-quiz-section">
                                No quizzes available
                            </div>
                        `;
                    } else {
                        // Clear the container and append completed quizzes
                        completedContainer.innerHTML = "";
                        data.completedQuizzes.forEach(quiz => {
                            if (!document.querySelector(`[data-quiz-id="${quiz.quiz_id}"]`)) {
                                completedContainer.appendChild(createCard(quiz, "completed"));
                            }
                        });
                    }
                }

                // Calculate total pages based on the number of quizzes
                const totalRunningPages = Math.ceil(data.runningQuizzes.length / cardsPerPage);
                const totalUpcomingPages = Math.ceil(data.upcomingQuizzes.length / cardsPerPage);
                const totalPages = Math.max(totalRunningPages, totalUpcomingPages);

                // Generate pagination buttons only if there are quizzes
                const paginationContainer = document.getElementById("pagination-container");
                if (paginationContainer) {
                    if (data.runningQuizzes.length > 0 || data.upcomingQuizzes.length > 0) {
                        generatePaginationButtons(totalPages, page);
                        paginationContainer.style.display = "block";
                    } else {
                        paginationContainer.style.display = "none";
                    }
                }
            })
            .catch(error => console.error("Error fetching quiz data:", error));
    }

    // Function to check if a quiz is completed
    function checkQuizCompletion(quizId) {
        return fetch(`../tools/quiz_participants_answers.php?quiz_id=${quizId}`)
            .then(response => response.text())
            .then(text => {
                console.log(`Quiz ${quizId} Completion Check Response:`, text);
                try {
                    return JSON.parse(text).completed;
                } catch (error) {
                    console.error("Invalid JSON response:", text);
                    return false;
                }
            })
            .catch(error => {
                console.error("Error checking quiz completion:", error);
                return false;
            });
    }

    // Function to display cards with pagination
    async function displayCards(quizzes, containerId, page) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container with id ${containerId} not found.`);
            return;
        }

        container.innerHTML = "";

        if (!quizzes || quizzes.length === 0) {
            container.innerHTML = `
                <div class="no-quiz-section">
                
                </div>
            `;
            return;
        }

        for (const quiz of quizzes) {
            console.log("Quiz Data:", quiz);
            const isCompleted = containerId === "running-card-container" ? await checkQuizCompletion(quiz.quiz_id) : false;

            if (isCompleted) {
                console.log(`Moving quiz ${quiz.quiz_id} to completed section.`);

                const completedContainer = document.getElementById("completed-card-container");
                if (completedContainer) {
                    completedContainer.appendChild(createCard(quiz, "completed"));
                } else {
                    console.error("Completed container not found!");
                }
                continue;
            }

            container.appendChild(createCard(quiz, containerId));
        }
    }

    // Function to create a quiz card
    function createCard(quiz, containerId) {
        console.log("Checking quiz data:", quiz);

        if (!quiz.quiz_code) {
            console.error(`⚠️ Missing quiz_code for Quiz ID: ${quiz.quiz_id}`);
        }

        const card = document.createElement('div');
        card.classList.add('card');
        card.dataset.quizId = quiz.quiz_id;

        // Check if the quiz is accessible
        const isAccessible = isQuizAccessible(quiz);
        const isCompleted = containerId === "completed";
        const isMissed = !isAccessible && !isCompleted;
        const isUpcoming = containerId === "upcoming-card-container" && !isCompleted && !isMissed;
        const isRunning = containerId === "running-card-container" && !isCompleted && !isMissed;

        // Generate consistent image based on quiz_id
        const imageNum = (parseInt(quiz.quiz_id) % 9) + 1;
        const imageSrc = `../img/cards/${imageNum}.jpg`;

        if (isMissed) {
            // Missed Quiz Layout
            card.innerHTML = `
                    <div class="quiz-card-container">
                    <div class="card-menu">
                        <button class="menu-toggle">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="menu-dropdown">
                            <button class="menu-item copy-icon" data-quiz-id="${quiz.quiz_id}" data-quiz-code="${quiz.quiz_code}">
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

                     <img src="${quiz.creator_photo || '../img/default-avatar.jpg'}" 
                            alt="Creator" 
                            class="creator-avatar">
                        <div class="creator-details">
                            <span class="creator-name">
                                ${quiz.creator_first_name} 
                                ${quiz.creator_middle_name ? quiz.creator_middle_name + ' ' : ''}
                                ${quiz.creator_last_name}
                            </span>
                            <span class="creator-email">${quiz.creator_email}</span>
                        </div>
                    </div>
                    
                    <div class="card-info">
                       
                        <h2 class="card-title">Subject: ${quiz.subject}</h2>
                        
                        <div class="people-joined">
                            <a href="AcademAI-Upcoming-People-Join.php?quiz_id=${quiz.quiz_id}" class="people-link">
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
                            </div>`;

            card.addEventListener('click', () => {
                window.location.href = `AcademAI-missed-quiz.php?quiz_id=${quiz.quiz_id}`;
            });
        } else {
            // Standard Quiz Layout (running/upcoming/completed)
            card.innerHTML = `
                <div class="quiz-card-container">
                    <div class="card-menu">
                        <button class="menu-toggle">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="menu-dropdown">
                            <button class="menu-item copy-icon" data-quiz-id="${quiz.quiz_id}" data-quiz-code="${quiz.quiz_code}">
                                <i class="fas fa-clone"></i>
                                <span>Copy Quiz ID</span>
                            </button>
                            <button class="menu-item close-icon" data-quiz-id="${quiz.quiz_id}" data-bs-toggle="modal" data-bs-target="#leave--modal">
                               <i class="fas fa-sign-out-alt"></i>
                            <span>Leave</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="card-image-container">
                        <img src="${imageSrc}" alt="Card Image" class="card-image">
                        <div class="card-badge">${quiz.title}</div>
                    </div>
                    <div class = "quiz-creator">
                     <img src="${quiz.creator_photo || '../img/default-avatar.jpg'}" 
                            alt="Creator" 
                            class="creator-avatar">
                        <div class="creator-details"> Created by: 
                            <span class="creator-name">
                                ${quiz.creator_first_name} 
                                ${quiz.creator_middle_name ? quiz.creator_middle_name + ' ' : ''}
                                ${quiz.creator_last_name}
                            </span>
                         
                        </div>
                        </div>
                    
                    <div class="card-info">
                        <h2 class="card-title">Subject: ${quiz.subject}</h2>
                        
                        <div class="people-joined">
                            <a href="AcademAI-Upcoming-People-Join.php?quiz_id=${quiz.quiz_id}" class="people-link">
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
                            
                            ${isUpcoming ? `
                                <div class="quiz-status">
                                    <i class="fas fa-clock"></i>
                                    <span>This quiz isn't available yet</span>
                                </div>
                            ` : ''}
                            
                            ${isRunning ? `
                                <button class="start-quiz-btn" data-quiz-id="${quiz.quiz_id}">
                                    <i class="fas fa-play"></i> Start Quiz
                                </button>
                            ` : ''}
                            
                            ${isCompleted ? `
                                <div class="completed-status">
                                    <i class="fas fa-check-circle"></i> Completed
                                </div>
                                <button class="view-quiz-btn" onclick="window.location.href='AcademAI-user(learners)-view-quiz-answer-1.php?quiz_id=${quiz.quiz_id}'">
                                  <i class="fas fa-book" title="View Quiz"> View Quiz</i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>`;
        }

        // Add menu toggle functionality
        const menuToggle = card.querySelector('.menu-toggle');
        if (menuToggle) {
            const menuDropdown = card.querySelector('.menu-dropdown');
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                menuDropdown.classList.toggle('show');
            });
            
            document.addEventListener('click', () => {
                menuDropdown.classList.remove('show');
            });
        }

       

        // Add leave quiz functionality
        const leaveIcon = card.querySelector('.fa-right-from-bracket');
        if (leaveIcon) {
            leaveIcon.addEventListener('click', (event) => {
                event.stopPropagation();
                currentQuizId = quiz.quiz_id;
                const leaveModal = document.getElementById('leave--modal');
                if (leaveModal) {
                    const modal = new bootstrap.Modal(leaveModal);
                    modal.show();
                }
            });
        }

        // Add start quiz button functionality for running quizzes
        const startQuizBtn = card.querySelector('.start-quiz-btn');
        if (startQuizBtn) {
            startQuizBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                currentQuizId = quiz.quiz_id;
                showModal("running-card-modal", "This quiz is currently running. Do you want to start now?");
            });
        }

        // Add card click handler
        card.addEventListener('click', () => {
            currentQuizId = quiz.quiz_id;
            console.log("Card clicked! Quiz ID:", currentQuizId);

            if (isMissed) {
                window.location.href = `AcademAI-missed-quiz.php?quiz_id=${quiz.quiz_id}`;
            } else if (isRunning) {
                // Handled by the start quiz button now
            } else if (isUpcoming) {
                showModal("upcoming-card-modal", `This quiz will be available on ${quiz.start_date} at ${formatTime(quiz.start_time)}.`);
            } else if (isCompleted) {
                return; // Just exit the function
            }
        });

        return card;
    }

    // Helper function to format time (12-hour format)
    function formatTime(time24) {
        if (!time24) return '';
        const [hours, minutes] = time24.split(':');
        const hourNum = parseInt(hours, 10);
        const period = hourNum >= 12 ? 'PM' : 'AM';
        let hour12 = hourNum % 12;
        hour12 = hour12 === 0 ? 12 : hour12;
        return `${hour12}:${minutes} ${period}`;
    }

    // Function to generate pagination buttons
    function generatePaginationButtons(totalPages, currentPage) {
        const paginationContainer = document.createElement("div");
        paginationContainer.classList.add("pagination");

        // Function to create a page button
        function createPageButton(page, isActive = false, size = "medium") {
            const pageButton = document.createElement("button");
            pageButton.innerText = page;
            pageButton.classList.add("page-button", size);
            if (isActive) {
                pageButton.classList.add("active");
            }
            pageButton.addEventListener("click", () => {
                fetchAndDisplayQuizzes(page);
            });
            return pageButton;
        }

        // Add Previous Arrow (always visible)
        const prevButton = document.createElement("button");
        prevButton.innerHTML = "&larr;"; // Left arrow
        prevButton.classList.add("arrow-button");
        prevButton.disabled = currentPage === 1; // Disable if on the first page
        prevButton.addEventListener("click", () => {
            if (currentPage > 1) {
                fetchAndDisplayQuizzes(currentPage - 1);
            }
        });
        paginationContainer.appendChild(prevButton);

        // Show only 3 pages at a time
        if (currentPage > 1) {
            paginationContainer.appendChild(createPageButton(currentPage - 1, false, "medium"));
        }

        // Current page (largest)
        paginationContainer.appendChild(createPageButton(currentPage, true, "large"));

        if (currentPage < totalPages) {
            paginationContainer.appendChild(createPageButton(currentPage + 1, false, "medium"));
        }

        // Add Next Arrow (always visible)
        const nextButton = document.createElement("button");
        nextButton.innerHTML = "&rarr;"; // Right arrow
        nextButton.classList.add("arrow-button");
        nextButton.disabled = currentPage === totalPages; // Disable if on the last page
        nextButton.addEventListener("click", () => {
            if (currentPage < totalPages) {
                fetchAndDisplayQuizzes(currentPage + 1);
            }
        });
        paginationContainer.appendChild(nextButton);

        // Append pagination container to the DOM
        const container = document.getElementById("pagination-container");
        if (container) {
            container.innerHTML = "";
            container.appendChild(paginationContainer);
        }
    }

    // Function to show modal
    function showModal(modalId, message) {
        const modal = document.getElementById(modalId);
        const modalMessage = modal.querySelector(".submit-text");

        if (modalMessage) modalMessage.textContent = message;
        modal.style.display = "block";

        const closeButton = modal.querySelector(".close");
        closeButton.addEventListener("click", () => closeModal(modal));

        window.onclick = (event) => {
            if (event.target === modal) closeModal(modal);
        };
    }

    // Function to close modal
    function closeModal(modal) {
        modal.style.display = "none";
        currentQuizId = null;
    }

    // Event listener for "Yes" buttons in modals
    document.body.addEventListener("click", (event) => {
        
        if (event.target.classList.contains("yes-btn-leave")) {
            event.preventDefault();

            // This is where you'll handle the leave action
            // alert("You're leaving the quiz with ID: " + currentQuizId);
            
            // You would typically have a fetch request here similar to your other modals
            // For example:
            fetch('../tools/leave_quiz.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'quiz_id=' + encodeURIComponent(currentQuizId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert("Error: " + data.error);
                    console.error("Error: ", data.error);
                    return;
                }
                
                if (data.success) {
                    alert("You have successfully left the quiz!");
                    window.location.reload();
                } else {
                    alert("Failed to leave the quiz.");
                    console.error("Failed to leave the quiz.");
                }
            })
            .catch(error => {
                console.error("Fetch error: ", error);
                alert("An error occurred while processing your request.");
            });
            
            // Close the modal
            const leaveModal = document.getElementById('leave--modal');
            closeModal(leaveModal);
        }
        
        else if (event.target.classList.contains("yes-btn") || event.target.classList.contains("yes-btn-running")) {
            event.preventDefault();

            if (!currentQuizId) {
                alert("Quiz ID is missing.");
                console.error("Quiz ID is missing. Cannot proceed.");
                return;
            }

            const modalId = event.target.closest('.modal').id;

            fetch('../tools/quiz_playing.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'quiz_id=' + encodeURIComponent(currentQuizId)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert("Error: " + data.error);
                        console.error("Error: ", data.error);
                        return;
                    }

                    if (modalId === "running-card-modal") {
                        if (data.has_essay) {
                            window.location.href = "AcademAI-Join-Quiz-Essay.php?quiz_id=" + currentQuizId;
                        } else {
                            window.location.href = "AcademAI-Join-Quiz.php?quiz_id=" + currentQuizId;
                        }
                    } else if (modalId === "upcoming-card-modal") {
                        if (data.success) {
                            alert("You have successfully joined the quiz!");
                            window.location.reload();
                        } else {
                            alert("Failed to join the quiz.");
                            console.error("Failed to join the quiz.");
                        }
                    }else if (modalId === "upcoming-card-modal") {

                    }
                })
                .catch(error => {
                    console.error("Fetch error: ", error);
                    alert("An error occurred while processing your request.");
                });
        }
    });

    // Initial fetch and display of quizzes (first page)
    fetchAndDisplayQuizzes(1);

    // Refresh quizzes every 60 seconds
    setInterval(fetchAndDisplayQuizzes, 60000);
});



















// Function to fetch quiz code
function fetchQuizCode(quizId, iconElement) {
    fetch(`get_quiz_code.php?quiz_id=${quizId}`)
        .then(response => response.json())
        .then(data => {
            if (data.quiz_code) {
                iconElement.setAttribute("data-quiz-code", data.quiz_code);
                copyToClipboard(data.quiz_code, iconElement);
            } else {
                alert("Failed to retrieve quiz code.");
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            alert("An error occurred while retrieving the quiz code.");
        });
}

// Function to copy text to clipboard
function copyToClipboard(text, copyIcon) {
    navigator.clipboard.writeText(text)
        .then(() => showCopySuccess(copyIcon))
        .catch(err => {
            console.error("Failed to copy quiz code:", err);
            alert("Failed to copy quiz code. Try again!");
        });
}

// Function to show copy success message
function showCopySuccess(copyIcon) {
    const successMessage = document.createElement("div");
    successMessage.textContent = "Successfully Copied Quiz_Code to the Clipboard";
    successMessage.classList.add("copy-success-message");

    document.body.appendChild(successMessage);

    const rect = copyIcon.getBoundingClientRect();
    successMessage.style.position = "absolute";
    successMessage.style.top = `${window.scrollY + rect.top - 30}px`;
    successMessage.style.left = `${window.scrollX + rect.left + 20}px`;
    successMessage.style.backgroundColor = "green";
    successMessage.style.color = "white";
    successMessage.style.padding = "5px 10px";
    successMessage.style.borderRadius = "5px";
    successMessage.style.zIndex = "9999";
    successMessage.style.fontSize = "14px";
    successMessage.style.boxShadow = "0px 2px 5px rgba(0, 0, 0, 0.2)";
    successMessage.style.opacity = "1";
    successMessage.style.transition = "opacity 0.5s ease";

    setTimeout(() => {
        successMessage.style.opacity = "0";
        setTimeout(() => successMessage.remove(), 500);
    }, 1500);
}
