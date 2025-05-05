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
    <script src="script.js" defer></script>
    <title>Academai Library | Ongoing Quiz</title> 
</head>
<body>
<div class="mcq-container">
   




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
  






          <main>
    <!-- Ongoing Quizzes Section -->
    <div class="container card-con">
        <div class="label-for-card-page-activity">
            <h1 class="label-quizzes-status">ONGOING QUIZZES</h1>
        </div>
        <div class="ongoing-quizzes"></div> <!-- Cards will go here -->
       
    </div>


    <div class = "paginationn">
    <div class="ongoing-pagination"></div> <!-- Pagination will go here -->
    
    <!-- Activity Feed -->
    <div class="activity">
        <img src="../img/activity-feed.gif" class="gifactivity">
        <h4>Quiz Archives</h4>
    </div>
    </div>
</main>

   

    <script>
        // Function to fetch ongoing quizzes via AJAX
        function fetchOngoingQuizzes() {
            $.ajax({
                url: 'your_php_script_for_ongoing.php',  // PHP script to handle the request for ongoing quizzes
                method: 'GET',
                success: function(response) {
                    var data = JSON.parse(response);
                    // Update the page content with the new quiz data
                    $('#ongoing-quizzes').html(renderQuizzes(data.ongoing));
                }
            });
        }

        // Function to render quizzes dynamically
        function renderQuizzes(quizzes) {
            if (quizzes.length === 0) return '<p>No ongoing quizzes.</p>';

            var html = '<ul>';
            quizzes.forEach(function(quiz) {
                html += '<li>' + quiz.title + ' (' + quiz.subject + ') - ' + quiz.start_date + ' to ' + quiz.end_date + '</li>';
            });
            html += '</ul>';
            return html;
        }

        // Fetch quizzes every 10 seconds for real-time update
        setInterval(fetchOngoingQuizzes, 10000);

        // Initial fetch on page load
        $(document).ready(function() {
            fetchOngoingQuizzes();
        });
    </script>






<script>
  document.addEventListener("DOMContentLoaded", function () {
    // ✅ Match the container used in your HTML
    const cardContainer = document.querySelector(".ongoing-quizzes");
    if (!cardContainer) return;
    
    const searchInput = document.getElementById("searchInput");
    const searchButton = document.getElementById("searchButton");
    const monthDropdown = document.querySelector("#dropdown-month .dropdown-content");
    const yearDropdown = document.querySelector("#dropdown-year .dropdown-content");

    let filters = {
        query: "",
        month: null,
        year: null
    };

    let originalCards = [];

    function initializeCards() {
        originalCards = [];
        const cards = cardContainer.querySelectorAll('.card');
        cards.forEach(card => {
            const cardData = {
                element: card.cloneNode(true),
                title: card.querySelector('.card-title')?.textContent.toLowerCase() || '',
                subtitle: card.querySelector('.card-title1')?.textContent.toLowerCase() || '',
                startDate: ''
            };
            const startDateEl = card.querySelector('.start .card-description:first-child');
            if (startDateEl) {
                cardData.startDate = startDateEl.textContent.replace('Start Date: ', '');
            }
            originalCards.push(cardData);
        });
    }

    function applyFilters() {
        if (originalCards.length === 0) {
            initializeCards();
        }

        let filteredCards = originalCards;

        if (filters.query) {
            const query = filters.query.toLowerCase();
            filteredCards = filteredCards.filter(card =>
                card.title.includes(query) || card.subtitle.includes(query)
            );
        }

        if (filters.month) {
            filteredCards = filteredCards.filter(card => {
                if (!card.startDate) return false;
                const startDate = new Date(card.startDate);
                return !isNaN(startDate) && startDate.getMonth() === filters.month - 1;
            });
        }

        if (filters.year) {
            filteredCards = filteredCards.filter(card => {
                if (!card.startDate) return false;
                const startDate = new Date(card.startDate);
                return !isNaN(startDate) && startDate.getFullYear() === filters.year;
            });
        }

        renderCards(filteredCards);
        updateFilterButtons();
    }

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

    // ✅ Simplified renderCards function with no pagination
    function renderCards(cards) {
        cardContainer.innerHTML = '';

        if (cards.length === 0) {
            cardContainer.innerHTML = '<div class="no-results">No quizzes match your filters</div>';
            return;
        }

        cards.forEach(card => {
            cardContainer.appendChild(card.element.cloneNode(true));
        });
    }

    function getFilteredCards() {
        let result = originalCards;

        if (filters.query) {
            const query = filters.query.toLowerCase();
            result = result.filter(card =>
                card.title.includes(query) || card.subtitle.includes(query)
            );
        }

        if (filters.month) {
            result = result.filter(card => {
                if (!card.startDate) return false;
                const startDate = new Date(card.startDate);
                return !isNaN(startDate) && startDate.getMonth() === filters.month - 1;
            });
        }

        if (filters.year) {
            result = result.filter(card => {
                if (!card.startDate) return false;
                const startDate = new Date(card.startDate);
                return !isNaN(startDate) && startDate.getFullYear() === filters.year;
            });
        }

        return result;
    }

    searchInput.addEventListener('input', function () {
        filters.query = this.value.trim();
        applyFilters();
    });

    searchButton.addEventListener('click', function () {
        filters.query = searchInput.value.trim();
        applyFilters();
    });

    const monthOptions = monthDropdown.querySelectorAll('a');
    monthOptions.forEach((option, index) => {
        option.addEventListener('click', function (e) {
            e.preventDefault();
            filters.month = index + 1;
            applyFilters();
        });
    });

    const yearOptions = yearDropdown.querySelectorAll('a');
    yearOptions.forEach((option) => {
        option.addEventListener('click', function (e) {
            e.preventDefault();
            filters.year = parseInt(this.textContent);
            applyFilters();
        });
    });

    function addClearFilters() {
        const clearMonthLink = document.createElement('a');
        clearMonthLink.href = '#';
        clearMonthLink.textContent = 'Clear Month Filter';
        clearMonthLink.className = 'clear-filter';
        clearMonthLink.addEventListener('click', function (e) {
            e.preventDefault();
            filters.month = null;
            applyFilters();
        });
        monthDropdown.appendChild(clearMonthLink);

        const clearYearLink = document.createElement('a');
        clearYearLink.href = '#';
        clearYearLink.textContent = 'Clear Year Filter';
        clearYearLink.className = 'clear-filter';
        clearYearLink.addEventListener('click', function (e) {
            e.preventDefault();
            filters.year = null;
            applyFilters();
        });
        yearDropdown.appendChild(clearYearLink);
    }

    function init() {
        addClearFilters();

        setTimeout(function () {
            initializeCards();

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('query')) {
                const queryParam = urlParams.get('query');
                searchInput.value = queryParam;
                filters.query = queryParam;
            }
            if (urlParams.has('month')) {
                filters.month = parseInt(urlParams.get('month'));
            }
            if (urlParams.has('year')) {
                filters.year = parseInt(urlParams.get('year'));
            }

            if (filters.query || filters.month || filters.year) {
                applyFilters();
            }

            if (originalCards.length === 0) {
                cardContainer.innerHTML = '<div class="no-results">No quizzes available</div>';
            }
        }, 300);
    }

    init();
});

</script>




</body>
</html>
