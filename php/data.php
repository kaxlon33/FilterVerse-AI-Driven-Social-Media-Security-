<?php
while($row = mysqli_fetch_assoc($query)){
    // Check if the user is a friend
    $sqlCheckFriend = "SELECT * FROM friends WHERE 
                        (user_id = {$outgoing_id} AND friend_id = {$row['unique_id']}) 
                        OR (user_id = {$row['unique_id']} AND friend_id = {$outgoing_id})";
    $queryFriend = mysqli_query($conn, $sqlCheckFriend);
    
    if(mysqli_num_rows($queryFriend) > 0) { // Only proceed if they are friends
        // Fetch the latest message
        $sql2 = "SELECT * FROM messages 
                 WHERE (incoming_msg_id = {$row['unique_id']} OR outgoing_msg_id = {$row['unique_id']}) 
                 AND (outgoing_msg_id = {$outgoing_id} OR incoming_msg_id = {$outgoing_id}) 
                 ORDER BY msg_id DESC LIMIT 1";
        $query2 = mysqli_query($conn, $sql2);
        $row2 = mysqli_fetch_assoc($query2);

        $result = (mysqli_num_rows($query2) > 0) ? $row2['msg'] : "No message available";
        $msg = (strlen($result) > 28) ? substr($result, 0, 28) . '...' : $result;
        
        if(isset($row2['outgoing_msg_id'])){
            $you = ($outgoing_id == $row2['outgoing_msg_id']) ? "You: " : "";
        } else {
            $you = "";
        }

        $offline = ($row['status'] == "Offline now") ? "offline" : "";
        $hid_me = ($outgoing_id == $row['unique_id']) ? "hide" : "";

        $output .= '
        <a href="chat.php?user_id='. $row['unique_id'] .'" style="display: flex; align-items: center; padding: 10px; text-decoration: none; color: inherit; border-bottom: 1px solid #dddfe2; transition: background-color 0.3s ease;">
            <div class="content" style="display: flex; align-items: center; flex-grow: 1;">
                <img src="php/images/'. $row['img'] .'" alt="" style="width: 40px; height: 40px; border-radius: 50%; margin-right: 10px;">
                <div class="details" style="flex-grow: 1;">
                    <span style="display: block; font-size: 16px; font-weight: bold; color: #1c1e21;">'. $row['fname']. " " . $row['lname'] .'</span>
                    <p style="margin: 0; font-size: 14px; color: #65676b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px;">
                        <span style="font-weight: '. ($you ? 'bold' : 'normal') .'; color: '. ($you ? '#1c1e21' : '#65676b') .';">'. $you . $msg .'</span>
                    </p>
                </div>
            </div>
            <div class="status-dot '. $offline .'" style="width: 10px; height: 10px; border-radius: 50%; margin-left: 10px; '. ($offline ? 'background-color: #ccc;' : 'background-color: #42b72a;') .'"></div>
        </a>';
    }
}
?>
