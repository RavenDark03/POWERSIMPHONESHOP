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

// KPI Queries

// 1. Active Loans (Snapshot - Current) -> Filter by "Pawned" status regardless of date, OR filtered by date?
// Usually Active Loans means "Currently Sitting in the Vault". 
// But "New Loans" would be filtered by date.
// Let's do "Current Active Loans" as a snapshot.
$active_loans_sql = "SELECT COUNT(*) as count, SUM(loan_amount) as total_value FROM items WHERE status = 'pawned'";
$active_loans_result = $conn->query($active_loans_sql);
$active_loans_data = $active_loans_result->fetch_assoc();

// 2. Capital Out (New Loans in Date Range)
// Items CREATED/PAWNED within range
$capital_out_sql = "SELECT SUM(loan_amount) as total FROM items WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$capital_out_result = $conn->query($capital_out_sql);
$capital_out_data = $capital_out_result->fetch_assoc();
$capital_out = $capital_out_data['total'] ?? 0;

// 3. Sales & Profit (Sold in Date Range)
// Using date_sold if available, otherwise fallback to transaction logic or created_at (if just testing)
// We added date_sold column.
$sales_sql = "SELECT SUM(sale_price) as total_sales, SUM(sale_price - loan_amount) as total_profit FROM items WHERE status = 'sold' AND DATE(date_sold) BETWEEN '$start_date' AND '$end_date'";
$sales_result = $conn->query($sales_sql);
$sales_data = $sales_result->fetch_assoc();
$total_sales = $sales_data['total_sales'] ?? 0;
$total_profit = $sales_data['total_profit'] ?? 0;

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
                    <li><a href="pawning.php">Pawning</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="reports.php" style="color: #d4af37;">Reports</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

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
            <!-- Card 1: New Loans (Capital Out) -->
            <div class="kpi-card">
                <div class="kpi-title">New Loans Given</div>
                <div class="kpi-value">₱<?php echo number_format($capital_out, 2); ?></div>
                <div class="kpi-sub"><?php echo date('M d', strtotime($start_date)) . ' - ' . date('M d', strtotime($end_date)); ?></div>
            </div>

            <!-- Card 2: Total Sales -->
            <div class="kpi-card">
                <div class="kpi-title">Total Sales Revenue</div>
                <div class="kpi-value" style="color: #2e7d32;">₱<?php echo number_format($total_sales, 2); ?></div>
                <div class="kpi-sub"><?php echo date('M d', strtotime($start_date)) . ' - ' . date('M d', strtotime($end_date)); ?></div>
            </div>
            
             <!-- Card 3: Gross Profit -->
             <div class="kpi-card">
                <div class="kpi-title">Gross Profit</div>
                <div class="kpi-value" style="color: <?php echo $total_profit >= 0 ? '#2e7d32' : '#d32f2f'; ?>;">
                    ₱<?php echo number_format($total_profit, 2); ?>
                </div>
                <div class="kpi-sub">Sales - Loan Value</div>
            </div>

            <!-- Card 4: Active Portfolio (Snapshot) -->
            <div class="kpi-card" style="background: #fffcf0; border-color: #f0e68c;">
                <div class="kpi-title">Active Portfolio (Current)</div>
                <div class="kpi-value" style="color: #b58900;"><?php echo $active_loans_data['count']; ?> Items</div>
                <div class="kpi-sub">Valued at ₱<?php echo number_format($active_loans_data['total_value'], 2); ?></div>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

        <!-- Dynamic Tables -->

        <!-- 1. Pawned Items (Filtered by Created At) -->
        <h3 style="margin-bottom: 15px; color: #0a3d0a;">Pawned Items (In Range)</h3>
        <table class="customer-table" style="margin-bottom: 40px;">
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

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        
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
                        echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                        echo "<td>" . $row['customer_name'] . "</td>";
                        echo "<td>";
                        if (!empty($displayName)) {
                            echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . $displayName . "</div>";
                        }
                        echo "<div style='font-size:0.85rem; color:#666;'>" . $row['category'] . "</div>";
                        echo "</td>";
                        echo "<td>" . number_format($row['loan_amount'], 2) . "</td>";
                        echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:15px; color:#777;'>No items pawned in this date range.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- 2. Sales Report (Filtered by Date Sold) -->
        <h3 style="margin-bottom: 15px; color: #0a3d0a;">Sales Report (In Range)</h3>
        <table class="customer-table" style="margin-bottom: 40px;">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Loan Amount</th>
                    <th>Sale Price</th>
                    <th>Profit</th>
                    <th>Date Sold</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Use date_sold column
                $sql = "SELECT * FROM items WHERE status = 'sold' AND DATE(date_sold) BETWEEN '$start_date' AND '$end_date' ORDER BY date_sold DESC";
                $result = $conn->query($sql);
                $period_profit = 0;

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $sale_price = $row['sale_price'];
                        $profit = $sale_price - $row['loan_amount'];
                        $period_profit += $profit;

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
                         echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                        echo "<td>";
                        if (!empty($displayName)) {
                            echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . $displayName . "</div>";
                        }
                        echo "<div style='font-size:0.85rem; color:#666;'>" . $row['category'] . "</div>";
                        echo "</td>";
                        echo "<td>" . number_format($row['loan_amount'], 2) . "</td>";
                        echo "<td>" . number_format($sale_price, 2) . "</td>";
                        echo "<td style='font-weight:bold; color:" . ($profit >= 0 ? 'green' : 'red') . ";'>" . number_format($profit, 2) . "</td>";
                         echo "<td>" . date('M d, Y', strtotime($row['date_sold'])) . "</td>";
                        echo "</tr>";
                    }
                     // Summary
                     echo "<tr style='background-color: #f8f9fa; font-weight: bold;'>";
                     echo "<td colspan='4' style='text-align: right;'>Period Profit:</td>";
                     echo "<td colspan='2' style='color: green;'>" . number_format($period_profit, 2) . "</td>";
                     echo "</tr>";
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding:15px; color:#777;'>No sales in this date range.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <!-- 3. Redeemed Items (Filtered by Transaction Date) -->
        <h3 style="margin-bottom: 15px; color: #0a3d0a;">Redeemed Items (In Range)</h3>
        <table class="customer-table">
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
                // Redeemed items rely on transaction table or we filter by status date?
                // Best to use transactions table for reliable date filtering of ACTIONS.
                $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name, transactions.transaction_date FROM items JOIN customers ON items.customer_id = customers.id JOIN transactions ON items.id = transactions.item_id WHERE items.status = 'redeemed' AND transactions.transaction_type = 'redemption' AND DATE(transactions.transaction_date) BETWEEN '$start_date' AND '$end_date'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {

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
                        echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                        echo "<td>" . $row['customer_name'] . "</td>";
                        echo "<td>";
                        if (!empty($displayName)) {
                            echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . $displayName . "</div>";
                        }
                        echo "<div style='font-size:0.85rem; color:#666;'>" . $row['category'] . "</div>";
                        echo "</td>";
                        echo "<td>" . number_format($row['loan_amount'], 2) . "</td>";
                        echo "<td>" . date('M d, Y', strtotime($row['transaction_date'])) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding:15px; color:#777;'>No items redeemed in this date range.</td></tr>";
                }
                ?>
            </tbody>
        </table>

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