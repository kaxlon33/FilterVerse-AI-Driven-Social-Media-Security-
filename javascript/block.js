document.addEventListener("DOMContentLoaded", function() {
    var blockBtn = document.getElementById("blockBtn");
    var unblockBtn = document.getElementById("unblockBtn");
    var incomingIdElement = document.querySelector(".incoming_id");

    if (!incomingIdElement) {
        console.error("Error: Incoming ID not found.");
        return;
    }

    var incomingId = incomingIdElement.value;  // This should be the unique_id of the target user

    if (blockBtn) {
        blockBtn.addEventListener("click", function() {
            if (confirm("Are you sure you want to block this user?")) {
                blockUser(incomingId);
            }
        });
    }

    if (unblockBtn) {
        unblockBtn.addEventListener("click", function() {
            if (confirm("Are you sure you want to unblock this user?")) {
                unblockUser(incomingId);
            }
        });
    }

    function blockUser(targetUserId) {
        fetch("block_user.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "target_user_id=" + encodeURIComponent(targetUserId),
        })
        .then(response => response.text())
        .then(data => {
            console.log("Block Response:", data);
            alert(data);
            if (data.includes("User blocked successfully")) {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred while blocking the user.");
        });
    }

    function unblockUser(targetUserId) {
        fetch("unblock_user.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: "target_user_id=" + encodeURIComponent(targetUserId),
        })
        .then(response => response.text())
        .then(data => {
            console.log("Unblock Response:", data);
            alert(data);
            if (data.includes("User unblocked successfully")) {
                window.location.reload();
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred while unblocking the user.");
        });
    }
});
