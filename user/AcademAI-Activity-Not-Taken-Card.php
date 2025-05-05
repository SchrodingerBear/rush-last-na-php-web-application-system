<?php
require_once('../include/extension_links.php');
require_once '../include/new-academai-sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/academAI-activity-upcoming-card.css">

    <title>Missed Quizzes</title>
    <style>
        /* Add these styles to your CSS */
        .toggle-container {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 0 15px;
        }

        .toggle-label {
            margin-right: 10px;
            font-weight: 500;
            color: #555;
        }

        .toggle-options {
            display: flex;
            background: #f0f0f0;
            border-radius: 20px;
            overflow: hidden;
        }

        .toggle-option {
            padding: 12px 15px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .toggle-option.active {
            background: #5C8374;
            color: white;
            border-radius: 20px;
        }

        .quiz-restriction {
            display: flex;
            align-items: center;
            margin-top: 8px;
            font-size: 14px;
            color: #666;
        }

        .quiz-restriction i {
            margin-right: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="mcq-container">
  <div class="mcq-container-2">
    <div class="header-top">
        <div class="search-container col-12">
            <input type="text" id="searchInput" placeholder="Type here...">
            <div class="search-btn-container"><button type="button" id="searchButton">Search</button></div>
            <div class="filter-container">
                <div class="dropdown" id="dropdown-month">
                    <div class="dropdown-month"><button class="filter-button" id="rankFilter">Month <i class="fa-solid fa-filter"></i></button></div>
                    <div class="dropdown-content">
                        <a href="#">January</a>
                        <a href="#">February</a>
                        <a href="#">March</a>
                        <a href="#">April</a>
                        <a href="#">May</a>
                        <a href="#">June</a>
                        <a href="#">July</a>
                        <a href="#">August</a>
                        <a href="#">September</a>
                        <a href="#">October</a>
                        <a href="#">November</a>
                        <a href="#">December</a>
                    </div>
                </div>
                <div class="dropdown" id="dropdown-year">
                    <div class="dropdown-year"><button class="filter-button" id="orderFilter">Yearly <i class="fa-solid fa-filter"></i></button></div>
                    <div class="dropdown-content">
                        <a href="#">2021</a>
                        <a href="#">2022</a>
                        <a href="#">2023</a>
                        <a href="#">2024</a>
                        <a href="#">2025</a>
                        <a href="#">2026</a>
                    </div>
                </div>
            </div>
        </div>
        
       
    </div>

    <div class="container card-con">
         <!-- Toggle Filter -->
         <div class="toggle-container">
            <span class="toggle-label">Show:</span>
            <div class="toggle-options">
                <button id="showAll" class="toggle-option active">All Quizzes</button>
                <button id="showRestricted" class="toggle-option">Restricted</button>
                <button id="showUnrestricted" class="toggle-option">Unrestricted</button>
            </div>
        </div>
        
        <div class="label-for-card-page-activity">
            <h1 class="label-quizzes-status">MISSED QUIZZES</h1>
        </div>

        <!-- Container for displaying missed quizzes -->
        <div id="missed-quizzes-container"></div>

        <div class = "paginationn">
        <!-- Pagination container -->
        <div class = "pagination">
 <div id="pagination-container"></div>
 </div>
        <div class="activity">
            <img src="../img/walk.gif" class="gifactivity">
            <h4>Activity</h4>
        </div>
    </div>
  </div>
</div>

<!-- Leave Quiz Modal -->
<div class="modal fade" id="leave--modal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Leave Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to leave this quiz?</p>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn yesdelete"><a href="#" class="yes-btn-exit">Yes</a></button>
                <button type="button" class="btn nodelete" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>



 <!-- Ongoing Quiz Modal -->

 <div id="running-card-modal" class="modal">
        <div class="modal-content" id = "ongoingmodel">
            <span class="close">&times;</span>
            <div class="half-image">
                <img src="../img/modal/modal-12.gif" alt="First Image" style="width: 100%; height: 100%;">
            </div>
            <form action="#">
            <div class="submit-content">
                <p class="submit-text">This quiz has already ended but you can still take it.
                    Are you sure you want to start this quiz now?</p> <!-- Text -->
                <div class="yes-btn-modal">
                <a href="#" class="yes-btn" id ="yes-btn-go">Yes</a> <!-- Yes Button -->
                <a href="AcademAI-Activity-Running-Card.php" class="cancel-btn">Cancel</a> <!-- Cancel Button -->
                </div>
            </div>
        </div>
        </form>
    </div>


    
    <style>
    /* Import Inter font from Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    
    /* Modal Background */
    #running-card-modal {
        display: none; /* Hidden by default */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent black */
        z-index: 1000; /* Make sure it appears on top */
        font-family: 'Inter', sans-serif;
    }

    /* Modal Content Box */
    #running-card-modal .modal-content {
        position: relative;
        background-color: white;
        margin: 5% auto; /* Centered with top margin */
        padding: 20px;
        width: 60%;
        max-width: 400px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        text-align: center;
        font-family: 'Inter', sans-serif;
    }

    /* Close Button */
    #running-card-modal .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.5em;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
        font-family: 'Inter', sans-serif;
    }

    #running-card-modal .close:hover {
        color: #333;
    }

    /* Half Image at Top */
    #running-card-modal .half-image {
        width: 100%;
        height: 220px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    /* Text Content */
    #running-card-modal .submit-text {
        margin: 20px 0;
        font-size: 18px;
        color: #333;
        font-family: 'Inter', sans-serif;
        font-weight: 500; /* Medium weight for better readability */
    }

    /* Button Container */
    #running-card-modal .yes-btn-modal {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }

    /* Buttons */
    #running-card-modal .yes-btn,
    #running-card-modal .cancel-btn {
        padding: 10px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600; /* Semi-bold for buttons */
        transition: all 0.3s;
        font-family: 'Inter', sans-serif;
        font-size: 0.875em;
        border: none;
        cursor: pointer;
    }

    #running-card-modal .yes-btn {
        background-color: #092635;
        color: white;
        width:95px;
    }

    #running-card-modal .cancel-btn {
        background-color:rgb(236, 236, 236);
        color: #333;
    }

    #running-card-modal .yes-btn:hover {
        background-color: #1b4242;
    }

    #running-card-modal .cancel-btn:hover {
        background-color: #ddd;
    }
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const missedQuizzesContainer = document.getElementById("missed-quizzes-container");
    const paginationContainer = document.getElementById("pagination-container");
    const showAllBtn = document.getElementById('showAll');
    const showRestrictedBtn = document.getElementById('showRestricted');
    const showUnrestrictedBtn = document.getElementById('showUnrestricted');
    
    let currentFilter = 'all';
    let allMissedQuizzes = [];
    let filteredQuizzes = [];
    let currentPage = 1;
    const quizzesPerPage = 4; // Show 4 quizzes per page
    let totalPages = 1;
    let currentQuizId = null;

    // Function to format time
    function formatTime(timeString) {
        return timeString.split(':').slice(0, 2).join(':');
    }

    // Function to show/hide pagination
    function togglePaginationVisibility(shouldShow) {
        paginationContainer.style.display = shouldShow ? "flex" : "none";
    }

    // Function to set active toggle button
    function setActiveToggle(filterType) {
        currentFilter = filterType;
        showAllBtn.classList.remove('active');
        showRestrictedBtn.classList.remove('active');
        showUnrestrictedBtn.classList.remove('active');
        
        if (filterType === 'all') showAllBtn.classList.add('active');
        else if (filterType === 'restricted') showRestrictedBtn.classList.add('active');
        else if (filterType === 'unrestricted') showUnrestrictedBtn.classList.add('active');
    }

    // Function to filter quizzes based on current filter
    function filterQuizzes() {
        if (currentFilter === 'all') {
            filteredQuizzes = allMissedQuizzes;
        } else if (currentFilter === 'restricted') {
            filteredQuizzes = allMissedQuizzes.filter(quiz => quiz.is_active == 1);
        } else if (currentFilter === 'unrestricted') {
            filteredQuizzes = allMissedQuizzes.filter(quiz => quiz.is_active == 0);
        }
        
        // Calculate total pages
        totalPages = Math.ceil(filteredQuizzes.length / quizzesPerPage);
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        }
        
        displayQuizzes();
    }

    // Function to initialize modal
    function initModal() {
        const modal = document.getElementById("running-card-modal");
        const closeBtn = document.querySelector("#running-card-modal .close");
        const yesBtn = document.getElementById("yes-btn-go");
        
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
        
        yesBtn.onclick = function(e) {
            e.preventDefault();
            if (currentQuizId) {
                window.location.href = "AcademAI-Join-Quiz-Essay.php?quiz_id=" + currentQuizId;
            }
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    }

    // Function to display quizzes with pagination
    function displayQuizzes() {
        missedQuizzesContainer.innerHTML = "";

        if (filteredQuizzes.length === 0) {
            missedQuizzesContainer.innerHTML = `
                <div class="no-quiz-section">
                    <p class="no-quiz-txt">No ${currentFilter} quizzes available</p>
                </div>
            `;
            togglePaginationVisibility(false);
            return;
        }

        togglePaginationVisibility(totalPages > 1);

        // Calculate which quizzes to show for current page
        const startIndex = (currentPage - 1) * quizzesPerPage;
        const endIndex = Math.min(startIndex + quizzesPerPage, filteredQuizzes.length);
        const quizzesToShow = filteredQuizzes.slice(startIndex, endIndex);

        quizzesToShow.forEach(quiz => {
            const card = document.createElement("div");
            card.classList.add("card");

            const imageSrc = "../img/cards/1.jpg";

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

                    <div class="quiz-creator">
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
                        
                        <div class="quiz-restriction">
                            <i class="fas ${quiz.is_active == 1 ? 'fa-lock' : 'fa-unlock'}"></i>
                            <span>${quiz.is_active == 1 ? 'Restricted' : 'Unrestricted'}</span>
                        </div>
                        
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
                        </div>

                        <div class="quiz-status">
                            ${quiz.is_active == 1 ? 
                                '<i class="fas fa-lock"></i><span>This quiz is no longer accessible</span>' : 
                                '<button class="start-quiz-btn" data-quiz-id="' + quiz.quiz_id + '"> <i class="fas fa-play"></i> Start Quiz</button>'}
                        </div>
                    </div>
                </div>
            `;
            
            // Add event listeners for menu
            const menuToggle = card.querySelector('.menu-toggle');
            const menuDropdown = card.querySelector('.menu-dropdown');
            
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
                    if (dropdown !== menuDropdown) dropdown.style.display = 'none';
                });
                menuDropdown.style.display = menuDropdown.style.display === 'block' ? 'none' : 'block';
            });
            
            const copyIcon = card.querySelector('.copy-icon');
            copyIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                const quizCode = copyIcon.getAttribute('data-quiz-code');
                
                // Create success message element
                const successMessage = document.createElement('div');
                successMessage.textContent = `Successfully copied quiz code ${quizCode} to clipboard!`;
                successMessage.style.display = 'none';
                document.body.appendChild(successMessage);
                
                const copyToClipboard = () => {
                    successMessage.style.position = 'fixed';
                    successMessage.style.bottom = '20px';
                    successMessage.style.left = '50%';
                    successMessage.style.transform = 'translateX(-50%)';
                    successMessage.style.backgroundColor = "#1b4332";
                    successMessage.style.color = 'white';
                    successMessage.style.padding = '10px 20px';
                    successMessage.style.borderRadius = '5px';
                    successMessage.style.zIndex = '9999';
                    successMessage.style.fontFamily = '"Times New Roman", Times, serif';
                    successMessage.style.fontSize = '18px';
                    successMessage.style.display = "block";
                    
                    // Hide menu dropdown
                    menuDropdown.style.display = 'none';
                    
                    // Hide the message after 2 seconds
                    setTimeout(() => {
                        successMessage.style.opacity = "0";
                        setTimeout(() => {
                            successMessage.remove();
                        }, 500);
                    }, 2000);
                };
                
                // Fallback for browsers that don't support clipboard API
                const copyToClipboardFallback = (text) => {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        copyToClipboard();
                    } catch (err) {
                        successMessage.textContent = 'Failed to copy!';
                        successMessage.style.backgroundColor = "red";
                        copyToClipboard();
                    }
                    document.body.removeChild(textarea);
                };

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(quizCode)
                        .then(() => {
                            copyToClipboard();
                        })
                        .catch(err => {
                            console.error('Failed to copy: ', err);
                            copyToClipboardFallback(quizCode);
                        });
                } else {
                    copyToClipboardFallback(quizCode);
                }
            });
            
            // Add event listener for start quiz button if it exists
            const startQuizBtn = card.querySelector('.start-quiz-btn');
            if (startQuizBtn) {
                startQuizBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    currentQuizId = startQuizBtn.getAttribute('data-quiz-id');
                    const modal = document.getElementById("running-card-modal");
                    modal.style.display = "block";
                });
            }
            
            document.addEventListener('click', (e) => {
                if (!card.contains(e.target)) {
                    menuDropdown.style.display = 'none';
                }
            });
            
            missedQuizzesContainer.appendChild(card);
        });

        generatePaginationButtons();
    }

    // Function to generate pagination buttons (updated style)
    function generatePaginationButtons() {
        paginationContainer.innerHTML = "";

        if (totalPages <= 1) {
            return;
        }

        // Create a container for the pagination buttons
        const paginationWrapper = document.createElement("div");
        paginationWrapper.className = "pagination-wrapper";
        
        // Previous button
        if (currentPage > 1) {
            const prevButton = document.createElement("button");
            prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
            prevButton.classList.add("page-button");
            prevButton.addEventListener("click", () => {
                currentPage--;
                displayQuizzes();
            });
            paginationWrapper.appendChild(prevButton);
        }

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const button = document.createElement("button");
            button.innerText = i;
            button.classList.add("page-button");
            if (i === currentPage) {
                button.classList.add("active");
            }
            button.addEventListener("click", () => {
                currentPage = i;
                displayQuizzes();
            });
            paginationWrapper.appendChild(button);
        }

        // Next button
        if (currentPage < totalPages) {
            const nextButton = document.createElement("button");
            nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
            nextButton.classList.add("page-button");
            nextButton.addEventListener("click", () => {
                currentPage++;
                displayQuizzes();
            });
            paginationWrapper.appendChild(nextButton);
        }
        
        paginationContainer.appendChild(paginationWrapper);
    }

    // Function to fetch missed quizzes
    function fetchMissedQuizzes() {
        fetch(`../tools/Sorting-Of-Join-Quiz.php`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error("Error:", data.error);
                    return;
                }

                allMissedQuizzes = data.missedQuizzes || [];
                filterQuizzes();
            })
            .catch(error => console.error("Error fetching missed quizzes:", error));
    }

    // Event listeners for toggle buttons
    showAllBtn.addEventListener('click', () => {
        currentPage = 1;
        setActiveToggle('all');
        filterQuizzes();
    });

    showRestrictedBtn.addEventListener('click', () => {
        currentPage = 1;
        setActiveToggle('restricted');
        filterQuizzes();
    });

    showUnrestrictedBtn.addEventListener('click', () => {
        currentPage = 1;
        setActiveToggle('unrestricted');
        filterQuizzes();
    });

    // Initialize modal
    initModal();
    
    // Initial fetch
    fetchMissedQuizzes();
});
</script>

</body>
</html>