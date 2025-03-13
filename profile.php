<?php
session_start();
include 'php/config.php';

// Check if the user is logged in
if (!isset($_SESSION['unique_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

$loggedInUserId = $_SESSION['unique_id'];

// Fetch logged-in user details including bio
$userSql = "SELECT fname, lname, img, bio, bg_img FROM users WHERE unique_id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("s", $loggedInUserId);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
    $userName = $user['fname'] . ' ' . $user['lname'];
    $userImage = !empty($user['img']) ? "php/images/" . htmlspecialchars($user['img']) . "?height=32&width=32" : "php/images/default-avatar.png";
    $userBio = !empty($user['bio']) ? htmlspecialchars($user['bio']) : "No bio available."; // Default bio if none is set
    $userBgImg = !empty($user['bg_img']) ? "php/images/" . htmlspecialchars($user['bg_img']) : "php/images/default-bg.jpg"; // Default background if none is set
} else {
    $userName = "Unknown User";
    $userImage = "php/images/default-avatar.png";
    $userBio = "No bio available.";
    $userBgImg = "php/images/default-bg.jpg"; // Default background
}

// Handle form submission for profile updates
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);

    // Handle file upload for background image
    if (!empty($_FILES['background']['name'])) {
        $targetDir = "php/images/";
        $fileName = basename($_FILES["background"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow only image file types
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($fileType), $allowedTypes)) {
            move_uploaded_file($_FILES["background"]["tmp_name"], $targetFilePath);
            $backgroundImage = $fileName;
        }
    } else {
        $backgroundImage = $user['bg_img']; // Keep existing image
    }

    // Extract first and last name
    $nameParts = explode(" ", $name);
    $fname = $nameParts[0] ?? "";
    $lname = isset($nameParts[1]) ? $nameParts[1] : "";

    // Update user details in the database
    $updateSql = "UPDATE users SET fname=?, lname=?, bio=?, bg_img=? WHERE unique_id=?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sssss", $fname, $lname, $bio, $backgroundImage, $loggedInUserId);

    if ($stmt->execute()) {
        header("Location: profile.php"); // Redirect to profile page after saving
        exit();
    } else {
        echo "Error updating profile.";
    }
}


$userSql = "SELECT fname, lname, img FROM users WHERE unique_id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("s", $loggedInUserId);
$userStmt->execute();
$userResult = $userStmt->get_result();

$userImage = "php/images/default-avatar.png"; // Default profile image

if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
    $userName = htmlspecialchars($user['fname'] . ' ' . $user['lname']);

    // Validate user profile image
    if (!empty($user['img'])) {
        $imagePath = "php/images/" . $user['img'];
        $imageExtension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png'];

        if (in_array($imageExtension, $allowedExtensions) && file_exists($imagePath)) {
            $userImage = $imagePath;
        }
    }
} else {
    $userName = "Unknown User";
}

$messages_count = 0;
$notifications_count = 0;
$addfriend_count = 0;

$msg_query = "SELECT DISTINCT outgoing_msg_id FROM messages WHERE incoming_msg_id = ?";
$msg_stmt = $conn->prepare($msg_query);
$msg_stmt->bind_param("i", $loggedInUserId);
$msg_stmt->execute();
$msg_stmt->bind_result($senderId);

$unique_senders = [];
while ($msg_stmt->fetch()) {
    $unique_senders[] = $senderId;
}
$msg_stmt->close();

foreach ($unique_senders as $sender) {
    // echo "User $sender sent you a message.<br>";
}

// Get unread notifications count for the logged-in user
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM notification_actors WHERE actor_id = ?")) {
    $stmt->bind_param("i", $loggedInUserId);
    $stmt->execute();
    $stmt->bind_result($notifications_count);
    $stmt->fetch();
    $stmt->close();
}

// Fetch unique senders who sent friend requests to the logged-in user
$friend_query = "SELECT DISTINCT sender_id FROM friend_requests WHERE receiver_id = ? AND status = 'pending'";
$friend_stmt = $conn->prepare($friend_query);
$friend_stmt->bind_param("i", $loggedInUserId);
$friend_stmt->execute();
$friend_stmt->bind_result($senderId);

$friend_requests = [];
while ($friend_stmt->fetch()) {
    $friend_requests[] = $senderId;
}
$friend_stmt->close();

// Close the database connection

// // Output unique friend request senders
// foreach ($friend_requests as $sender) {
//     echo "User $sender sent you a friend request.<br>";
// }

?>

