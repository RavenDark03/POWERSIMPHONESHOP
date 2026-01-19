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

$sql = "SELECT i.*, c.first_name, c.last_name, CONCAT_WS(' ', c.present_house_num, c.present_street, c.present_subdivision, c.present_barangay, c.present_city, c.present_province, c.present_zip) as address, c.contact_number, c.email,
    COALESCE(ic.name, i.category) AS category_display,
    COALESCE(it.name, i.item_type) AS item_type_display,
    COALESCE(cond.name, i.item_condition) AS item_condition_display,
    COALESCE(n.notes, i.item_description) AS item_description_display
    FROM items i
    JOIN customers c ON i.customer_id = c.id
    LEFT JOIN item_categories ic ON ic.name = i.category
    LEFT JOIN item_types it ON it.name = i.item_type
    LEFT JOIN item_conditions cond ON cond.name = i.item_condition
    LEFT JOIN item_notes n ON n.item_id = i.id
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
$tx = isset($_GET['tx']) ? $_GET['tx'] : '';

// Renewal: monthly simple interest
if ($tx === 'renewal') {
    $renewal_amount = round($pawn['loan_amount'] * ($pawn['interest_rate'] / 100), 2);
}

// Redemption: principal + accrued interest + service charge
if ($tx === 'redemption') {
    $principal = (float)$pawn['loan_amount'];
    $interest_rate = (float)$pawn['interest_rate'];
    $created_ts = strtotime($pawn['created_at'] ?? 'now');
    $now_ts = time();
    $months_elapsed = max(1, ceil(($now_ts - $created_ts) / (30 * 24 * 60 * 60)));
    $total_interest = round($principal * ($interest_rate / 100) * $months_elapsed, 2);
    $service_charge = 5.00;
    $redemption_amount = $principal + $total_interest + $service_charge;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawn Receipt</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        @page { size: A4; margin: 10mm 8mm; }
        body { background:#f6f7fb; font-family: 'Outfit', 'Arial', sans-serif; color:#0f172a; }
        .receipt-container {
            max-width: 620px;
            margin: 10px auto;
            padding: 14px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 14px 40px rgba(0,0,0,0.06);
        }
        .receipt-header {
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .receipt-header img {
            width: 78px;
            height: auto;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 1.05rem;
            color: #0f172a;
        }
        .receipt-header p { margin: 4px 0 0; color:#475569; }
        .receipt-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 4px 0 12px;
            color: #b8860b;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: left;
        }
        .section { margin-bottom: 12px; }
        .section-title {
            font-size: 0.96rem;
            font-weight: 700;
            margin: 0 0 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
            color: #0f172a;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 0; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }
        th { width: 38%; font-weight: 700; color: #475569; }
        .receipt-details th { width: 32%; }
        .item-section, .loan-section { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px; }
        .item-info-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(170px,1fr)); gap:8px; }
        .info-block { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px; }
        .info-block .label { display:block; font-size:0.72rem; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:3px; }
        .info-block .value { font-weight:700; color:#0f172a; font-size:0.97rem; }
        .item-info-note { margin-top:8px; padding:8px; border:1px dashed #e2e8f0; border-radius:8px; color:#475569; background:#fff; font-size:0.95rem; }
        .item-info-note .label { font-size:0.76rem; text-transform:uppercase; letter-spacing:0.04em; color:#64748b; }
        .loan-table th { width: 55%; }
        .loan-table td { text-align: right; font-weight:700; color:#0f172a; font-size:0.98rem; }
        .total-amount { font-size: 1.08rem; color:#16a34a; }
        .receipt-footer { margin-top: 10px; text-align: center; font-size: 0.88rem; color: #475569; }
        .actions { text-align: center; margin: 10px 0 0; }
        .btn-print, .btn-back { padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; margin: 0 6px; }
        .btn-print { background-color: #0ea5e9; color: #fff; }
        .btn-back { background-color: #475569; color: #fff; text-decoration: none; }
        @media print {
            body { background:#fff; }
            body * { visibility: hidden; }
            .receipt-container, .receipt-container * { visibility: visible; }
            .receipt-container { position: absolute; left: 0; top: 0; width: 100%; max-width: 100%; margin: 0; padding: 0 4mm; border: none; box-shadow: none; }
            .actions { display: none; }
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

        <h2 class="receipt-title"><?php echo ($tx === 'renewal') ? 'Renewal Receipt' : 'Pawn Ticket'; ?></h2>

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

        <?php if ($tx === 'redemption'): ?>
        <div class="redemption-details">
            <div class="section-title">Redemption Breakdown</div>
            <table class="loan-table">
                <tr><th>Principal Loan:</th><td>₱<?php echo number_format($principal, 2); ?></td></tr>
                <tr><th>Total Interest (<?php echo $months_elapsed; ?> mo @ <?php echo number_format($interest_rate, 2); ?>%):</th><td>₱<?php echo number_format($total_interest, 2); ?></td></tr>
                <tr><th>Service Charge:</th><td>₱<?php echo number_format($service_charge, 2); ?></td></tr>
                <tr><th>Redemption Amount:</th><td><span class="total-amount">₱<?php echo number_format($redemption_amount, 2); ?></span></td></tr>
            </table>
        </div>
        <?php endif; ?>
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
            <div class="item-section">
                <h3 class="section-title" style="margin-top: 0;">Item Information</h3>
                <div class="item-info-card">
                <div class="item-info-grid">
                    <div class="info-block">
                        <span class="label">Category</span>
                        <span class="value"><?php echo htmlspecialchars($pawn['category_display']); ?></span>
                    </div>
                    <?php if (!empty($pawn['item_type_display'])): ?>
                    <div class="info-block">
                        <span class="label">Type</span>
                        <span class="value"><?php echo htmlspecialchars($pawn['item_type_display']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($pawn['category'] === 'Gadget'): ?>
                        <?php if (!empty($pawn['brand'])): ?>
                        <div class="info-block">
                            <span class="label">Brand</span>
                            <span class="value"><?php echo htmlspecialchars($pawn['brand']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($pawn['model'])): ?>
                        <div class="info-block">
                            <span class="label">Model</span>
                            <span class="value"><?php echo htmlspecialchars($pawn['model']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($pawn['serial_number'])): ?>
                        <div class="info-block">
                            <span class="label">Serial No.</span>
                            <span class="value" style="font-family: 'Courier New', monospace; letter-spacing: 0.4px;">
                                <?php echo htmlspecialchars($pawn['serial_number']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php elseif ($pawn['category'] === 'Silver Jewelry'): ?>
                        <?php if (!empty($pawn['weight_grams'])): ?>
                        <div class="info-block">
                            <span class="label">Weight</span>
                            <span class="value"><?php echo htmlspecialchars($pawn['weight_grams']); ?> g</span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($pawn['purity'])): ?>
                        <div class="info-block">
                            <span class="label">Purity</span>
                            <span class="value"><?php echo htmlspecialchars($pawn['purity']); ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="info-block">
                        <span class="label">Condition</span>
                        <span class="value"><?php echo htmlspecialchars($pawn['item_condition_display']); ?></span>
                    </div>
                </div>

                <?php if (!empty($pawn['item_description_display'])): ?>
                <div class="item-info-note">
                    <span class="label">Description</span>
                    <div><?php echo htmlspecialchars($pawn['item_description_display']); ?></div>
                </div>
                <?php endif; ?>

                <?php if ($pawn['category'] === 'Gadget' && !empty($pawn['accessories'])): ?>
                <div class="item-info-note">
                    <span class="label">Accessories</span>
                    <div><?php echo htmlspecialchars($pawn['accessories']); ?></div>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="loan-details">
            <div class="loan-section">
                <h3 class="section-title" style="margin-top: 0;">Loan Details</h3>
                <table class="loan-table">
                    <tr>
                        <th>Loan Amount:</th>
                        <td class="total-amount">₱<?php echo number_format($pawn['loan_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Interest Rate:</th>
                        <td><?php echo htmlspecialchars($pawn['interest_rate']); ?>%</td>
                    </tr>
                    <?php if ($tx === 'renewal'): ?>
                    <tr>
                        <th>Renewal Amount (1 month):</th>
                        <td class="total-amount">₱<?php echo number_format($renewal_amount, 2); ?></td>
                    </tr>
                    <tr>
                        <th>Renewal Date:</th>
                        <td><?php echo date("F j, Y"); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Due Date:</th>
                        <td><?php echo date("F j, Y", strtotime($pawn['due_date'])); ?></td>
                    </tr>
                </table>
            </div>
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
