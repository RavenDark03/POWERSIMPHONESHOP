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

$id = $_GET['id'];
$sql = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    header("Location: customers.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customer - Powersim Phoneshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .view-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .view-row { display: flex; gap: 20px; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .view-label { font-weight: 600; color: #0a3d0a; width: 150px; }
        .view-value { flex: 1; color: #333; }
        .id-image { max-width: 100%; max-height: 400px; border: 1px solid #ddd; padding: 5px; border-radius: 4px; }
        .btn-back { background-color: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 20px; }
        .btn-back:hover { background-color: #5a6268; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Customer Details: <?php echo $row['customer_code']; ?></h2>
            <a href="customers.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>

        <div class="view-section">
            <h3>Personal Information</h3>
            <div class="view-row"><div class="view-label">Full Name:</div><div class="view-value"><?php echo $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']; ?></div></div>
            <div class="view-row"><div class="view-label">Contact Number:</div><div class="view-value"><?php echo $row['contact_number']; ?></div></div>
        </div>

        <div class="view-section">
            <h3>Present Address</h3>
            <?php
            $present_addr = $row['present_house_num'] . ' ' . $row['present_street'] . ', ' . $row['present_subdivision'];
            $present_loc = $row['present_barangay'] . ', ' . $row['present_city'] . ', ' . $row['present_province'] . ' ' . $row['present_zip'];
            ?>
            <div class="view-row"><div class="view-label">Address Line 1:</div><div class="view-value"><?php echo $present_addr; ?></div></div>
            <div class="view-row"><div class="view-label">Location:</div><div class="view-value"><?php echo $present_loc; ?></div></div>
        </div>

        <div class="view-section">
            <h3>Permanent Address</h3>
            <?php
            $perm_addr = $row['permanent_house_num'] . ' ' . $row['permanent_street'] . ', ' . $row['permanent_subdivision'];
            $perm_loc = $row['permanent_barangay'] . ', ' . $row['permanent_city'] . ', ' . $row['permanent_province'] . ' ' . $row['permanent_zip'];
            ?>
            <div class="view-row"><div class="view-label">Address Line 1:</div><div class="view-value"><?php echo $perm_addr; ?></div></div>
            <div class="view-row"><div class="view-label">Location:</div><div class="view-value"><?php echo $perm_loc; ?></div></div>
        </div>

        <div class="view-section">
            <h3>Identification</h3>
            <div class="view-row"><div class="view-label">ID Type:</div><div class="view-value"><?php echo $row['id_type'] . ($row['other_id_type'] ? ' (' . $row['other_id_type'] . ')' : ''); ?></div></div>
            <div class="view-row">
                <div class="view-label">Front ID:</div>
                <div class="view-value">
                    <?php if(!empty($row['id_image_front_path'])): ?>
                        <img src="../<?php echo $row['id_image_front_path']; ?>" alt="Front ID" class="id-image">
                    <?php elseif(!empty($row['id_image_path'])): ?>
                         <img src="../<?php echo $row['id_image_path']; ?>" alt="Front ID (Legacy)" class="id-image"><br><small>(Migrated)</small>
                    <?php else: ?>
                        No Front ID uploaded.
                    <?php endif; ?>
                </div>
            </div>
            <div class="view-row">
                <div class="view-label">Back ID:</div>
                <div class="view-value">
                    <?php if(!empty($row['id_image_back_path'])): ?>
                        <img src="../<?php echo $row['id_image_back_path']; ?>" alt="Back ID" class="id-image">
                    <?php else: ?>
                        No Back ID uploaded.
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
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