<?php
// Helper function to format time ago
function timeAgo($timestamp)
{
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes      = round($seconds / 60);           // value 60 is seconds
    $hours        = round($seconds / 3600);         // value 3600 is 60 minutes * 60 sec
    $days         = round($seconds / 86400);        // value 86400 is 24 hours * 60 minutes * 60 sec
    $weeks        = round($seconds / 604800);       // value 604800 is 7 days * 24 hours * 60 minutes * 60 sec
    $months       = round($seconds / 2629440);      // value 2629440 is ((365+365+365+365)/4/12) days * 24 hours * 60 minutes * 60 sec
    $years        = round($seconds / 31553280);     // value 31553280 is (365+365+365+365)/4 days * 24 hours * 60 minutes * 60 sec

    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "one minute ago";
        } else {
            return "$minutes minutes ago";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "an hour ago";
        } else {
            return "$hours hours ago";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "yesterday";
        } else {
            return "$days days ago";
        }
    } else if ($weeks <= 4.3) { // 4.3 == 30/7
        if ($weeks == 1) {
            return "one week ago";
        } else {
            return "$weeks weeks ago";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "one month ago";
        } else {
            return "$months months ago";
        }
    } else {
        if ($years == 1) {
            return "one year ago";
        } else {
            return "$years years ago";
        }
    }
}


$notifications_count = 0;

// Fetch likes notifications
$likesSql = "
    SELECT COUNT(*) 
    FROM likes l
    JOIN posts p ON p.id = l.post_id
    WHERE p.user_id = ?";
$likesStmt = $conn->prepare($likesSql);
$likesStmt->bind_param("s", $loggedInUserId);
$likesStmt->execute();
$likesStmt->bind_result($likes_count);
$likesStmt->fetch();
$likesStmt->close();

// Fetch comments notifications
$commentsSql = "
    SELECT COUNT(*) 
    FROM comments c
    JOIN posts p ON p.id = c.post_id
    WHERE p.user_id = ?";
$commentsStmt = $conn->prepare($commentsSql);
$commentsStmt->bind_param("s", $loggedInUserId);
$commentsStmt->execute();
$commentsStmt->bind_result($comments_count);
$commentsStmt->fetch();
$commentsStmt->close();

// Fetch shares notifications
$sharesSql = "
    SELECT COUNT(*) 
    FROM post_shares ps
    JOIN posts p ON p.id = ps.post_id
    WHERE p.user_id = ?";
$sharesStmt = $conn->prepare($sharesSql);
$sharesStmt->bind_param("s", $loggedInUserId);
$sharesStmt->execute();
$sharesStmt->bind_result($shares_count);
$sharesStmt->fetch();
$sharesStmt->close();


// Fetch unread messages for the logged-in user
$messagesSql = "
    SELECT COUNT(*) 
    FROM messages
    WHERE incoming_msg_id = ? AND is_read = 0";  // Fetch unread messages where the logged-in user is the recipient
$messagesStmt = $conn->prepare($messagesSql);
$messagesStmt->bind_param("s", $loggedInUserId);
$messagesStmt->execute();
$messagesStmt->bind_result($messages_count);
$messagesStmt->fetch();
$messagesStmt->close();

// Fetch new pending friend requests for the logged-in user
$friendRequestsSql = "
    SELECT COUNT(*) 
    FROM friend_requests 
    WHERE receiver_id = ? AND status = 'pending'";  // assuming `status` is 'pending'
$friendRequestsStmt = $conn->prepare($friendRequestsSql);
$friendRequestsStmt->bind_param("s", $loggedInUserId);
$friendRequestsStmt->execute();
$friendRequestsStmt->bind_result($friend_requests_count);
$friendRequestsStmt->fetch();
$friendRequestsStmt->close();

// Update the notifications count
$notifications_count = $likes_count + $comments_count + $shares_count;

// Get unread notifications count from notification_actors
if ($stmt = $conn->prepare("SELECT COUNT(*) FROM notification_actors WHERE actor_id = ?")) {
    $stmt->bind_param("i", $loggedInUserId);
    $stmt->execute();
    $stmt->bind_result($unread_notifications);
    $stmt->fetch();
    $stmt->close();

    // Add unread notifications to the total count
    $notifications_count += $unread_notifications;
}

$friendRequestsSql = "SELECT COUNT(*) FROM friend_requests WHERE receiver_id = ? AND status = 'pending'";

