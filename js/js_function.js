document.getElementById('saveChanges').addEventListener('click', function() {
    // Get form data
    let formData = new FormData(document.getElementById('updateForm'));
    // Send AJAX request
    fetch('../student/home.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if the response status is OK (200–299)
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json(); // Parse the JSON response
        })
        .then(data => {
            // Check if the server returned success
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Profile Updated',
                    text: data.message || 'Your profile has been updated successfully!',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload(); // Optionally reload the page to reflect changes
                });
            } else {
                // Display error message from server
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: data.message || 'An error occurred while updating your profile. Please try again.',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Display error message for any network issues or unexpected responses
            Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'Unable to communicate with the server. Please check your internet connection and try again.',
                confirmButtonText: 'OK'
            });
        });
});

/*------------------------------------------------ Search Function --------------------------------------------*/
document.addEventListener("DOMContentLoaded", function() {
    const serviceHoursTitle = document.getElementById("service-hours");
    const titleText = serviceHoursTitle.textContent;
    serviceHoursTitle.textContent = ''; // Clear the title text initially
    let titleIndex = 0;
    // Function to display the title one letter at a time
    function displayTitle() {
        if (titleIndex < titleText.length) {
            serviceHoursTitle.textContent += titleText[titleIndex];
            titleIndex++;
        } else {
            clearInterval(titleInterval);
            serviceHoursTitle.style.display = 'block'; // Show the title after it's fully displayed
            // Set a timeout to clear the title and restart the display
            setTimeout(() => {
                serviceHoursTitle.textContent = ''; // Clear the title
                titleIndex = 0; // Reset index to start from the beginning
                titleInterval = setInterval(displayTitle, 500); // Restart the display
            }, 500); // Delay before clearing the title (1 second)
        }
    }
    // Start displaying the title letter by letter
    let titleInterval = setInterval(displayTitle, 500); // Display each letter every 0.5 seconds
});
function updateTime() {
    const date = new Date();
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    const time = date.toLocaleTimeString();
    const formattedDate = date.toLocaleDateString(undefined, options);
    // Update the content of the separate IDs
    document.getElementById('datetime-date').innerHTML = formattedDate;
    document.getElementById('datetime-time').innerHTML = time;
}
// Update the time every second
setInterval(updateTime, 1000);