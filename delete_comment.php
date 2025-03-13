<?php
session_start();
include 'php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id']) && isset($_SESSION['unique_id'])) {
    $commentId = intval($_POST['comment_id']);  // Rename variable for clarity
    $uniqueId = $_SESSION['unique_id'];

    // Fetch user_id using unique_id
    $userQuery = $conn->prepare("SELECT user_id FROM users WHERE unique_id = ?");
    $userQuery->bind_param("s", $uniqueId);
    if (!$userQuery->execute()) {
        die("Error fetching user ID: " . $conn->error);
    }
    $userQuery->bind_result($userId);
    $userQuery->fetch();
    $userQuery->close();

    if (!$userId) {
        die("Error: User not found.");
    }

    // Check if comment exists and belongs to the user
    $checkComment = $conn->prepare("SELECT id, user_id FROM comments WHERE id = ? AND user_id = ?");
    $checkComment->bind_param("ii", $commentId, $uniqueId);  // Use $userId for checking ownership
    $checkComment->execute();
    $checkComment->store_result();
    if ($checkComment->num_rows === 0) {
        die("Error: You are not allowed to delete another user's comment.");
    }
    $checkComment->close();

    // Proceed with deletion of the comment
    $deleteStmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $deleteStmt->bind_param("ii", $commentId, $uniqueId);

    if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
        echo 'success';  // Comment deleted successfully
    } else {
        echo 'error: ' . $conn->error;  // Database error
    }

    $deleteStmt->close();
    $conn->close();
} else {
    echo 'error: Invalid request';  // Invalid POST request
}
?>
