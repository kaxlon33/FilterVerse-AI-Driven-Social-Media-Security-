<?php
session_start();

if (isset($_SESSION['unique_id'])) {
    include_once "config.php";
    
    // Sanitize the GET parameter to prevent SQL injection
    $logout_id = mysqli_real_escape_string($conn, $_GET['logout_id']);
    
    if (isset($logout_id)) {
        // Set the user's status to "Offline now"
        $status = "Offline now";
        $sql = mysqli_query($conn, "UPDATE users SET status = '{$status}' WHERE unique_id = '{$logout_id}'");

        if ($sql) {
            // Unset all session variables and destroy the session
            session_unset();
            session_destroy();
            
            // Redirect to login page
            header("Location: ../login.php");
            exit(); // Ensure no further code is executed
        } else {
            // If the query failed, redirect to users page
            header("Location: ../users.php");
            exit();
        }
    } else {
        // If logout_id is not set, redirect to users page
        header("Location: ../users.php");
        exit();
    }
} else {
    // If no session exists, redirect to login page
    header("Location: ../login.php");
    exit();
}
?>
