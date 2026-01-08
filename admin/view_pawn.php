<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pawning.php");
    exit();
}

$id = intval($_GET['id']);

// Fetch Item + Customer Details
$sql = "SELECT items.*, 
        customers.id AS cust_id, 
        customers.customer_code, 
        CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name,
        customers.contact_number,
        customers.present_barangay, customers.present_city
        FROM items 
        JOIN customers ON items.customer_id = customers.id 
        WHERE items.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Item not found.";
    exit();
}

$item = $result->fetch_assoc();

// Smart Name Logic
$displayName = $item['item_description'];
if (empty($displayName) || strlen($displayName) < 5) {
    if (!empty($item['brand'])) {
        $displayName = $item['brand'] . ' ' . $item['model'];
    } elseif (!empty($item['item_type'])) {
        $displayName = $item['item_type'];
        if (!empty($item['purity'])) {
            $displayName = $item['purity'] . ' ' . $displayName;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Pawn Item - PT-<?php echo sprintf("%06d", $item['id']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        .detail-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .section-title {
            color: #0a3d0a;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            margin-bottom: 12px;
            border-bottom: 1px dashed #f0f0f0;
            padding-bottom: 8px;
        }
        .info-label {
            width: 140px;
            color: #666;
            font-weight: 500;
        }
        .info-value {
            flex: 1;
            color: #333;
            font-weight: 500;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge.pawned { background: #e8f5e9; color: #2e7d32; }
        .status-badge.redeemed { background: #e3f2fd; color: #1976d2; }
        .status-badge.sold { background: #ffebee; color: #c62828; }
        .status-badge.for_sale { background: #fff3e0; color: #ef6c00; }
        
        .action-bar {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <a href="index.php" class="logo-container">
                <img src="../images/powersim logo.png" alt="Powersim Phoneshop" class="logo-img">
                <span class="logo-text">Powersim Phoneshop</span>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="customers.php">Customers</a></li>
                    <li><a href="pawning.php" style="color: #d4af37;">Pawning</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <a href="pawning.php" style="color: #666; text-decoration: none;"><i class="fas fa-arrow-left"></i> Back</a>
            <h2 style="margin: 0;">Pawn Details: <span style="font-weight: 400; color: #555;">PT-<?php echo sprintf("%06d", $item['id']); ?></span></h2>
            <span class="status-badge <?php echo $item['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?></span>
        </div>

        <div class="details-grid">
            <!-- Left Column: Item Details -->
            <div>
                <div class="detail-card">
                    <div class="section-title"><i class="fas fa-box"></i> Item Information</div>
                    
                    <div class="info-row">
                        <span class="info-label">Item Name</span>
                        <span class="info-value" style="font-size: 1.1rem; color: #000;"><?php echo htmlspecialchars($displayName); ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Category</span>
                        <span class="info-value"><?php echo htmlspecialchars($item['category']); ?></span>
                    </div>

                    <?php if (!empty($item['item_type'])): ?>
                    <div class="info-row">
                        <span class="info-label">Type</span>
                        <span class="info-value"><?php echo htmlspecialchars($item['item_type']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($item['serial_number'])): ?>
                    <div class="info-row">
                        <span class="info-label">Serial Number</span>
                        <span class="info-value" style="font-family: monospace; letter-spacing: 0.5px;"><?php echo htmlspecialchars($item['serial_number']); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="info-row">
                        <span class="info-label">Condition</span>
                        <span class="info-value"><?php echo htmlspecialchars($item['item_condition']); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Received Date</span>
                        <span class="info-value"><?php echo date('F d, Y h:i A', strtotime($item['created_at'])); ?></span>
                    </div>

                    <div style="margin-top: 15px;">
                        <span class="info-label" style="display: block; margin-bottom: 5px;">Description / Notes</span>
                        <div style="background: #f9f9f9; padding: 10px; border-radius: 4px; color: #555; font-size: 0.9rem;">
                            <?php echo nl2br(htmlspecialchars($item['item_description'])); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($item['accessories'])): ?>
                    <div style="margin-top: 15px;">
                        <span class="info-label" style="display: block; margin-bottom: 5px;">Accessories</span>
                        <div style="background: #f9f9f9; padding: 10px; border-radius: 4px; color: #555; font-size: 0.9rem;">
                             <?php echo htmlspecialchars($item['accessories']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Specs Box (Conditional) -->
                <?php if ($item['category'] == 'Jewelry' || !empty($item['weight_grams']) || !empty($item['gemstones'])): ?>
                <div class="detail-card">
                     <div class="section-title"><i class="far fa-gem"></i> Specifications</div>
                     <?php if (!empty($item['weight_grams'])): ?>
                     <div class="info-row">
                        <span class="info-label">Weight</span>
                        <span class="info-value"><?php echo $item['weight_grams']; ?>g</span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($item['purity'])): ?>
                     <div class="info-row">
                        <span class="info-label">Karat / Purity</span>
                        <span class="info-value"><?php echo $item['purity']; ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($item['gemstones'])): ?>
                     <div class="info-row">
                        <span class="info-label">Gemstones</span>
                        <span class="info-value"><?php echo $item['gemstones']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Loan & Customer -->
            <div>
                <!-- Customer Card -->
                <div class="detail-card">
                     <div class="section-title"><i class="fas fa-user"></i> Customer</div>
                     <div style="text-align: center; margin-bottom: 15px;">
                        <div style="font-size: 1.1rem; font-weight: 600; color: #333;"><a href="view_customer.php?id=<?php echo $item['cust_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($item['customer_name']); ?></a></div>
                        <div style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($item['customer_code']); ?></div>
                     </div>
                     <div class="info-row" style="border:0;">
                        <span class="info-label" style="width: auto; margin-right: 10px;"><i class="fas fa-phone"></i></span>
                        <span class="info-value"><?php echo htmlspecialchars($item['contact_number']); ?></span>
                    </div>
                    <div class="info-row" style="border:0;">
                        <span class="info-label" style="width: auto; margin-right: 10px;"><i class="fas fa-map-marker-alt"></i></span>
                        <span class="info-value"><?php echo htmlspecialchars($item['present_barangay'] . ', ' . $item['present_city']); ?></span>
                    </div>
                </div>

                <!-- Loan Details Card -->
                <div class="detail-card">
                     <div class="section-title"><i class="fas fa-file-contract"></i> Loan Details</div>
                     
                     <div class="info-row">
                        <span class="info-label">Principal</span>
                        <span class="info-value" style="font-size: 1.2rem; font-weight: 700; color: #0a3d0a;">₱<?php echo number_format($item['loan_amount'], 2); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Interest Rate</span>
                        <span class="info-value"><?php echo $item['interest_rate']; ?>% / mo</span>
                    </div>

                    <div class="info-row">
                        <span class="info-label">Due Date</span>
                        <span class="info-value" style="color: #d32f2f; font-weight: 600;"><?php echo date('M d, Y', strtotime($item['due_date'])); ?></span>
                    </div>

                    <?php if ($item['status'] == 'pawned'): ?>
                    <div class="action-bar">
                        <a href="renew_pawn.php?id=<?php echo $id; ?>" class="btn" style="flex: 1; text-align: center; background: #28a745;">Renew</a>
                        <a href="redeem_pawn.php?id=<?php echo $id; ?>" class="btn" style="flex: 1; text-align: center;">Redeem</a>
                    </div>
                    <?php elseif ($item['status'] == 'redeemed' || $item['status'] == 'sold'): ?>
                        <div style="margin-top: 15px; text-align: center; color: #666; font-style: italic;">
                            Transaction Closed
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-contact">
                <p><i class="fas fa-phone-alt"></i> 0910-809-9699</p>
                <p><a href="https://www.facebook.com/PowerSimPhoneshopOfficial" target="_blank"><i class="fab fa-facebook"></i> Powersim Phoneshop Gadget Trading Inc.</a></p>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2026 Powersim Phoneshop Gadget Trading Inc. Baliuag. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
