function deletePost(postId) {
  if (!confirm("Are you sure you want to delete this post?")) {
    return;
  }

  fetch("delete_post.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `post_id=${postId}`,
  })
    .then((response) => response.text())
    .then((data) => {
      console.log(data); // Debug response
      if (data.trim() === "success") {
        alert("Post deleted successfully!");
        document.getElementById(`post-${postId}`).remove(); // Assuming each post has an ID like "post-1"
      } else {
        alert("Error deleting post: " + data);
      }
    })
    .catch((error) => console.error("Error:", error));
}

function deleteComment(commentId) {
  if (confirm("Are you sure you want to delete this comment?")) {
    var formData = new FormData();
    formData.append("comment_id", commentId); // Pass comment ID

    fetch("delete_comment.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.text())
      .then((data) => {
        if (data === "success") {
          alert("Comment deleted successfully!");
          location.reload(); // Reload the page to remove the deleted comment
        } else {
          alert("Error deleting the comment: " + data);
        }
      })
      .catch((error) => {
        alert("An error occurred: " + error);
      });
  }
}
