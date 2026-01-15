<?php
include 'includes/connection.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    echo "<script>alert('Invalid verification link.'); window.location.href='login.php';</script>";
    exit();
}

$sql = "SELECT id, first_name, last_name, verification_expires, is_verified FROM customers WHERE verification_token = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('Prepare failed: ' . $conn->error);
    echo "<script>alert('An error occurred.'); window.location.href='login.php';</script>";
    exit();
}
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if ($row['is_verified']) {
        echo "<script>alert('Your account is already verified.'); window.location.href='login.php';</script>";
        exit();
    }
    $expires = $row['verification_expires'];
    if ($expires && strtotime($expires) < time()) {
        echo "<script>alert('Verification link has expired. Please request a new verification email.'); window.location.href='login.php';</script>";
        exit();
    }

    $update = $conn->prepare("UPDATE customers SET is_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?");
    if ($update) {
        $update->bind_param('i', $row['id']);
        if ($update->execute()) {
            echo "<script>alert('Your email has been verified. You may now login.'); window.location.href='login.php';</script>";
            exit();
        }
    }
    error_log('Failed to update verification for token: ' . $token);
    echo "<script>alert('An error occurred during verification.'); window.location.href='login.php';</script>";
    exit();
} else {
    echo "<script>alert('Invalid or already-used verification link.'); window.location.href='login.php';</script>";
    exit();
}
?>