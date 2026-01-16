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

                    <div style="margin-top: 25px;">
                        <button type="submit" class="btn" style="width: 100%; padding: 12px;">Confirm Renewal</button>
                        <a href="pawning.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Cancel</a>
                    </div>
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