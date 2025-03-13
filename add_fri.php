<?php
session_start();
include 'php/config.php'; // Database connection

if (!isset($_SESSION['unique_id'])) {
    echo "error";
    exit;
}

if (isset($_POST['receiver_id'])) {
    $senderId = $_SESSION['unique_id'];
    $receiverId = $_POST['receiver_id'];

    // Check if a request already exists
    $sqlCheck = "SELECT * FROM friend_requests WHERE sender_id = ? AND receiver_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("ii", $senderId, $receiverId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows == 0) {
        // Insert new friend request
        $sqlInsert = "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("ii", $senderId, $receiverId);
        if ($stmtInsert->execute()) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "already_sent";
    }
}
?>
