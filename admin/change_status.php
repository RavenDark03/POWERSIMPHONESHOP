<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check REQUEST (GET or POST)
if ((!isset($_GET['id']) && !isset($_POST['id'])) || (!isset($_GET['status']) && !isset($_POST['status']))) {
    header("Location: inventory.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "You are not authorized to perform this action.";
    exit();
}

$id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
$status = isset($_POST['status']) ? $_POST['status'] : $_GET['status'];

if ($status === 'sold') {
    $sale_price = isset($_POST['sale_price']) ? floatval($_POST['sale_price']) : NULL;
    
    // Only update if price is valid
    if ($sale_price !== NULL && $sale_price >= 0) {
        $sql = "UPDATE items SET status = ?, sale_price = ?, date_sold = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdi", $status, $sale_price, $id);
        
        // Also record transaction for sale? Maybe later. For now just update item.
        // Actually, we should probably add a 'sale' transaction record if we want deeper tracking, 
        // but user only asked for items table update.
    } else {
        // Fallback if price missing but marked sold (shouldn't happen with required input)
        $sql = "UPDATE items SET status = ?, date_sold = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
    }
} else {
    $sql = "UPDATE items SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
}

if ($stmt->execute()) {
    header("Location: inventory.php");
    exit();
} else {
    echo "Error updating record: " . $conn->error;
}

$stmt->close();
$conn->close();
?>