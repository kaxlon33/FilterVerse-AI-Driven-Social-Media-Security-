<?php
session_start();
include 'config.php'; // Ensure this file has a proper database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']); 

    // echo "Email: " . $email . "<br>"; // Debug: Output email
    // echo "Password: " . $password . "<br>"; // Debug: Output password

    if (empty($email) || empty($password)) {
        echo "<script>alert('Email and password cannot be empty.'); window.location.href='index.php';</script>";
        exit();
    }

    // Check if user is banned
    $ban_check_query = "SELECT * FROM banned_users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $ban_check_query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $ban_result = mysqli_stmt_get_result($stmt);

    // Debugging: Check if the user is banned
    if (mysqli_num_rows($ban_result) > 0) {
        echo "Your account is banned for 3 days. You cannot log in due to a violation of our terms and conditions."; // Debug: User is banned
        exit();
    }

    // Check if user exists in users table
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // Debugging: Check if user is found
    if (!$user) {
        echo "No user found.<br>"; // Debug: User not found
        echo "<script>alert('Invalid email or password.'); window.location.href='index.php';</script>";
        exit();
    }

    // Debugging: Output user details (excluding password)
    echo "User Found: <br>";
    echo "User ID: " . $user['unique_id'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo "Password does not match.<br>"; // Debug: Password mismatch
        echo "<script>alert('Invalid email or password.'); window.location.href='index.php';</script>";
        exit();
    }

    // Successful login, create session
    $_SESSION['unique_id'] = $user['unique_id'];
    echo "Login successful! Redirecting...<br>"; // Debug: Successful login
    header("Location: homeee.php");
    exit();
}
?>
