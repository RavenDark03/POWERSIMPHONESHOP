<?php
session_start();
include 'includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: login.php');
    exit();
}

$customer_id = intval($_SESSION['id']);

$stmt = $conn->prepare('SELECT * FROM customers WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

$items_stmt = $conn->prepare('SELECT * FROM items WHERE customer_id = ? ORDER BY created_at DESC');
$items_stmt->bind_param('i', $customer_id);
$items_stmt->execute();
$items = $items_stmt->get_result();
$items_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Customer Portal</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .container { max-width: 980px; margin: 2rem auto; padding: 0 1rem; }
        .grid { display: flex; gap: 1rem; flex-wrap: wrap; }
        .card { background:#fff; border:1px solid #e6e6e6; border-radius:8px; padding:1rem; flex:1 1 320px; }
        table { width:100%; border-collapse: collapse; }
        table th, table td { padding:8px; border-bottom:1px solid #f0f0f0; text-align:left; }
        .muted { color:#666; font-size:0.9rem; }
        .actions { display:flex; gap:8px; }
        .btn { padding:8px 12px; background:#d4af37; color:#fff; border-radius:4px; text-decoration:none; }
    </style>
</head>
<body>
    <div class="container">
        <header style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <h2>Customer Portal</h2>
            <nav class="muted">
                <a href="index.php">Home</a> &nbsp;|&nbsp; <a href="dashboard.php">Dashboard</a> &nbsp;|&nbsp; <a href="logout.php">Logout</a>
            </nav>
        </header>

        <div class="grid">
            <div class="card">
                <h3>Profile</h3>
                <p><strong><?php echo htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')); ?></strong></p>
                <p class="muted">Customer Code: <?php echo htmlspecialchars($customer['customer_code'] ?? ''); ?></p>
                <p class="muted">Username: <?php echo htmlspecialchars($customer['username'] ?? ''); ?></p>
                <p class="muted">Email: <?php echo htmlspecialchars($customer['email'] ?? ''); ?></p>
                <p class="muted">Contact: <?php echo htmlspecialchars($customer['contact_number'] ?? ''); ?></p>
                <p class="muted">Birthdate: <?php echo htmlspecialchars($customer['birthdate'] ?? ''); ?></p>
                <div style="margin-top:0.75rem;"><a class="btn" href="logout.php">Logout</a> &nbsp; <a class="btn" href="index.php">Shop</a></div>
            </div>

            <div class="card" style="flex:2 1 600px;">
                <h3>Your Pawns</h3>
                <?php if ($items && $items->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr><th>Item</th><th>Loan</th><th>Status</th><th>Due Date</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $items->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                                    <td><?php echo number_format($row['loan_amount'],2); ?></td>
                                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                                    <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                    <td class="actions">
                                        <a class="btn" href="admin/receipt.php?item_id=<?php echo $row['id']; ?>" target="_blank">Receipt</a>
                                        <a class="btn" href="view_pawn.php?id=<?php echo $row['id']; ?>">Details</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="muted">You have no pawned items yet. <a href="index.php">Start pawning</a>.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <script>
    // Stagger card animations when redirected after login
    (function(){
        const params = new URLSearchParams(window.location.search);
        if (!params.get('login')) return;
        const cards = document.querySelectorAll('.grid .card');
        let base = 80;
        cards.forEach((c, i) => {
            c.style.opacity = 0;
            c.style.transform = 'translateY(18px)';
            c.style.filter = 'blur(6px)';
            c.style.animation = 'fadeUp 760ms cubic-bezier(0.2,0.8,0.2,1) both';
            c.style.animationDelay = (base * (i+1)) + 'ms';
        });
        params.delete('login');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    })();
    </script>
</body>
</html>
