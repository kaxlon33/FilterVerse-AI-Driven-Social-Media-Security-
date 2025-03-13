<?php
session_start();
include 'php/config.php';

if (!isset($_SESSION['unique_id'])) {
    echo "User not logged in.";
    exit();
}

$outgoing_id = $_SESSION['unique_id'];
$incoming_id = mysqli_real_escape_string($conn, $_POST['incoming_id']);
$message = isset($_POST['message']) ? mysqli_real_escape_string($conn, $_POST['message']) : "";

// Fetch user offense count
$userQuery = mysqli_query($conn, "SELECT offense_count, is_banned, email FROM users WHERE unique_id = '$outgoing_id'");
$userData = mysqli_fetch_assoc($userQuery);

if ($userData['is_banned']) {
    echo "<script>
                showCustomAlert('🚫 You are Banned for 3 days!', 'Redirecting to login page...', 'black');
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>";
    exit();
}

$offense_count = $userData['offense_count'] ?? 0;

// **Step 1: Offensive Word Detection**
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postContent = $_POST['message'] ?? '';
    $escaped_content = escapeshellarg($postContent);
    $output = shell_exec("D:/CRP/AM_app/AM_app/offensive_detection.py $escaped_content");

    if (trim($output) !== "SAFE_MESSAGE") {
        $offense_count++;

        if ($offense_count == 1) {
            echo "<script>
            showCustomAlert('⚠️ Warning!', 'Please do not use offensive words.', 'orange');
        </script>";
        } elseif ($offense_count == 2) {
            echo "<script>
            showCustomAlert('⚠️ Final Warning!', 'This is your second warning. If you do it again, you will be banned.', 'red');
        </script>";
        } elseif ($offense_count >= 3) {
            // Ban the user and set ban time
            $ban_time = date('Y-m-d H:i:s', strtotime('+3 days'));
            mysqli_query($conn, "UPDATE users SET is_banned = 1, ban_time = '$ban_time' WHERE unique_id = '$outgoing_id'");

            // Insert into banned_users table
            $email = $userData['email'];
            $ban_query = "INSERT INTO banned_users (email, ban_time) VALUES ('$email', '$ban_time')";
            if (!mysqli_query($conn, $ban_query)) {
                echo "Error: Unable to insert into banned_users table.";
                exit();
            }

            // Destroy session and display ban message
            session_destroy();
            echo "<script>
                showCustomAlert('🚫 You are Banned for 3 days!', 'Redirecting to login page...', 'black');
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>";
            exit();
        }

        // Update offense count
        mysqli_query($conn, "UPDATE users SET offense_count = $offense_count WHERE unique_id = '$outgoing_id'");
        exit();
    }
}

// **Step 2: Image Upload & Nudity Detection**
$imagePath = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $image_name = $_FILES['image']['name'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($image_extension, $allowed_extensions)) {
        echo "Error: Only JPG, JPEG, PNG, and GIF files are allowed.";
        exit();
    }

    $image_new_name = uniqid('IMG_', true) . "." . $image_extension;
    $imagePath = "images/" . $image_new_name;

    if (move_uploaded_file($image_tmp_name, $imagePath)) {
        $image_absolute_path = str_replace("\\", "/", realpath($imagePath));

        $nudity_command = escapeshellcmd("D:/python_path/python.exe D:/CRP/AM_app/AM_app/ifnude-main/nudity_detection.py \"$image_absolute_path\"");
        $nudity_output = shell_exec($nudity_command . " 2>&1");

        if (trim($nudity_output) === "NUDITY_DETECTED") {
            unlink($imagePath);
            $offense_count++;

            if ($offense_count == 1) {
                echo "Warning: Please do not upload inappropriate images.";
            } elseif ($offense_count == 2) {
                echo "This is your second warning. If you do it again, you will be banned.";
            } elseif ($offense_count >= 3) {
                // Ban the user and set ban time
                $ban_time = date('Y-m-d H:i:s', strtotime('+3 days'));
                mysqli_query($conn, "UPDATE users SET is_banned = 1, ban_time = '$ban_time' WHERE unique_id = '$outgoing_id'");

                // Insert into banned_users table
                $email = $userData['email'];
                $ban_query = "INSERT INTO banned_users (email, ban_time) VALUES ('$email', '$ban_time')";
                if (!mysqli_query($conn, $ban_query)) {
                    echo "Error: Unable to insert into banned_users table.";
                    exit();
                }

                // Destroy session and display ban message
                session_destroy();
                echo "<script>
                showCustomAlert('🚫 You are Banned for 3 days!', 'Redirecting to login page...', 'black');
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000);
            </script>";
                exit();
            }

            // Update offense count
            mysqli_query($conn, "UPDATE users SET offense_count = $offense_count WHERE unique_id = '$outgoing_id'");
            exit();
        }
    } else {
        echo "Error: Unable to upload image.";
        exit();
    }
}

// **Step 3: Insert into Database**
if (!empty($message) || !empty($imagePath)) {
    $query = "INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg, image) 
              VALUES ('$incoming_id', '$outgoing_id', '$message', '$imagePath')";

    if (!mysqli_query($conn, $query)) {
        echo "Database Insert Error: " . mysqli_error($conn);
        exit();
    } else {
        echo "Message sent successfully";
    }
}
