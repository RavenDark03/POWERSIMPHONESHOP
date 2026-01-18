<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "You are not authorized to perform this action.";
    exit();
}

$id = (int) $_GET['id'];

// Fetch customer to ensure it exists and grab basic info for UI messages
$customerSql = "SELECT id, customer_code, first_name, last_name, is_deleted FROM customers WHERE id = ? LIMIT 1";
$custStmt = $conn->prepare($customerSql);
$custStmt->bind_param("i", $id);
$custStmt->execute();
$custData = $custStmt->get_result()->fetch_assoc();
$custStmt->close();

if (!$custData) {
    header("Location: customers.php");
    exit();
}

// Handle restore
if (isset($_GET['restore'])) {
    $restoreSql = "UPDATE customers SET is_deleted = 0, deleted_at = NULL WHERE id = ?";
    $restoreStmt = $conn->prepare($restoreSql);
    $restoreStmt->bind_param("i", $id);
    $restoreStmt->execute();
    $restoreStmt->close();
    header("Location: archived.php");
    exit();
}

// Prevent deleting customers that still have items to preserve referential integrity
$checkSql = "SELECT COUNT(*) AS cnt FROM items WHERE customer_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$row = $checkResult->fetch_assoc();
$hasItems = ($row && (int)$row['cnt'] > 0);
$checkStmt->close();

if ($hasItems) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Cannot Delete Customer</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
        <style>
            body { background: #f8f9fa; }
            .error-card { max-width: 520px; margin: 80px auto; background: #fff; padding: 32px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border-left: 5px solid #c0392b; }
            .error-card h2 { margin: 0 0 10px 0; color: #c0392b; }
            .error-card p { margin: 0 0 18px 0; color: #555; line-height: 1.5; }
            .error-actions { display: flex; gap: 10px; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h2><i class="fas fa-exclamation-circle"></i> Cannot delete customer</h2>
            <p>This customer still has associated items (<?php echo (int)$row['cnt']; ?> record<?php echo ((int)$row['cnt'] === 1 ? '' : 's'); ?>). Delete or reassign those items first, then try again.</p>
            <div class="error-actions">
                <a href="customers.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Customers</a>
                <a href="inventory.php?search=<?php echo urlencode($id); ?>" class="btn" style="background:#0a3d0a; color:#fff;"><i class="fas fa-box"></i> View Items</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Soft delete the customer (toggle flag)
$deleteSql = "UPDATE customers SET is_deleted = 1, deleted_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($deleteSql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: customers.php");
    exit();
} else {
    echo "Error deleting record: " . $conn->error;
}

$stmt->close();
$conn->close();
?>