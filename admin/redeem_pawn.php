<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: pawning.php");
    exit();
}

$id = $_GET['id'];
$sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id WHERE items.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

// Compute redemption breakdown
$principal = (float)$item['loan_amount'];
$interest_rate = (float)$item['interest_rate'];
$created_ts = strtotime($item['created_at'] ?? 'now');
$now_ts = time();
$months_elapsed = max(1, ceil(($now_ts - $created_ts) / (30 * 24 * 60 * 60)));
$total_interest = round($principal * ($interest_rate / 100) * $months_elapsed, 2);
$service_charge = 5.00;
$redemption_amount = $principal + $total_interest + $service_charge;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redeem Pawn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container main-content">
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="margin-bottom: 20px; text-align: center;">
                <h2 style="margin-bottom: 5px; color: var(--primary-color);">Redeem Item</h2>
                <p style="color: #666; font-size: 0.9rem;">Process redemption and release item</p>
            </div>

            <div style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <form action="pawn_process.php" method="post" target="_blank">
                    <input type="hidden" name="action" value="redeem_pawn">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    
                    <div style="text-align: center; margin-bottom: 25px;">
                        <div style="width: 60px; height: 60px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                            <i class="fas fa-check-circle" style="font-size: 30px; color: #2e7d32;"></i>
                        </div>
                        <h3 style="color: #2e7d32; margin: 0;">Ready to Redeem?</h3>
                    </div>

                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef;">
                        <p style="margin: 8px 0; display: flex; justify-content: space-between;">
                            <span style="color: #666;">Customer:</span>
                            <span style="font-weight: 500;"><?php echo $item['customer_name']; ?></span>
                        </p>
                        <p style="margin: 8px 0; display: flex; justify-content: space-between;">
                            <span style="color: #666;">Item:</span>
                            <span style="font-weight: 500;"><?php echo $item['item_description']; ?></span>
                        </p>
                        <hr style="border: 0; border-top: 1px dashed #ddd; margin: 15px 0;">
                        <div style="margin: 12px 0;">
                            <div style="display:flex; justify-content:space-between; margin:4px 0; color:#555;"><span>Principal Loan</span><span>₱<?php echo number_format($principal, 2); ?></span></div>
                            <div style="display:flex; justify-content:space-between; margin:4px 0; color:#555;"><span>Total Interest (<?php echo $months_elapsed; ?> mo @ <?php echo number_format($interest_rate, 2); ?>%)</span><span>₱<?php echo number_format($total_interest, 2); ?></span></div>
                            <div style="display:flex; justify-content:space-between; margin:4px 0; color:#555;"><span>Service Charge</span><span>₱<?php echo number_format($service_charge, 2); ?></span></div>
                            <hr style="border:0; border-top:1px dashed #ddd; margin:10px 0;">
                            <div style="display:flex; justify-content:space-between; margin:4px 0; font-size:1.1rem; font-weight:700; color:#2e7d32;"><span>Redemption Amount</span><span>₱<?php echo number_format($redemption_amount, 2); ?></span></div>
                        </div>
                    </div>

                    <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; font-size: 0.9rem; margin-bottom: 20px; text-align: center; border: 1px solid #ffeeba;">
                        <i class="fas fa-exclamation-triangle"></i> This action will mark the item as redeemed and close the transaction.
                    </div>

                    <button type="submit" class="btn" style="width: 100%; padding: 12px; background-color: #2e7d32;">Confirm Redemption &amp; Print Receipt</button>
                    <a href="pawning.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    </div>

    <?php if (empty($_SESSION['loggedin'])) { ?>
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
    <?php } ?>
</body>
</html>