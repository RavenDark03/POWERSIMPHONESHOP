<?php
session_start();

// TODO: include your DB connection file
// Example: include '../includes/connection.php';
include '../includes/connection.php';

// Role check: allow manager/admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['manager','admin'])) {
    header('Location: ../login.php');
    exit();
}

// --- Top Cards ---
$cashOnHand = 0.0;
$disbursedToday = 0.0;
$collectionsToday = 0.0;

// Total Cash in Vault
$cashSql = "SELECT COALESCE(SUM(amount), 0) AS total_cash FROM cash_on_hand";
if ($res = $conn->query($cashSql)) {
    $row = $res->fetch_assoc();
    $cashOnHand = (float)$row['total_cash'];
    $res->close();
}

// Total Disbursed Today (principal for pawn transactions today)
$disbursedSql = "SELECT COALESCE(SUM(i.loan_amount),0) AS total_disbursed
                 FROM transactions t
                 JOIN items i ON t.item_id = i.id
                 WHERE t.transaction_type = 'pawn' AND DATE(t.transaction_date) = CURDATE()";
if ($res = $conn->query($disbursedSql)) {
    $row = $res->fetch_assoc();
    $disbursedToday = (float)$row['total_disbursed'];
    $res->close();
}

// Total Collections Today (payments received today)
// Adjust table/column names if your payments table differs.
$collectSql = "SELECT COALESCE(SUM(p.amount),0) AS total_collections
               FROM payments p
               WHERE DATE(p.received_at) = CURDATE()";
if ($res = $conn->query($collectSql)) {
    $row = $res->fetch_assoc();
    $collectionsToday = (float)$row['total_collections'];
    $res->close();
}

// --- Inventory Summary (Pie: Jewelry vs Gadgets vs Appliances) ---
$inventoryCounts = ['Jewelry' => 0, 'Gadgets' => 0, 'Appliances' => 0];
$invSql = "SELECT category, COUNT(*) AS cnt FROM items WHERE status != 'archived' GROUP BY category";
if ($res = $conn->query($invSql)) {
    while ($row = $res->fetch_assoc()) {
        $cat = strtolower($row['category']);
        if (strpos($cat, 'jewel') !== false) $inventoryCounts['Jewelry'] += (int)$row['cnt'];
        elseif (strpos($cat, 'gadg') !== false) $inventoryCounts['Gadgets'] += (int)$row['cnt'];
        elseif (strpos($cat, 'appli') !== false) $inventoryCounts['Appliances'] += (int)$row['cnt'];
    }
    $res->close();
}

// --- Monthly Profit & Loss (last 6 months) ---
$months = [];
$incomeSeries = [];
$expenseSeries = [];

$incomeSql = "SELECT DATE_FORMAT(received_at, '%Y-%m') AS ym, SUM(amount) AS total
              FROM payments
              WHERE received_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              GROUP BY ym";
$expenseSql = "SELECT DATE_FORMAT(incurred_at, '%Y-%m') AS ym, SUM(amount) AS total
               FROM expenses
               WHERE incurred_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
               GROUP BY ym";

$incomeData = [];
$expenseData = [];
if ($res = $conn->query($incomeSql)) {
    while ($row = $res->fetch_assoc()) { $incomeData[$row['ym']] = (float)$row['total']; }
    $res->close();
}
if ($res = $conn->query($expenseSql)) {
    while ($row = $res->fetch_assoc()) { $expenseData[$row['ym']] = (float)$row['total']; }
    $res->close();
}

// Build month labels (chronological for last 6 months including current)
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-{$i} month"));
    $months[] = $ym;
    $incomeSeries[] = $incomeData[$ym] ?? 0;
    $expenseSeries[] = $expenseData[$ym] ?? 0;
}

// --- Expiring Pawns (due within 7 days, still pawned) ---
$expiringRows = [];
$expSql = "SELECT i.id, i.item_description, i.due_date, i.loan_amount,
                  CONCAT(c.first_name, ' ', c.last_name) AS customer_name
           FROM items i
           JOIN customers c ON i.customer_id = c.id
           WHERE i.status = 'pawned' AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
           ORDER BY i.due_date ASC";
