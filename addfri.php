<?php
session_start();
include_once "php/config.php"; // Database connection file

// Ensure the user is logged in
if (!isset($_SESSION['unique_id'])) {
    die("You must be logged in to view this page.");
}

$loggedInUserId = $_SESSION['unique_id']; // Get logged-in user ID

$sql = "
    SELECT u.unique_id, u.fname, u.lname, u.img 
    FROM users u
    WHERE u.unique_id != ? 
    AND NOT EXISTS (
        SELECT 1 
        FROM friends f
        WHERE (
            (f.user_id = u.unique_id AND f.friend_id = ?) 
            OR (f.friend_id = u.unique_id AND f.user_id = ?)
        )
        AND f.status = 'accepted'  -- Only exclude confirmed friends
    )
";



$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $loggedInUserId, $loggedInUserId, $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();

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

    // Validate image format (JPEG, JPG, PNG)
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    $imagePath = "php/images/" . $user['img'];
    $imageExtension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

    if (!empty($user['img']) && in_array($imageExtension, $allowedExtensions)) {
        $userImage = $imagePath;
    } else {
        $userImage = "php/images/default-avatar.png"; // Provide a default image
    }
} else {
    $userName = "Unknown User";
    $userImage = "php/images/default-avatar.png"; // Provide a default image
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

// Output unique message senders

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
    <title>Add Friends - SocialConnect</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/addfri.css">
    <script src="javascript/addfri.js"></script>

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

            <section class="friend-suggestions">
                <h1 class="section-title">People You May Know</h1>

                <?php
                // Check if there are users to suggest
                if ($result->num_rows > 0) {
                    while ($user = $result->fetch_assoc()) {
                        // Validate and display user image
                        $allowedExtensions = ['jpg', 'jpeg', 'png'];
                        $imagePath = "php/images/" . $user['img'];
                        $imageExtension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

                        if (!empty($user['img']) && in_array($imageExtension, $allowedExtensions)) {
                            $userImage = $imagePath;
                        } else {
                            $userImage = "php/images/default-avatar.png"; // Default image if invalid
                        }

                        // Check if a friend request has already been sent
                        $sqlCheckRequest = "SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
                        $stmt = $conn->prepare($sqlCheckRequest);
                        $stmt->bind_param("ii", $_SESSION['unique_id'], $user['unique_id']);
                        $stmt->execute();
                        $requestResult = $stmt->get_result();
                        $isRequestSent = $requestResult->num_rows > 0;

                        // Set button text based on request status
                        $buttonText = $isRequestSent ? "Request Sent" : "Add Friend";
                        $buttonClass = $isRequestSent ? "btn-secondary" : "btn-primary";
                        $disabledAttr = $isRequestSent ? "disabled" : "";

                        // Display each user in the suggestion card
                        echo "
        <div class='suggestion-card'>
            <div class='user-info'>
                <img src='" . htmlspecialchars($userImage) . "' alt='Profile Picture' class='user-avatar-large'>
                <div class='user-details'>
                    <h3>" . htmlspecialchars($user['fname']) . " " . htmlspecialchars($user['lname']) . "</h3>
                    <p class='mutual-friends'>No mutual friends</p>
                </div>
            </div>
            <div class='action-buttons'>
                <button class='btn $buttonClass add-friend' data-id='" . htmlspecialchars($user['unique_id']) . "' $disabledAttr>$buttonText</button>
            </div>
        </div>";
                    }
                } else {
                    echo "<p>No suggestions at the moment.</p>";
                }
                ?>

            </section>
        </main>
    </div>
  


</body>

</html>