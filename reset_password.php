<?php
include 'includes/connection.php';
$token = $_GET['token'] ?? '';
if (!$token) { echo "<script>alert('Invalid reset link.'); window.location.href='login.php';</script>"; exit; }
// validate token
$chk = $conn->prepare('SELECT id FROM customers WHERE password_reset_token = ? AND password_reset_expires > NOW() LIMIT 1');
if (!$chk) { echo "<script>alert('Invalid link.'); window.location.href='login.php';</script>"; exit; }
$chk->bind_param('s', $token);
$chk->execute();
$res = $chk->get_result();
if (!$res->fetch_assoc()) { echo "<script>alert('Reset link invalid or expired.'); window.location.href='forgot_password.php';</script>"; exit; }
$chk->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="container">
    <form action="reset_password_process.php" method="post" class="login-form">
        <h2>Reset Password</h2>
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <button type="submit" class="btn">Update Password</button>
    </form>
</div>
</body>
</html>