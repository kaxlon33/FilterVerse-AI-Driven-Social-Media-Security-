<?php
session_start();
include 'php/config.php';

if (!isset($_SESSION['unique_id'])) {
    echo "NOT_LOGGED_IN";
    exit();
}

$unique_id = $_SESSION['unique_id'];
$query = mysqli_query($conn, "SELECT is_banned FROM users WHERE unique_id = '$unique_id'");
$user = mysqli_fetch_assoc($query);

if ($user['is_banned']) {
    echo "BANNED";
} else {
    echo "ALLOWED";
}
?>
