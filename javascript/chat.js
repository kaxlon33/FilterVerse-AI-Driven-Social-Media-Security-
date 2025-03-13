const form = document.querySelector(".typing-area"),
  incoming_id = form.querySelector(".incoming_id").value,
  inputField = form.querySelector(".input-field"),
  sendBtn = form.querySelector("button"),
  chatBox = document.querySelector(".chat-box");

form.onsubmit = (e) => {
  e.preventDefault();
};

inputField.focus();
inputField.onkeyup = () => {
  if (inputField.value != "") {
    sendBtn.classList.add("active");
  } else {
    sendBtn.classList.remove("active");
  }
};

sendBtn.onclick = (event) => {
  event.preventDefault(); // Prevent default form submission

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "insert-chat.php", true);

  // Create a FormData object from the form
  let form = document.querySelector("form");
  let formData = new FormData(form);

  xhr.onload = () => {
    if (xhr.status === 200) {
      let response = xhr.responseText.trim();
      console.log("Server Response:", response);

      if (
        response.includes("Your message contains offensive content") ||
        response.includes("Nudity detected")
      ) {
        alert(response); // Show warning
        return;
      }

      if (response === "Message sent successfully") {
        inputField.value = ""; // Clear input
        scrollToBottom(); // Scroll to the bottom of the chat window
      } else {
        alert("Unexpected error: " + response);
      }
    } else {
      alert("Server error: " + xhr.status);
    }
  };

  // Send the form data to the PHP script
  xhr.send(formData);
};



// Helper function to scroll the chat window to the bottom
function scrollToBottom() {
  let chatContainer = document.querySelector(".chat-container"); // Adjust selector based on your HTML structure
  chatContainer.scrollTop = chatContainer.scrollHeight;
}

chatBox.onmouseenter = () => {
  chatBox.classList.add("active");
};

chatBox.onmouseleave = () => {
  chatBox.classList.remove("active");
};

setInterval(() => {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "php/get-chat.php", true); // Ensure correct path
  xhr.onload = () => {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      if (xhr.status === 200) {
        let data = xhr.response;
        chatBox.innerHTML = data;
        if (!chatBox.classList.contains("active")) {
          scrollToBottom();
        }
      }
    }
  };
  xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  xhr.send("incoming_id=" + incoming_id);
}, 500);


function scrollToBottom() {
  chatBox.scrollTop = chatBox.scrollHeight;
}


function deleteMessage(msgId) {
  // Confirm before deleting the message
  if (confirm("Are you sure you want to delete this message?")) {
    let formData = new FormData();
    formData.append("msg_id", msgId);

    // Send AJAX request to delete the message
    fetch("delete_message.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        if (data.trim() === "success") {
          // Find and remove the message container
          const messageElement = document.querySelector(`[data-msg-id="${msgId}"]`).closest(".chat");
          if (messageElement) {
            messageElement.remove();
          }
        } else {
          // Display error message if deletion fails
          alert("Failed to delete the message: " + data);
        }
      })
      .catch(error => {
        console.error("Error:", error); // Log any errors
        alert("An error occurred while deleting the message.");
      });
  }
}



function checkBanStatus() {
  fetch('check_ban_status.php') // Create a PHP file to check the ban status
    .then(response => response.text())
    .then(data => {
      if (data.trim() === "BANNED") {
        document.getElementById("chatbox").style.display = "none"; // Hide chatbox
        document.getElementById("banMessage").style.display = "block"; // Show ban message
        setTimeout(() => {
          window.location.href = "login.php"; // Redirect to login
        }, 3000);
      }
    });
}

setInterval(checkBanStatus, 5000); // Check ban status every 5 seconds
