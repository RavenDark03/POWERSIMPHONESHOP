<?php
include 'includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit; }
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$token || !$password) { echo "<script>alert('Invalid request.'); window.location.href='forgot_password.php';</script>"; exit; }
if ($password !== $confirm) { echo "<script>alert('Passwords do not match.'); window.history.back();</script>"; exit; }
if (strlen($password) < 8) { echo "<script>alert('Password too short.'); window.history.back();</script>"; exit; }

$chk = $conn->prepare('SELECT id FROM customers WHERE password_reset_token = ? AND password_reset_expires > NOW() LIMIT 1');
if (!$chk) { echo "<script>alert('Invalid token.'); window.location.href='forgot_password.php';</script>"; exit; }
$chk->bind_param('s', $token);
$chk->execute();
$res = $chk->get_result();
if (!$row = $res->fetch_assoc()) { echo "<script>alert('Reset link invalid or expired.'); window.location.href='forgot_password.php';</script>"; exit; }
$userId = $row['id'];
$chk->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$up = $conn->prepare('UPDATE customers SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?');
if (!$up) { error_log('Prepare failed: '.$conn->error); echo "<script>alert('An error occurred.'); window.location.href='forgot_password.php';</script>"; exit; }
$up->bind_param('si', $hash, $userId);
if ($up->execute()) {
    echo "<script>alert('Password updated. You may now login.'); window.location.href='login.php';</script>";
} else {
    error_log('Execute failed: '.$up->error);
    echo "<script>alert('An error occurred.'); window.location.href='forgot_password.php';</script>";
}
$up->close();
?>