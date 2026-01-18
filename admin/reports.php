<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Date Range Logic
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // FIRST DAY of CURRENT MONTH
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // TODAY

// KPI Queries - Enhanced Formulas

// 1. Active Loans (Current Snapshot)
// All items currently in "pawned" status, regardless of date range
$active_loans_sql = "SELECT COUNT(*) as count, SUM(loan_amount) as total_value, AVG(loan_amount) as avg_loan FROM items WHERE status = 'pawned'";
$active_loans_result = $conn->query($active_loans_sql);
$active_loans_data = $active_loans_result->fetch_assoc();

// 2. New Pawns in Date Range
// Items created (first pawned) within the date range
$new_pawns_sql = "SELECT COUNT(*) as count, SUM(loan_amount) as total FROM items WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$new_pawns_result = $conn->query($new_pawns_sql);
$new_pawns_data = $new_pawns_result->fetch_assoc();
$capital_out = $new_pawns_data['total'] ?? 0;
$new_pawns_count = $new_pawns_data['count'] ?? 0;

// 3. Redeemed Items in Date Range
// Items with redemption transactions in the date range
$redeemed_sql = "SELECT COUNT(DISTINCT items.id) as count, SUM(items.loan_amount) as total_redeemed FROM items 
                 JOIN transactions ON items.id = transactions.item_id 
                 WHERE items.status = 'redeemed' AND transactions.transaction_type = 'redemption' 
                 AND DATE(transactions.transaction_date) BETWEEN '$start_date' AND '$end_date'";
$redeemed_result = $conn->query($redeemed_sql);
$redeemed_data = $redeemed_result->fetch_assoc();
$redeemed_count = $redeemed_data['count'] ?? 0;
$total_redeemed = $redeemed_data['total_redeemed'] ?? 0;

// 4. Sales & Profit (Sold in Date Range)
// Items with sold status and date_sold within the range
$sales_sql = "SELECT COUNT(*) as count, SUM(sale_price) as total_sales, SUM(sale_price - loan_amount) as total_profit, 
             SUM(loan_amount) as total_loan_value FROM items 
             WHERE status = 'sold' AND DATE(date_sold) BETWEEN '$start_date' AND '$end_date'";
$sales_result = $conn->query($sales_sql);
$sales_data = $sales_result->fetch_assoc();
$total_sales = $sales_data['total_sales'] ?? 0;
$total_profit = $sales_data['total_profit'] ?? 0;
$sales_count = $sales_data['count'] ?? 0;
$total_sales_loan_value = $sales_data['total_loan_value'] ?? 0;

// 5. Interest Revenue (Renewals in Date Range)
// Calculate interest from all renewal transactions
$interest_sql = "SELECT COUNT(t.id) as renewal_count FROM transactions t 
                 JOIN items i ON t.item_id = i.id 
                 WHERE t.transaction_type = 'renewal' 
                 AND DATE(t.transaction_date) BETWEEN '$start_date' AND '$end_date'";
$interest_result = $conn->query($interest_sql);
$interest_data = $interest_result->fetch_assoc();
$renewal_count = $interest_data['renewal_count'] ?? 0;
// Estimated interest: renewal count * average interest rate * average loan amount
$avg_interest_estimate = $renewal_count > 0 ? (($active_loans_data['avg_loan'] ?? 0) * 0.03) * $renewal_count : 0;

// 6. Customer Registration Reports
// Walk-in customers registered in date range
$walkin_sql = "SELECT COUNT(*) as count FROM customers WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND registration_source = 'walk_in'";
$walkin_result = $conn->query($walkin_sql);
$walkin_data = $walkin_result->fetch_assoc();
$walkin_count = $walkin_data['count'] ?? 0;

// Online customers registered in date range
$online_sql = "SELECT COUNT(*) as count FROM customers WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND registration_source = 'online'";
$online_result = $conn->query($online_sql);
$online_data = $online_result->fetch_assoc();
$online_count = $online_data['count'] ?? 0;

// Total new customers
$total_new_customers = $walkin_count + $online_count;

