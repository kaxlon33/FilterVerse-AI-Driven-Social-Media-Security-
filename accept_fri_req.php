<?php
session_start();
include 'php/config.php';  // Database connection file

// Check if the user is logged in
if (!isset($_SESSION['unique_id'])) {
    echo "User not logged in!";
    exit;
}

// Check if the sender's unique_id is provided in the POST request
if (!isset($_POST['sender_id'])) {
    echo "Sender unique_id is missing!";
    exit;
}

$senderId = $_POST['sender_id'];  // Sender unique_id from the form
$receiverId = $_SESSION['unique_id']; // Receiver is the logged-in user (using their unique_id)

// Step 1: Check if the sender exists in the users table based on unique_id
$sqlCheckSender = "SELECT unique_id FROM users WHERE unique_id = ?";
$stmtCheckSender = $conn->prepare($sqlCheckSender);
$stmtCheckSender->bind_param("s", $senderId); // Binding sender unique_id
if ($stmtCheckSender->execute()) {
    $resultSender = $stmtCheckSender->get_result();
} else {
    echo "Error in sender check!";
    exit;
}

// Check if sender exists
if ($resultSender->num_rows > 0) {
    // Step 2: Check if the receiver exists in the users table based on unique_id (logged-in user)
    $sqlCheckReceiver = "SELECT unique_id FROM users WHERE unique_id = ?";
    $stmtCheckReceiver = $conn->prepare($sqlCheckReceiver);
    $stmtCheckReceiver->bind_param("s", $receiverId); // Binding receiver unique_id to match the logged-in user
    if ($stmtCheckReceiver->execute()) {
        $resultReceiver = $stmtCheckReceiver->get_result();
    } else {
        echo "Error in receiver check!";
        exit;
    }

    // Check if receiver exists
    if ($resultReceiver->num_rows > 0) {
        // Step 3: Update the friend request status to 'accepted'
        $sqlUpdateRequest = "UPDATE friend_requests SET status = 'accepted' WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
        $stmtUpdate = $conn->prepare($sqlUpdateRequest);
        $stmtUpdate->bind_param("ss", $senderId, $receiverId); // Binding both sender and receiver unique_id
        if ($stmtUpdate->execute()) {

            
            // Step 4: Check if the friendship already exists (in either direction)
            $sqlCheckFriendship = "SELECT * FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)";
            $stmtCheckFriendship = $conn->prepare($sqlCheckFriendship);
            $stmtCheckFriendship->bind_param("ssss", $receiverId, $senderId, $senderId, $receiverId);
            $stmtCheckFriendship->execute();
            $resultFriendship = $stmtCheckFriendship->get_result();

            $sqlDeleteRequest = "DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'accepted'";
            $stmtDelete = $conn->prepare($sqlDeleteRequest);
            $stmtDelete->bind_param("ii", $senderId, $receiverId);
            $stmtDelete->execute();


            if ($resultFriendship->num_rows == 0) {
                // Friendship doesn't exist yet, insert the friendship
                $sqlInsertFriendship = "INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted'), (?, ?, 'accepted')";
                $stmtInsert = $conn->prepare($sqlInsertFriendship);
                $stmtInsert->bind_param("ssss", $receiverId, $senderId, $senderId, $receiverId);
                if ($stmtInsert->execute()) {
                    echo "<script>alert('Friend request accepted! Friendship established.'); window.location.href='all_fri.php';</script>";
                } else {
                    echo "<script>alert('Error establishing friendship.'); window.history.back();</script>";
                }
            } else {
                echo "<script>alert('Friendship already exists!'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Error accepting friend request.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Receiver does not exist!'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Sender does not exist!'); window.history.back();</script>";
}
?>
