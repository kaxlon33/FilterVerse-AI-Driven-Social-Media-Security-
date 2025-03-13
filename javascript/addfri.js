
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".add-friend").forEach((button) => {
        button.addEventListener("click", function() {
            let userId = this.getAttribute("data-id");
            let btn = this;

            fetch("add_fri.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "receiver_id=" + userId
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "success") {
                        btn.textContent = "Request Sent";
                        btn.classList.remove("btn-primary");
                        btn.classList.add("btn-secondary");
                        btn.disabled = true;
                    } else {
                        alert("Error sending request.");
                    }
                });
        });
    });
});
