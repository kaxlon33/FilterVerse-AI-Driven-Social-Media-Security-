<?php
session_start();
include_once "php/config.php";
if (!isset($_SESSION['unique_id'])) {
  header("location: login.php");
}
?>
<?php include_once "header.php"; ?>

<body style="font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0;">
  <div class="wrapper" style="max-width: 800px; margin: 20px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <section class="chat-area" style="display: flex; flex-direction: column; height: 75vh;">
      <header style="display: flex; align-items: center; padding: 20px; border-bottom: 1px solid #e4e6eb;">
        <?php
        $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
        $sql = mysqli_query($conn, "SELECT * FROM users WHERE unique_id = {$user_id}");
        if (mysqli_num_rows($sql) > 0) {
          $row = mysqli_fetch_assoc($sql);
        } else {
          header("location: userss.php");
        }

        // Check if the user is blocked
        $current_user_id = $_SESSION['unique_id'];
        $block_sql = mysqli_query($conn, "SELECT * FROM blocked_users WHERE blocker_id = {$current_user_id} AND target_user_id = {$user_id}");
        $is_blocked = mysqli_num_rows($block_sql) > 0;
        ?>
        <a href="userss.php" class="back-icon" style="color: #1877f2; text-decoration: none; margin-right: 15px;"><i class="fas fa-arrow-left"></i></a>
        <img src="php/images/<?php echo $row['img']; ?>" alt="" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
        <div class="details" style="flex-grow: 1;">
          <span style="font-size: 18px; font-weight: bold; color: #1c1e21;"><?php echo $row['fname'] . " " . $row['lname'] ?></span>
          <p style="margin: 0; font-size: 14px; color: #65676b;"><?php echo $row['status']; ?></p>
        </div>
        <?php if ($is_blocked): ?>
          <button id="unblockBtn" style="background-color: #28a745; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;" onclick="toggleBlock(<?php echo $target_user_id; ?>)">Unblock User</button>
        <?php else: ?>
          <button id="blockBtn" style="background-color: #dc3545; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;" onclick="toggleBlock(<?php echo $target_user_id; ?>)">Block User</button>
        <?php endif; ?>

      </header>
      <div class="chat-box" style="flex-grow: 1; overflow-y: auto; padding: 20px; background-color: #f0f2f5;">
        <!-- Chat messages will be loaded here -->
      </div>
      <?php if ($is_blocked): ?>
        <div class="blocked-message" style="padding: 15px; background-color: #f0f2f5; text-align: center; color: #65676b; border-top: 1px solid #e4e6eb;">
          You can't send messages to this user.
        </div>
      <?php else: ?>
        <form action="insert-chart.php" method="POST" class="typing-area" enctype="multipart/form-data" style="display: flex; padding: 10px; background-color: #fff; border-top: 1px solid #e4e6eb; align-items: center;">
          <input type="text" class="incoming_id" name="incoming_id" value="<?php echo $user_id; ?>" hidden>
          <input type="text" id="messageInput" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off" style="flex-grow: 1; padding: 10px; border: 1px solid #ccd0d5; border-radius: 20px; margin-right: 10px; font-size: 14px;">
          <label for="file-input" class="file-label" style="cursor: pointer; padding: 10px; color: #1877f2;">
            <i class="fas fa-paperclip"></i>
          </label>
          <input type="file" id="file-input" name="image" class="image-input" style="display:none;">
          <button type="submit" id="send-button" style="background-color: #1877f2; color: #fff; border: none; padding: 10px 15px; border-radius: 50%; cursor: pointer;">
            <i class="fab fa-telegram-plane"></i>
          </button>
        </form>
      <?php endif; ?>
    </section>
  </div>

  <script src="javascript/chat.js"></script>
  <script src="javascript/block.js"></script>


  <div id="banMessage" style="display:none; text-align:center; font-size:20px; color:red;">
    <p>You are banned from using the chat.</p>
  </div>





</body>

</html>