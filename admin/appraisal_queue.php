<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Check if user has permission to appraise (admin, appraiser, or manager)
$userRole = $_SESSION['role'] ?? 'staff';
$canAppraise = in_array($userRole, ['admin', 'appraiser', 'manager']);
$isManager = in_array($userRole, ['admin', 'manager']);

// Handle appraisal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_appraisal') {
    if (!$canAppraise) {
        header("Location: appraisal_queue.php?error=unauthorized");
        exit();
    }
    
    $item_id = intval($_POST['item_id']);
    $loan_amount = floatval($_POST['loan_amount']);
    $interest_rate = floatval($_POST['interest_rate']);
    $due_date = $_POST['due_date'];
    $appraised_value = floatval($_POST['appraised_value'] ?? $loan_amount);
    
    // Update the item with appraisal values - send to manager for approval
    $sql = "UPDATE items SET 
            loan_amount = ?, 
            interest_rate = ?, 
            due_date = ?, 
            appraised_value = ?,
            status = 'pending_approval' 
            WHERE id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ddsdi", $loan_amount, $interest_rate, $due_date, $appraised_value, $item_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        header("Location: appraisal_queue.php?success=sent_for_approval&item_id=" . $item_id);
        exit();
    } else {
        header("Location: appraisal_queue.php?error=update_failed");
        exit();
    }
}

// Handle manager approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manager_approve') {
    if (!$isManager) {
        header("Location: appraisal_queue.php?error=unauthorized");
        exit();
    }
    
    $item_id = intval($_POST['item_id']);
    
    // Approve and activate the pawn
    $sql = "UPDATE items SET status = 'pawned' WHERE id = ? AND status = 'pending_approval'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Create transaction record
        $sql = "INSERT INTO transactions (item_id, transaction_type) VALUES (?, 'pawn')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        
        header("Location: appraisal_queue.php?success=approved&item_id=" . $item_id);
        exit();
    } else {
        header("Location: appraisal_queue.php?error=update_failed");
        exit();
    }
}

// Handle manager rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'manager_reject') {
    if (!$isManager) {
        header("Location: appraisal_queue.php?error=unauthorized");
        exit();
    }
    
    $item_id = intval($_POST['item_id']);
    $rejection_reason = trim($_POST['rejection_reason'] ?? 'Item rejected by manager');
    
    // Reject and send back to pending or mark as rejected
    $sql = "UPDATE items SET status = 'rejected', item_description = CONCAT(IFNULL(item_description, ''), '\n[REJECTED: " . $conn->real_escape_string($rejection_reason) . "]') WHERE id = ? AND status = 'pending_approval'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        header("Location: appraisal_queue.php?success=rejected&item_id=" . $item_id);
        exit();
    } else {
        header("Location: appraisal_queue.php?error=update_failed");
        exit();
    }
}

// Fetch pending items (awaiting appraisal from clerk)
$sql = "SELECT i.*, c.first_name, c.last_name, c.customer_code 
        FROM items i 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.status = 'pending' 
        ORDER BY i.created_at DESC";
$pendingResult = $conn->query($sql);

// Fetch items pending manager approval (appraised, awaiting manager)
$sql = "SELECT i.*, c.first_name, c.last_name, c.customer_code 
        FROM items i 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.status = 'pending_approval' 
        ORDER BY i.created_at DESC";
$approvalResult = $conn->query($sql);

