<?php
session_start();
if (isset($_SESSION['unique_id'])) {
    include_once "config.php"; // Database connection
    $outgoing_id = $_SESSION['unique_id'];
    $incoming_id = mysqli_real_escape_string($conn, $_POST['incoming_id']);
    $output = "";

    // Fetch messages
    $sql = "SELECT msg_id, incoming_msg_id, outgoing_msg_id, msg, image 
            FROM messages 
            WHERE (outgoing_msg_id = {$outgoing_id} AND incoming_msg_id = {$incoming_id}) 
               OR (outgoing_msg_id = {$incoming_id} AND incoming_msg_id = {$outgoing_id}) 
            ORDER BY msg_id";

    $query = mysqli_query($conn, $sql);

    if (mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            if ($row['outgoing_msg_id'] == $outgoing_id) { // Outgoing messages
                $output .= '<div class="chat outgoing">
                                <div class="details">
                                    <p>' . htmlspecialchars($row['msg']) . '
                                    <button class="delete-btn" data-msg-id="' . $row['msg_id'] . '" 
                                            style="background: none; border: none; cursor: pointer; color: red;"
                                            onclick="deleteMessage(' . $row['msg_id'] . ')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    </p>';
                // Display image only if it exists
                if (!empty($row['image'])) {
                    $output .= '<img src="' . $row['image'] . '" alt="Image" style="max-width: 300%; max-height: 300px; height: auto; width: auto;">';
                }
                $output .= '</div></div>';
            } else { // Incoming messages
                $output .= '<div class="chat incoming">
                                <div class="details">
                                    <p>' . htmlspecialchars($row['msg']) . '
                                    <button class="delete-btn" data-msg-id="' . $row['msg_id'] . '" 
                                            style="background: none; border: none; cursor: pointer; color: red;"
                                            onclick="deleteMessage(' . $row['msg_id'] . ')">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    </p>';
                // Display image only if it exists
                if (!empty($row['image'])) {
                    $output .= '<img src="' . $row['image'] . '" alt="Image" style="max-width: 300%; max-height: 300px; height: auto; width: auto;">';
                }
                $output .= '</div></div>';
            }
        }
    } else {
        $output .= '<div class="text">No messages are available. Once you send a message, they will appear here.</div>';
    }
    echo $output;
} else {
    header("location: ../login.php");
}
?>
