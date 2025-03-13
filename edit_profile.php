<?php
// Include database connection
include 'php/config.php';

session_start();
$loggedInUserId = $_SESSION['unique_id'] ?? null;

if (!$loggedInUserId) {
    header("Location: login.php");
    exit();
}

// Fetch existing user details
$sql = "SELECT fname, lname, bio, img FROM users WHERE unique_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $loggedInUserId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$userName = $user ? $user['fname'] . " " . $user['lname'] : "";
$userBio = $user && !empty($user['bio']) ? $user['bio'] : "";
$Image = $user && !empty($user['img']) ? "php/images/" . $user['img'] : "/placeholder.svg?height=200&width=800";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);

    // Handle file upload for background image
    if (!empty($_FILES['background']['name'])) {
        $targetDir = "php/images/";
        $fileName = basename($_FILES["background"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        // Allow only image file types
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($fileType), $allowedTypes)) {
            move_uploaded_file($_FILES["background"]["tmp_name"], $targetFilePath);
            $Image = $fileName;
        }
    } else {
        $Image = $user['bg_img']; // Keep existing image
    }

    // Extract first and last name
    $nameParts = explode(" ", $name);
    $fname = $nameParts[0] ?? "";
    $lname = isset($nameParts[1]) ? $nameParts[1] : "";

    // Update user details in the database
    $updateSql = "UPDATE users SET fname=?, lname=?, bio=?, img=? WHERE unique_id=?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("sssss", $fname, $lname, $bio, $Image, $loggedInUserId);

    if ($stmt->execute()) {
        header("Location: profile.php"); // Redirect to profile page after saving
        exit();
    } else {
        echo "Error updating profile.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - SocialConnect</title>
    <style>
        :root {
            --primary-color: #1877f2;
            --secondary-color: #42b72a;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #1c1e21;
            --text-muted: #65676b;
            --border-color: #dddfe2;
            --hover-bg: #f2f2f2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.5;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: var(--primary-color);
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        input[type="text"],
        textarea {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .background-photo {
            width: 100%;
            height: 200px;
            background-color: var(--background-color);
            background-size: cover;
            background-position: center;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .file-input {
            display: none;
        }

        .file-label {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .file-label:hover {
            background-color: #166fe5;
        }

        .save-btn {
            align-self: flex-end;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .save-btn:hover {
            background-color: #166fe5;
        }
    </style>
    <style>
        .profile-upload-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 20px;
        }

        .profile-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 3px solid #fff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-preview:hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .profile-preview:hover::after {
            content: 'Change Photo';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 14px;
            font-weight: 500;
        }

        .file-input {
            display: none;
        }

        .profile-label {
            color: #4b5563;
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .upload-hint {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Edit Profile</h1>
        <form class="edit-form" action="edit_profile.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userName); ?>" required>
            </div>
            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio"><?php echo htmlspecialchars($userBio); ?></textarea>
            </div>
            <div class="profile-upload-container">
                <label class="profile-label">Profile Photo</label>
                <div class="profile-preview" id="background-preview"
                    style="background-image: url('<?php echo htmlspecialchars($Image); ?>');"
                    onclick="document.getElementById('background').click()">
                </div>
                <input type="file" id="background" name="background" class="file-input" accept="image/*">
                <span class="upload-hint">Click the circle to upload a new photo</span>
            </div>

            <button type="submit" class="save-btn">Save Changes</button>
        </form>
    </div>

    <script>
        document.getElementById('background').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('background-preview').style.backgroundImage = `url(${e.target.result})`;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
    <script>
        document.getElementById('background').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('background-preview').style.backgroundImage = `url(${e.target.result})`;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>

</html>