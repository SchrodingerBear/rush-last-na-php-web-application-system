<?php include __DIR__ . '/../include/info.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../img/light-logo-img.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Theme */
            --primary-dark: #092635;
            --primary: #1B4242;
            --secondary: #5C8374;
            --light: #9EC8B9;
            --white: #ffffff;
            --text-dark: #092635;
            --text-light: #f8f9fa;
            --sidebar-bg: #092635;
            --sidebar-text: #f8f9fa;
            --sidebar-hover: #5C8374;
            --navbar-bg: #ffffff;
            --content-bg: #f8f9fa;
            --card-bg: #ffffff;
            --radius: 8px;
            --transition: all 0.3s ease;
        }

        [data-theme="dark"] {
            /* Dark Theme */
            --primary-dark: #121212;
            --primary: #1E1E1E;
            --secondary: #2D2D2D;
            --light: #3A3A3A;
            --white: #252525;
            --text-dark: #E0E0E0;
            --text-light: #F5F5F5;
            --sidebar-bg: #121212;
            --sidebar-text: #F5F5F5;
            --sidebar-hover: #2D2D2D;
            --navbar-bg: #1E1E1E;
            --content-bg: #121212;
            --card-bg: #1E1E1E;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--content-bg);
            color: var(--text-dark);
            transition: var(--transition);
        }

        /* Modern Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding: 10px 0;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar.mobile-sidebar {
            transform: translateX(-100%);
        }

        .sidebar.mobile-sidebar.show {
            transform: translateX(0);
        }

        .sidebar-header {
         
            display: flex;
            align-items: center;
        
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 70px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #academAI-logo {
            width: 80px;
            height: 80px;
            transition: all 0.3s ease;
            /* Prevent logo from being affected by dark mode */
            filter: brightness(0) invert(1);
        }

        #academAIsidebar-logo-title {
            height: 100px;
            transition: all 0.3s ease;
            /* Prevent logo text from being affected by dark mode */
            filter: brightness(0) invert(1);
        }

        .sidebar.collapsed #academAIsidebar-logo-title {
            display: none;
        }

        .sidebar-menu {
            padding: 0 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .menu-group {
            margin-bottom: 10px;
            position: relative; /* Add this to make dropdown position relative to menu-group */
        }

        .dropdown-menu {
            position: absolute;
            top: 100%; /* Position below the parent menu item */
            left: 0;
            background: var(--sidebar-bg);
            border-radius: 0 0 var(--radius) var(--radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            margin: 0;
            padding: 0;
            width: 100%;
            display: none;
            animation: fadeIn 0.3s ease;
            overflow: hidden;
            z-index: 10; /* Ensure it appears above other elements */
        }

        /* Remove the max-height animation since we're using absolute positioning */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            border-radius: var(--radius);
            color: var(--sidebar-text);
            text-decoration: none !important;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
            font-size: 1.1em;
            font-weight: 500;
        }

        .menu-item:hover, .menu-item.active {
            background-color: var(--sidebar-hover);
            color: white;
            text-decoration: none !important;
        }

        .menu-icon {
            font-size: 1.4rem;
            margin-right: 15px;
            min-width: 24px;
            transition: var(--transition);
        }

        .menu-title {
            white-space: nowrap;
            transition: var(--transition);
        }

        .sidebar.collapsed .menu-title {
            display: none;
        }


      

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            padding: 12px 20px 12px 55px;
            color: var(--sidebar-text);
            text-decoration: none !important;
            display: block;
            transition: var(--transition);
            font-size: 14px;
            font-weight: 400;
            border-left: 3px solid transparent;
        }

        .dropdown-item:hover, .dropdown-item.active {
            background-color: rgba(0,0,0,0.2);
            color: white;
            text-decoration: none !important;
            border-left: 3px solid var(--light);
        }

        .toggle-btn {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .sidebar:not(.collapsed) .toggle-btn {
            pointer-events: auto;
        }

        .sidebar.collapsed .toggle-btn {
            pointer-events: none;
        }

        .arrow {
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .sidebar.collapsed .arrow {
            display: none;
        }

        .toggle-btn.active .arrow {
            transform: rotate(180deg);
        }

        /* Theme Toggle */
        .theme-toggle-container {
            margin-top: auto;
        
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            background: rgba(255,255,255,0.15);
        }

        .theme-toggle-text {
            font-size: 15px;
            font-weight: 500;
            margin-left: 10px;
        }

        .sidebar.collapsed .theme-toggle-text {
            display: none;
        }

        .theme-icon {
            font-size: 1.3rem;
        }

        /* Main Content Area */
        .main-container {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }

        .sidebar.collapsed ~ .main-container {
            margin-left: 80px;
        }

        /* Top Navigation Bar */
        .top-nav {
            height: 80px;
            background: var(--navbar-bg);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            position: sticky;
            top: 0;
            z-index: 900;
            gap: 20px;
            transition: var(--transition);
        }

        .nav-left, .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-center {
            flex: 1;
            max-width: 600px;
            margin: 0 20px;
            display: flex;
            justify-content: flex-end;
        }

        .toggle-sidebar {
            font-size: 1.7rem;
            color: var(--text-dark);
            cursor: pointer;
            display: flex;
            align-items: center;
            background: none;
            border: none;
            padding: 5px;
            transition: var(--transition);
        }

        

       

        /* Profile in Navbar - Side by Side */
        .profile-nav {
            display: flex;
            align-items: center;
        }

        .profile-dropdown {
            position: relative;
            display: flex;
            align-items: center;
        }

        .profile-btn {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: var(--radius);
            transition: var(--transition);
            background: none;
            border: none;
            gap: 10px;
        }

        .profile-btn:hover {
            background-color: rgba(92, 131, 116, 0.1);
        }

        .profile-img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .profile-name {
            font-weight: 500;
            color: var(--text-dark);
            white-space: nowrap;
            transition: var(--transition);
            font-size: 16px;
        }

        .dropdown-arrow {
            transition: var(--transition);
            font-size: 0.8rem;
            color: var(--text-dark);
        }

        .profile-dropdown.show .dropdown-arrow {
            transform: rotate(180deg);
        }

        .profile-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: var(--card-bg);
            min-width: 220px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: var(--radius);
            padding: 10px 0;
            display: none;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .profile-dropdown.show .profile-menu {
            display: block;
        }

        .profile-menu-item {
            padding: 12px 20px;
            color: var(--text-dark);
            text-decoration: none !important;
            display: flex;
            align-items: center;
            transition: var(--transition);
            font-size: 15px;
        }

        .profile-menu-item:hover {
            background-color: rgba(92, 131, 116, 0.1);
            color: var(--primary);
            text-decoration: none !important;
        }

        .profile-menu-icon {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .divider {
            height: 1px;
            background-color: rgba(0,0,0,0.1);
            margin: 8px 0;
        }

        /* Modal Styles */
        #logoutConfirmButton,
            .cancel-btn-logout {
                background-color: #092635;
                color: white;
                border: none;
                border-radius: 5px;
                text-decoration: none;
                padding: 0.5em 1em; /* more scalable than px */
                width: 100%;        /* make it fill the container */
                max-width: 100px;   /* limit width on bigger screens */
                text-align: center;
                box-sizing: border-box; /* ensures padding doesn't break layout */
                transition: background-color 0.3s ease;
            }

        .cancel-btn-logout {
            background-color: #1b4242;
        }

        .cancel-btn-logout:hover,
        #logoutConfirmButton:hover {
            background-color: #5C8374;
            color: white;
            text-decoration: none;
        }
        

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1100;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-container {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }

            .nav-center {
                margin: 0 10px;
            }

            .profile-name {
                display: none;
            }

            /* Adjust modal for tablet */
            #logoutmodal-content {
                width: 80%;
                height: auto;
                min-height: 200px;
            }

            #modal-body-logout p {
                font-size: 1.4em;
            }
        }

        @media (max-width: 768px) {
            .nav-left, .nav-right {
                gap: 10px;
            }

            /* Adjust modal for mobile */
            #logoutmodal-content {
                width: 95%;
                margin: 20% auto;
            }

            .cancel-btn-logout, #logoutConfirmButton {
                width: 120px;
            }
        }

        @media (max-width: 576px) {
            #modal-body-logout p {
                font-size: 1.2em;
            }

            .cancel-btn-logout, #logoutConfirmButton {
                width: 100px;
                padding: 8px;
            }
        }

        /* Page Content */
        .page-content {
            padding: 25px;
            background-color: var(--content-bg);
        
            transition: var(--transition);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../img/light-logo-img.png" alt="logo" id="academAI-logo">
                <img src="../img/light-logo-txt.png" alt="logo" id="academAIsidebar-logo-title">
            </div>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-group">
                <a href="../user/AcademAI-Quiz-Room.php" class="menu-item">
                    <ion-icon name="layers-outline" class="menu-icon"></ion-icon>
                    <span class="menu-title">Quiz Card</span>
                </a>
            </div>

            <div class="menu-group">
                <div class="menu-item toggle-btn">
                    <div>
                        <ion-icon name="clipboard-outline" class="menu-icon"></ion-icon>
                        <span class="menu-title">Activity</span>
                    </div>
                    <span class="arrow">▼</span>
                </div>
                <div class="dropdown-menu">
                    <a href="../user/AcademAI-Activity-Upcoming-Card.php" class="dropdown-item">Upcoming</a>
                    <a href="../user/AcademAI-Activity-Running-Card.php" class="dropdown-item">On-going</a>
                    <a href="../user/AcademAI-Activity-Completed-Card.php" class="dropdown-item">Completed</a>
                    <a href="../user/AcademAI-Activity-Not-Taken-Card.php" class="dropdown-item">Missed</a>
                </div>
            </div>

            <div class="menu-group">
                <div class="menu-item toggle-btn">
                    <div>
                        <ion-icon name="library-outline" class="menu-icon"></ion-icon>
                        <span class="menu-title">Quiz Archives</span>
                    </div>
                    <span class="arrow">▼</span>
                </div>
                <div class="dropdown-menu">
                    <a href="../user/AcademAI-Library-Upcoming-Card.php" class="dropdown-item">Upcoming</a>
                    <a href="../user/AcademAI-Library-Running-Card.php" class="dropdown-item">On-going</a>
                    <a href="../user/AcademAI-Library-Completed-Card.php" class="dropdown-item">Completed</a>
                </div>
            </div>

            <div class="menu-group">
                <a href="../user/AcademAI-Essay-Viewing-Rubric-Setting.php" class="menu-item">
                    <ion-icon name="settings-outline" class="menu-icon"></ion-icon>
                    <span class="menu-title">Essay Rubric Setting</span>
                </a>
            </div>

         
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="nav-left">
                <button class="toggle-sidebar">
                    <ion-icon name="menu-outline"></ion-icon>
                </button>
            </div>
            
        
            
            <div class="nav-right">
                <div class="profile-nav">
                    <div class="profile-dropdown">
            <button class="profile-btn">
                <?php
                $default_avatar = '../img/default-avatar.jpg';
                $profilePicture = $user->getPhotoPath(); // Using the new method
                ?>
                <img src="<?php echo htmlspecialchars($profilePicture); ?>" 
                     class="profile-img" 
                     alt="Profile Picture"
                     onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($default_avatar); ?>'">
                <span class="profile-name"><?php echo strtoupper($first_name . ' ' . $last_name); ?></span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        
                        <div class="profile-menu">
                            <a href="profile-settings.php" class="profile-menu-item">
                                <ion-icon name="settings-outline" class="profile-menu-icon"></ion-icon>
                                Profile Settings
                            </a>
                            <div class="divider"></div>
                            <a href="#" class="profile-menu-item" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                                <ion-icon name="log-out-outline" class="profile-menu-icon"></ion-icon>
                                Log Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

    


    <!-- Logout Modal -->
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" id="logoutmodal-content">
                <div class="modal-header" id="modal-header-logout">
                <h5 class="modal-title">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body-logout">
                    <p>Are you sure you want to log out?</p>
                </div>
                <div class="modal-footer">
                    <a href="../user.logout.php" class="btn" id="logoutConfirmButton">Logout</a>
                    <button type="button" class="cancel-btn-logout" data-bs-dismiss="modal">Cancel</button>
                   
                </div>
            </div>
        </div>
    </div>

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            
            if (window.innerWidth <= 992) {
                // Mobile behavior - toggle show class
                sidebar.classList.toggle('show');
            } else {
                // Desktop behavior - toggle collapsed class
                sidebar.classList.toggle('collapsed');
            }
        });

        // Toggle dropdown menus in sidebar
        document.querySelectorAll('.sidebar-menu .toggle-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (document.querySelector('.sidebar').classList.contains('collapsed')) return;
                
                e.stopPropagation();
                this.classList.toggle('active');
                const dropdown = this.parentElement.querySelector('.dropdown-menu');
                dropdown.classList.toggle('show');
                
                // Close other open dropdowns
                document.querySelectorAll('.dropdown-menu').forEach(otherDropdown => {
                    if (otherDropdown !== dropdown && otherDropdown.classList.contains('show')) {
                        otherDropdown.classList.remove('show');
                        otherDropdown.parentElement.querySelector('.toggle-btn').classList.remove('active');
                    }
                });
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sidebar-menu .toggle-btn') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
                document.querySelectorAll('.toggle-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
            }
            
            // Close profile dropdown when clicking outside
            if (!e.target.closest('.profile-dropdown')) {
                document.querySelector('.profile-dropdown').classList.remove('show');
            }
        });

        // Profile dropdown
        const profileDropdown = document.querySelector('.profile-dropdown');
        document.querySelector('.profile-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });

        // Toggle search input below nav
        const searchToggle = document.querySelector('.search-toggle-btn');
        const searchContainer = document.querySelector('.search-container');
        
        if (searchToggle && searchContainer) {
            searchToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                searchContainer.classList.toggle('show');
                
                if (searchContainer.classList.contains('show')) {
                    setTimeout(() => {
                        document.getElementById('searchInput').focus();
                    }, 100);
                }
            });

            // Close search when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.search-container') && !e.target.closest('.search-toggle-btn')) {
                    searchContainer.classList.remove('show');
                }
            });

            // Search functionality
            document.getElementById('searchButton').addEventListener('click', function() {
                const searchTerm = document.getElementById('searchInput').value;
                // Implement your search functionality here
                console.log('Searching for:', searchTerm);
            });

            // Allow search on Enter key
            document.getElementById('searchInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('searchButton').click();
                }
            });
        }

        // Responsive sidebar toggle
        function handleResponsive() {
            const sidebar = document.querySelector('.sidebar');
            
            if (window.innerWidth <= 992) {
                sidebar.classList.add('mobile-sidebar');
                sidebar.classList.remove('collapsed');
            } else {
                sidebar.classList.remove('mobile-sidebar');
            }
        }

        window.addEventListener('resize', handleResponsive);
        handleResponsive();
    </script>
</body>
</html>
