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

// 3. Deletion Logic (Transactions First -> Item Second)
// We must delete transactions first due to Foreign Key constraints
$sql_transactions = "DELETE FROM transactions WHERE item_id = ?";
$stmt_trans = $conn->prepare($sql_transactions);
$stmt_trans->bind_param("i", $id);

if ($stmt_trans->execute()) {
    $stmt_trans->close();
    
    // Now delete the item
    $sql_item = "DELETE FROM items WHERE id = ?";
    $stmt_item = $conn->prepare($sql_item);
    $stmt_item->bind_param("i", $id);
    
    if ($stmt_item->execute()) {
        $stmt_item->close();
        header("Location: inventory.php?msg=deleted");
    } else {
        $stmt_item->close();
        header("Location: inventory.php?error=delete_item_failed");
    }
} else {
    $stmt_trans->close();
    header("Location: inventory.php?error=delete_trans_failed");
}

$conn->close();
?>
