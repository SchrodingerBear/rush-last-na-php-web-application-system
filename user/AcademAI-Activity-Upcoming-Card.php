<?php
    require_once('../include/extension_links.php');
?>


<?php
  require_once '../include/new-academai-sidebar.php';
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/academAI-activity-upcoming-card.css">
    <script src="script.js" defer></script>
    <title>Academai | Upcoming Quiz</title>  
   
</head>
<body>

<div class="mcq-container">
  <div class="mcq-container-2">
  <div class = "header-top">

  
  <div class="search-container col-12">
             <input type="text" id="searchInput" placeholder="Type here...">
             <div class="search-btn-container"><button type="button" id="searchButton">Search</button></div>
             <div class="filter-container">
                 <div class="dropdown" id ="dropdown-month">
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
                 <div class="dropdown" id = "dropdown-year">
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
    <div class="label-for-card-page-activity">
    <h1 class = "label-quizzes-status">UPCOMING QUIZZES</h1>
    </div>
    
    <div id="upcoming-card-container"></div>


    

    
    
    <script src="../js/quiz.card.js"></script>
    

 


  <div class = "paginationn">
  <div id="pagination-container"></div>
<div class="activity ">
        <img src ="../img/walk.gif" class="gifactivity">
    <h4>Activity</h4>
    </div>
    </div>
  <!-- Modal -->
  <div class="modal fade" id="leave--modal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                <h5 class="modal-title">Confirm Leave Quiz</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                
                    <p>Are you sure you want to leave this quiz?</P>
                </div>
                 <div class="modal-footer">
                <button type="submit" class="btn yesdelete"><a href="#" class="yes-btn yes-btn-leave">Yes</a></button>
              <button type="button" class="btn nodelete" data-bs-dismiss="modal">Cancel</button>
                </div>
    </div>
  </div>
</div>
</div>
                   
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('leave--modal');
    const yesBtn = document.querySelector('.yes-btn-leave');

    if (yesBtn) {
        yesBtn.addEventListener('click', function (e) {
            e.preventDefault(); // Prevent immediate navigation

            // Hide modal using Bootstrap API
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }

            // Wait a little for modal to hide, then redirect
            setTimeout(() => {
                window.location.href = yesBtn.getAttribute('href');
            }, 300); // match Bootstrap modal hide transition
        });
    }

    // Optional cleanup if modal remains stuck (edge cases)
    modal.addEventListener('hidden.bs.modal', () => {
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
        document.body.style = ''; // Reset any inline styles
    });
});
</script>
     


<script>
    document.addEventListener('DOMContentLoaded', function () {
        var myModal = new bootstrap.Modal(document.getElementById('leave--modal'), {
            keyboard: false
        });

        var closeButton = document.querySelector('#leave--modal .close');
        closeButton.addEventListener('click', function () {
            myModal.hide();
        });

        var cancelButton = document.querySelector('#leave--modal .cancel-btn');
        cancelButton.addEventListener('click', function () {
            myModal.hide();
        });

        var yesButton = document.querySelector('#leave--modal .yes-btn');
        yesButton.addEventListener('click', function () {
            // Add functionality for the "Yes" button if needed
            myModal.hide();
        });
    });
