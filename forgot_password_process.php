<?php
include 'includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php'); exit;
}

$email = trim(strtolower($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Please provide a valid email.'); window.history.back();</script>"; exit;
}

// Check user exists
$chk = $conn->prepare('SELECT id, first_name, last_name FROM customers WHERE email = ? LIMIT 1');
if (!$chk) { error_log('Prepare failed: '.$conn->error); echo "<script>alert('An error occurred.'); window.history.back();</script>"; exit; }
$chk->bind_param('s', $email);
$chk->execute();
$res = $chk->get_result();
if (!$row = $res->fetch_assoc()) {
    // Do not reveal whether email exists; respond success for privacy
    echo "<script>alert('If the email exists, a reset link was sent.'); window.location.href='login.php';</script>"; exit;
}
$userId = $row['id'];
$first = $row['first_name']; $last = $row['last_name'];
$chk->close();

// generate token
try { $token = bin2hex(random_bytes(16)); } catch (Exception $e) { $token = bin2hex(openssl_random_pseudo_bytes(16)); }
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$up = $conn->prepare('UPDATE customers SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?');
if (!$up) { error_log('Prepare failed: '.$conn->error); echo "<script>alert('An error occurred.'); window.history.back();</script>"; exit; }
$up->bind_param('ssi', $token, $expires, $userId);
if (!$up->execute()) { error_log('Execute failed: '.$up->error); }
$up->close();

// send email
if (file_exists(__DIR__.'/includes/send_email.php')) {
    require_once __DIR__.'/includes/send_email.php';
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = $scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
    $link = rtrim($base, '/').'/reset_password.php?token='.$token;
    $subject = 'Password reset request';
    $message = "Hello $first $last,\n\nWe received a request to reset your password. Click the link below to reset it:\n\n$link\n\nThis link expires in 1 hour. If you did not request this, please ignore this email.";
    @sendEmail($email, $subject, $message);
}

echo "<script>alert('If the email exists, a reset link was sent.'); window.location.href='login.php';</script>";
?>