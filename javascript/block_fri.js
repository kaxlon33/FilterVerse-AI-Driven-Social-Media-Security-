
document.addEventListener('DOMContentLoaded', function() {
    const blockedUsersList = document.getElementById('blockedUsersList');
    const toast = document.getElementById('toast');

    // Handle unblock action
    blockedUsersList.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-unblock')) {
            const userId = e.target.getAttribute('data-id');
            const card = e.target.closest('.blocked-user-card');

            // Send unblock request to server via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'unblock_user.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200 && xhr.responseText === 'success') {
                    // Remove the card with a fade-out effect
                    card.style.transition = 'opacity 0.5s ease';
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        if (blockedUsersList.children.length === 0) {
                            blockedUsersList.innerHTML = '<p>No blocked users.</p>';
                        }
                    }, 500);

                    // Show toast notification
                    showToast('User unblocked');
                } else {
                    showToast('Error unblocking user');
                }
            };
            xhr.send('user_id=' + userId);
        }
    });

    // Function to show toast notification
    function showToast(message) {
        toast.textContent = message;
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }
});

document.addEventListener("DOMContentLoaded", function() {
    // Select all unblock buttons
    const unblockBtns = document.querySelectorAll('.btn-unblock');

    unblockBtns.forEach(function(button) {
        button.addEventListener('click', function() {
            const targetUserId = this.getAttribute('data-id'); // Get the target user ID

            if (confirm("Are you sure you want to unblock this user?")) {
                unblockUser(targetUserId);
            }
        });
    });

    function unblockUser(targetUserId) {
        // Create a new XMLHttpRequest to handle the AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "unblock_user.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Send the target user ID to unblock_user.php
        xhr.send("target_user_id=" + encodeURIComponent(targetUserId));

        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = xhr.responseText;
                alert(response); // Show the response from unblock_user.php

                // Optionally, update the UI to reflect the unblocking action
                if (response.includes("User unblocked successfully")) {
                    // Hide the unblock button or update the UI
                    document.querySelector(`[data-id='${targetUserId}']`).parentElement.style.display = 'none';
                }
            } else {
                alert("An error occurred while unblocking the user.");
            }
        };

        xhr.onerror = function() {
            alert("Request failed. Please try again.");
        };
    }
});