$friendRequestsStmt = $conn->prepare($friendRequestsSql);
$friendRequestsStmt->bind_param("i", $loggedInUserId); // Changed "s" to "i" since receiver_id is INT
$friendRequestsStmt->execute();
$friendRequestsStmt->store_result(); // Ensure result is stored
$friendRequestsStmt->bind_result($friend_requests_count);
$friendRequestsStmt->fetch();
$friendRequestsStmt->close();



?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - SocialConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
   <link rel="stylesheet" href="css/profile.css">
   <script src="javascript/profile.js"></script>


</head>

<body>
    <header>
        <div class="header-content">
            <a href="#" class="logo">SocialConnect</a>
            <div class="search-bar">
                <input type="text" placeholder="Search..." required>
            </div>

            <nav class="nav-links">
                <!-- Home -->
                <a href="homeee.php" title="Home" class="nav-link"><i class="fas fa-home"></i></a>

                <!-- Profile -->
                <a href="profile.php" title="Profile" class="nav-link"><i class="fas fa-user-circle"></i></a>

                <!-- Messages -->
                <a href="userss.php" title="Messages" class="nav-link">
                    <i class="fas fa-envelope"></i>
                    <span class="badge"><?php echo ($messages_count > 0) ? $messages_count : '0'; ?></span>
                </a>

                <!-- Notifications -->
                <a href="noti.php" title="Notifications" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span class="badge"><?php echo ($notifications_count > 0) ? $notifications_count : '0'; ?></span>
                </a>

                <!-- Add Friend -->
                <a href="addfri.php" title="Add Friend" class="nav-link">
                    <i class="fas fa-user-plus"></i>
                    <span class="badge"><?php echo ($friend_requests_count > 0) ? $friend_requests_count : '0'; ?></span>
                </a>
            </nav>


            <div class="user-menu">
                <!-- Display user image if available -->
                <?php if ($userImage): ?>
                    <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Your profile" class="login-avatar">
                <?php else: ?>
                    <p>No image available</p>
                <?php endif; ?>

                <div class="user-details">
                    <!-- Display user name -->
                    <span><?php echo htmlspecialchars($userName); ?></span>
                </div>

                <a href="php/logout.php" title="Logout" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>



        </div>
    </header>

    <div class="container">
        <div class="profile-header">
            <!-- Background Image -->
            <div class="cover-photo" id="background-preview" style="background-image: url('<?php echo htmlspecialchars($backgroundImage); ?>');"></div>


            <div class="profile-info">
                <!-- Profile Picture -->
                <img src="<?php echo !empty($user['img']) ? 'php/images/' . $user['img'] . '?height=168&width=168' : '/placeholder.svg?height=168&width=168'; ?>"
                    alt="Profile Picture"
                    class="profile-picture">

                <div class="profile-name-bio">
                    <!-- Profile Name -->
                    <h1 class="profile-name"><?php echo htmlspecialchars($userName); ?></h1>

                    <!-- Profile Bio -->
                    <p class="profile-bio"><?php echo htmlspecialchars($userBio); ?></p>
                </div>

                <!-- Edit Profile Button -->
                <form action="edit_profile.php" method="get">
                    <button type="submit" class="edit-profile-btn">Edit Profile</button>
                </form>
            </div>
        </div>


        <div class="main-content">
            <aside class="sidebar">
                <h2>Friends</h2>
                <ul class="friends-list">
                    <?php
                    $friendsCheckSql = "
                        SELECT DISTINCT 
                            CASE 
                                WHEN f.user_id = ? THEN f.friend_id
                                ELSE f.user_id
                            END AS unique_id, 
                            u.fname, u.lname, u.img 
                        FROM friends f
                        JOIN users u ON u.unique_id = CASE 
                                                            WHEN f.user_id = ? THEN f.friend_id
                                                            ELSE f.user_id 
                                                        END
                        WHERE (f.user_id = ? OR f.friend_id = ?) AND f.status = 'accepted'";

                    $friendsCheckStmt = $conn->prepare($friendsCheckSql);
                    $friendsCheckStmt->bind_param("ssss", $loggedInUserId, $loggedInUserId, $loggedInUserId, $loggedInUserId);
                    $friendsCheckStmt->execute();
                    $friendsCheckResult = $friendsCheckStmt->get_result();

                    if ($friendsCheckResult->num_rows > 0) {
                        while ($row = $friendsCheckResult->fetch_assoc()) {
                            $friendId = $row['unique_id'];
                            $friendName = htmlspecialchars($row['fname'] . ' ' . $row['lname']);

                            // Validate and set the friend avatar
                            $friendAvatar = !empty($row['img']) && file_exists('php/images/' . $row['img'])
                                ? 'php/images/' . $row['img']
                                : '/placeholder.svg?height=40&width=40';

                            echo "
                    <li class='friend-item'>
                        <img src='" . htmlspecialchars($friendAvatar) . "' alt='" . htmlspecialchars($friendName) . "' class='friend-avatar'>
                        <span>" . htmlspecialchars($friendName) . "</span>
                    </li>";
                        }
                    } else {
                        echo "<p style='font-size: 14px; color: #1c1e21;'>You have no friends yet.</p>";
                    }

                    $friendsCheckStmt->close();
                    ?>

                </ul>
            </aside>

            <main class="posts">
                <?php
                $sql = "SELECT posts.id, posts.content, posts.media_url, posts.created_at, users.fname, users.lname, 
                        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count, 
                        (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count, 
                        (SELECT COUNT(*) FROM post_shares WHERE post_shares.post_id = posts.id) AS share_count
                        FROM posts
                        INNER JOIN users ON posts.user_id = users.unique_id
                        WHERE posts.user_id = ?  -- Only logged-in user posts
                        ORDER BY posts.created_at DESC";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $_SESSION['unique_id']); // Assuming 'unique_id' is stored in session
                $stmt->execute();
                $result = $stmt->get_result();

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

                        $comments_sql = "SELECT comments.id, users.fname, users.lname, users.img, comments.content, comments.created_at 
                        FROM comments 
                        JOIN users ON comments.user_id = users.unique_id 
                        WHERE comments.post_id = ? 
                        ORDER BY comments.created_at ASC";

                        $comments_stmt = $conn->prepare($comments_sql);
                        $comments_stmt->bind_param("i", $post['id']);
                        $comments_stmt->execute();
                        $comments_result = $comments_stmt->get_result();


                        // Display post content
                        echo '
                            <article class="post">
                               <div class="post-header" style="display: flex; align-items: center; justify-content: space-between; padding: 10px; width: 100%;">
                                <div style="display: flex; align-items: center; flex: 1;">
                                    <img src="' . htmlspecialchars($userImage) . '" alt="User Avatar" class="post-avatar" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                                    <div class="post-details">
                                        <div class="post-author" style="font-weight: bold; font-size: 1.1em;">' . htmlspecialchars($userName) . '</div>
                                        <div class="post-timestamp" style="font-size: 0.8em; color: #777;">' . $formatted_date . '</div>
                                    </div>
                                </div>
                                <div class="post-header-right" style="margin-left: auto; padding-left: 15px;">
                                    <button class="delete-post-btn" onclick="deletePost(' . $post['id'] . ')" title="Delete post" style="background: none; border: none; cursor: pointer; padding: 8px; color: #666; font-size: 1.2em;">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </div>
                            </div>
                               <div class="post-content" style="padding: 0 15px 10px 15px;">
                                    <p>' . htmlspecialchars($post['content']) . '</p>
                                </div>';


                        // Show media if available
                        if (!empty($post['media_url'])) {
                            echo '<div style="margin: 10px 0 15px 0;">
                                <img src="' . htmlspecialchars($post['media_url']) . '" 
                                    alt="Post Image" 
                                    style="width: 100%; 
                                       display: block; 
                                       border-radius: 0; 
                                       object-fit: cover;">
                            </div>';
                        }

                        // Display the buttons with dynamic counts
                        echo '
                          <div style="display: flex; align-items: center; justify-content: space-between; padding: 0 16px; ">
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
                    </div>';

                        // If there are comments, display them
                        if ($comments_result && $comments_result->num_rows > 0) {
                            echo '<div class="comments-section">';
                            while ($comment = $comments_result->fetch_assoc()) {
                                $comment_date = new DateTime($comment['created_at']);
                                $formatted_comment_date = $comment_date->format('F j, Y, g:i a');
                                if (!empty($comment['img']) && file_exists($imagePath)) {
                                    $userImageSrc = '/php/images/' . $comment['img'];  // Relative path to the image
                                } else {
                                    $userImageSrc = '/php/images/default-avatar.png';  // Default image if not found
                                }
                                echo '
                                    <div class="comment" style="position: relative; margin-bottom: 15px; padding: 10px; border-bottom: 1px solid #e0e0e0;">
                                        <!-- Comment Avatar (Profile picture) -->
                                             <div class="comment-avatar" style="float: left; margin-right: 10px;">
                                                <img src="' . htmlspecialchars($userImageSrc) . '" alt="User Avatar" class="comment-avatar-img" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid grey;">
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
            </main>
        </div>
    </div>
</body>


</html>