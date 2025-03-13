
document.addEventListener('DOMContentLoaded', function() {
    const friendRequestsList = document.getElementById('friendRequestsList');
    const toast = document.getElementById('toast');

    // Handle accept and decline actions
    friendRequestsList.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-accept') || e.target.classList.contains('btn-decline')) {
            const requestId = e.target.getAttribute('data-id');
            const action = e.target.classList.contains('btn-accept') ? 'accept' : 'decline';

            // Send AJAX request to server to update friend request status
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true); // Send request to the same page
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        // Remove the card with a fade-out effect
                        const card = e.target.closest('.friend-request-card');
                        card.style.transition = 'opacity 0.5s ease';
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.remove();
                            if (friendRequestsList.children.length === 0) {
                                friendRequestsList.innerHTML = '<p class="no-friend-requests">No pending friend requests.</p>';
                            }

                        }, 500);

                        // Show toast notification
                        showToast(`Friend request ${action}ed`);
                    } else {
                        showToast(response.message);
                    }
                }
            };
            xhr.send('action=' + action + '&request_id=' + requestId);
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
