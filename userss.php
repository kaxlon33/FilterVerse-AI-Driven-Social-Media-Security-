<?php
session_start();
include_once "php/config.php";
if (!isset($_SESSION['unique_id'])) {
    header("location: login.php");
}
$loggedInUserId = $_SESSION['unique_id'];

// Fetch logged-in user details
$userSql = "SELECT fname, lname, img FROM users WHERE unique_id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("s", $loggedInUserId);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
    $userName = $user['fname'] . ' ' . $user['lname'];
    $userImage = !empty($user['img']) ? "php/images/" . $user['img'] . "?height=32&width=32" : "php/images/default-avatar.png";
} else {
    $userName = "Unknown User";
    $userImage = "php/images/default-avatar.png";
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
    <title>SocialConnect - Friend Requests</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/userss.css">
 
</head>

<body>
    <header>
        <div class="header-content">
            <a href="#" class="logo">SocialConnect</a>
            <div class="search-barr">
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
                <h2>Connections</h2>
                <a href="userss.php" class="sidebar-link">Conversation</a>
                <a href="block_fri.php" class="sidebar-link">Blocked</a>
            </aside>

            <section class="users-section">
                <div class="users-header">
                    <div class="user-profile">
                        <?php
                        $sql = mysqli_query($conn, "SELECT * FROM users WHERE unique_id = {$_SESSION['unique_id']}");
                        if (mysqli_num_rows($sql) > 0) {
                            $row = mysqli_fetch_assoc($sql);
                        }
                        ?>
                        <img src="php/images/<?php echo $row['img']; ?>" alt="<?php echo $row['fname'] . " " . $row['lname']; ?>">
                        <div class="user-details">
                            <span><?php echo $row['fname'] . " " . $row['lname']; ?></span>
                            <p><?php echo $row['status']; ?></p>
                        </div>
                    </div>
                    <a href="php/logout.php?logout_id=<?php echo $row['unique_id']; ?>" class="logout">Logout</a>
                </div>
                <div class="search">
                <span class="text"><strong>Select a user to start chat</strong></span>
                <div class="search-bar">
                        <input type="text" placeholder="Enter name to search...">
                        <button><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="users-list">
                    <!-- User list will be populated by JavaScript -->
                </div>
            </section>
        </main>
    </div>


    <script src="javascript/users.js"></script>



</body>

</html>