<?php
// Simple form to request password reset
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <form action="forgot_password_process.php" method="post" class="login-form">
        <h2>Forgot Password</h2>
        <div class="form-group">
            <label for="email">Enter your registered email</label>
            <input type="email" name="email" id="email" required>
        </div>
        <button type="submit" class="btn">Send Reset Link</button>
        <p><a href="login.php">Back to login</a></p>
    </form>
</div>
</body>
</html>