if ($res = $conn->query($expSql)) {
    while ($row = $res->fetch_assoc()) { $expiringRows[] = $row; }
    $res->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        body { background: #f7f8fb; }
        .card-metric { border: 0; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
        .card-metric .card-body { display: flex; justify-content: space-between; align-items: center; }
        .card-metric .icon { width: 48px; height: 48px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; color: #fff; }
        .chart-card { border: 0; box-shadow: 0 4px 14px rgba(0,0,0,0.06); }
        .table-expiring tbody tr td { vertical-align: middle; }
    </style>
</head>
<body class="has-sidebar">
<?php include '../includes/sidebar_nav.php'; ?>

<div class="main-content-wrapper">
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Manager Dashboard</h2>
            <small class="text-muted">Overview of cash, disbursements, collections, and inventory.</small>
        </div>
    </div>

    <!-- Top Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-metric">
                <div class="card-body">
                    <div>
                        <div class="text-muted text-uppercase small">Cash in Vault</div>
                        <div class="h4 mb-0">₱<?php echo number_format($cashOnHand, 2); ?></div>
                    </div>
                    <div class="icon" style="background:#0a7f4f;"><i class="fas fa-vault"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric">
                <div class="card-body">
                    <div>
                        <div class="text-muted text-uppercase small">Disbursed Today</div>
                        <div class="h4 mb-0">₱<?php echo number_format($disbursedToday, 2); ?></div>
                    </div>
                    <div class="icon" style="background:#d4af37;"><i class="fas fa-hand-holding-usd"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric">
                <div class="card-body">
                    <div>
                        <div class="text-muted text-uppercase small">Collections Today</div>
                        <div class="h4 mb-0">₱<?php echo number_format($collectionsToday, 2); ?></div>
                    </div>
                    <div class="icon" style="background:#0a6abf;"><i class="fas fa-cash-register"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card chart-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Inventory Summary</h6>
                        <i class="fas fa-chart-pie text-muted"></i>
                    </div>
                    <canvas id="inventoryPie"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card chart-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Monthly Profit & Loss</h6>
                        <i class="fas fa-chart-line text-muted"></i>
                    </div>
                    <canvas id="plLine"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiring Pawns Table -->
    <div class="card chart-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Expiring Pawns (next 7 days)</h6>
                <i class="fas fa-hourglass-half text-muted"></i>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-expiring mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket #</th>
                            <th>Customer</th>
                            <th>Item</th>
                            <th class="text-end">Loan Amount</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($expiringRows) > 0): ?>
                            <?php foreach ($expiringRows as $row): ?>
                                <tr>
                                    <td><?php echo sprintf('PT-%06d', $row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                                    <td class="text-end">₱<?php echo number_format($row['loan_amount'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No expiring pawns in the next 7 days.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const inventoryData = <?php echo json_encode(array_values($inventoryCounts)); ?>;
const inventoryLabels = <?php echo json_encode(array_keys($inventoryCounts)); ?>;

new Chart(document.getElementById('inventoryPie'), {
    type: 'pie',
    data: {
        labels: inventoryLabels,
        datasets: [{
            data: inventoryData,
            backgroundColor: ['#d4af37', '#0a6abf', '#1f7a3b']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

const plLabels = <?php echo json_encode($months); ?>;
const incomeSeries = <?php echo json_encode($incomeSeries); ?>;
const expenseSeries = <?php echo json_encode($expenseSeries); ?>;

new Chart(document.getElementById('plLine'), {
    type: 'line',
    data: {
        labels: plLabels,
        datasets: [
            { label: 'Income', data: incomeSeries, borderColor: '#1f7a3b', backgroundColor: 'rgba(31,122,59,0.08)', tension: 0.25, fill: true },
            { label: 'Expenses', data: expenseSeries, borderColor: '#c0392b', backgroundColor: 'rgba(192,57,43,0.08)', tension: 0.25, fill: true }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
