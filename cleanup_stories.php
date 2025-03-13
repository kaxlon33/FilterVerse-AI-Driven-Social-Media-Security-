<?php
session_start();
include 'php/config.php'; // Include your DB connection file

// Check if story_id and user session are set
if (isset($_POST['story_id']) && isset($_SESSION['unique_id'])) {
    $storyId = $_POST['story_id'];
    $userId = $_SESSION['unique_id']; // Logged-in user ID

    // Prepare and execute the query to check if the story belongs to the logged-in user
    $query = "SELECT user_id FROM stories WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        // Handle preparation error
        echo 'Error preparing query.';
        exit();
    }
    
    $stmt->bind_param("i", $storyId);
    if (!$stmt->execute()) {
        // Handle execution error
        echo 'Error executing query.';
        exit();
    }

    $stmt->bind_result($storyUserId);
    $stmt->fetch();
    $stmt->close(); // Close statement after use

    // Check if the story belongs to the logged-in user
    if ($storyUserId == $userId) {
        // Story belongs to the user, proceed with deletion
        $deleteQuery = "DELETE FROM stories WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        if ($deleteStmt === false) {
            // Handle preparation error
            echo 'Error preparing delete query.';
            exit();
        }

        $deleteStmt->bind_param("i", $storyId);
        if ($deleteStmt->execute()) {
            // Story successfully deleted
            echo 'success';
        } else {
            // Error executing the deletion query
            echo 'Error deleting story';
        }
        $deleteStmt->close(); // Close delete statement after use
    } else {
        // The story does not belong to the logged-in user
        echo 'You do not have permission to delete this story.';
    }
} else {
    // Invalid request if story_id or session is not set
    echo 'Invalid request.';
}

$conn->close(); // Close the database connection
?>
