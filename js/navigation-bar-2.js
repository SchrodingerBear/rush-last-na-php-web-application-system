// Function to toggle sidebar
function sidebarToggle(event) {
    event.preventDefault(); // Prevent default action
    const sidebar = document.querySelector('.sidebar-navigation');
    sidebar.style.display = sidebar.style.display === 'flex' ? 'none' : 'flex';
  }
  
  function hideSidebar() {
    const sidebar = document.querySelector('.sidebar-navigation');
    sidebar.style.display = 'none';
  }
  
  // Function to handle hover on dropdowns
  function handleDropdownHover(event) {
    // Remove 'hovered' class from all navigation list items
    document.querySelectorAll(".navigation li").forEach((item) => {
      item.classList.remove("hovered");
    });
    // Add 'hovered' class to the parent navigation list item of the hovered dropdown
    event.currentTarget.parentElement.classList.add("hovered");
  }
  
  // Get all navigation list items and dropdowns
  let navItems = document.querySelectorAll(".navigation li .navigation-lists");
  let dropdowns = document.querySelectorAll(".dropdown");
  
  // Add event listeners for hover on navigation items and dropdowns
  navItems.forEach((item) => item.addEventListener("mouseenter", handleDropdownHover));
  dropdowns.forEach((dropdown) => dropdown.addEventListener("mouseenter", handleDropdownHover));
  
  // Menu Toggle
  let toggleSidebar = document.querySelector(".toggle"); // Select the menu toggle button
  let navigation = document.querySelector(".academAI-sidebar-container .navigation"); // Adjust the selector for navigation to match the correct container
  let main = document.querySelector(".main");
  let academAILogo = document.querySelector("#academAI-logo"); // Select the logo element
  
  // Check if the sidebar state is stored in the local storage
  let isSidebarActive = localStorage.getItem("sidebarActive") === "true";
  
  // Function to update the sidebar state and toggle icons
  function updateSidebarState() {
    navigation.classList.toggle("active", isSidebarActive);
    main.classList.toggle("active", isSidebarActive);
    academAILogo.classList.toggle("active", isSidebarActive);
  
    let menuOutlineIcon = document.querySelector(".topbar .toggle ion-icon[name='menu-outline']");
    let faXIcon = document.querySelector(".topbar .toggle .fa-x");
  
    if (isSidebarActive) {
      menuOutlineIcon.style.display = "none";
      faXIcon.style.display = "inline-block";
    } else {
      menuOutlineIcon.style.display = "inline-block";
      faXIcon.style.display = "none";
    }
  }
  
  // Update sidebar state and icons on page load
  updateSidebarState();
  
  // Add click event listener to toggle button
  toggleSidebar.addEventListener("click", function () {
    isSidebarActive = !isSidebarActive; // Toggle sidebar state
    localStorage.setItem("sidebarActive", isSidebarActive); // Store sidebar state in local storage
    updateSidebarState(); // Update sidebar state and icons
  });
  
  // Add event listener to navigation links for handling clicks
  document.querySelectorAll('.navigation a').forEach((link) => {
    link.addEventListener('click', (event) => {
      // Remove this if you want the default link behavior (navigation) to happen
      // event.preventDefault(); // Prevent default navigation if necessary
  
      // You can handle additional logic here if needed before navigation
      // For example, logging the link or performing an action
  
      // If you want the link to navigate, avoid calling preventDefault()
    });
  });
  