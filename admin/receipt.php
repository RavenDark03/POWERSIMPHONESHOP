<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['item_id'])) {
    die("Error: Item ID is missing.");
}

$item_id = $_GET['item_id'];

$sql = "SELECT i.*, c.first_name, c.last_name, CONCAT_WS(' ', c.present_house_num, c.present_street, c.present_subdivision, c.present_barangay, c.present_city, c.present_province, c.present_zip) as address, c.contact_number, c.email
        FROM items i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Pawn transaction not found.");
}

$pawn = $result->fetch_assoc();
$pawn_ticket_number = 'PS' . str_pad($pawn['id'], 8, '0', STR_PAD_LEFT);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawn Receipt</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border: 1px solid #ccc;
            border-radius: 10px;
            background: #fff;
            font-family: 'Arial', sans-serif;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px dashed #ccc;
        }
        .receipt-header img {
            max-width: 150px;
            margin-bottom: 0.5rem;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }
        .receipt-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 2rem;
            color: #d4af37;
            text-transform: uppercase;
        }
        .receipt-details, .customer-details, .item-details, .loan-details {
            margin-bottom: 1.5rem;
        }
        .receipt-details table, .customer-details table, .item-details table, .loan-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .receipt-details th, .receipt-details td,
        .customer-details th, .customer-details td,
        .item-details th, .item-details td,
        .loan-details th, .loan-details td {
            padding: 8px 0;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .receipt-details th, .customer-details th, .item-details th, .loan-details th {
            width: 35%;
            font-weight: bold;
            color: #555;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #d4af37;
            color: #333;
        }
        .total-amount {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2e7d32;
        }
        .receipt-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #777;
        }
        .actions {
            text-align: center;
            margin-top: 2rem;
        }
        .btn-print, .btn-back {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0 10px;
        }
        .btn-print {
            background-color: #d4af37;
            color: #fff;
        }
        .btn-back {
            background-color: #6c757d;
            color: #fff;
            text-decoration: none;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .receipt-container, .receipt-container * {
                visibility: visible;
            }
            .receipt-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: none;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container" id="receipt">
        <div class="receipt-header">
            <img src="../images/powersim logo.png" alt="Powersim Phoneshop">
            <h1>Powersim Phoneshop Gadget Trading Inc.</h1>
            <p>Baliuag, Bulacan</p>
        </div>

        <h2 class="receipt-title">Pawn Ticket</h2>

        <div class="receipt-details">
            <table>
                <tr>
                    <th>Pawn Ticket No:</th>
                    <td><?php echo htmlspecialchars($pawn_ticket_number); ?></td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td><?php echo date("F j, Y, g:i a"); ?></td>
                </tr>
            </table>
        </div>

        <div class="customer-details">
            <h3 class="section-title">Customer Information</h3>
            <table>
                <tr>
                    <th>Name:</th>
                    <td><?php echo htmlspecialchars($pawn['first_name'] . ' ' . $pawn['last_name']); ?></td>
                </tr>
                <tr>
                    <th>Address:</th>
                    <td><?php echo htmlspecialchars($pawn['address']); ?></td>
                </tr>
                <tr>
                    <th>Contact:</th>
                    <td><?php echo htmlspecialchars($pawn['contact_number']); ?></td>
                </tr>
                 <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($pawn['email']); ?></td>
                </tr>
            </table>
        </div>

        <div class="item-details">
            <h3 class="section-title">Item Information</h3>
            <table>
                <tr>
                    <th>Category:</th>
                    <td><?php echo htmlspecialchars($pawn['category']); ?></td>
                </tr>
                <tr>
                    <th>Type:</th>
                    <td><?php echo htmlspecialchars($pawn['item_type']); ?></td>
                </tr>
                <?php if ($pawn['category'] === 'Gadget'): ?>
                <tr>
                    <th>Brand:</th>
                    <td><?php echo htmlspecialchars($pawn['brand']); ?></td>
                </tr>
                <tr>
                    <th>Model:</th>
                    <td><?php echo htmlspecialchars($pawn['model']); ?></td>
                </tr>
                <tr>
                    <th>Serial No:</th>
                    <td><?php echo htmlspecialchars($pawn['serial_number']); ?></td>
                </tr>
                 <tr>
                    <th>Accessories:</th>
                    <td><?php echo htmlspecialchars($pawn['accessories']); ?></td>
                </tr>
                <?php elseif ($pawn['category'] === 'Silver Jewelry'): ?>
                <tr>
                    <th>Weight (g):</th>
                    <td><?php echo htmlspecialchars($pawn['weight_grams']); ?>g</td>
                </tr>
                <tr>
                    <th>Purity:</th>
                    <td><?php echo htmlspecialchars($pawn['purity']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th>Condition:</th>
                    <td><?php echo htmlspecialchars($pawn['item_condition']); ?></td>
                </tr>
                 <tr>
                    <th>Description:</th>
                    <td><?php echo htmlspecialchars($pawn['item_description']); ?></td>
                </tr>
            </table>
        </div>

        <div class="loan-details">
            <h3 class="section-title">Loan Details</h3>
            <table>
                <tr>
                    <th>Loan Amount:</th>
                    <td class="total-amount">₱<?php echo number_format($pawn['loan_amount'], 2); ?></td>
                </tr>
                <tr>
                    <th>Interest Rate:</th>
                    <td><?php echo htmlspecialchars($pawn['interest_rate']); ?>%</td>
                </tr>
                <tr>
                    <th>Due Date:</th>
                    <td><?php echo date("F j, Y", strtotime($pawn['due_date'])); ?></td>
                </tr>
            </table>
        </div>

        <div class="receipt-footer">
            <p>Thank you for your business. Please keep this receipt for your records.</p>
        </div>
    </div>

    <div class="actions">
        <button class="btn-print" onclick="window.print()">Print Receipt</button>
        <a href="pawning.php" class="btn-back">Back to Pawning List</a>
    </div>

</body>
</html>
