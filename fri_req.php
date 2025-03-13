<?php
session_start();
include_once "php/config.php"; // Database connection file

// Ensure the user is logged in
if (!isset($_SESSION['unique_id'])) {
    die("You must be logged in to see notifications.");
}

$loggedInUserId = $_SESSION['unique_id'];

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

$uniqueId = $_SESSION['unique_id']; // Get logged-in user's unique_id

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

// Close the database connection


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
    <link rel="stylesheet" href="css/fri_req.css">
    <script src="javascript/fri_req.js"></script>

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
                <a href="fri_req.php" class="sidebar-link-1">Friend Requests</a>
                <a href="addfri.php" class="sidebar-link-1">People You May Know</a>

                <h2>Friend Lists</h2>
                <a href="all_fri.php" class="sidebar-link-2">Close Friends</a>

            </aside>


            <section class="friend-requests-section">
                <div class="friend-requests-header">
                    <h2>Friend Requests</h2>
                </div>
                <div class="friend-requests-list">
                    <?php
                    $sqlUserId = "SELECT unique_id FROM users WHERE unique_id = ?";
                    $stmt = $conn->prepare($sqlUserId);
                    $stmt->bind_param("s", $uniqueId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    // If the user_id exists, proceed to fetch friend requests
                    if ($result->num_rows > 0) {
                        $user = $result->fetch_assoc();
                        $userId = $user['unique_id'];

                        // Step 2: Retrieve all friend requests for the logged-in user (no JOIN, only the friend_requests table)
                        $sqlFriendRequests = "SELECT * FROM friend_requests WHERE receiver_id = ?";  // No JOIN, just select from friend_requests

                        $stmtFriendRequests = $conn->prepare($sqlFriendRequests);
                        $stmtFriendRequests->bind_param("i", $userId); // Bind user_id as an integer
                        $stmtFriendRequests->execute();
                        $friendRequestsResult = $stmtFriendRequests->get_result();

                        // Step 3: Display the result (you can modify this part as per your needs)
                    ?>
                        <section class="friend-requests">

                            <?php
                            // Check if there are any friend requests
                            if ($friendRequestsResult->num_rows > 0) {
                                while ($request = $friendRequestsResult->fetch_assoc()) {
                                    // Extract sender details from the friend_requests table
                                    $senderId = $request['sender_id'];
                                    $createdAt = $request['created_at']; // Use the 'created_at' timestamp

                                    // Step 3a: Get sender's info (e.g., name and image) based on sender_id
                                    $sqlSenderInfo = "SELECT fname, lname, img FROM users WHERE unique_id = ?";
                                    $stmtSenderInfo = $conn->prepare($sqlSenderInfo);
                                    $stmtSenderInfo->bind_param("i", $senderId); // Bind sender_id as an integer
                                    $stmtSenderInfo->execute();
                                    $senderInfoResult = $stmtSenderInfo->get_result();
                                    $senderInfo = $senderInfoResult->fetch_assoc();

                                    // Extract sender's name and image
                                    $senderName = $senderInfo['fname'] . ' ' . $senderInfo['lname'];
                                    $senderImg = $senderInfo['img'] ? 'php/images/' . $senderInfo['img'] : '/placeholder.svg?height=50&width=50';

                            ?>
                                    <div class="friend-request-card" data-sender-id="<?php echo $senderId; ?>">
                                        <div class="user-info">
                                            <!-- Display sender's image -->
                                            <img src="<?php echo $senderImg; ?>" class="user-avatar-medium" style="width: 50px; height: 50px; border-radius: 50%; margin-right: 10px;">
                                            <div class="request-details">
                                                <h3><?php echo $senderName; ?> sent you a friend request</h3>
                                                <p class="request-time" style="margin: 5px 0 0; font-size: 14px; color: #777;"><?php echo timeAgo($createdAt); ?></p>
                                            </div>
                                        </div>
                                        <div class="request-actions" id="friend-request-<?php echo $senderId; ?>" style="margin-top: 5px;">
                                            <form action="accept_fri_req.php" method="POST" style="display: inline;" onsubmit="handleRequest('accept', <?php echo $senderId; ?>); return false;">
                                                <input type="hidden" name="sender_id" value="<?php echo $senderId; ?>">
                                                <button type="submit" class="accept-request" style="padding: 8px 12px; border: none; background-color: #4CAF50; color: white; cursor: pointer; border-radius: 5px;">Accept</button>
                                            </form>
                                            <form action="reject_fri_req.php" method="POST" style="display: inline;" id="rejectForm" onsubmit="handleRequest('reject', <?php echo $senderId; ?>); return false;">
                                                <input type="hidden" name="sender_id" value="<?php echo $senderId; ?>">
                                                <button type="submit" class="reject-request" style="padding: 8px 12px; border: none; background-color: #f44336; color: white; cursor: pointer; border-radius: 5px;">Reject</button>
                                            </form>



                                        </div>


                                    </div>

                            <?php
                                }
                            } else {
                                echo "
                                <p style='font-size: 18px; font-weight: bold; color: #555; background: #f8f9fa; '>
                                    No friend requests found 🙁
                                </p>
                             ";
                            }
                            ?>
                        </section>

                    <?php
                    } else {
                        echo "<p>User not found.</p>";
                    }

                    ?>
                </div>

            </section>

        </main>
    </div>

    <div class="toast" id="toast"></div>


</body>

</html>
</body>

</html>