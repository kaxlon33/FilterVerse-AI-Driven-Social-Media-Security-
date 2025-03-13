<?php
session_start();

if (!isset($_SESSION['unique_id'])) {
    echo "User not logged in.";
    exit();
}

include 'php/config.php'; // Include the database connection

$outgoing_id = $_SESSION['unique_id'];
$incoming_id = mysqli_real_escape_string($conn, $_POST['incoming_id']);
$message = isset($_POST['message']) ? mysqli_real_escape_string($conn, $_POST['message']) : "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postContent = $_POST['message'] ?? '';
    $imagePath = '';

    // Run offensive language detection on the post content (text only)
    $escaped_content = escapeshellarg($postContent);
    $output = shell_exec("D:/CRP/AM_app/AM_app/offensive_detection.py $escaped_content");

    // If offensive content is detected, reject the post
    if (trim($output) !== "SAFE_MESSAGE") {
        echo "<script>
                alert('Your post contains offensive content and was blocked.');
                window.location.href = 'homeee.php';
              </script>";
        exit();
    }


    // 🚀 **Step 2: Image Upload & Nudity Detection**
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $image_name = $_FILES['image']['name'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        // Check if the file extension is valid
        if (!in_array($image_extension, $allowed_extensions)) {
            echo "Error: Only JPG, JPEG, PNG, and GIF files are allowed.";
            exit();
        }

        // Generate a unique file name for the image
        $image_new_name = uniqid('IMG_', true) . "." . $image_extension;
        $imagePath = "images/" . $image_new_name; // Save in /public/images/

        // Move the uploaded file to the images folder
        if (move_uploaded_file($image_tmp_name, $imagePath)) {
            // Get the absolute path for nudity detection
            $image_absolute_path = realpath($imagePath);
            $image_absolute_path = str_replace("\\", "/", $image_absolute_path); // Fix Windows path issues

            // Run nudity detection
            $nudity_command = escapeshellcmd("D:/python_path/python.exe D:/CRP/AM_app/AM_app/ifnude-main/nudity_detection.py \"$image_absolute_path\"");
            $nudity_output = shell_exec($nudity_command . " 2>&1");

            // If nudity is detected, delete the image and exit
            if (trim($nudity_output) === "NUDITY_DETECTED") {
                unlink($imagePath); // Delete the inappropriate image
                echo "In your message, there was nudity content and it was blocked.";
                exit();
            }
        } else {
            echo "Error: Unable to upload image.";
            exit();
        }
    }

    // 🚀 **Step 3: Insert into Database**
    if (!empty($message) || !empty($imagePath)) {
        $query = "INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg, image) 
              VALUES ('$incoming_id', '$outgoing_id', '$message', '$imagePath')";

        if (!mysqli_query($conn, $query)) {
            echo "Database Insert Error: " . mysqli_error($conn);
            exit();
        } else {
            echo "Message sent successfully"; // ✅ JavaScript will handle this response
        }
    }
}
