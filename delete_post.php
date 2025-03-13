<?php
session_start();
include 'php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id']) && isset($_SESSION['unique_id'])) {
    $postId = intval($_POST['post_id']);
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

    // Check if post exists and belongs to the user
    $checkPost = $conn->prepare("SELECT id FROM posts WHERE id = ? AND user_id = ?");
    $checkPost->bind_param("ii", $postId, $uniqueId);
    $checkPost->execute();
    $checkPost->store_result();
    if ($checkPost->num_rows === 0) {
        die("Error: You are not allowed to delete another user's post.");
    }
    $checkPost->close();

    // Proceed with deletion
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $postId, $uniqueId);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo 'success';
    } else {
        echo 'error: ' . $conn->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo 'error: Invalid request';
}
?>
