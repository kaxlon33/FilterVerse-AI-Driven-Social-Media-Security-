<?php include_once "header.php"; ?>

<body style="font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; height: 100vh;">
  <div class="wrapper" style="width: 100%; max-width: 400px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px;">
    <section class="form login" style="display: flex; flex-direction: column;">
      <header style="font-size: 24px; font-weight: bold; color: #1877f2; text-align: center; margin-bottom: 20px;">Social Connect</header>
      <form action="#" method="POST" enctype="multipart/form-data" autocomplete="off">
        <div class="error-text" style="background-color: #ffcccb; color: #d8000c; padding: 8px; border-radius: 5px; display: none;"></div>
        <div class="field input" style="display: flex; flex-direction: column;">
          <label style="font-size: 14px; color: #65676b; margin-bottom: 5px;">Email Address</label>
          <input type="text" name="email" placeholder="Enter your email" required style="padding: 10px; border: 1px solid #dddfe2; border-radius: 5px; font-size: 16px;">
        </div>
        <div class="field input" style="display: flex; flex-direction: column; position: relative;">
          <label style="font-size: 14px; color: #65676b; margin-bottom: 5px;">Password</label>
          <input type="password" name="password" placeholder="Enter your password" required style="padding: 10px; border: 1px solid #dddfe2; border-radius: 5px; font-size: 16px;">
          <i class="fas fa-eye" style="position:center; cursor: pointer; color: #65676b; "></i>
        </div>
        <div class="field button">
          <input type="submit" name="submit" value="Continue to Chat" style="width: 100%; padding: 12px; background-color: #1877f2; color: #fff; border: none; border-radius: 5px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s ease;">
        </div>
      </form>
      <div class="link" style="text-align: center; margin-top: 20px; font-size: 14px; color: #65676b;">
        Not yet signed up? <a href="php/signup.php" style="color: #1877f2; text-decoration: none; font-weight: bold;">Signup now</a>
      </div>
    </section>
  </div>

  <script src="javascript/pass-show-hide.js"></script>
  <script src="javascript/login.js"></script>

</body>

</html>