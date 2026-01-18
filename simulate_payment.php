<?php
session_start();
include 'includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: login.php');
    exit();
}

$item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;
$customer_id = (int) $_SESSION['id'];
$action = isset($_GET['type']) && $_GET['type'] === 'renew' ? 'renew' : 'redeem';

if ($item_id <= 0) {
    http_response_code(400);
    echo 'Missing or invalid item id';
    exit();
}

// Verify the item belongs to the logged-in customer
$stmt = $conn->prepare('SELECT status, due_date FROM items WHERE id = ? AND customer_id = ? LIMIT 1');
$stmt->bind_param('ii', $item_id, $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo 'Item not found for this customer';
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// If already redeemed, just send them to the receipt
if ($item['status'] === 'redeemed') {
    header('Location: customer_receipt.php?item_id=' . $item_id . '&tx=redemption&paid=1');
    exit();
}

$conn->begin_transaction();
try {
    if ($action === 'renew') {
        $current_due = $item['due_date'] ?: date('Y-m-d');
        $new_due = date('Y-m-d', strtotime($current_due . ' +30 days'));

        $update = $conn->prepare("UPDATE items SET due_date = ? WHERE id = ? AND customer_id = ?");
        $update->bind_param('sii', $new_due, $item_id, $customer_id);
        $update->execute();
        $update->close();

        $txn = $conn->prepare("INSERT INTO transactions (item_id, transaction_type) VALUES (?, 'renewal')");
        $txn->bind_param('i', $item_id);
        $txn->execute();
        $txn->close();

        $conn->commit();
        header('Location: customer_receipt.php?item_id=' . $item_id . '&tx=renewal&paid=1');
        exit();
    }

    // Mark the item as redeemed to simulate payment completion
    $update = $conn->prepare("UPDATE items SET status = 'redeemed' WHERE id = ? AND customer_id = ?");
    $update->bind_param('ii', $item_id, $customer_id);
    $update->execute();
    $update->close();

    // Log the transaction
    $txn = $conn->prepare("INSERT INTO transactions (item_id, transaction_type) VALUES (?, 'redemption')");
    $txn->bind_param('i', $item_id);
    $txn->execute();
    $txn->close();

    $conn->commit();

    header('Location: customer_receipt.php?item_id=' . $item_id . '&tx=redemption&paid=1');
    exit();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo 'Payment simulation failed.';
    exit();
}
