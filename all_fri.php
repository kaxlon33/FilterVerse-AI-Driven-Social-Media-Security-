<?php
session_start();
include_once "php/config.php"; // Database connection file

// Ensure user is logged in
if (!isset($_SESSION['unique_id'])) {
    die("You must be logged in to see friends.");
}

$loggedInUserId = $_SESSION['unique_id'];  // Use unique_id from session for logged-in user

// Step 1: Query the friends table to get the friends of the logged-in user
// Fetch logged-in user details

// Fetch logged-in user details
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

// Close the database connection


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
    <title>SocialConnect - Friend Requests</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/all_fri.css">
    <script src="javascript/all_fri.js"></script>

   
</head>

<body>
    <header>
        <div class="header-content">
            <a href="#" class="logo">SocialConnect</a>
            <div class="search-bar">
                <input type="text" placeholder="Search...">
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
            <aside class="sidebar">
                <h2>Discover People</h2>
                <a href="fri_req.php" class="sidebar-link">Friend Requests</a>
                <a href="addfri.php" class="sidebar-link">People You May Know</a>
                <h2>Friend Lists</h2>
                <a href="all_fri.php" class="sidebar-link">Close Friends</a>

            </aside>

            <section class="friend-requests-section">
                <div class="friend-requests-header">
                    <h2>Friend Requests</h2>
                </div>
                <div class="friend-requests-list" id="friendRequestsList">
                    <section class="friends">
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

                        // Prepare and execute SQL query
                        $friendsCheckStmt = $conn->prepare($friendsCheckSql);
                        $friendsCheckStmt->bind_param("ssss", $loggedInUserId, $loggedInUserId, $loggedInUserId, $loggedInUserId);
                        $friendsCheckStmt->execute();
                        $friendsCheckResult = $friendsCheckStmt->get_result();

                        // Display friends list
                        if ($friendsCheckResult->num_rows > 0) {
                            while ($row = $friendsCheckResult->fetch_assoc()) {
                                $friendId = $row['unique_id'];
                                $friendName = $row['fname'] . ' ' . $row['lname'];
                                $friendAvatar = (!empty($row['img']) && file_exists("php/images/" . $row['img']))
                                    ? "php/images/" . $row['img'] . "?t=" . time()
                                    : 'https://via.placeholder.com/100';

                                echo "
                    <div style='background-color: #ffffff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); margin-bottom: 10px; padding: 12px; display: flex; align-items: center;'>
                        <img src='" . htmlspecialchars($friendAvatar) . "' style='width: 60px; height: 60px; border-radius: 50%; margin-right: 12px; object-fit: cover;'>
                        <div>
                            <h3 style='font-size: 16px; font-weight: 600; color: #1c1e21; margin: 0 0 4px 0;'>" . htmlspecialchars($friendName) . "</h3>
                        </div>
                    </div>";
                            }
                        } else {
                            echo "
                            <p style='font-size: 18px; font-weight: bold; color: #555; background: #f8f9fa; '>
                                You have no Friends yet 🫂
                            </p>
                        ";
                        }

                        // Close the prepared statement
                        $friendsCheckStmt->close();
                        ?>
                    </section>
                </div>
            </section>
    </div>
    </section>
    </main>
    </div>

    <div class="toast" id="toast"></div>

   
</body>

</html>
</body>

</html>