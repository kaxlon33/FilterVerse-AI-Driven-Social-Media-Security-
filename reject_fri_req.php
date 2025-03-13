<?php
session_start();
include 'php/config.php';  // Database connection file

if (!isset($_SESSION['unique_id'])) {
    echo "User not logged in!";
    exit;
}

if (isset($_POST['sender_id'])) {
    $senderId = $_POST['sender_id'];
    $receiverId = $_SESSION['unique_id'];  // Receiver is the logged-in user

    // Step 1: Check if there is a pending friend request
    $sqlCheckRequest = "SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
    $stmtCheckRequest = $conn->prepare($sqlCheckRequest);
    $stmtCheckRequest->bind_param("ii", $senderId, $receiverId); // Bind parameters

    if ($stmtCheckRequest->execute()) {
        $resultRequest = $stmtCheckRequest->get_result();
        if ($resultRequest->num_rows > 0) {
            // Step 2: Delete the pending friend request
            $sqlDeleteRequest = "DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
            $stmtDelete = $conn->prepare($sqlDeleteRequest);
            $stmtDelete->bind_param("ii", $senderId, $receiverId); // Bind parameters
            if ($stmtDelete->execute()) {
                echo "Friend request rejected from Sender ID: " . $senderId . " to Receiver ID: " . $receiverId . "<br>";
                header("Location: fri_req.php?status=rejected");  // Redirect after rejection
                exit;
            } else {
                echo "Failed to reject the friend request.<br>";
            }
        } else {
            echo "No pending request found from Sender ID: " . $senderId . " to Receiver ID: " . $receiverId . "<br>";
        }
    } else {
        echo "Error checking the friend request: " . $stmtCheckRequest->error . "<br>";
    }
}
?>
