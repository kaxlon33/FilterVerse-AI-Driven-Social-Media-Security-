<?php
include 'php/config.php';

// Fetch the logged-in user's profile image
$userSql = "SELECT fname, lname, img FROM users WHERE unique_id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("s", $loggedInUserId);
$userStmt->execute();
$userResult = $userStmt->get_result();

// Default profile image
$userImage = "php/images/default-avatar.png";

// Function to validate and return a valid image path
function getValidUserImage($imageFile)
{
    $imagePath = "php/images/" . $imageFile;
    $imageExtension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png'];

    if (!empty($imageFile) && in_array($imageExtension, $allowedExtensions) && file_exists($imagePath)) {
        return $imagePath;
    }

    return "php/images/default-avatar.png"; // Return default if invalid
}

// Assign user image if found
if ($userRow = $userResult->fetch_assoc()) {
    $userImage = getValidUserImage($userRow['img']);
}
// Fetch user comments
$commentSql = "SELECT users.fname, users.lname, users.img, comments.content
               FROM comments 
               JOIN users ON comments.user_id = users.unique_id 
               WHERE comments.post_id = ? 
               ORDER BY comments.created_at DESC";

$commentStmt = $conn->prepare($commentSql);
$commentStmt->bind_param("s", $postId);
$commentStmt->execute();
$commentResult = $commentStmt->get_result();


