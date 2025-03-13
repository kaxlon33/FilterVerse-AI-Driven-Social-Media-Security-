<?php
session_start();
include 'php/config.php'; // Include database connection

// Ensure the user is logged in
if (!isset($_SESSION['unique_id'])) {
    echo "<script>
            alert('User not logged in. Please log in to upload a story.');
            window.location.href = 'login.php';
          </script>";
    exit();
}

// Get the logged-in user's ID
$unique_id = $_SESSION['unique_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postContent = $_POST['post_content'] ?? '';
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

    // Check if an image is uploaded
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/'; // Directory for storing images

        // Ensure the directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Validate image type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($_FILES['post_image']['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>
                    alert('Invalid image format. Only JPG and PNG are allowed.');
                    window.location.href = 'homeee.php';
                  </script>";
            exit();
        }

        // Generate a unique filename
        $imageName = uniqid() . '-' . basename($_FILES['post_image']['name']);
        $imagePath = $uploadDir . $imageName;

        // Move the uploaded file to the target directory
        if (!move_uploaded_file($_FILES['post_image']['tmp_name'], $imagePath)) {
            echo "<script>
                    alert('Error uploading image.');
                    window.location.href = 'homeee.php';
                  </script>";
            exit();
        }

        // Get absolute path and format it for Windows compatibility
        $imageAbsPath = realpath($imagePath);
        $imageAbsPath = str_replace("\\", "/", $imageAbsPath);

        // Path to Python and nudity detection script
        $pythonPath = "D:/python_path/python.exe";
        $scriptPath = "D:/CRP/AM_app/AM_app/ifnude-main/nudity_detection.py";

        // Execute nudity detection script
        $command = escapeshellcmd("$pythonPath $scriptPath $imageAbsPath");
        $output = shell_exec($command . " 2>&1");

        // If nudity is detected, delete the uploaded image and reject the post
        if (trim($output) === "NUDITY_DETECTED") {
            unlink($imagePath); // Remove the image from storage
            echo "<script>
                    alert('Nudity detected. Please upload a different image.');
                    window.location.href = 'homeee.php';
                  </script>";
            exit();
        }
    }

    // Insert post into the database (only if no offensive content or nudity detected)
    $sql = "INSERT INTO posts (user_id, content, media_url) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $unique_id, $postContent, $imagePath);

    if ($stmt->execute()) {
        header('Location: homeee.php');
        exit;
    } else {
        echo "<script>
                alert('Error saving post.');
                window.location.href = 'homeee.php';
              </script>";
    }
}
?>
