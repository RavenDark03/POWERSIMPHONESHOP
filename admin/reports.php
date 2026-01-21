<?php
session_start();
// 1. Set Timezone to Philippines
date_default_timezone_set('Asia/Manila');
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// 2. Date Range Logic
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- SECTION A: CASH FLOW CALCULATIONS (Totals) ---

// 1. Cash OUT: New Pawns (Capital Released)
$out_pawns_sql = "SELECT COUNT(*) as count, SUM(loan_amount) as total_out FROM items 
                  WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$out_pawns_result = $conn->query($out_pawns_sql);
$out_pawns_data = $out_pawns_result->fetch_assoc();
$cash_out_capital = $out_pawns_data['total_out'] ?? 0;
$count_new_pawns = $out_pawns_data['count'] ?? 0;

// 2. Cash IN: Sales Revenue
$in_sales_sql = "SELECT COUNT(*) as count, SUM(sale_price) as total_sales, SUM(sale_price - loan_amount) as gross_profit 
                 FROM items WHERE status = 'sold' AND DATE(date_sold) BETWEEN '$start_date' AND '$end_date'";
$in_sales_result = $conn->query($in_sales_sql);
$in_sales_data = $in_sales_result->fetch_assoc();
$cash_in_sales = $in_sales_data['total_sales'] ?? 0;
$sales_profit = $in_sales_data['gross_profit'] ?? 0;
$count_sales = $in_sales_data['count'] ?? 0;

// 3. Cash IN: Redemptions (Principal + Interest)
$in_redeem_sql = "SELECT 
                    COUNT(t.id) as count,
                    SUM(i.loan_amount) as principal,
                    SUM(i.loan_amount * (i.interest_rate / 100)) as interest_est
                  FROM transactions t
                  JOIN items i ON t.item_id = i.id
                  WHERE t.transaction_type = 'redemption' 
                  AND DATE(t.transaction_date) BETWEEN '$start_date' AND '$end_date'";
$in_redeem_result = $conn->query($in_redeem_sql);
$in_redeem_data = $in_redeem_result->fetch_assoc();
$redeem_principal = $in_redeem_data['principal'] ?? 0;
$redeem_interest = $in_redeem_data['interest_est'] ?? 0;
$cash_in_redeem = $redeem_principal + $redeem_interest;
$count_redeem = $in_redeem_data['count'] ?? 0;

// 4. Cash IN: Renewals (Interest Payment Only)
$in_renewal_sql = "SELECT 
                     COUNT(t.id) as count,
                     SUM(i.loan_amount * (i.interest_rate / 100)) as interest_paid
                   FROM transactions t
                   JOIN items i ON t.item_id = i.id
                   WHERE t.transaction_type = 'renewal'
                   AND DATE(t.transaction_date) BETWEEN '$start_date' AND '$end_date'";
$in_renewal_result = $conn->query($in_renewal_sql);
$in_renewal_data = $in_renewal_result->fetch_assoc();
$cash_in_renewal = $in_renewal_data['interest_paid'] ?? 0;
$count_renewal = $in_renewal_data['count'] ?? 0;

// 5. TOTALS
$total_cash_in = $cash_in_sales + $cash_in_redeem + $cash_in_renewal;
$total_cash_out = $cash_out_capital;
$net_cash_flow = $total_cash_in - $total_cash_out;

// --- SECTION B: OTHER METRICS ---
$active_loans_sql = "SELECT COUNT(*) as count, SUM(loan_amount) as total_value FROM items WHERE status = 'pawned'";
$active_result = $conn->query($active_loans_sql)->fetch_assoc();