// Fetch posts from the database
$sql = "SELECT posts.id, posts.content, posts.media_url, posts.created_at, 
               users.fname, users.lname, users.img, 
               (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count, 
               (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count, 
               (SELECT COUNT(*) FROM post_shares WHERE post_shares.post_id = posts.id) AS share_count 
        FROM posts 
        INNER JOIN users ON posts.user_id = users.unique_id 
        ORDER BY posts.created_at DESC";


$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($post = $result->fetch_assoc()) {
        // Format the created_at date
        $created_at = new DateTime($post['created_at']);
        $formatted_date = $created_at->format('F j, Y, g:i a'); // Example format: January 1, 2025, 2:30 pm

        // Handle media URL (fallback image if not available)
        $image_url = !empty($post['media_url']) ? $post['media_url'] : 'path/to/default-image.jpg';

        // Get dynamic counts
        $like_count = $post['like_count'];
        $comment_count = $post['comment_count'];
        $share_count = $post['share_count'];

        // Fetch the comments for the current post
        $comments_sql = "SELECT comments.id, users.fname, users.lname, users.img, comments.content, comments.created_at 
        FROM comments 
        JOIN users ON comments.user_id = users.unique_id 
        WHERE comments.post_id = ? 
        ORDER BY comments.created_at ASC";

        $comments_stmt = $conn->prepare($comments_sql);
        $comments_stmt->bind_param("i", $post['id']);
        $comments_stmt->execute();
        $comments_result = $comments_stmt->get_result();

        // Determine the image path
        $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/php/images/' . $post['img'];  // Absolute path to the image

        // Check if the image file exists
        if (!empty($post['img']) && file_exists($imagePath)) {
            $userImageSrc = '/php/images/' . $post['img'];  // Relative path to the image
        } else {
            $userImageSrc = '/php/images/default-avatar.png';  // Default image if not found
        }
        // Display post content
        echo '
        <article class="post">
            <div class="post-header">
        <img src="' . htmlspecialchars($userImageSrc) . '" alt="User Avatar" class="post-avatar">
                <div  class="post-details">
                    <div class="post-author">' . htmlspecialchars($post['fname'] . ' ' . $post['lname']) . '</div>
                    <div class="post-timestamp">' . $formatted_date . '</div>
                </div>
                  <button class="delete-post-btn" onclick="deletePost(' . $post['id'] . ')" title="Delete post">
                <i class="fas fa-ellipsis-h"></i>
            </button>
            </div>
            <div class="post-content">
        <p style="margin: 0 0 8px 0;">' . htmlspecialchars($post['content']) . '</p>
    </div>';


    if (!empty($post['media_url'])) {
        echo '<img src="' . htmlspecialchars($post['media_url']) . '" alt="Post Image" class="post-image" style="border-radius: 15px;">';
    }
    

        // Display the buttons with dynamic counts
        echo '<div style="display: flex; align-items: center; justify-content: space-between; padding: 0 16px; ">
        <form method="POST" action="update_interaction.php" style="flex: 1;">
            <input type="hidden" name="post_id" value="' . $post['id'] . '">
            <button type="submit" name="like" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 0; font-size: 15px; font-weight: 500; color: #4b5563; border: none; background: transparent; border-radius: 8px; transition: background-color 0.15s; cursor: pointer;">
                <i class="far fa-thumbs-up" style="font-size: 1.25rem;"></i>
                <span>Like (' . $like_count . ')</span>
            </button>
        </form>
    
        <button onclick="toggleCommentBox(' . $post['id'] . ')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 0; font-size: 15px; font-weight: 500; color: #4b5563; border: none; background: transparent; border-radius: 8px; transition: background-color 0.15s; cursor: pointer;">
            <i class="far fa-comment" style="font-size: 1.25rem;"></i>
            <span>Comment (' . $comment_count . ')</span>
        </button>
    
        <form method="POST" action="update_interaction.php" style="flex: 1;">
            <input type="hidden" name="post_id" value="' . $post['id'] . '">
            <button type="submit" name="share" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 0; font-size: 15px; font-weight: 500; color: #4b5563; border: none; background: transparent; border-radius: 8px; transition: background-color 0.15s; cursor: pointer;">
                <i class="far fa-share-square" style="font-size: 1.25rem;"></i>
                <span>Share (' . $share_count . ')</span>
            </button>
        </form>
    </div>
        
                <!-- Comment box (hidden by default) -->
            <div id="comment-box-' . $post['id'] . '" class="comment-box" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 8px; background-color: #f9f9f9;">
            <form method="POST" action="update_interaction.php" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <input type="hidden" name="post_id" value="' . $post['id'] . '">
                
                <!-- Comment input field -->
                <input type="text" name="content" placeholder="Write a comment..." required style="flex-grow: 1; padding: 8px 12px; border: 1px solid #ccc; border-radius: 20px; font-size: 1em; margin-right: 10px;">

                <!-- Post button -->
                <button type="submit" name="comment" class="post-button" style="background-color: #4CAF50; color: white; padding: 8px 15px; border: none; border-radius: 20px; cursor: pointer; font-size: 1em;">
                    Post
                </button>
            </form>
        </div>

        ';

        // If there are comments, display them

        if ($comments_result && $comments_result->num_rows > 0) {
            echo '<div class="comments-section">';
            while ($comment = $comments_result->fetch_assoc()) {
                $comment_date = new DateTime($comment['created_at']);
                $formatted_comment_date = $comment_date->format('F j, Y, g:i a');

                // Default image
                $commentUserImage = 'php/images/default-avatar.png';

                // Get the image for the commenter's user
                $userSql = "SELECT img FROM users WHERE unique_id = ?";
                $userStmt = $conn->prepare($userSql);
                $userStmt->bind_param("s", $comment['user_id']);
                $userStmt->execute();
                $userResult = $userStmt->get_result();

                if ($userResult && $userResult->num_rows > 0) {
                    $user = $userResult->fetch_assoc();

                    // Validate image format (JPEG, JPG, PNG)
                    $allowedExtensions = ['jpg', 'jpeg', 'png'];
                    $imagePath = "php/images/" . $user['img'];
                    $imageExtension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

                    if (!empty($user['img']) && in_array($imageExtension, $allowedExtensions) && file_exists(__DIR__ . '/php/images/' . $user['img'])) {
                        $commentUserImage = 'php/images/' . $user['img']; // Valid image path
                    }
                }
                if (!empty($comment['img']) && file_exists($imagePath)) {
                    $userImageSrc = '/php/images/' . $comment['img'];  // Relative path to the image
                } else {
                    $userImageSrc = '/php/images/default-avatar.png';  // Default image if not found
                }


                // Display comment
                echo '
                <div class="comment" style="position: relative; margin-bottom: 15px; padding: 10px; border-bottom: 1px solid #e0e0e0;">
                    <!-- Comment Avatar (Profile picture) -->
                    <div class="comment-avatar" style="float: left; margin-right: 10px;">
                    <div class="comment-avatar" style="float: left; margin-right: 10px;">
                        <img src="' . htmlspecialchars($userImageSrc) . '" alt="User Avatar" class="comment-avatar-img" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid grey;">
                    </div>
                    </div>


                    <!-- Comment Content -->
                    <div class="comment-content" style="margin-left: 50px; font-size: 1em; color: #333;">
                        <!-- Username and Delete button in a flex container -->
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <!-- User Name -->
                            <div style="font-weight: bold; font-size: 1em; color: #333;">
                                ' . htmlspecialchars($comment['fname']) . ' ' . htmlspecialchars($comment['lname']) . '
                            </div>
                
                            <!-- Delete Button -->
                            <button class="delete-comment-btn" onclick="deleteComment(' . $comment['id'] . ')" 
                                style="background: none; border: none; color: red; cursor: pointer; font-size: 1em;">
                                <i class="fas fa-trash-alt"></i> 
                            </button>
                        </div>
                        
                        <!-- Comment Content -->
                        <div style="font-size: 1em; color: #333;">
                            ' . htmlspecialchars($comment['content']) . '
                        </div>
                    
                        <!-- Comment Date/Time -->
                        <div style="font-size: 0.8em; color: #888; margin-top: 3px;">
                            ' . $formatted_comment_date . '
                        </div>
                    </div>
                </div>';
            }
            echo '</div>'; // End comments-section
        }

        echo '</article>';
    }
} else {
    echo '<p>No posts yet. Be the first to post something!</p>';
}
?>

<script>
    function deletePost(postId) {
        if (!confirm("Are you sure you want to delete this post?")) {
            return;
        }

        fetch("delete_post.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `post_id=${postId}`
            })
            .then(response => response.text())
            .then(data => {
                console.log(data); // Debug response
                if (data.trim() === "success") {
                    alert("Post deleted successfully!");
                    document.getElementById(`post-${postId}`).remove(); // Assuming each post has an ID like "post-1"
                } else {
                    alert("Error deleting post: " + data);
                }
            })
            .catch(error => console.error("Error:", error));
    }
</script>

<script>
    function deleteComment(commentId) {
        if (confirm('Are you sure you want to delete this comment?')) {
            var formData = new FormData();
            formData.append('comment_id', commentId); // Pass comment ID

            fetch('delete_comment.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'success') {
                        alert('Comment deleted successfully!');
                        location.reload(); // Reload the page to remove the deleted comment
                    } else {
                        alert('Error deleting the comment: ' + data);
                    }
                })
                .catch(error => {
                    alert('An error occurred: ' + error);
                });
        }
    }
</script>