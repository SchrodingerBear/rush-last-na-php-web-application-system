
//slidersection
let next = document.querySelector('.next')
let prev = document.querySelector('.prev')

next.addEventListener('click', function(){
    let items = document.querySelectorAll('.item')
    document.querySelector('.slide').appendChild(items[0])
})

prev.addEventListener('click', function(){
    let items = document.querySelectorAll('.item')
    document.querySelector('.slide').prepend(items[items.length - 1])
})

//benefit-section- text-animation
document.querySelectorAll('.text').forEach(text => {
observer.observe(text);
});

document.addEventListener('DOMContentLoaded', function() {
// Function to check if an element is in the viewport
function isInViewport(element) {
  const rect = element.getBoundingClientRect();
  return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  );
}



// Function to handle animations when scrolling
function handleScrollAnimations() {
  const images = document.querySelectorAll('.benefit-container img');
  images.forEach(image => {
      if (isInViewport(image)) {
          image.classList.add('animate');
      }
  });
}

// Trigger handleScrollAnimations initially
handleScrollAnimations();


// Add event listener for scrolling
window.addEventListener('scroll', handleScrollAnimations);
});

document.addEventListener('DOMContentLoaded', function() {
const numbers = document.querySelectorAll('.number');

numbers.forEach(number => {
  number.addEventListener('click', function() {
  const index = this.dataset.index;


// Highlight the clicked number
  numbers.forEach(n => n.classList.remove('active'));
   this.classList.add('active');
   

// Show the corresponding input and change highlight
      const inputs = document.querySelectorAll('.how-it-works-input');
      inputs.forEach(input => {
          input.classList.remove('shadow-lg', 'active');
      });
      const currentInput = document.querySelector(`.how-it-works-input[data-index="${index}"]`);
      if (currentInput) {
          currentInput.classList.add('shadow-lg', 'active');
      }



// Update the image based on the clicked number
      const images = document.querySelectorAll('.img-fluid');
      images.forEach(image => {
          image.classList.add('hidden-img');
      });
      const currentImage = document.getElementById(`how-it-works-img-${index}`);
      if (currentImage) {
          currentImage.classList.remove('hidden-img');
      }
  });
});
});
// Function to apply navbar styles based on scroll position
function applyNavbarStyles() {
    // Get the navbar element
    const navbar = document.querySelector('.academAI-navbar');
    // Get the slider section element
    const sliderSection = document.querySelector('.slidersection');
    // Calculate the offset position of the slider section
    const sliderSectionOffset = sliderSection.offsetTop;
  
    // Function to change navbar background color
    function changeNavbarBackground() {
      if (window.pageYOffset >= sliderSectionOffset) {
        // Remove filter
        navbar.style.filter = 'none';
        // Change background color
        navbar.style.backgroundColor = '#092635';
      } else {
        // Restore original styles
        navbar.style.backgroundColor = 'rgba(7, 73, 68, 0.5)';
        navbar.style.filter = 'brightness(2.0) saturate(1000%)';
        // Add more lines to restore other styles as needed
      }
    }
  
    // Apply styles when the page loads
    changeNavbarBackground();
  
    // Listen for scroll events
    window.addEventListener('scroll', changeNavbarBackground);
  
    // Prevent default behavior for anchor links
    const navLinks = document.querySelectorAll('.primary-navigation a');
    navLinks.forEach(link => {
      link.addEventListener('click', function(event) {
        event.preventDefault(); // Prevent the default action
        const targetId = this.getAttribute('href').substring(1); // Get the target element ID
        const targetElement = document.getElementById(targetId); // Find the target element
        if (targetElement) {
          // Scroll to the target element
          window.scrollTo({
            top: targetElement.offsetTop,
            behavior: 'smooth'
          });
        }
      });
    });
  }
  
  // Apply navbar styles when the DOM content is loaded
  document.addEventListener('DOMContentLoaded', applyNavbarStyles);
  