$walkin_sql = "SELECT COUNT(*) as count FROM customers WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND registration_source = 'walk_in'";
$walkin_count = $conn->query($walkin_sql)->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .filter-bar { background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .quick-btn { background: #f0f0f0; border: 1px solid #ddd; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; color: #333; transition: all 0.2s; }
        .quick-btn:hover { background: #e0e0e0; border-color: #ccc; }
        
        .section-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 30px; border: 1px solid #eee; }
        .section-header { border-bottom: 2px solid #f8f9fa; padding-bottom: 15px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
        
        /* Cash Flow Grid */
        .cf-grid { display: flex; flex-wrap: wrap; gap: 40px; }
        .cf-col { flex: 1; min-width: 300px; }
        .cf-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .cf-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 0.95rem; }
        .cf-table tr:last-child td { border-bottom: none; font-weight: 700; font-size: 1.1rem; padding-top: 15px; border-top: 2px solid #0a3d0a; background: #f8f9fa; }
        .cf-amount { text-align: right; font-family: 'Consolas', monospace; font-weight: 600; }
        .cf-in { color: #2e7d32; }
        .cf-out { color: #d32f2f; }
        .net-result { background: linear-gradient(135deg, #f8f9fa, #e9ecef); padding: 20px; border-radius: 10px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        
        /* KPI & Data Tables */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .kpi-card { background: linear-gradient(135deg, #fff, #f8f9fa); padding: 20px; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border: 1px solid #e9ecef; transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-2px); }
        .kpi-title { font-size: 0.9rem; color: #6c757d; margin-bottom: 5px; font-weight: 500; }
        .kpi-value { font-size: 1.5rem; font-weight: 700; color: #0a3d0a; }

        .table-scroll { max-height: 500px; min-height: 200px; overflow: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .data-table { width: 100%; border-collapse: collapse; background: #fff; }
        .data-table th { position: sticky; top: 0; background: linear-gradient(135deg, #0a3d0a, #145214); z-index: 1; padding: 15px 12px; text-align: left; color: #fff; font-weight: 700; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-table td { padding: 15px 12px; border-bottom: 1px solid #e9ecef; vertical-align: middle; font-size: 0.9rem; }
        .data-table tr:nth-child(even) { background-color: #f8f9fa; }
        .data-table tr:hover { background-color: #e8f5e9; transition: background-color 0.2s; }
        .data-table tfoot td { background: linear-gradient(135deg, #f1f5f9, #e9ecef); font-weight: 700; padding: 18px 12px; border-top: 2px solid #0a3d0a; color: #0a3d0a; }
        
        .print-btn { border: 2px solid #0a3d0a; background: linear-gradient(135deg, #fff, #f8f9fa); color: #0a3d0a; border-radius: 8px; padding: 8px 16px; cursor: pointer; font-size: 0.9rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .print-btn:hover { background: linear-gradient(135deg, #0a3d0a, #145214); color: #fff; transform: translateY(-1px); }

        /* PRINT STYLES */
        .print-details-container { display: none; } /* Hidden by default */
        
        @media print {
            body.has-sidebar #sidebar, .filter-bar, header, footer { display:none; }
            .container { width: 100%; max-width: 100%; margin: 0; padding: 0; }
            .section-card { box-shadow:none; border: 1px solid #000; page-break-inside: avoid; margin-bottom: 20px; }
            .print-btn { display: none; }
            
            /* Reveal the detailed tables when printing */
            .print-details-container { display: block !important; margin-top: 30px; border-top: 2px solid #000; padding-top: 20px; }
            
            /* Print Header */
            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 15px; }
            .print-header h1 { font-size: 1.5rem; margin: 0; color: #000; }
            .print-header p { margin: 5px 0; color: #333; }
            
            .print-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 0.85rem; border: 2px solid #000; background: #fff; }
            .print-table th { text-align: left; border: 1px solid #000; padding: 12px; background-color: #e0e0e0; font-weight: bold; font-size: 0.9rem; text-transform: uppercase; }
            .print-table td { border: 1px solid #000; padding: 10px; }
            .print-table tr:nth-child(even) { background-color: #f5f5f5; }
            /* Right align numeric data */
            .print-table td.num { text-align: right; font-family: 'Courier New', monospace; font-weight: bold; }
            .print-section-title { font-weight: bold; font-size: 1.1rem; margin-top: 25px; margin-bottom: 10px; color: #000; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 3px; page-break-after: avoid; }
        }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <div class="main-content-wrapper">
    <div class="container main-content">
        
        <div class="page-hero">
            <div>
                <h2 class="page-hero-title"><i class="fas fa-chart-line"></i> Operational Reports</h2>
                <p class="page-hero-subtitle">Financial overview and activity logs</p>
            </div>
        </div>

        <form class="filter-bar" method="GET" id="reportForm">
            <div style="font-weight: 600; color: #444;">Period:</div>
            <button type="button" class="quick-btn" onclick="setDateRange('today')">Today</button>
            <button type="button" class="quick-btn" onclick="setDateRange('yesterday')">Yesterday</button>
            <button type="button" class="quick-btn" onclick="setDateRange('month')">This Month</button>
            
            <div style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                <span style="color:#888;">to</span>
                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>" style="padding: 6px; border: 1px solid #ddd; border-radius: 4px;">
                <button type="submit" class="btn" style="padding: 6px 20px; height: auto;">Filter</button>
            </div>
        </form>

        <div class="section-card" id="section-cashflow">
            <div class="section-header">
                <h3><i class="fas fa-cash-register"></i> Cash Flow Statement</h3>
                <div>
                    <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; margin-right: 10px;">
                        <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
                    </span>
                    <button type="button" class="print-btn" onclick="printSection('section-cashflow')"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
            
            <div class="cf-grid">
                <div class="cf-col">
                    <h4 style="color: #2e7d32; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px;">
                        <i class="fas fa-arrow-down"></i> (Revenue)
                    </h4>
                    <table class="cf-table">
                        <tr>
                            <td>Sales Revenue <small style="color:#888;">(<?php echo $count_sales; ?> sold)</small></td>
                            <td class="cf-amount cf-in">+ ₱<?php echo number_format($cash_in_sales, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Redemption Principal <small style="color:#888;">(<?php echo $count_redeem; ?> items)</small></td>
                            <td class="cf-amount cf-in">+ ₱<?php echo number_format($redeem_principal, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Redemption Interest (Est.)</td>
                            <td class="cf-amount cf-in">+ ₱<?php echo number_format($redeem_interest, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Renewal Fees <small style="color:#888;">(<?php echo $count_renewal; ?> renewed)</small></td>
                            <td class="cf-amount cf-in">+ ₱<?php echo number_format($cash_in_renewal, 2); ?></td>
                        </tr>
                        <tr>
                            <td>TOTAL</td>
                            <td class="cf-amount cf-in" style="font-size: 1.2rem;">₱<?php echo number_format($total_cash_in, 2); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="cf-col">
                    <h4 style="color: #d32f2f; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px;">
                        <i class="fas fa-arrow-up"></i>  (Expenses)
                    </h4>
                    <table class="cf-table">
                        <tr>
                            <td>New Loans Released <small style="color:#888;">(<?php echo $count_new_pawns; ?> items)</small></td>
                            <td class="cf-amount cf-out">- ₱<?php echo number_format($cash_out_capital, 2); ?></td>
                        </tr>
                        <tr>
                            <td><i>Operating Expenses</i></td>
                            <td class="cf-amount cf-out">₱0.00</td>
                        </tr>
                        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                        <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
                        <tr>
                            <td>TOTAL</td>
                            <td class="cf-amount cf-out" style="font-size: 1.2rem;">₱<?php echo number_format($total_cash_out, 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="net-result">
                <div style="font-weight: 600; color: #555; font-size: 1.1rem;">NET CASH FLOW</div>
                <div style="font-weight: 700; font-size: 1.6rem; color: <?php echo $net_cash_flow >= 0 ? '#2e7d32' : '#d32f2f'; ?>;">
                    ₱<?php echo number_format($net_cash_flow, 2); ?>
                </div>
            </div>

            <div class="print-details-container">
                <div class="print-header">
                    <h1>Powersim Phoneshop Gadget Trading Inc.</h1>
                    <p>Transaction Ledger Report</p>
                    <p>Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                    <p>Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
                </div>

                <?php if($count_sales > 0): ?>
                <div class="print-section-title">Sales Transactions</div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Date/Time</th>
                            <th>Customer</th>
                            <th>Customer Code</th>
                            <th>Contact</th>
                            <th>Item Description</th>
                            <th>Serial</th>
                            <th style="text-align: right;">Sold For</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM items JOIN customers c ON items.customer_id = c.id WHERE items.status = 'sold' AND DATE(items.date_sold) BETWEEN '$start_date' AND '$end_date'");
                        while($row = $res->fetch_assoc()){
                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                            echo "<tr>
                                    <td>".intval($row['id'])."</td>
                                    <td>".date('Y-m-d h:i A', strtotime($row['date_sold']))."</td>
                                    <td>".$custName."</td>
                                    <td>".$custCode."</td>
                                    <td>".$contact."</td>
                                    <td>".$itemDesc."</td>
                                    <td>".$serial."</td>
                                    <td class='num'>₱".number_format($row['sale_price'], 2)."</td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <?php if($count_redeem > 0): ?>
                <div class="print-section-title">Redemptions (Principal + Interest)</div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Txn ID</th>
                            <th>Date/Time</th>
                            <th>Customer</th>
                            <th>Customer Code</th>
                            <th>Contact</th>
                            <th>Item Description</th>
                            <th>Serial</th>
                            <th style="text-align: right;">Principal</th>
                            <th style="text-align: right;">Interest</th>
                            <th style="text-align: right;">Service</th>
                            <th style="text-align: right;">Total</th>
                            <?php if($has_payment_method) echo '<th>Payment Method</th>'; ?>
                            <?php if($has_reference) echo '<th>Reference</th>'; ?>
                            <?php if($has_processed_by) echo '<th>Processed By</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $selectCols = "transactions.id as txn_id, transactions.transaction_date" . ($has_payment_method ? ", transactions.payment_method" : "") . ($has_reference ? ", transactions.reference" : "") . ($has_processed_by ? ", transactions.processed_by" : "");
                        $res = $conn->query("SELECT $selectCols, items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM transactions JOIN items ON items.id = transactions.item_id JOIN customers c ON items.customer_id = c.id WHERE transactions.transaction_type = 'redemption' AND DATE(transactions.transaction_date) BETWEEN '$start_date' AND '$end_date'");
                        while($row = $res->fetch_assoc()){
                            $interest = $row['loan_amount'] * ($row['interest_rate']/100);
                            $service = 5.00;
                            $total = $row['loan_amount'] + $interest + $service;
                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                            $paymentMethod = $has_payment_method ? htmlspecialchars($row['payment_method'] ?? '-') : '-';
                            $reference = $has_reference ? htmlspecialchars($row['reference'] ?? '-') : '-';
                            $processedBy = $has_processed_by ? htmlspecialchars($row['processed_by'] ?? '-') : '-';
                            echo "<tr>
                                    <td>".intval($row['txn_id'])."</td>
                                    <td>".date('Y-m-d h:i A', strtotime($row['transaction_date']))."</td>
                                    <td>".$custName."</td>
                                    <td>".$custCode."</td>
                                    <td>".$contact."</td>
                                    <td>".$itemDesc."</td>
                                    <td>".$serial."</td>
                                    <td class='num'>₱".number_format($row['loan_amount'], 2)."</td>
                                    <td class='num'>₱".number_format($interest, 2)."</td>
                                    <td class='num'>₱".number_format($service, 2)."</td>
                                    <td class='num'>₱".number_format($total, 2)."</td>";
                            if($has_payment_method) echo "<td>".$paymentMethod."</td>";
                            if($has_reference) echo "<td>".$reference."</td>";
                            if($has_processed_by) echo "<td>".$processedBy."</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php endif; ?>

                 <?php if($count_renewal > 0): ?>
                <div class="print-section-title"> Renewals (Interest Only)</div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Txn ID</th>
                            <th>Date/Time</th>
                            <th>Customer</th>
                            <th>Customer Code</th>
                            <th>Contact</th>
                            <th>Item Description</th>
                            <th>Serial</th>
                            <th style="text-align: right;">Interest Paid</th>
                            <?php if($has_payment_method) echo '<th>Payment Method</th>'; ?>
                            <?php if($has_reference) echo '<th>Reference</th>'; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $selectCols = "transactions.id as txn_id, transactions.transaction_date" . ($has_payment_method ? ", transactions.payment_method" : "") . ($has_reference ? ", transactions.reference" : "");
                        $res = $conn->query("SELECT $selectCols, items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM transactions JOIN items ON items.id = transactions.item_id JOIN customers c ON items.customer_id = c.id WHERE transactions.transaction_type = 'renewal' AND DATE(transactions.transaction_date) BETWEEN '$start_date' AND '$end_date'");
                        while($row = $res->fetch_assoc()){
                            $interest = $row['loan_amount'] * ($row['interest_rate']/100);
                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                            $paymentMethod = $has_payment_method ? htmlspecialchars($row['payment_method'] ?? '-') : '-';
                            $reference = $has_reference ? htmlspecialchars($row['reference'] ?? '-') : '-';
                            echo "<tr>
                                    <td>".intval($row['txn_id'])."</td>
                                    <td>".date('Y-m-d h:i A', strtotime($row['transaction_date']))."</td>
                                    <td>".$custName."</td>
                                    <td>".$custCode."</td>
                                    <td>".$contact."</td>
                                    <td>".$itemDesc."</td>
                                    <td>".$serial."</td>
                                    <td class='num'>₱".number_format($interest, 2)."</td>";
                            if($has_payment_method) echo "<td>".$paymentMethod."</td>";
                            if($has_reference) echo "<td>".$reference."</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <?php if($count_new_pawns > 0): ?>
                <div class="print-section-title"> New Loans Released</div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Date/Time</th>
                            <th>Customer</th>
                            <th>Customer Code</th>
                            <th>Contact</th>
                            <th>Item Description</th>
                            <th>Serial</th>
                            <th style="text-align: right;">Loan Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $res = $conn->query("SELECT items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM items JOIN customers c ON items.customer_id = c.id WHERE DATE(items.created_at) BETWEEN '$start_date' AND '$end_date'");
                        while($row = $res->fetch_assoc()){
                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                            echo "<tr>
                                    <td>".intval($row['id'])."</td>
                                    <td>".date('Y-m-d h:i A', strtotime($row['created_at']))."</td>
                                    <td>".$custName."</td>
                                    <td>".$custCode."</td>
                                    <td>".$contact."</td>
                                    <td>".$itemDesc."</td>
                                    <td>".$serial."</td>
                                    <td class='num'>₱".number_format($row['loan_amount'], 2)."</td>
                                  </tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <?php if($count_sales > 0): ?>
                <div class="print-section-title">Detailed Sales Transactions</div>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Description</th>
                            <th style="text-align: right;">Capital (Loan)</th>
                            <th style="text-align: right;">Sold For</th>
                            <th style="text-align: right;">Profit</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM items WHERE status = 'sold' AND DATE(date_sold) BETWEEN '$start_date' AND '$end_date' ORDER BY date_sold DESC";
                        $result = $conn->query($sql);
                        $t_capital = 0; $t_sales = 0; $t_profit = 0;
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $profit = $row['sale_price'] - $row['loan_amount'];
                                $t_capital += $row['loan_amount'];
                                $t_sales += $row['sale_price'];
                                $t_profit += $profit;

                                echo "<tr>";
                                echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['item_description']) . "</td>";
                                echo "<td class='num'>₱" . number_format($row['loan_amount'], 2) . "</td>";
                                echo "<td class='num'>₱" . number_format($row['sale_price'], 2) . "</td>";
                                echo "<td class='num' style='color:#2e7d32; font-weight:bold;'>₱" . number_format($profit, 2) . "</td>";
                                echo "<td>" . date('M d, h:i A', strtotime($row['date_sold'])) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:20px; color:#888;'>No sales in this period.</td></tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align: right; font-weight: bold;">TOTALS:</td>
                            <td class="num" style="font-weight: bold;">₱<?php echo number_format($t_capital, 2); ?></td>
                            <td class="num" style="font-weight: bold;">₱<?php echo number_format($t_sales, 2); ?></td>
                            <td class="num" style="font-weight: bold; color:#2e7d32;">₱<?php echo number_format($t_profit, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-title">Gross Sales Profit</div>
                <div class="kpi-value" style="color: #2e7d32;">₱<?php echo number_format($sales_profit, 2); ?></div>
                <div style="font-size: 0.8rem; color: #888;">Margin: <?php echo $cash_in_sales > 0 ? round(($sales_profit/$cash_in_sales)*100, 1) : 0; ?>%</div>
            </div>
            <div class="kpi-card" style="background: #fffcf0; border-color: #f0e68c;">
                <div class="kpi-title">Active Loan Portfolio</div>
                <div class="kpi-value" style="color: #b58900;">₱<?php echo number_format($active_result['total_value'], 2); ?></div>
                <div style="font-size: 0.8rem; color: #888;"><?php echo $active_result['count']; ?> items currently held</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">New Walk-ins</div>
                <div class="kpi-value"><?php echo $walkin_count; ?></div>
                <div style="font-size: 0.8rem; color: #888;">Customers registered in-store</div>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 40px 0;">

        <div class="section-card" id="section-sales">
            <div class="section-header">
                <h3><i class="fas fa-shopping-bag"></i> Sales Transactions</h3>
                <button type="button" class="print-btn" onclick="printSection('section-sales')"><i class="fas fa-print"></i> Print</button>
            </div>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Description</th>
                            <th>Capital (Loan)</th>
                            <th>Sold For</th>
                            <th>Profit</th>
                            <th>Date/Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM items WHERE status = 'sold' AND DATE(date_sold) BETWEEN '$start_date' AND '$end_date' ORDER BY date_sold DESC";
                        $result = $conn->query($sql);
                        $t_capital = 0; $t_sales = 0; $t_profit = 0;
                        
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $profit = $row['sale_price'] - $row['loan_amount'];
                                $t_capital += $row['loan_amount'];
                                $t_sales += $row['sale_price'];
                                $t_profit += $profit;

                                echo "<tr>";
                                echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['item_description']) . "</td>";
                                echo "<td>₱" . number_format($row['loan_amount'], 2) . "</td>";
                                echo "<td>₱" . number_format($row['sale_price'], 2) . "</td>";
                                echo "<td style='color:#2e7d32; font-weight:bold;'>₱" . number_format($profit, 2) . "</td>";
                                echo "<td>" . date('M d, h:i A', strtotime($row['date_sold'])) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:20px; color:#888;'>No sales in this period.</td></tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align: right;">TOTALS:</td>
                            <td>₱<?php echo number_format($t_capital, 2); ?></td>
                            <td>₱<?php echo number_format($t_sales, 2); ?></td>
                            <div class="print-details-container">
                                <h4 style="text-align:center; margin-bottom:20px;">Transaction Ledger</h4>

                                <?php
                                // Detect optional columns in transactions for safer display
                                $has_payment_method = false; $has_reference = false; $has_processed_by = false;
                                $col = $conn->query("SHOW COLUMNS FROM transactions LIKE 'payment_method'"); if ($col && $col->num_rows) $has_payment_method = true;
                                $col = $conn->query("SHOW COLUMNS FROM transactions LIKE 'reference'"); if ($col && $col->num_rows) $has_reference = true;
                                $col = $conn->query("SHOW COLUMNS FROM transactions LIKE 'processed_by'"); if ($col && $col->num_rows) $has_processed_by = true;
                                ?>

                                <?php if($count_sales > 0): ?>
                                <div class="print-section-title">Sales Transactions</div>
                                <table class="print-table">
                                    <thead>
                                        <tr>
                                            <th>Item ID</th>
                                            <th>Date/Time</th>
                                            <th>Customer</th>
                                            <th>Customer Code</th>
                                            <th>Contact</th>
                                            <th>Item Description</th>
                                            <th>Serial</th>
                                            <th style="text-align: right;">Sold For</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $res = $conn->query("SELECT items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM items JOIN customers c ON items.customer_id = c.id WHERE items.status = 'sold' AND DATE(items.date_sold) BETWEEN '$start_date' AND '$end_date'");
                                        while($row = $res->fetch_assoc()){
                                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                                            echo "<tr>
                                                    <td>".intval($row['id'])."</td>
                                                    <td>".date('Y-m-d h:i A', strtotime($row['date_sold']))."</td>
                                                    <td>".$custName."</td>
                                                    <td>".$custCode."</td>
                                                    <td>".$contact."</td>
                                                    <td>".$itemDesc."</td>
                                                    <td>".$serial."</td>
                                                    <td class='num'>₱".number_format($row['sale_price'], 2)."</td>
                                                  </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>

                                <?php if($count_redeem > 0): ?>
                                <div class="print-section-title">Redemptions (Principal + Interest)</div>
                                <table class="print-table">
                                    <thead>
                                        <tr>
                                            <th>Txn ID</th>
                                            <th>Date/Time</th>
                                            <th>Customer</th>
                                            <th>Customer Code</th>
                                            <th>Contact</th>
                                            <th>Item Description</th>
                                            <th>Serial</th>
                                            <th style="text-align: right;">Principal</th>
                                            <th style="text-align: right;">Interest</th>
                                            <th style="text-align: right;">Service</th>
                                            <th style="text-align: right;">Total</th>
                                            <?php if($has_payment_method) echo '<th>Payment Method</th>'; ?>
                                            <?php if($has_reference) echo '<th>Reference</th>'; ?>
                                            <?php if($has_processed_by) echo '<th>Processed By</th>'; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $selectCols = "transactions.id as txn_id, transactions.transaction_date" . ($has_payment_method ? ", transactions.payment_method" : "") . ($has_reference ? ", transactions.reference" : "") . ($has_processed_by ? ", transactions.processed_by" : "");
                                        $res = $conn->query("SELECT $selectCols, items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM transactions JOIN items ON items.id = transactions.item_id JOIN customers c ON items.customer_id = c.id WHERE transactions.transaction_type = 'redemption' AND DATE(transactions.transaction_date) BETWEEN '$start_date' AND '$end_date'");
                                        while($row = $res->fetch_assoc()){
                                            $interest = $row['loan_amount'] * ($row['interest_rate']/100);
                                            $service = 5.00;
                                            $total = $row['loan_amount'] + $interest + $service;
                                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                                            $paymentMethod = $has_payment_method ? htmlspecialchars($row['payment_method'] ?? '-') : '-';
                                            $reference = $has_reference ? htmlspecialchars($row['reference'] ?? '-') : '-';
                                            $processedBy = $has_processed_by ? htmlspecialchars($row['processed_by'] ?? '-') : '-';
                                            echo "<tr>
                                                    <td>".intval($row['txn_id'])."</td>
                                                    <td>".date('Y-m-d h:i A', strtotime($row['transaction_date']))."</td>
                                                    <td>".$custName."</td>
                                                    <td>".$custCode."</td>
                                                    <td>".$contact."</td>
                                                    <td>".$itemDesc."</td>
                                                    <td>".$serial."</td>
                                                    <td class='num'>₱".number_format($row['loan_amount'], 2)."</td>
                                                    <td class='num'>₱".number_format($interest, 2)."</td>
                                                    <td class='num'>₱".number_format($service, 2)."</td>
                                                    <td class='num'>₱".number_format($total, 2)."</td>";
                                            if($has_payment_method) echo "<td>".$paymentMethod."</td>";
                                            if($has_reference) echo "<td>".$reference."</td>";
                                            if($has_processed_by) echo "<td>".$processedBy."</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>

                                 <?php if($count_renewal > 0): ?>
                                <div class="print-section-title"> Renewals (Interest Only)</div>
                                <table class="print-table">
                                    <thead>
                                        <tr>
                                            <th>Txn ID</th>
                                            <th>Date/Time</th>
                                            <th>Customer</th>
                                            <th>Customer Code</th>
                                            <th>Contact</th>
                                            <th>Item Description</th>
                                            <th>Serial</th>
                                            <th style="text-align: right;">Interest Paid</th>
                                            <?php if($has_payment_method) echo '<th>Payment Method</th>'; ?>
                                            <?php if($has_reference) echo '<th>Reference</th>'; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $selectCols = "transactions.id as txn_id, transactions.transaction_date" . ($has_payment_method ? ", transactions.payment_method" : "") . ($has_reference ? ", transactions.reference" : "");
                                        $res = $conn->query("SELECT $selectCols, items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM transactions JOIN items ON items.id = transactions.item_id JOIN customers c ON items.customer_id = c.id WHERE transactions.transaction_type = 'renewal' AND DATE(transactions.transaction_date) BETWEEN '$start_date' AND '$end_date'");
                                        while($row = $res->fetch_assoc()){
                                            $interest = $row['loan_amount'] * ($row['interest_rate']/100);
                                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                                            $paymentMethod = $has_payment_method ? htmlspecialchars($row['payment_method'] ?? '-') : '-';
                                            $reference = $has_reference ? htmlspecialchars($row['reference'] ?? '-') : '-';
                                            echo "<tr>
                                                    <td>".intval($row['txn_id'])."</td>
                                                    <td>".date('Y-m-d h:i A', strtotime($row['transaction_date']))."</td>
                                                    <td>".$custName."</td>
                                                    <td>".$custCode."</td>
                                                    <td>".$contact."</td>
                                                    <td>".$itemDesc."</td>
                                                    <td>".$serial."</td>
                                                    <td class='num'>₱".number_format($interest, 2)."</td>";
                                            if($has_payment_method) echo "<td>".$paymentMethod."</td>";
                                            if($has_reference) echo "<td>".$reference."</td>";
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>

                                <?php if($count_new_pawns > 0): ?>
                                <div class="print-section-title"> New Loans Released</div>
                                <table class="print-table">
                                    <thead>
                                        <tr>
                                            <th>Item ID</th>
                                            <th>Date/Time</th>
                                            <th>Customer</th>
                                            <th>Customer Code</th>
                                            <th>Contact</th>
                                            <th>Item Description</th>
                                            <th>Serial</th>
                                            <th style="text-align: right;">Loan Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $res = $conn->query("SELECT items.*, c.first_name, c.last_name, c.customer_code, c.contact_number FROM items JOIN customers c ON items.customer_id = c.id WHERE DATE(items.created_at) BETWEEN '$start_date' AND '$end_date'");
                                        while($row = $res->fetch_assoc()){
                                            $custName = htmlspecialchars($row['first_name'].' '.$row['last_name']);
                                            $custCode = htmlspecialchars($row['customer_code'] ?? '');
                                            $contact = htmlspecialchars($row['contact_number'] ?? '');
                                            $itemDesc = htmlspecialchars($row['item_description'] ?? '');
                                            $serial = htmlspecialchars($row['serial_number'] ?? '');
                                            echo "<tr>
                                                    <td>".intval($row['id'])."</td>
                                                    <td>".date('Y-m-d h:i A', strtotime($row['created_at']))."</td>
                                                    <td>".$custName."</td>
                                                    <td>".$custCode."</td>
                                                    <td>".$contact."</td>
                                                    <td>".$itemDesc."</td>
                                                    <td>".$serial."</td>
                                                    <td class='num'>₱".number_format($row['loan_amount'], 2)."</td>
                                                  </tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                            </div>