$totalPending = $pendingResult->num_rows + $approvalResult->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appraisal Queue</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .queue-header h2 {
            margin: 0;
            color: #116530;
        }
        .badge-pending {
            background: #ff9800;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .item-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 15px;
            overflow: hidden;
            border: 1px solid #e6e6e6;
        }
        .item-card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e6e6e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .item-card-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }
        .item-card-body {
            padding: 20px;
        }
        .item-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 0.95rem;
            color: #333;
            font-weight: 500;
        }
        .appraisal-form {
            background: #f0f7f0;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px solid #c8e6c9;
        }
        .appraisal-form h4 {
            margin: 0 0 15px 0;
            color: #116530;
            font-size: 1rem;
        }
        .form-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .form-col {
            flex: 1;
            min-width: 150px;
        }
        .form-col label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.8rem;
            color: #555;
            text-transform: uppercase;
        }
        .form-col input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        .form-col input:focus {
            border-color: #116530;
            outline: none;
            box-shadow: 0 0 0 2px rgba(17, 101, 48, 0.1);
        }
        .btn-approve {
            background: #116530;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-approve:hover {
            background: #0d4a24;
        }
        .btn-view {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-view:hover {
            background: #e8e8e8;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #666;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .customer-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .customer-info i {
            color: #888;
        }
        .timestamp {
            font-size: 0.85rem;
            color: #888;
        }
        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: #fff;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e6e6e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            color: #116530;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #888;
        }
        .modal-body {
            padding: 20px;
        }
        @media (max-width: 768px) {
            .item-details {
                grid-template-columns: 1fr 1fr;
            }
            .form-row {
                flex-direction: column;
            }
            .form-col {
                min-width: 100%;
            }
        }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <div class="main-content-wrapper">
        <div class="container main-content">
            <div class="queue-header">
                <div>
                    <h2><i class="fas fa-clipboard-check"></i> Appraisal Queue</h2>
                    <p style="margin: 5px 0 0; color: #666;">Items awaiting appraisal and manager approval</p>
                </div>
                <span class="badge-pending"><?php echo $totalPending; ?> Pending</span>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <?php 
                switch($_GET['success']) {
                    case 'approved':
                        echo 'Item #' . htmlspecialchars($_GET['item_id'] ?? '') . ' has been approved and the pawn transaction is now active.';
                        break;
                    case 'sent_for_approval':
                        echo 'Item #' . htmlspecialchars($_GET['item_id'] ?? '') . ' has been appraised and sent to manager for approval.';
                        break;
                    case 'rejected':
                        echo 'Item #' . htmlspecialchars($_GET['item_id'] ?? '') . ' has been rejected.';
                        break;
                    default:
                        echo 'Operation completed successfully.';
                }
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <?php 
                switch($_GET['msg']) {
                    case 'pending_submitted':
                        echo 'Item has been submitted for appraisal. An appraiser will review and set the loan terms.';
                        break;
                    case 'appraisal_submitted':
                        echo 'Appraisal submitted successfully. Awaiting manager approval.';
                        break;
                    default:
                        echo 'Operation completed.';
                }
                ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> 
                <?php 
                switch($_GET['error']) {
                    case 'unauthorized':
                        echo 'You do not have permission to appraise items.';
                        break;
                    case 'update_failed':
                        echo 'Failed to update the item. Please try again.';
                        break;
                    default:
                        echo 'An error occurred.';
                }
                ?>
            </div>
            <?php endif; ?>

            <?php if ($isManager && $approvalResult->num_rows > 0): ?>
            <!-- MANAGER APPROVAL SECTION -->
            <div style="margin-bottom: 30px;">
                <h3 style="color: #1565c0; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-user-tie"></i> Awaiting Manager Approval 
                    <span style="background: #1565c0; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;"><?php echo $approvalResult->num_rows; ?></span>
                </h3>
                
                <?php while ($item = $approvalResult->fetch_assoc()): ?>
                <div class="item-card" style="border-left: 4px solid #1565c0;">
                    <div class="item-card-header">
                        <div>
                            <h3><?php echo htmlspecialchars($item['category']); ?> - <?php echo htmlspecialchars($item['item_type'] ?? 'N/A'); ?></h3>
                            <div class="customer-info">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></span>
                                <span style="color: #aaa;">|</span>
                                <span style="color: #888;"><?php echo htmlspecialchars($item['customer_code']); ?></span>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span style="background: #1565c0; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">Awaiting Approval</span>
                            <div class="timestamp" style="margin-top: 5px;">
                                <i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <div class="item-card-body">
                        <div class="item-details">
                            <?php if ($item['category'] === 'Gadget'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Brand</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['brand'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Model</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['model'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Serial Number</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['serial_number'] ?? 'N/A'); ?></span>
                            </div>
                            <?php elseif ($item['category'] === 'Silver Jewelry'): ?>
                            <div class="detail-item">
                                <span class="detail-label">Weight</span>
                                <span class="detail-value"><?php echo $item['weight_grams'] ? number_format($item['weight_grams'], 2) . 'g' : 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Purity</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['purity'] ?? 'N/A'); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <span class="detail-label">Condition</span>
                                <span class="detail-value"><?php echo htmlspecialchars($item['item_condition'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        
                        <!-- Appraisal Details -->
                        <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #90caf9;">
                            <h4 style="margin: 0 0 10px 0; color: #1565c0; font-size: 0.95rem;"><i class="fas fa-calculator"></i> Appraisal Details</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                                <div>
                                    <span class="detail-label">Appraised Value</span>
                                    <span class="detail-value" style="font-size: 1.1rem; color: #1565c0;">₱<?php echo number_format($item['appraised_value'] ?? $item['loan_amount'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="detail-label">Loan Amount</span>
                                    <span class="detail-value" style="font-size: 1.1rem; font-weight: 700; color: #2e7d32;">₱<?php echo number_format($item['loan_amount'], 2); ?></span>
                                </div>
                                <div>
                                    <span class="detail-label">Interest Rate</span>
                                    <span class="detail-value"><?php echo number_format($item['interest_rate'], 2); ?>%</span>
                                </div>
                                <div>
                                    <span class="detail-label">Due Date</span>
                                    <span class="detail-value"><?php echo date('M d, Y', strtotime($item['due_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Manager Actions -->
                        <div style="display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">
                            <a href="view_pawn.php?id=<?php echo $item['id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <form method="POST" action="appraisal_queue.php" style="display: inline;">
                                <input type="hidden" name="action" value="manager_reject">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="rejection_reason" value="Item does not meet criteria">
                                <button type="submit" style="background: #dc3545; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;" onclick="return confirm('Are you sure you want to REJECT this appraisal?');">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                            <form method="POST" action="appraisal_queue.php" style="display: inline;">
                                <input type="hidden" name="action" value="manager_approve">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn-approve" onclick="return confirm('Approve this appraisal and activate the pawn transaction?');">
                                    <i class="fas fa-check"></i> Approve & Activate
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>

            <!-- PENDING APPRAISAL SECTION (for appraisers) -->
            <?php if ($pendingResult->num_rows === 0 && $approvalResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Pending Items</h3>
                <p>All items have been processed. Check back later for new submissions.</p>
            </div>
            <?php elseif ($pendingResult->num_rows > 0): ?>
            
            <h3 style="color: #ff9800; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-hourglass-half"></i> Awaiting Appraisal 
                <span style="background: #ff9800; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem;"><?php echo $pendingResult->num_rows; ?></span>
            </h3>
            
            <?php while ($item = $pendingResult->fetch_assoc()): ?>
            <div class="item-card">
                <div class="item-card-header">
                    <div>
                        <h3><?php echo htmlspecialchars($item['category']); ?> - <?php echo htmlspecialchars($item['item_type'] ?? 'N/A'); ?></h3>
                        <div class="customer-info">
                            <i class="fas fa-user"></i>
                            <span><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></span>
                            <span style="color: #aaa;">|</span>
                            <span style="color: #888;"><?php echo htmlspecialchars($item['customer_code']); ?></span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge-pending">Pending</span>
                        <div class="timestamp" style="margin-top: 5px;">
                            <i class="fas fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <div class="item-card-body">
                    <div class="item-details">
                        <?php if ($item['category'] === 'Gadget'): ?>
                        <div class="detail-item">
                            <span class="detail-label">Brand</span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['brand'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Model</span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['model'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Serial Number</span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['serial_number'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Accessories</span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['accessories'] ?? 'None'); ?></span>
                        </div>
                        <?php elseif ($item['category'] === 'Silver Jewelry'): ?>
                        <div class="detail-item">
                            <span class="detail-label">Weight</span>
                            <span class="detail-value"><?php echo $item['weight_grams'] ? number_format($item['weight_grams'], 2) . 'g' : 'N/A'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Purity</span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['purity'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Gemstones</span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['gemstones'] ?? 'None'); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <span class="detail-label">Condition</span>
                            <span class="detail-value"><?php echo htmlspecialchars($item['item_condition'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($item['item_description'])): ?>
                    <div class="detail-item" style="margin-bottom: 15px;">
                        <span class="detail-label">Description / Notes</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($item['item_description'])); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($canAppraise): ?>
                    <div class="appraisal-form">
                        <h4><i class="fas fa-calculator"></i> Set Appraisal Values</h4>
                        <form method="POST" action="appraisal_queue.php">
                            <input type="hidden" name="action" value="approve_appraisal">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <label for="appraised_value_<?php echo $item['id']; ?>">Appraised Value (₱)</label>
                                    <input type="number" name="appraised_value" id="appraised_value_<?php echo $item['id']; ?>" 
                                           step="0.01" min="0" placeholder="Market value estimate">
                                </div>
                                <div class="form-col">
                                    <label for="loan_amount_<?php echo $item['id']; ?>">Loan Amount (₱) *</label>
                                    <input type="number" name="loan_amount" id="loan_amount_<?php echo $item['id']; ?>" 
                                           step="0.01" min="0" required placeholder="Amount to lend">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-col">
                                    <label for="interest_rate_<?php echo $item['id']; ?>">Interest Rate (%) *</label>
                                    <input type="number" name="interest_rate" id="interest_rate_<?php echo $item['id']; ?>" 
                                           step="0.01" min="0" value="5" required>
                                </div>
                                <div class="form-col">
                                    <label for="due_date_<?php echo $item['id']; ?>">Due Date *</label>
                                    <input type="date" name="due_date" id="due_date_<?php echo $item['id']; ?>" 
                                           value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" required>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 10px;">
                                <a href="view_pawn.php?id=<?php echo $item['id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <button type="submit" class="btn-approve" style="background: #ff9800;" onclick="return confirm('Submit this appraisal for manager approval?');">
                                    <i class="fas fa-paper-plane"></i> Submit for Manager Approval
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center; color: #856404;">
                        <i class="fas fa-lock"></i> You do not have permission to appraise items. Please contact an appraiser or admin.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
