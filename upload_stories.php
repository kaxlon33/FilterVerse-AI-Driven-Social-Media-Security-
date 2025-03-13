<?php
session_start();
include 'php/config.php'; // Database connection

// Ensure user is logged in
if (!isset($_SESSION['unique_id'])) {
    echo "<script>
            alert('User not logged in. Please log in to upload a story.');
            window.location.href = 'login.php';
          </script>";
    exit();
}

$unique_id = $_SESSION['unique_id'];

try {
    $imagePath = '';

    // Check if an image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/stories/";

        // Ensure the directory exists
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Validate image type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid image format. Only JPG and PNG allowed.');
        }

        // Generate a unique filename and move uploaded file
        $imageExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageNewName = uniqid('', true) . "." . $imageExtension;
        $imagePath = $targetDir . $imageNewName;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            throw new Exception('Error uploading image.');
        }

        // Get the absolute path and normalize it for shell execution
        $imagePathAbsolute = realpath($imagePath);
        $imagePathAbsolute = str_replace("\\", "/", $imagePathAbsolute);

        // Debugging: Print image path
        // echo "Image path: " . $imagePathAbsolute; 

        // Construct the Python command
        $pythonPath = "D:/python_path/python.exe"; // Change this to your actual Python path
        $scriptPath = "D:/CRP/AM_app/AM_app/ifnude-main/nudity_detection.py";
        $command = escapeshellcmd("$pythonPath $scriptPath " . escapeshellarg($imagePathAbsolute));

        // Execute the command
        $output = shell_exec($command . " 2>&1");

        // Debugging: Output the result of the command
        // echo "<pre>$output</pre>";

        // If nudity is detected, delete the image and reject upload
        if (trim($output) === "NUDITY_DETECTED") {
            unlink($imagePath); // Remove inappropriate image
            throw new Exception('Nudity detected. Upload rejected.');
        }

        // Insert into database
        $sql = "INSERT INTO stories (user_id, media_url) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $unique_id, $imagePath);

        if ($stmt->execute()) {
            echo "<script>
                    alert('Story uploaded successfully!');
                    window.location.href = 'homeee.php';
                  </script>";
        } else {
            throw new Exception('Database error: Failed to insert story.');
        }
    } else {
        throw new Exception('No image file uploaded or file upload error.');
    }
} catch (Exception $e) {
    echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'homeee.php';
          </script>";
}
?>
