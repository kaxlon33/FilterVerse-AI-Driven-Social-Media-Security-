
function handleRequest(action, senderId) {
    var form = document.getElementById('rejectForm');

    // You can add custom logic here if needed
    // For example, you can confirm the action:
    if (confirm("Are you sure you want to reject this request?")) {
        form.submit(); // Submit the form
    }
}

function handleRequest(action, senderId) {
    // Prevent multiple actions by hiding the forms after clicking either button
    if (action === 'accept') {
        document.getElementById('acceptForm-' + senderId).style.display = 'none';
        document.getElementById('rejectForm-' + senderId).style.display = 'none';
    } else if (action === 'reject') {
        document.getElementById('acceptForm-' + senderId).style.display = 'none';
        document.getElementById('rejectForm-' + senderId).style.display = 'none';
    }


}
