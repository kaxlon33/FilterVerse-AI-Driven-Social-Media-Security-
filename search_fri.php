<?php
session_start(); // Start session
include('php/config.php'); // Database connection

// Check if session variable 'unique_id' is set
if (!isset($_SESSION['unique_id'])) {
    die("Error: Target user ID is missing. Please log in.");
}

$logged_in_user_id = $_SESSION['unique_id']; // Logged-in user's unique ID

// Check if search term is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_term'])) {
    // Sanitize input (trim spaces and prevent SQL injection)
    $search_term = trim($conn->real_escape_string($_POST['search_term']));

    if (empty($search_term)) {
        echo "<p>Please enter a name to search.</p>";
        exit;
    }

    // SQL Query to find users by first name or last name (case-insensitive search)
    $sql = "SELECT user_id, unique_id, fname, lname, email, img, status 
            FROM users 
            WHERE fname LIKE '%$search_term%' OR lname LIKE '%$search_term%'";

    $result = $conn->query($sql);

    // Debugging: Check if the query fails
    if (!$result) {
        die("Query Error: " . $conn->error);
    }

    // Display results
    if ($result->num_rows > 0) {
        echo "<h2>Search Results:</h2>";
        echo "<ul>";

        while ($row = $result->fetch_assoc()) {
            $full_name = htmlspecialchars($row['fname']) . " " . htmlspecialchars($row['lname']);
            $email = htmlspecialchars($row['email']);
            $profile_img = !empty($row['img']) ? "uploads/" . htmlspecialchars($row['img']) : "default.png"; // Default image if none exists
            $status = htmlspecialchars($row['status']);

            echo "<li>
                    <img src='$profile_img' alt='Profile Picture' width='50' height='50'>
                    <strong>$full_name</strong> ($email) - Status: $status
                    - <a href='profile.php?user_id=" . htmlspecialchars($row['user_id']) . "'>View Profile</a>
                  </li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No users found.</p>";
    }
} else {
    echo "<p>Invalid request.</p>";
}

// Close database connection
$conn->close();
?>
