<?php
session_start();
include 'php/config.php'; // Replace with your database connection file

// Check if the user is logged in
if (!isset($_SESSION['unique_id'])) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['name'])) {
    echo "Welcome, " . htmlspecialchars($_SESSION['name']) . "!";
} else {
    // echo "Welcome!";
}


$unique_id = $_SESSION['unique_id'];

try {
    // Check if user exists
    $sql = "SELECT user_id, fname, lname FROM users WHERE unique_id = ?";
    $stmt = $conn->prepare($sql); // Use $conn (MySQLi connection)
    $stmt->bind_param("i", $unique_id); // Bind the parameter
    $stmt->execute();
    $result = $stmt->get_result();

    $user = $result->fetch_assoc(); // Fetch the result as an associative array

    if (!$user) {
        throw new Exception('Invalid user.');
    }

    $user_id = $user['user_id'];

    // Fetch stories
    $sql = "SELECT stories.media_url, users.fname, users.lname 
            FROM stories 
            INNER JOIN users ON stories.user_id = users.user_id 
            ORDER BY stories.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();

    $stories = [];
    while ($story = $result->fetch_assoc()) {
        $stories[] = $story; // Add each story to the stories array
    }
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
    exit();
}

if (isset($_SESSION['unique_id'])) {
    $user_id = $_SESSION['unique_id'];  // Get the logged-in user ID from the session

    // Query the database to get the user's profile image and name
    $sql = "SELECT fname, lname, img FROM users WHERE unique_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $unique_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($fname, $lname, $img);
    $stmt->fetch();

    // Check if the user has a profile image, if not, use a default image
    $image_path = !empty($img) ? 'php/images/' . $img : 'php/images/default-avatar.png';
    $full_name = htmlspecialchars($fname . ' ' . $lname); // Combine first name and last name
} else {
    // If not logged in, use a default image
    $image_path = 'php/images/default-avatar.png';
    $full_name = "Guest"; // Display "Guest" if not logged in
}
$loggedInUserId = $_SESSION['unique_id'];


try {
    $userImage = '';

    // Fetch user details from the database
    $userSql = "SELECT fname, lname, img FROM users WHERE unique_id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("s", $unique_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        $userName = htmlspecialchars($user['fname'] . ' ' . $user['lname']);

        // Check if the user has a profile image
        if (!empty($user['img'])) {
            $imagePath = "php/images/" . $user['img'];  // Store in the uploads/images directory
            $absolutePath = __DIR__ . "/" . $imagePath; // Get the full path to check the image

            // Check if the file exists and is a valid image
            if (file_exists($absolutePath)) {
                $fileType = mime_content_type($absolutePath);
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif , image/JPG'];

                // Check if file type is allowed
                if (in_array($fileType, $allowedTypes)) {
                    $userImage = $imagePath;  // Set the user image path if valid
                } else {
                    throw new Exception('Invalid image format. Only JPG, PNG, and JPEG are allowed.');
                }
            } else {
                $userImage = '';  // Optionally leave it empty or set a default image
            }
        }
    }
} catch (Exception $e) {
    echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'homeee.php';
          </script>";
}

$sql = "SELECT s.media_url, u.fname, u.lname 
        FROM stories s 
        JOIN users u ON s.user_id = u.unique_id 
        WHERE s.user_id = ? ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $unique_id);
$stmt->execute();
$result = $stmt->get_result();

$stories = [];
while ($row = $result->fetch_assoc()) {
    $stories[] = $row;
}

// Initialize counts
$messages_count = 0;
$notifications_count = 0;
$addfriend_count = 0;

// Fetch unique senders who sent messages to the logged-in user
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

$query = "
    SELECT s.id, s.user_id, s.media_url, u.fname, u.lname, u.email, u.password, u.img, u.status
    FROM stories s
    INNER JOIN users u ON s.user_id = u.unique_id
";
$result = $conn->query($query);

$stories = [];
if ($result->num_rows > 0) {
    // Fetch all stories and associated user data from the database
    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }
} else {
    echo "No stories found.";
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SocialConnect - Your Social Media Hub</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/home.css">
    <script src="javascript/home.js"></script>

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
        <main>
            <section class="stories-container">
                <!-- "Add to your day" Story -->
                <div class="story add-story" id="add-photo-icon" onclick="document.getElementById('photo-input').click();">
                    <div class="story-circle">
                        <div class="circle-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                    </div>
                    <span class="story-username">Add Your Day</span>
                </div>

                <!-- Display uploaded "My Day" stories -->
                <?php if (!empty($stories)): ?>
                    <?php foreach ($stories as $story): ?>
                        <div class="story">
                            <div class="story-circle">
                                <img src="<?= htmlspecialchars($story['media_url']); ?>"
                                    alt="<?= htmlspecialchars($story['fname']) . ' ' . htmlspecialchars($story['lname']); ?>">

                                <!-- Show the delete button only if the user owns the story -->
                                <?php if ($_SESSION['unique_id'] == $story['user_id']): ?>
                                    <button class="delete-story-btn" onclick="deleteStory(<?= $story['id']; ?>)"
                                        style="background: none; border: none; color: red; cursor: pointer; font-size: 1em;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php endif; ?>

                            </div>
                            <span class="story-username">
                                <?= htmlspecialchars($story['fname']) . ' ' . htmlspecialchars($story['lname']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #65676b; padding: 10px;">No stories available.</p>
                <?php endif; ?>
            </section>

            <!-- Hidden file input for uploading the photo -->
            <form id="story-upload-form" action="upload_stories.php" method="post" enctype="multipart/form-data">
                <input type="file" id="photo-input" name="image" style="display: none;" accept="image/jpeg, image/png, image/jpg" onchange="uploadStory();">
            </form>

            <script>
                function uploadStory() {
                    document.getElementById('story-upload-form').submit();
                }
            </script>


            <!-- Hidden file input for uploading the photo -->
            <input type="file" id="photo-input" style="display: none;" accept="image/*">

            <section class="create-post">
                <form id="post-upload-form" action="upload_post.php" method="post" enctype="multipart/form-data">
                    <div class="post-input">
                        <!-- Displaying user's avatar -->
                        <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Your profile" class="user-avatar">

                        <!-- Text area for entering post content -->
                        <textarea name="post_content" id="post_content" placeholder="What's on your mind, <?php echo htmlspecialchars($full_name); ?>?" class="status-input" required></textarea>

                        <!-- Submit Button -->
                        <button type="submit" style="background-color: #4CAF50; color: white; border: none; padding: 15px 20px; border-radius: 5px; cursor: pointer; font-size: 1em;">
                            Post
                        </button>
                    </div>

                    <!-- Post Actions for additional options like Image upload, Tagging Friends, and adding Feelings -->
                    <div class="post-actions">
                        <label class="post-action-button">
                            <i class="fas fa-camera"></i> Photo/Video
                            <!-- File input for image upload -->
                            <input type="file" name="post_image" accept="image/*" hidden>
                        </label>
                        <button type="button" class="post-action-button" onclick="tagFriends()">
                            <i class="fas fa-user-friends"></i> Tag Friends
                        </button>
                        <button type="button" class="post-action-button" onclick="addFeeling()">
                            <i class="fas fa-smile"></i> Feeling/Activity
                        </button>
                    </div>
                </form>
            </section>


            <section class="content">
                <?php include 'fetch_post.php' ?>
            </section>

           
            </section>

            <script src="javascript/users.js"></script>

        </main>
    </div>



</body>

</html>