</script>
<script>// script.js
    document.addEventListener("DOMContentLoaded", function() {
        const monthlyFilter = document.getElementById("monthlyFilter");
        const yearlyFilter = document.getElementById("yearlyFilter");
    
        monthlyFilter.addEventListener("click", function() {
            monthlyFilter.classList.add("active");
            yearlyFilter.classList.remove("active");
            // Add logic to filter monthly
        });
    
        yearlyFilter.addEventListener("click", function() {
            yearlyFilter.classList.add("active");
            monthlyFilter.classList.remove("active");
            // Add logic to filter yearly
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
    const searchContainer = document.querySelector(".search-container");
    const cardContainer = document.querySelector(".container.card-con");
    let lastScrollTop = 0;

    cardContainer.addEventListener("scroll", function() {
        let scrollTop = cardContainer.scrollTop;
        if (scrollTop > lastScrollTop) {
            // Downscroll code
            searchContainer.style.opacity = "0"; // Hide the search container
        } else {
            // Upscroll code
            searchContainer.style.opacity = "1"; // Show the search container
        }
        lastScrollTop = scrollTop;
    });
});
    </script>




<script>
document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("upcoming-card-modal");
    const modalBtn = document.getElementById("upcoming-card-modalBtn");
    const closeModalBtn = document.querySelector(".close");
    const joinLinks = document.querySelectorAll(".people-link");
    const closeIcon = document.querySelector(".fa-right-from-bracket"); // Select the close icon

    // Function to open modal
    function openModal() {
        modal.style.display = "block";
    }

    // Function to close modal
    function closeModal() {
        modal.style.display = "none";
    }

    // Event listener for modal open button
    modalBtn.onclick = openModal;

    // Event listener for modal close button
    closeModalBtn.onclick = closeModal;

    // Event listener for clicks outside modal to close it
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }

    // Event listener for each "People who join" link
    joinLinks.forEach(function(link) {
        link.addEventListener("click", function(event) {
            event.stopPropagation(); // Prevent event from bubbling up
        });
    });

    // Event listener for close icon
    closeIcon.addEventListener("click", function(event) {
        event.stopPropagation(); // Prevent event from bubbling up
    });
});

