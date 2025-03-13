<?php
session_start();
include 'php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['msg_id']) && isset($_SESSION['unique_id'])) {
    $msgId = intval($_POST['msg_id']); // Ensure msg_id is an integer
    $uniqueId = $_SESSION['unique_id']; // Use unique_id directly

    // Check if the message exists and was sent by the user (outgoing_msg_id = unique_id)
    $checkMessage = $conn->prepare("SELECT msg_id FROM messages WHERE msg_id = ? AND outgoing_msg_id = ?");
    $checkMessage->bind_param("is", $msgId, $uniqueId); // outgoing_msg_id is stored as unique_id
    $checkMessage->execute();
    $checkMessage->store_result();

    if ($checkMessage->num_rows === 0) {
        $checkMessage->close();
        exit("error: Message not found or does not belong to the user.");
    }
    $checkMessage->close();

    // Proceed with deletion
    $deleteStmt = $conn->prepare("DELETE FROM messages WHERE msg_id = ? AND outgoing_msg_id = ?");
    $deleteStmt->bind_param("is", $msgId, $uniqueId);

    if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
        echo 'success';  // Message deleted successfully
    } else {
        echo 'error: Unable to delete message.';
    }

    $deleteStmt->close();
    $conn->close();
} else {
    echo 'error: Invalid request';  // Invalid request
}
