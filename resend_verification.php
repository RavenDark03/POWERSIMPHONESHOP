<?php
include 'includes/connection.php';

// Accept email via POST or GET
$email = trim(strtolower($_POST['email'] ?? $_GET['email'] ?? ''));
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Please provide a valid email.'); window.history.back();</script>"; exit;
}

$chk = $conn->prepare('SELECT id, first_name, last_name, is_verified FROM customers WHERE email = ? LIMIT 1');
if (!$chk) { echo "<script>alert('An error occurred.'); window.location.href='login.php';</script>"; exit; }
$chk->bind_param('s', $email);
$chk->execute();
$res = $chk->get_result();
if (!$row = $res->fetch_assoc()) {
    echo "<script>alert('If the email exists, a verification email has been sent.'); window.location.href='login.php';</script>"; exit;
}
if ($row['is_verified']) {
    echo "<script>alert('Account is already verified. Please login.'); window.location.href='login.php';</script>"; exit;
}

$userId = $row['id']; $first = $row['first_name']; $last = $row['last_name'];

// generate new token
try { $token = bin2hex(random_bytes(16)); } catch (Exception $e) { $token = bin2hex(openssl_random_pseudo_bytes(16)); }
$expires = date('Y-m-d H:i:s', strtotime('+2 days'));

$up = $conn->prepare('UPDATE customers SET verification_token = ?, verification_expires = ? WHERE id = ?');
$up->bind_param('ssi', $token, $expires, $userId);
$up->execute();
$up->close();

if (file_exists(__DIR__.'/includes/send_email.php')) {
    require_once __DIR__.'/includes/send_email.php';
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = $scheme.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
    $verify_url = rtrim($base, '/').'/verify.php?token='.$token;
    $subject = 'Verify your email address';
    $message = "Hello $first $last,\n\nPlease verify your email by clicking the link below:\n\n$verify_url\n\nThis link will expire in 48 hours.";
    @sendEmail($email, $subject, $message);
}

echo "<script>alert('If the email exists, a verification email has been sent.'); window.location.href='login.php';</script>";
?>