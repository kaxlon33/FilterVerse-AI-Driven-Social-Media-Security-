// JavaScript function to prevent the post from submitting if offensive language is detected in the text content
document
  .getElementById("post-upload-form")
  .addEventListener("submit", function (event) {
    let postContent = document.getElementById("post_content").value;

    // Check if post content is empty or contains only spaces
    if (!postContent.trim()) {
      alert("Post content cannot be empty.");
      event.preventDefault();
      return;
    }

    // Here you can also call an API or use JS to verify offensive language before submitting
    // For simplicity, let's assume you are using PHP to check it server-side after the form submission
  });

// Optional: Add functions for "Tag Friends" and "Add Feeling" (you can customize these based on your requirements)
function tagFriends() {
  alert("Tag friends functionality here.");
}

function addFeeling() {
  alert("Add Feeling/Activity functionality here.");
}

function toggleCommentBox(postId) {
  const commentBox = document.getElementById(`comment-box-${postId}`);
  commentBox.style.display =
    commentBox.style.display === "none" ? "block" : "none";
}

function tagFriends() {
  alert("Tag friends functionality coming soon!");
}

function addFeeling() {
  alert("Add feelings/activity functionality coming soon!");
}

// JavaScript function to delete the story
function deleteStory(storyId) {
  if (confirm("Are you sure you want to delete this story?")) {
    var formData = new FormData();
    formData.append("story_id", storyId); // Pass story ID to be deleted

    fetch("cleanup_stories.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((data) => {
        if (data === "success") {
          alert("Story deleted successfully!");
          location.reload(); // Reload the page to remove the deleted story
        } else {
          alert("Error deleting the story: " + data);
        }
      })
      .catch((error) => {
        alert("An error occurred: " + error);
      });
  }
}
