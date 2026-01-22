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

// Define renewal cooldown period in days
define('RENEWAL_COOLDOWN_DAYS', 20);

$id = $_GET['id'];
$sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id WHERE items.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

// Check renewal eligibility (20-day constraint)
$canRenew = true;
$daysRemaining = 0;
$lastRenewalDate = null;

$renewalCheck = $conn->prepare("SELECT created_at FROM transactions WHERE item_id = ? AND transaction_type = 'renewal' ORDER BY created_at DESC LIMIT 1");
$renewalCheck->bind_param("i", $id);
$renewalCheck->execute();
$renewalResult = $renewalCheck->get_result();

if ($renewalResult->num_rows > 0) {
    $lastRenewal = $renewalResult->fetch_assoc();
    $lastRenewalDate = $lastRenewal['created_at'];
    $daysSince = floor((time() - strtotime($lastRenewalDate)) / (24 * 60 * 60));
    $daysRemaining = RENEWAL_COOLDOWN_DAYS - $daysSince;
    
    if ($daysRemaining > 0) {
        $canRenew = false;
    }
}
$renewalCheck->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Pawn</title>
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
                <h2 style="margin-bottom: 5px; color: var(--primary-color);">Renew Pawn</h2>
                <p style="color: #666; font-size: 0.9rem;">Extend the loan period for this item</p>
            </div>

            <div style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                
                <?php if (!$canRenew): ?>
                <!-- Renewal Not Allowed - Cooldown Period -->
                <div style="text-align: center; padding: 30px 20px;">
                    <div style="width: 80px; height: 80px; background: #fff3cd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                        <i class="fas fa-hourglass-half" style="font-size: 35px; color: #856404;"></i>
                    </div>
                    <h3 style="color: #856404; margin: 0 0 10px;">Renewal Not Available Yet</h3>
                    <p style="color: #666; margin: 0 0 20px;">This pawn ticket was recently renewed. A <?php echo RENEWAL_COOLDOWN_DAYS; ?>-day waiting period is required between renewals.</p>
                    
                    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; border: 1px solid #ffeeba; margin-bottom: 20px;">
                        <p style="margin: 5px 0; font-size: 0.9rem;"><strong>Last Renewed:</strong> <?php echo date('M d, Y', strtotime($lastRenewalDate)); ?></p>
                        <p style="margin: 5px 0; font-size: 0.9rem;"><strong>Days Remaining:</strong> <span style="font-weight: 700; color: #856404;"><?php echo $daysRemaining; ?> day(s)</span></p>
                        <p style="margin: 5px 0; font-size: 0.9rem;"><strong>Renewal Available:</strong> <?php echo date('M d, Y', strtotime($lastRenewalDate . ' + ' . RENEWAL_COOLDOWN_DAYS . ' days')); ?></p>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef; text-align: left;">
                        <p style="margin: 5px 0;"><strong>Customer:</strong> <?php echo $item['customer_name']; ?></p>
                        <p style="margin: 5px 0;"><strong>Item:</strong> <?php echo $item['item_description']; ?> (<?php echo $item['category']; ?>)</p>
                        <p style="margin: 5px 0;"><strong>Loan Amount:</strong> <span style="color: #2e7d32; font-weight: bold;">₱<?php echo number_format($item['loan_amount'], 2); ?></span></p>
                        <p style="margin: 5px 0;"><strong>Current Due Date:</strong> <?php echo date('M d, Y', strtotime($item['due_date'])); ?></p>
                    </div>
                    
                    <a href="pawn_processing.php" class="btn" style="display: inline-block; padding: 12px 24px; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Back to Processing
                    </a>
                </div>
                
                <?php else: ?>
                <!-- Renewal Form -->
                <form action="pawn_process.php" method="post">
                    <input type="hidden" name="action" value="renew_pawn">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #e9ecef;">
                        <p style="margin: 5px 0;"><strong>Customer:</strong> <?php echo $item['customer_name']; ?></p>
                        <p style="margin: 5px 0;"><strong>Item:</strong> <?php echo $item['item_description']; ?> (<?php echo $item['category']; ?>)</p>
                        <p style="margin: 5px 0;"><strong>Loan Amount:</strong> <span style="color: #2e7d32; font-weight: bold;">₱<?php echo number_format($item['loan_amount'], 2); ?></span></p>
                        <p style="margin: 5px 0;"><strong>Current Due Date:</strong> <?php echo date('M d, Y', strtotime($item['due_date'])); ?></p>
                    </div>

                    <div class="form-group">
                        <label for="new_due_date" style="font-weight: 500; color: #444;">New Due Date</label>
                        <input type="date" name="new_due_date" id="new_due_date" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>

                    <div id="renewal-calculation" style="margin-top:18px; background:#f4f7f6; padding:12px; border-radius:8px; border:1px solid #e9efee; display:block;">
                        <h4 style="margin:0 0 8px; font-size:1rem;">Renewal Calculation (Monthly)</h4>
                        <p style="margin:4px 0;"><strong>Loan Amount:</strong> <span id="calc-loan">₱<?php echo number_format($item['loan_amount'],2); ?></span></p>
                        <p style="margin:4px 0;"><strong>Interest Rate:</strong> <span id="calc-rate"><?php echo htmlspecialchars($item['interest_rate']); ?>%</span></p>
                        <p style="margin:4px 0;"><strong>Monthly Interest:</strong> <span id="calc-interest">₱0.00</span></p>
                        <p style="margin:4px 0;"><strong>Amount Due for Renewal:</strong> <span id="calc-renewal">₱0.00</span></p>
                    </div>
                    
                    <div style="background: #e8f5e9; padding: 12px; border-radius: 8px; margin-top: 15px; border: 1px solid #c8e6c9;">
                        <p style="margin: 0; font-size: 0.85rem; color: #2e7d32;">
                            <i class="fas fa-info-circle"></i> After this renewal, the next renewal will be available after <?php echo RENEWAL_COOLDOWN_DAYS; ?> days.
                        </p>
                    </div>

                    <div style="margin-top: 25px;">
                        <button type="submit" class="btn" style="width: 100%; padding: 12px;">Confirm Renewal</button>
                        <a href="pawn_processing.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Cancel</a>
                    </div>
                </form>
                <?php endif; ?>
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
    <script>
        // Prefill new due date as current due date + 1 month and calculate renewal amount
        (function(){
            const currentDue = '<?php echo $item['due_date']; ?>';
            const loanAmount = parseFloat('<?php echo $item['loan_amount']; ?>');
            const interestRate = parseFloat('<?php echo $item['interest_rate']; ?>');

            // helper to add months preserving day where possible
            function addMonths(dateStr, months) {
                const d = new Date(dateStr);
                const day = d.getDate();
                d.setMonth(d.getMonth() + months);
                // handle month overflow
                if (d.getDate() !== day) {
                    d.setDate(0); // last day of previous month
                }
                return d.toISOString().split('T')[0];
            }

            // set default new due date to +1 month
            const newDueEl = document.getElementById('new_due_date');
            if (newDueEl) {
                try {
                    newDueEl.value = addMonths(currentDue, 1);
                    // ensure min is next day after current due
                    const minDate = addMonths(currentDue, 0);
                    newDueEl.min = addMonths(minDate, 0);
                } catch (e) {}
            }

            // Calculate monthly interest and renewal amount (simple monthly interest)
            function formatMoney(v){
                return '₱' + Number(v).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
            }

            const monthlyInterest = loanAmount * (interestRate / 100);
            document.getElementById('calc-interest').textContent = formatMoney(monthlyInterest);
            // Renewal amount for one month is typically the interest; show that clearly
            document.getElementById('calc-renewal').textContent = formatMoney(monthlyInterest);
        })();
    </script>
</body>
</html>