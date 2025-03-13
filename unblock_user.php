<?php
session_start();
include_once "php/config.php";  // Include your database connection file

if (!isset($_SESSION['unique_id'])) {
    die("Session unique_id is not set.");
}

// Ensure target_user_id is set and not empty
if (!isset($_POST['target_user_id']) || empty($_POST['target_user_id'])) {
    echo "Error: Target user ID is missing.";
    exit;
}

// Sanitize inputs
$blocker_id = $_SESSION['unique_id'];  // Blocker user ID from session (which is unique_id)
$target_user_id = mysqli_real_escape_string($conn, $_POST['target_user_id']);  // Sanitize the target user ID

// // Debugging: Print Blocker and Target IDs to check if they are valid
// echo "Blocker ID (Unique ID): " . $blocker_id . "<br>";
// echo "Target User ID (Unique ID): " . $target_user_id . "<br>";

// Ensure no leading/trailing spaces in IDs
$blocker_id = trim($blocker_id);
$target_user_id = trim($target_user_id);

// Check if both users exist in the users table based on unique_id
$stmt = $conn->prepare("SELECT unique_id FROM users WHERE unique_id = ?");
$stmt->bind_param("i", $blocker_id);
$stmt->execute();
$result1 = $stmt->get_result();

// Debug: Check results of Blocker query
// if ($result1->num_rows == 0) {
//     echo "Blocker user not found.";
// } else {
//     echo "Blocker user found.";
// }

$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$result2 = $stmt->get_result();

// // Debug: Check results of Target query
// if ($result2->num_rows == 0) {
//     echo "Target user not found.<br>";
// } else {
//     echo "Target user found.<br>";
// }

if ($result1->num_rows == 0 || $result2->num_rows == 0) {
    echo "Error: One or both users do not exist.";
    exit;
}

// Check if user is already unblocked
$stmt = $conn->prepare("SELECT * FROM blocked_users WHERE blocker_id = ? AND target_user_id = ?");
$stmt->bind_param("ii", $blocker_id, $target_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "User is not blocked.";
} else {
    // Delete from blocked_users table to unblock the user
    $stmt = $conn->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND target_user_id = ?");
    $stmt->bind_param("ii", $blocker_id, $target_user_id);
    if ($stmt->execute()) {
        echo "User unblocked successfully";
    } else {
        echo "Failed to unblock user: " . $conn->error;
    }
}

// Close the database connection
mysqli_close($conn);
?>
