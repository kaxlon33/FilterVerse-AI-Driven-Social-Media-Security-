<?php
session_start();
include 'php/config.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['unique_id'])) {
        die('User not logged in.');
    }

    $unique_id = $_SESSION['unique_id']; // Get the user's unique ID
    $post_id = $_POST['post_id'];

    if (isset($_POST['like'])) {
        // Handle "like" action
        $sql = "INSERT INTO likes (post_id, user_id, created_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE created_at = NOW()"; // Avoid duplicate likes
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $post_id, $unique_id);
        $stmt->execute(); // Missing execute added
        $stmt->close();
        header("Location: homeee.php"); // Redirect after action
        exit;
    }

    $comment = trim($_POST['content']);
    if (!empty($comment)) {
        // Run offensive detection
        $escaped_comment = escapeshellarg($comment);
        $output = shell_exec("D:/CRP/AM_app/AM_app/offensive_detection.py $escaped_comment");

        if (trim($output) !== "SAFE_MESSAGE") {
            echo "<script>
                    alert('Your comment contains offensive content and was blocked.');
                    window.history.back();
                  </script>";
            exit;
        }
        // Insert comment if it's safe
        $sql = "INSERT INTO comments (post_id, user_id, content, created_at) 
            VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $post_id, $unique_id, $comment);
        $stmt->execute();
        $stmt->close();
        header("Location: homeee.php");
        exit;
    }


    if (isset($_POST['share'])) {
        $post_id = $_POST['post_id']; // Ensure post_id is received
        $unique_id = $_SESSION['unique_id']; // Ensure user_id is set
    
        // Insert a record into post_shares (track share action)
        $share_sql = "INSERT INTO post_shares (post_id, user_id, created_at) VALUES (?, ?, NOW())";
        $share_stmt = $conn->prepare($share_sql);
        $share_stmt->bind_param("ii", $post_id, $unique_id);
        
        if ($share_stmt->execute()) {
            // Fetch the original post content and media
            $sql = "SELECT content, media_url FROM posts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $stmt->bind_result($content, $media_url);
            $stmt->fetch();
            $stmt->close();
    
            // Insert duplicate post with a new timestamp
            $insert_sql = "INSERT INTO posts (user_id, content, media_url, created_at) 
                           VALUES (?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iss", $unique_id, $content, $media_url);
            
            if ($insert_stmt->execute()) {
                header("Location: homeee.php"); // Redirect to refresh posts
                exit;
            }
        }
    }
    
    

    
}
