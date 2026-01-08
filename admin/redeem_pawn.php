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
    <title>Redeem Pawn</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <li><a href="pawning.php">Pawning</a></li>
                    <li><a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="margin-bottom: 20px; text-align: center;">
                <h2 style="margin-bottom: 5px; color: var(--primary-color);">Redeem Item</h2>
                <p style="color: #666; font-size: 0.9rem;">Process redemption and release item</p>
            </div>

            <div style="background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <form action="pawn_process.php" method="post">
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
                        <p style="margin: 8px 0; display: flex; justify-content: space-between; font-size: 1.1rem;">
                            <span style="color: #444;">Total Payment Required:</span>
                            <span style="font-weight: bold; color: #2e7d32;">₱<?php echo number_format($item['loan_amount'], 2); ?></span>
                        </p>
                    </div>

                    <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; font-size: 0.9rem; margin-bottom: 20px; text-align: center; border: 1px solid #ffeeba;">
                        <i class="fas fa-exclamation-triangle"></i> This action will mark the item as redeemed and close the transaction.
                    </div>

                    <button type="submit" class="btn" style="width: 100%; padding: 12px; background-color: #2e7d32;">Confim Redemption</button>
                    <a href="pawning.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Cancel</a>
                </form>
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