<?php
session_start();
include '../includes/connection.php';

// 1. Security Check: Admin Only
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    // If not admin/logged in, redirect or show error
    header("Location: inventory.php?error=unauthorized");
    exit();
}

// 2. Input Validation
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventory.php?error=missing_id");
    exit();
}

$id = intval($_GET['id']);

// 3. Archive instead of hard delete
$sql_item = "UPDATE items SET status = 'archived', archived_at = NOW(), archived_by = ? WHERE id = ?";
$stmt_item = $conn->prepare($sql_item);
$archivedBy = isset($_SESSION['id']) ? intval($_SESSION['id']) : null;
$stmt_item->bind_param("ii", $archivedBy, $id);

if ($stmt_item->execute()) {
    $stmt_item->close();
    header("Location: inventory.php?msg=archived");
} else {
    $stmt_item->close();
    header("Location: inventory.php?error=archive_failed");
}

$conn->close();
?>