// Verified customers (online registration)
$verified_sql = "SELECT COUNT(*) as count FROM customers WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND is_verified = 1";
$verified_result = $conn->query($verified_sql);
$verified_data = $verified_result->fetch_assoc();
$verified_count = $verified_data['count'] ?? 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        .kpi-title { font-size: 0.9rem; color: #666; margin-bottom: 5px; font-weight: 500; }
        .kpi-value { font-size: 1.5rem; font-weight: 700; color: #0a3d0a; }
        .kpi-sub { font-size: 0.8rem; color: #888; margin-top: 5px; }
        
        .filter-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .section-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); padding: 16px; margin-bottom: 30px; border: 1px solid #f0f0f0; }
        .section-header { position: sticky; top: 0; background: #fff; padding-bottom: 10px; margin-bottom: 10px; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .section-header h3 { margin: 0; color: #0a3d0a; display: flex; align-items: center; gap: 8px; }
        .section-actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .section-actions input { padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; min-width: 180px; }
        .section-actions select { padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; }
        .print-btn { border: 1px solid #0a3d0a; background: #fff; color: #0a3d0a; border-radius: 6px; padding: 8px 12px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
        .print-btn:hover { background: #0a3d0a; color: #fff; }
        .table-scroll { max-height: 420px; min-height: 220px; overflow: auto; }
        .customer-table thead th { position: sticky; top: 0; background: #fff; z-index: 1; }
        @media print {
            body.has-sidebar #sidebar { display:none; }
            .filter-bar, .section-actions, header, footer, .kpi-grid { display:none !important; }
            .section-card { box-shadow:none; border:0; }
            .table-scroll { overflow: visible; max-height:none; }
        }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin-bottom: 5px;">Business Reports</h2>
                <p style="color: #666; font-size: 0.9rem;">Overview and performance metrics</p>
            </div>
        </div>

        <!-- Date Filter Form -->
        <form class="filter-bar" method="GET" action="reports.php">
            <div style="font-weight: 500; font-size: 0.95rem; color: #444;">Date Range:</div>
            <div>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
            </div>
            <div style="color:#888;">to</div>
            <div>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
            </div>
            <button type="submit" class="btn" style="padding: 8px 20px; height: auto;">Filter Reports</button>
        </form>

        <!-- KPI Dashboard -->
        <div class="kpi-grid">
            <!-- Card 1: New Pawns Count & Value -->
            <div class="kpi-card">
                <div class="kpi-title">New Pawns Given</div>
                <div class="kpi-value">₱<?php echo number_format($capital_out, 2); ?></div>
                <div class="kpi-sub"><?php echo $new_pawns_count; ?> items - <?php echo date('M d', strtotime($start_date)) . ' - ' . date('M d', strtotime($end_date)); ?></div>
            </div>

            <!-- Card 2: Redeemed Items -->
            <div class="kpi-card">
                <div class="kpi-title">Items Redeemed</div>
                <div class="kpi-value" style="color: #1565c0;">₱<?php echo number_format($total_redeemed, 2); ?></div>
                <div class="kpi-sub"><?php echo $redeemed_count; ?> items redeemed</div>
            </div>

            <!-- Card 3: Total Sales Revenue -->
            <div class="kpi-card">
                <div class="kpi-title">Total Sales Revenue</div>
                <div class="kpi-value" style="color: #2e7d32;">₱<?php echo number_format($total_sales, 2); ?></div>
                <div class="kpi-sub"><?php echo $sales_count; ?> items sold</div>
            </div>
            
            <!-- Card 4: Gross Profit -->
            <div class="kpi-card">
                <div class="kpi-title">Gross Profit (Sales)</div>
                <div class="kpi-value" style="color: <?php echo $total_profit >= 0 ? '#2e7d32' : '#d32f2f'; ?>;">
                    ₱<?php echo number_format($total_profit, 2); ?>
                </div>
                <div class="kpi-sub">Sales - Loan Value</div>
            </div>

            <!-- Card 5: Active Portfolio (Snapshot) -->
            <div class="kpi-card" style="background: #fffcf0; border-color: #f0e68c;">
                <div class="kpi-title">Active Portfolio (Current)</div>
                <div class="kpi-value" style="color: #b58900;"><?php echo $active_loans_data['count']; ?> Items</div>
                <div class="kpi-sub">Valued at ₱<?php echo number_format($active_loans_data['total_value'], 2); ?></div>
            </div>

            <!-- Card 6: Profit Margin -->
            <div class="kpi-card">
                <div class="kpi-title">Profit Margin</div>
                <div class="kpi-value" style="color: #f57c00;">
                    <?php 
                    $margin = $total_sales > 0 ? ($total_profit / $total_sales) * 100 : 0;
                    echo number_format($margin, 1) . '%';
                    ?>
                </div>
                <div class="kpi-sub">On <?php echo $sales_count; ?> sales</div>
            </div>

            <!-- Card 7: Walk-in Registrations -->
            <div class="kpi-card" style="background: #f3e5f5; border-color: #e1bee7;">
                <div class="kpi-title">Walk-in Customers</div>
                <div class="kpi-value" style="color: #6a1b9a;"><?php echo $walkin_count; ?></div>
                <div class="kpi-sub">Registered in-store</div>
            </div>

            <!-- Card 8: Online Registrations -->
            <div class="kpi-card" style="background: #e3f2fd; border-color: #bbdefb;">
                <div class="kpi-title">Online Customers</div>
                <div class="kpi-value" style="color: #1565c0;"><?php echo $online_count; ?></div>
                <div class="kpi-sub">Registered online</div>
            </div>

            <!-- Card 9: Total New Customers -->
            <div class="kpi-card">
                <div class="kpi-title">New Customers</div>
                <div class="kpi-value" style="color: #0a3d0a;"><?php echo $total_new_customers; ?></div>
                <div class="kpi-sub"><?php echo $verified_count; ?> verified</div>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

        <!-- Dynamic Tables -->

        <!-- 1. Pawned Items (Filtered by Created At) -->
        <div class="section-card" id="section-pawned">
            <div class="section-header">
                <h3><i class="fas fa-gem"></i> Pawned Items (In Range)</h3>
                <div class="section-actions">
                    <input type="text" id="searchPawned" placeholder="Search..." aria-label="Search pawned items">
                    <select id="filterPawned" aria-label="Filter column">
                        <option value="all">All</option>
                        <option value="customer">Customer</option>
                        <option value="item">Item</option>
                        <option value="loan">Loan</option>
                        <option value="date">Date</option>
                    </select>
                    <button type="button" class="print-btn" onclick="printSection('section-pawned')"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
            <div class="table-scroll">
                <table class="customer-table" id="tablePawned">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Customer</th>
                            <th>Item Name</th>
                            <th>Loan Amount</th>
                            <th>Date Pawned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id WHERE DATE(items.created_at) BETWEEN '$start_date' AND '$end_date' ORDER BY items.created_at DESC";
                        $result = $conn->query($sql);
                        $pawned_total_loan = 0;
                        $pawned_count = 0;

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $pawned_total_loan += $row['loan_amount'];
                                $pawned_count++;
                                
                                $displayName = $row['item_description'];
                                if (empty($displayName) || strlen($displayName) < 5) {
                                    if (!empty($row['brand'])) {
                                        $displayName = $row['brand'] . ' ' . $row['model'];
                                    } elseif (!empty($row['item_type'])) {
                                        $displayName = $row['item_type'];
                                        if (!empty($row['purity'])) {
                                            $displayName = $row['purity'] . ' ' . $displayName;
                                        }
                                    }
                                }

                                echo "<tr>";
                                echo "<td data-col='id'>" . sprintf("PT-%06d", $row['id']) . "</td>";
                                echo "<td data-col='customer'>" . htmlspecialchars($row['customer_name']) . "</td>";
                                echo "<td data-col='item'>";
                                if (!empty($displayName)) {
                                    echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . htmlspecialchars($displayName) . "</div>";
                                }
                                echo "<div style='font-size:0.85rem; color:#666;'>" . htmlspecialchars($row['category']) . "</div>";
                                echo "</td>";
                                echo "<td data-col='loan'>₱" . number_format($row['loan_amount'], 2) . "</td>";
                                echo "<td data-col='date'>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>";
                            echo "<td colspan='3' style='text-align: right;'>Subtotal (" . $pawned_count . " items):</td>";
                            echo "<td style='color: #0a3d0a;'>₱" . number_format($pawned_total_loan, 2) . "</td>";
                            echo "<td></td>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:15px; color:#777;'>No items pawned in this date range.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Sales Report (Filtered by Date Sold) -->
        <div class="section-card" id="section-sales">
            <div class="section-header">
                <h3><i class="fas fa-receipt"></i> Sales Report (In Range)</h3>
                <div class="section-actions">
                    <input type="text" id="searchSales" placeholder="Search..." aria-label="Search sales">
                    <select id="filterSales" aria-label="Filter column">
                        <option value="all">All</option>
                        <option value="item">Item</option>
                        <option value="loan">Loan</option>
                        <option value="sale">Sale</option>
                        <option value="profit">Profit</option>
                        <option value="date">Date</option>
                    </select>
                    <button type="button" class="print-btn" onclick="printSection('section-sales')"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
            <div class="table-scroll">
                <table class="customer-table" id="tableSales">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Loan Amount</th>
                            <th>Sale Price</th>
                            <th>Profit</th>
                            <th>Margin %</th>
                            <th>Date Sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM items WHERE status = 'sold' AND DATE(date_sold) BETWEEN '$start_date' AND '$end_date' ORDER BY date_sold DESC";
                        $result = $conn->query($sql);
                        $sales_profit_total = 0;
                        $sales_table_count = 0;
                        $sales_table_total_loan = 0;
                        $sales_table_total_sale = 0;

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $sale_price = $row['sale_price'];
                                $profit = $sale_price - $row['loan_amount'];
                                $margin = $sale_price > 0 ? ($profit / $sale_price) * 100 : 0;
                                
                                $sales_profit_total += $profit;
                                $sales_table_count++;
                                $sales_table_total_loan += $row['loan_amount'];
                                $sales_table_total_sale += $sale_price;

                                $displayName = $row['item_description'];
                                if (empty($displayName) || strlen($displayName) < 5) {
                                    if (!empty($row['brand'])) {
                                        $displayName = $row['brand'] . ' ' . $row['model'];
                                    } elseif (!empty($row['item_type'])) {
                                        $displayName = $row['item_type'];
                                        if (!empty($row['purity'])) {
                                            $displayName = $row['purity'] . ' ' . $displayName;
                                        }
                                    }
                                }

                                echo "<tr>";
                                echo "<td data-col='id'>" . sprintf("PT-%06d", $row['id']) . "</td>";
                                echo "<td data-col='item'>";
                                if (!empty($displayName)) {
                                    echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . htmlspecialchars($displayName) . "</div>";
                                }
                                echo "<div style='font-size:0.85rem; color:#666;'>" . htmlspecialchars($row['category']) . "</div>";
                                echo "</td>";
                                echo "<td data-col='loan'>₱" . number_format($row['loan_amount'], 2) . "</td>";
                                echo "<td data-col='sale'>₱" . number_format($sale_price, 2) . "</td>";
                                echo "<td data-col='profit' style='font-weight:bold; color:" . ($profit >= 0 ? '#2e7d32' : '#d32f2f') . ";'>₱" . number_format($profit, 2) . "</td>";
                                echo "<td style='color: " . ($profit >= 0 ? '#2e7d32' : '#d32f2f') . ";'>" . number_format($margin, 1) . "%</td>";
                                echo "<td data-col='date'>" . date('M d, Y', strtotime($row['date_sold'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>";
                            echo "<td colspan='2' style='text-align: right;'>Totals (" . $sales_table_count . " items):</td>";
                            echo "<td style='color: #0a3d0a;'>₱" . number_format($sales_table_total_loan, 2) . "</td>";
                            echo "<td style='color: #2e7d32;'>₱" . number_format($sales_table_total_sale, 2) . "</td>";
                            $avg_margin = $sales_table_total_sale > 0 ? ($sales_profit_total / $sales_table_total_sale) * 100 : 0;
                            echo "<td style='color: " . ($sales_profit_total >= 0 ? '#2e7d32' : '#d32f2f') . ";'>₱" . number_format($sales_profit_total, 2) . "</td>";
                            echo "<td style='color: " . ($sales_profit_total >= 0 ? '#2e7d32' : '#d32f2f') . ";'>" . number_format($avg_margin, 1) . "%</td>";
                            echo "<td></td>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding:15px; color:#777;'>No sales in this date range.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Redeemed Items (Filtered by Transaction Date) -->
        <div class="section-card" id="section-redeemed">
            <div class="section-header">
                <h3><i class="fas fa-hand-holding-usd"></i> Redeemed Items (In Range)</h3>
                <div class="section-actions">
                    <input type="text" id="searchRedeemed" placeholder="Search..." aria-label="Search redeemed items">
                    <select id="filterRedeemed" aria-label="Filter column">
                        <option value="all">All</option>
                        <option value="customer">Customer</option>
                        <option value="item">Item</option>
                        <option value="loan">Loan</option>
                        <option value="date">Date</option>
                    </select>
                    <button type="button" class="print-btn" onclick="printSection('section-redeemed')"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
            <div class="table-scroll">
                <table class="customer-table" id="tableRedeemed">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Customer</th>
                            <th>Item Name</th>
                            <th>Loan Amount</th>
                            <th>Date Redeemed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name, transactions.transaction_date FROM items JOIN customers ON items.customer_id = customers.id LEFT JOIN transactions ON items.id = transactions.item_id AND transactions.transaction_type = 'redemption' WHERE items.status = 'redeemed' AND DATE(COALESCE(transactions.transaction_date, items.created_at)) BETWEEN '$start_date' AND '$end_date' GROUP BY items.id ORDER BY transactions.transaction_date DESC";
                        $result = $conn->query($sql);
                        $redeemed_total_loan = 0;
                        $redeemed_table_count = 0;

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $redeemed_total_loan += $row['loan_amount'];
                                $redeemed_table_count++;
                                
                                $displayName = $row['item_description'];
                                if (empty($displayName) || strlen($displayName) < 5) {
                                    if (!empty($row['brand'])) {
                                        $displayName = $row['brand'] . ' ' . $row['model'];
                                    } elseif (!empty($row['item_type'])) {
                                        $displayName = $row['item_type'];
                                        if (!empty($row['purity'])) {
                                            $displayName = $row['purity'] . ' ' . $displayName;
                                        }
                                    }
                                }

                                echo "<tr>";
                                echo "<td data-col='id'>" . sprintf("PT-%06d", $row['id']) . "</td>";
                                echo "<td data-col='customer'>" . htmlspecialchars($row['customer_name']) . "</td>";
                                echo "<td data-col='item'>";
                                if (!empty($displayName)) {
                                    echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . htmlspecialchars($displayName) . "</div>";
                                }
                                echo "<div style='font-size:0.85rem; color:#666;'>" . htmlspecialchars($row['category']) . "</div>";
                                echo "</td>";
                                echo "<td data-col='loan'>₱" . number_format($row['loan_amount'], 2) . "</td>";
                                echo "<td data-col='date'>" . date('M d, Y', strtotime($row['transaction_date'] ?? $row['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>";
                            echo "<td colspan='3' style='text-align: right;'>Subtotal (" . $redeemed_table_count . " items):</td>";
                            echo "<td style='color: #1565c0;'>₱" . number_format($redeemed_total_loan, 2) . "</td>";
                            echo "<td></td>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:15px; color:#777;'>No items redeemed in this date range.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Walk-in Customers Report (In Range) -->
        <div class="section-card" id="section-walkin">
            <div class="section-header">
                <h3><i class="fas fa-store"></i> Walk-in Customers (In Range)</h3>
                <div class="section-actions">
                    <input type="text" id="searchWalkin" placeholder="Search..." aria-label="Search walk-in customers">
                    <select id="filterWalkin" aria-label="Filter column">
                        <option value="all">All</option>
                        <option value="name">Name</option>
                        <option value="contact">Contact</option>
                        <option value="date">Date</option>
                    </select>
                    <button type="button" class="print-btn" onclick="printSection('section-walkin')"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
            <div class="table-scroll">
                <table class="customer-table" id="tableWalkin">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM customers WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND registration_source = 'walk_in' ORDER BY created_at DESC";
                        $result = $conn->query($sql);
                        $walkin_table_count = 0;

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $walkin_table_count++;
                                echo "<tr>";
                                echo "<td data-col='id'>" . htmlspecialchars($row['customer_code']) . "</td>";
                                echo "<td data-col='name'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                                echo "<td data-col='contact'>" . htmlspecialchars($row['contact_number']) . "</td>";
                                echo "<td data-col='email'>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td data-col='date'>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>";
                            echo "<td colspan='4' style='text-align: right;'>Total Walk-in Customers:</td>";
                            echo "<td>" . $walkin_table_count . "</td>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:15px; color:#777;'>No walk-in customers registered in this date range.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. Online Customers Report (In Range) -->
        <div class="section-card" id="section-online">
            <div class="section-header">
                <h3><i class="fas fa-globe"></i> Online Customers (In Range)</h3>
                <div class="section-actions">
                    <input type="text" id="searchOnline" placeholder="Search..." aria-label="Search online customers">
                    <select id="filterOnline" aria-label="Filter column">
                        <option value="all">All</option>
                        <option value="name">Name</option>
                        <option value="email">Email</option>
                        <option value="verified">Verified</option>
                        <option value="date">Date</option>
                    </select>
                    <button type="button" class="print-btn" onclick="printSection('section-online')"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>
            <div class="table-scroll">
                <table class="customer-table" id="tableOnline">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Verified</th>
                            <th>Date Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM customers WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND registration_source = 'online' ORDER BY created_at DESC";
                        $result = $conn->query($sql);
                        $online_table_count = 0;
                        $verified_table_count = 0;

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $online_table_count++;
                                if ($row['is_verified']) {
                                    $verified_table_count++;
                                }
                                echo "<tr>";
                                echo "<td data-col='id'>" . htmlspecialchars($row['customer_code']) . "</td>";
                                echo "<td data-col='name'>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
                                echo "<td data-col='email'>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td data-col='verified'>";
                                if ($row['is_verified']) {
                                    echo "<span style='background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem;'><i class='fas fa-check-circle'></i> Verified</span>";
                                } else {
                                    echo "<span style='background: #ffebee; color: #d32f2f; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem;'><i class='fas fa-times-circle'></i> Pending</span>";
                                }
                                echo "</td>";
                                echo "<td data-col='date'>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                                echo "</tr>";
                            }
                            echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>";
                            echo "<td colspan='3' style='text-align: right;'>Total Online Customers:</td>";
                            echo "<td>" . $verified_table_count . " verified</td>";
                            echo "<td>" . $online_table_count . "</td>";
                            echo "</tr>";
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding:15px; color:#777;'>No online customers registered in this date range.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    </div>

    <script>
    function attachFilter(inputId, tableId, selectId) {
        const input = document.getElementById(inputId);
        const table = document.getElementById(tableId);
        const selector = selectId ? document.getElementById(selectId) : null;
        if (!input || !table) return;

        function applyFilter() {
            const needle = input.value.toLowerCase();
            const columnKey = selector ? selector.value : 'all';
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td'));
                let match = false;
                if (columnKey === 'all') {
                    match = cells.some(td => td.textContent.toLowerCase().includes(needle));
                } else {
                    const target = row.querySelector(`[data-col="${columnKey}"]`);
                    match = target ? target.textContent.toLowerCase().includes(needle) : false;
                }
                row.style.display = match ? '' : 'none';
            });
        }

        input.addEventListener('input', applyFilter);
        if (selector) selector.addEventListener('change', applyFilter);
    }

    function printSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (!section) return;
        const w = window.open('', '_blank');
        const styles = document.querySelectorAll('link[rel="stylesheet"], style');
        w.document.write('<html><head><title>Print</title>');
        styles.forEach(s => { w.document.write(s.outerHTML); });
        w.document.write('</head><body>');
        w.document.write(section.outerHTML);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        w.print();
        w.close();
    }

    attachFilter('searchPawned', 'tablePawned', 'filterPawned');
    attachFilter('searchSales', 'tableSales', 'filterSales');
    attachFilter('searchRedeemed', 'tableRedeemed', 'filterRedeemed');
    attachFilter('searchWalkin', 'tableWalkin', 'filterWalkin');
    attachFilter('searchOnline', 'tableOnline', 'filterOnline');
    </script>

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