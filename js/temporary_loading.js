document.addEventListener("DOMContentLoaded", function() {


    
    setTimeout(function() {
        // Show the progress bar and probability info
        document.querySelector('.checking-the-rubric').style.display = 'block';
        document.querySelector('.rubrics').style.display = 'block';

        // Typing effect for rubrics
        typeWriterEffect();
    }, 3000); // 6 seconds delay after hiding loader and waiting result

    setTimeout(function() {
        // Show the progress bar and probability info
        document.querySelector('.rubrics').style.display = 'none';
        document.querySelector('.checking-the-rubric').style.display = 'none';
        document.querySelector('.check-rubrics').style.display = 'none';
        document.querySelector('.initiliazing-score').style.display = 'block';
    }, 35000); // 6 seconds delay after hiding loader and waiting result


    setTimeout(function() {
        // Show the progress bar and probability info
        document.querySelector('.loader').style.display = 'none';
        document.querySelector('.initiliazing-score').style.display = 'none';
        var myModal = new bootstrap.Modal(document.getElementById('score-card-modal'));
        myModal.show();
    }, 38000); // 6 seconds delay after hiding loader and waiting result
    
    
    // This line seems to be an error, remove it:
    // initializing
    });
    
    // Remove this extra parenthesis:
    // });
    
    function typeWriterEffect() {
        const rubrics = document.querySelectorAll('.rubrics');
        let index = 0;
        let charIndex = 0;
        let interval;
    
        function typeWriter() {
            const text = rubrics[index].innerText;
            rubrics[index].innerText = ''; // Clear the text content
            rubrics[index].style.display = 'block'; // Display the current paragraph
    
            interval = setInterval(() => {
                rubrics[index].textContent += text[charIndex];
                charIndex++;
                if (charIndex === text.length) {
                    clearInterval(interval);
                    charIndex = 0;
                    index++;
                    if (index < rubrics.length) {
                        typeWriter(); // Start typing the next paragraph
                    }
                }
            }, 100); // Change the interval value to adjust the speed of typing
        }
    
        // Hide all paragraphs initially
        rubrics.forEach(p => p.style.display = 'none');
    
        // Start typing effect for the first paragraph
        typeWriter();
    }
    
    