<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Fetch soft-deleted customers
$custSql = "SELECT id, customer_code, first_name, last_name, email, contact_number, deleted_at
            FROM customers
            WHERE is_deleted = 1
            ORDER BY COALESCE(deleted_at, created_at) DESC";
$custResult = $conn->query($custSql);

// Fetch archived items / pawn tickets
$itemSql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name
            FROM items
            LEFT JOIN customers ON items.customer_id = customers.id
            WHERE items.status = 'archived'
            ORDER BY COALESCE(items.archived_at, items.date_sold, items.created_at) DESC";
$itemResult = $conn->query($itemSql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Records</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .archived-wrapper { display: grid; gap: 20px; }
        .card { background: #fff; border-radius: 10px; box-shadow: 0 6px 18px rgba(0,0,0,0.05); padding: 18px; position: relative; }
        .card-header { position: sticky; top: 0; background: #fff; padding-bottom: 12px; margin-bottom: 8px; z-index: 2; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .card-title { margin: 0; color: #0a3d0a; display: flex; align-items: center; gap: 8px; }
        .card-controls { display: flex; gap: 8px; align-items: center; }
        .card-controls input { padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; min-width: 160px; }
        .card-controls select { padding: 8px 10px; border: 1px solid #ddd; border-radius: 6px; }
        table.archived-table { width: 100%; border-collapse: collapse; }
        table.archived-table th, table.archived-table td { padding: 10px 8px; text-align: left; border-bottom: 1px solid #f0f0f0; }
        table.archived-table th { font-weight: 700; color: #333; position: sticky; top: 0; background: #fff; z-index: 1; }
        table.archived-table td { color: #444; font-weight: 500; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.82rem; font-weight: 600; background: #f0f0f0; color: #444; }
        .table-scroll { max-height: 360px; min-height: 220px; overflow: auto; }
        .btn-ghost { border: 1px solid #0a3d0a; color: #0a3d0a; padding: 6px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; background: #fff; }
        .btn-ghost:hover { background: #0a3d0a; color: #fff; }
    </style>
</head>
<body class="has-sidebar">
<?php include '../includes/sidebar_nav.php'; ?>

<header><div class="container"></div></header>

<div class="main-content-wrapper">
    <div class="container main-content">
        <div class="page-hero">
            <div>
                <h2 class="page-hero-title"><i class="fas fa-box-archive"></i> Archived Records</h2>
                <p class="page-hero-subtitle">Review soft-deleted customers and archived pawn tickets/items.</p>
            </div>
        </div>

        <div class="archived-wrapper">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-slash"></i> Archived Customers</h3>
                    <div class="card-controls">
                        <input type="text" id="searchCustomers" placeholder="Search customers..." aria-label="Search archived customers">
                        <select id="filterCustomers" aria-label="Filter customers">
                            <option value="all">All columns</option>
                            <option value="customer_code">Customer Code</option>
                            <option value="name">Name</option>
                            <option value="email">Email</option>
                            <option value="contact">Contact</option>
                        </select>
                    </div>
                </div>
                <div class="table-scroll">
                    <table class="archived-table" id="tableCustomers">
                        <thead>
                            <tr>
                                <th>Customer Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Deleted At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($custResult && $custResult->num_rows > 0): ?>
                                <?php while($c = $custResult->fetch_assoc()): ?>
                                    <tr>
                                        <td data-col="customer_code"><?php echo htmlspecialchars($c['customer_code']); ?></td>
                                        <td data-col="name"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></td>
                                        <td data-col="email"><?php echo htmlspecialchars($c['email']); ?></td>
                                        <td data-col="contact"><?php echo htmlspecialchars($c['contact_number']); ?></td>
                                        <td><?php echo htmlspecialchars($c['deleted_at'] ? date('M d, Y h:i A', strtotime($c['deleted_at'])) : '-'); ?></td>
                                        <td>
                                            <a class="btn-ghost" href="delete_customer.php?id=<?php echo $c['id']; ?>&restore=1" onclick="return confirm('Restore this customer?');"><i class="fas fa-undo"></i> Revert</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center; padding: 12px; color:#777;">No archived customers.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-box-archive"></i> Archived Inventory</h3>
                    <div class="card-controls">
                        <input type="text" id="searchInventory" placeholder="Search inventory..." aria-label="Search archived inventory">
                    </div>
                </div>
                <div class="table-scroll">
                    <table class="archived-table" id="tableInventory">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Loan Amount</th>
                                <th>Archived At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($itemResult && $itemResult->num_rows > 0): ?>
                                <?php while($i = $itemResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo sprintf('PT-%06d', $i['id']); ?></td>
                                        <td><?php echo htmlspecialchars($i['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($i['item_description']); ?></td>
                                        <td>₱<?php echo number_format($i['loan_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($i['archived_at'] ? date('M d, Y h:i A', strtotime($i['archived_at'])) : '-'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding: 12px; color:#777;">No archived inventory items.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-hand-holding-usd"></i> Archived Pawn Tickets</h3>
                    <div class="card-controls">
                        <input type="text" id="searchTickets" placeholder="Search tickets..." aria-label="Search archived pawn tickets">
                    </div>
                </div>
                <div class="table-scroll">
                    <table class="archived-table" id="tableTickets">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Archived At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($itemResult && $itemResult->num_rows > 0): ?>
                                <?php $itemResult->data_seek(0); while($p = $itemResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo sprintf('PT-%06d', $p['id']); ?></td>
                                        <td><?php echo htmlspecialchars($p['customer_name']); ?></td>
                                        <td><span class="pill">Archived</span></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($p['due_date']))); ?></td>
                                        <td><?php echo htmlspecialchars($p['archived_at'] ? date('M d, Y h:i A', strtotime($p['archived_at'])) : '-'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center; padding: 12px; color:#777;">No archived pawn tickets.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
    <script>
    // Simple client-side search/filter for each table
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

    attachFilter('searchCustomers', 'tableCustomers', 'filterCustomers');
    attachFilter('searchInventory', 'tableInventory');
    attachFilter('searchTickets', 'tableTickets');
    </script>
</div>
</body>
</html>
