<?php
session_start();
include '../includes/connection.php';

// Admin only
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: inventory.php?error=unauthorized");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventory.php?error=missing_id");
    exit();
}

$id = intval($_GET['id']);

// Set status to archived (preserve history)
$sql = "UPDATE items SET status = 'archived', archived_at = NOW(), archived_by = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$archivedBy = isset($_SESSION['id']) ? intval($_SESSION['id']) : null;
$stmt->bind_param("ii", $archivedBy, $id);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: inventory.php?msg=archived");
    exit();
} else {
    $stmt->close();
    header("Location: inventory.php?error=archive_failed");
    exit();
}

?>