// Enhanced search and filter functionality
document.addEventListener("DOMContentLoaded", function() {
    // Get DOM elements
    const searchInput = document.getElementById("searchInput");
    const searchButton = document.getElementById("searchButton");
    const cardContainer = document.getElementById("upcoming-card-container");
    const paginationContainer = document.getElementById("pagination-container");
    const monthDropdown = document.querySelector("#dropdown-month .dropdown-content");
    const yearDropdown = document.querySelector("#dropdown-year .dropdown-content");
    
    // Filter state
    let filters = {
        query: "",
        month: null,
        year: null
    };
    
    // Store original card elements
    let originalCards = [];
    
    // Function to initialize the original cards
    function initializeCards() {
        // Clone all card elements in the container
        const cards = cardContainer.querySelectorAll('.card');
        cards.forEach(card => {
            // Store original card element and its data
            originalCards.push({
                element: card.cloneNode(true),
                title: card.querySelector('.card-title').textContent.toLowerCase(),
                subtitle: card.querySelector('.card-title1')?.textContent.toLowerCase() || '',
                // Extract start date for filtering
                startDate: card.querySelector('.start .card-description:first-child')?.textContent.replace('Start Date: ', '') || ''
            });
        });
    }
    
    // Function to apply filters
    function applyFilters() {
        // Clear the container
        cardContainer.innerHTML = '';
        
        let filteredCards = originalCards;
        
        // Apply search query filter
        if (filters.query) {
            const query = filters.query.toLowerCase();
            filteredCards = filteredCards.filter(card => 
                card.title.includes(query) || 
                card.subtitle.includes(query)
            );
        }
        
        // Apply month filter
        if (filters.month) {
            filteredCards = filteredCards.filter(card => {
                const startDate = new Date(card.startDate);
                return startDate.getMonth() === filters.month - 1; // -1 because months are 0-indexed
            });
        }
        
        // Apply year filter
        if (filters.year) {
            filteredCards = filteredCards.filter(card => {
                const startDate = new Date(card.startDate);
                return startDate.getFullYear() === filters.year;
            });
        }
        
        // Render filtered cards
        renderCards(filteredCards);
        
        // Update filter buttons to show active state
        updateFilterButtons();
    }
    
    // Function to update filter button text to show active filters
    function updateFilterButtons() {
        const monthButton = document.getElementById("rankFilter");
        const yearButton = document.getElementById("orderFilter");
        
        if (filters.month) {
            const monthNames = ["January", "February", "March", "April", "May", "June", 
                               "July", "August", "September", "October", "November", "December"];
            monthButton.innerHTML = monthNames[filters.month - 1] + ' <i class="fa-solid fa-filter"></i>';
        } else {
            monthButton.innerHTML = 'Month <i class="fa-solid fa-filter"></i>';
        }
        
        if (filters.year) {
            yearButton.innerHTML = filters.year + ' <i class="fa-solid fa-filter"></i>';
        } else {
            yearButton.innerHTML = 'Yearly <i class="fa-solid fa-filter"></i>';
        }
    }
    
    // Function to render cards with pagination
   // Function to render cards with pagination
function renderCards(cards, page = 1) {
    const cardsPerPage = 6;
    const start = (page - 1) * cardsPerPage;
    const end = start + cardsPerPage;
    const paginatedCards = cards.slice(start, end);
    
    // Clear container
    cardContainer.innerHTML = '';
    
    if (paginatedCards.length === 0) {
        cardContainer.innerHTML = '<div class="no-results">No upcoming quizzes match your filters</div>';
        paginationContainer.innerHTML = ''; // Clear pagination when no results
        return;
    }
    
    // Add cards to container
    paginatedCards.forEach(card => {
        // Clone the original card element to preserve all styling and structure
        const cardElement = card.element.cloneNode(true);
        
        // Re-attach event listeners to the cloned card
        attachEventListeners(cardElement);
        
        cardContainer.appendChild(cardElement);
    });
    
    // Only create pagination if we have more than one page
    if (cards.length > cardsPerPage) {
        createPagination(cards.length, cardsPerPage, page);
    } else {
        paginationContainer.innerHTML = ''; // Clear pagination if not needed
    }
}
    
    // Function to re-attach event listeners to cloned card elements
    function attachEventListeners(cardElement) {
        // Attach leave modal event listener
        const leaveModalBtn = cardElement.querySelector('#openLeaveModal');
        if (leaveModalBtn) {
            leaveModalBtn.addEventListener('click', function() {
                const leaveModal = new bootstrap.Modal(document.getElementById('leave--modal'));
                leaveModal.show();
            });
        }
        
        // Attach copy icon event listener
        const copyIcon = cardElement.querySelector('.copy-icon');
        if (copyIcon) {
            copyIcon.addEventListener('click', function() {
                const quizId = this.getAttribute('data-quiz-id');
                const quizCode = this.getAttribute('data-quiz-code');
                const tooltip = this.nextElementSibling;
                
                // Copy quiz code to clipboard
                navigator.clipboard.writeText(quizCode).then(() => {
                    tooltip.textContent = 'Copied!';
                    tooltip.style.display = 'inline';
                    setTimeout(() => {
                        tooltip.style.display = 'none';
                    }, 2000);
                });
            });
        }
        
        // Add other event listeners as needed
    }
    
    // Function to create pagination
    function createPagination(totalItems, itemsPerPage, currentPage) {
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        
        paginationContainer.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Create previous button
        if (currentPage > 1) {
            const prevButton = document.createElement('button');
            prevButton.textContent = '◀';
            prevButton.className = 'pagination-btn';
            prevButton.addEventListener('click', function() {
                renderCards(getFilteredCards(), currentPage - 1);
            });
            paginationContainer.appendChild(prevButton);
        }
        
        // Create page buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.className = i === currentPage ? 'pagination-btn active' : 'pagination-btn';
            pageButton.addEventListener('click', function() {
                renderCards(getFilteredCards(), i);
            });
            paginationContainer.appendChild(pageButton);
        }
        
        // Create next button
        if (currentPage < totalPages) {
            const nextButton = document.createElement('button');
            nextButton.textContent = '▶';
            nextButton.className = 'pagination-btn';
            nextButton.addEventListener('click', function() {
                renderCards(getFilteredCards(), currentPage + 1);
            });
            paginationContainer.appendChild(nextButton);
        }
    }
    
    // Function to get filtered cards based on current filters
    function getFilteredCards() {
        let result = originalCards;
        
        if (filters.query) {
            const query = filters.query.toLowerCase();
            result = result.filter(card => 
                card.title.includes(query) || 
                card.subtitle.includes(query)
            );
        }
        
        if (filters.month) {
            result = result.filter(card => {
                const startDate = new Date(card.startDate);
                return startDate.getMonth() === filters.month - 1;
            });
        }
        
        if (filters.year) {
            result = result.filter(card => {
                const startDate = new Date(card.startDate);
                return startDate.getFullYear() === filters.year;
            });
        }
        
        return result;
    }
    
    // Event listener for search input (real-time filtering)
    searchInput.addEventListener('input', function() {
        filters.query = this.value.trim();
        applyFilters();
    });
    
    // Event listener for search button
    searchButton.addEventListener('click', function() {
        filters.query = searchInput.value.trim();
        applyFilters();
    });
    
    // Event listeners for month filter options
    const monthOptions = monthDropdown.querySelectorAll('a');
    monthOptions.forEach((option, index) => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            filters.month = index + 1; // +1 because months are 1-indexed in our UI
            applyFilters();
        });
    });
    
    // Event listeners for year filter options
    const yearOptions = yearDropdown.querySelectorAll('a');
    yearOptions.forEach((option) => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            filters.year = parseInt(this.textContent);
            applyFilters();
        });
    });
    
    // Add clear filter functionality
    function addClearFilters() {
        // Add clear button to month dropdown
        const clearMonthLink = document.createElement('a');
        clearMonthLink.href = '#';
        clearMonthLink.textContent = 'Clear Month Filter';
        clearMonthLink.className = 'clear-filter';
        clearMonthLink.addEventListener('click', function(e) {
            e.preventDefault();
            filters.month = null;
            applyFilters();
        });
        monthDropdown.appendChild(clearMonthLink);
        
        // Add clear button to year dropdown
        const clearYearLink = document.createElement('a');
        clearYearLink.href = '#';
        clearYearLink.textContent = 'Clear Year Filter';
        clearYearLink.className = 'clear-filter';
        clearYearLink.addEventListener('click', function(e) {
            e.preventDefault();
            filters.year = null;
            applyFilters();
        });
        yearDropdown.appendChild(clearYearLink);
    }
    
    // Add some basic CSS for the no-results message and pagination
    function addFilterStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .no-results {
                text-align: center;
                font-size: 1.2em;
                color: #1b4242;
            }
            .pagination-btn {
                margin: 5px;
                padding: 5px 10px;
                background-color: #f0f0f0;
                border: 1px solid #ddd;
                cursor: pointer;
                border-radius: 5px;
            }
            .pagination-btn.active {
                background-color: #4CAF50;
                color: white;
            }
            .clear-filter {
                display: block;
                text-align: center;
                padding: 8px;
                background-color: #f9f9f9;
                border-top: 1px solid #ddd;
                color: #777;
                font-size: 0.9em;
            }
            .clear-filter:hover {
                background-color: #eee;
            }
            #pagination-container {
                display: flex;
                justify-content: center;
                margin-top: 20px;
            }
        `;
        document.head.appendChild(style);
    }
    
    // Initialize the page
    function init() {
        // Add styles
        addFilterStyles();
        
        // Add clear filter buttons
        addClearFilters();
        
        // After page load, initialize cards
        // Wait a bit to ensure all cards are loaded
        setTimeout(function() {
            initializeCards();
            
            // Initial render of cards if needed
            if (originalCards.length === 0) {
                cardContainer.innerHTML = '<div class="no-results">No quizzes available</div>';
            }
        }, 300);
    }
    
    // Call init
    init();
});
</script>
    
</body>
</html>






