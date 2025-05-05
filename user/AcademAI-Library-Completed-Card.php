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
    <title>Academai Library | Completed Quiz</title>  
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


   
    <main>
    <!-- Upcoming Quizzes Section -->
    <div class="container card-con">
        <div class="label-for-card-page-activity">
            <h1 class="label-quizzes-status">COMPLETED QUIZZES</h1>
        </div>
        <div class="completed-quizzes"></div> <!-- Here will be the upcoming quizzes -->

    </div>
    

    <div class = "paginationn">
    <div class="completed-pagination"></div> <!-- Pagination will go here -->
    <!-- Activity Feed -->
    <div class="activity">
        <img src="../img/activity-feed.gif" class="gifactivity">
        <h4>Quiz Archives</h4>
    </div>
    </div>
</main>
    


    <script src="script.js"></script>

    <script>
        // Function to fetch completed quizzes via AJAX
        function fetchCompletedQuizzes() {
            $.ajax({
                url: 'your_php_script_for_completed.php',  // PHP script to handle the request for completed quizzes
                method: 'GET',
                success: function(response) {
                    var data = JSON.parse(response);
                    // Update the page content with the new quiz data
                    $('#completed-quizzes').html(renderQuizzes(data.completed));
                }
            });
        }

        // Function to render quizzes dynamically
        function renderQuizzes(quizzes) {
            if (quizzes.length === 0) return '<p>No completed quizzes.</p>';

            var html = '<ul>';
            quizzes.forEach(function(quiz) {
                html += '<li>' + quiz.title + ' (' + quiz.subject + ') - ' + quiz.start_date + ' to ' + quiz.end_date + '</li>';
            });
            html += '</ul>';
            return html;
        }

        // Fetch quizzes every 10 seconds for real-time update
        setInterval(fetchCompletedQuizzes, 10000);

        // Initial fetch on page load
        $(document).ready(function() {
            fetchCompletedQuizzes();
        });
    </script>


</div>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const cardContainer = document.querySelector(".completed-quizzes");
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
            const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
            const subtitle = card.querySelector('.card-title1')?.textContent.toLowerCase() || '';
            const dateElement = card.querySelector('.start .card-description:first-child');
            let startDate = '';

            if (dateElement) {
                startDate = dateElement.textContent.replace('Start Date: ', '').trim();
            }

            originalCards.push({
                element: card.cloneNode(true),
                title: title,
                subtitle: subtitle,
                startDate: startDate
            });
        });
    }

    function parseDateParts(dateStr) {
        const parts = dateStr.trim().split(" ");
        if (parts.length === 2) {
            const year = parseInt(parts[0]);
            const month = parseInt(parts[1]);
            if (!isNaN(year) && !isNaN(month)) {
                return { year, month };
            }
        }
        return null;
    }

    function applyFilters() {
        if (originalCards.length === 0) initializeCards();

        let filtered = originalCards;

        // Search filter
        if (filters.query) {
            const q = filters.query.toLowerCase();
            filtered = filtered.filter(card =>
                card.title.includes(q) || card.subtitle.includes(q)
            );
        }

        // Month filter
        if (filters.month) {
            filtered = filtered.filter(card => {
                const date = parseDateParts(card.startDate);
                return date && date.month === filters.month;
            });
        }

        // Year filter
        if (filters.year) {
            filtered = filtered.filter(card => {
                const date = parseDateParts(card.startDate);
                return date && date.year === filters.year;
            });
        }

        renderCards(filtered);
        updateFilterLabels();
    }

    function renderCards(cards) {
        cardContainer.innerHTML = '';

        if (cards.length === 0) {
            cardContainer.innerHTML = '<div class="no-results">No quizzes match your filters.</div>';
            return;
        }

        cards.forEach(card => {
            cardContainer.appendChild(card.element.cloneNode(true));
        });
    }

    function updateFilterLabels() {
        const monthBtn = document.getElementById("rankFilter");
        const yearBtn = document.getElementById("orderFilter");

        const monthNames = ["January", "February", "March", "April", "May", "June",
                            "July", "August", "September", "October", "November", "December"];

        monthBtn.innerHTML = filters.month ? `${monthNames[filters.month - 1]} <i class="fa-solid fa-filter"></i>` : 'Month <i class="fa-solid fa-filter"></i>';
        yearBtn.innerHTML = filters.year ? `${filters.year} <i class="fa-solid fa-filter"></i>` : 'Yearly <i class="fa-solid fa-filter"></i>';
    }

    // Event Listeners
    searchInput.addEventListener('input', function () {
        filters.query = this.value.trim();
        applyFilters();
    });

    searchButton.addEventListener('click', function () {
        filters.query = searchInput.value.trim();
        applyFilters();
    });

    monthDropdown.querySelectorAll('a').forEach((item, idx) => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            filters.month = idx + 1;
            applyFilters();
        });
    });

    yearDropdown.querySelectorAll('a').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const year = parseInt(this.textContent);
            if (!isNaN(year)) {
                filters.year = year;
                applyFilters();
            }
        });
    });

    function addClearFilterLinks() {
        const clearMonth = document.createElement("a");
        clearMonth.href = "#";
        clearMonth.textContent = "Clear Month Filter";
        clearMonth.addEventListener("click", function (e) {
            e.preventDefault();
            filters.month = null;
            applyFilters();
        });
        monthDropdown.appendChild(clearMonth);

        const clearYear = document.createElement("a");
        clearYear.href = "#";
        clearYear.textContent = "Clear Year Filter";
        clearYear.addEventListener("click", function (e) {
            e.preventDefault();
            filters.year = null;
            applyFilters();
        });
        yearDropdown.appendChild(clearYear);
    }

    function init() {
        addClearFilterLinks();
        initializeCards();

        // Handle URL parameters (optional)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('query')) filters.query = urlParams.get('query');
        if (urlParams.has('month')) filters.month = parseInt(urlParams.get('month'));
        if (urlParams.has('year')) filters.year = parseInt(urlParams.get('year'));

        if (filters.query || filters.month || filters.year) {
            searchInput.value = filters.query;
            applyFilters();
        }
    }

    init();
});
</script>





</body>
</html>
