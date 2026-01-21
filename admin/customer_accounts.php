<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin','manager'])) {
    header('Location: ../login.php');
    exit();
}

$accounts = [];
$sql = "SELECT id, customer_code, first_name, last_name, email, username, is_verified, created_at
        FROM customers
        WHERE email IS NOT NULL AND email <> ''
        ORDER BY created_at DESC";
if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $accounts[] = $row;
    }
    $res->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --primary: #0c3a11;
            --primary-dark: #082d0c;
            --accent: #c9a227;
            --surface: #ffffff;
            --muted: #6c757d;
            --soft-bg: #f5f6f8;
        }
        body { background: var(--soft-bg); }
        .page-header { margin-bottom: 18px; }
        .card-plate { border: 1px solid #e7e9ef; box-shadow: 0 8px 22px rgba(0,0,0,0.05); border-radius: 12px; }
        .card-plate .card-body { padding: 16px; }
        .table { border-radius: 10px; overflow: hidden; }
        .table thead th { font-weight: 700; color: #fff; background: var(--primary); border-bottom: 0; text-transform: uppercase; font-size: 13px; letter-spacing: 0.3px; }
        .table tbody td { border-color: #eef0f4; color: #1f1f1f; }
        .table-hover tbody tr:hover { background: #f0f5ef; }
        .badge-verified { background: #e6f5ea; color: #0f6b2f; font-weight: 700; }
        .badge-pending { background: #fff4e5; color: #a35d00; font-weight: 700; }
        .btn-credentials { background: linear-gradient(135deg, #e5c454, var(--accent)); border: none; color: #1f1a08; font-weight: 700; min-width: auto; padding: 8px 12px; border-radius: 8px; box-shadow: 0 6px 16px rgba(0,0,0,0.08); white-space: nowrap; }
        .btn-credentials:hover { filter: brightness(0.95); color: #1f1a08; }
        .table > :not(caption) > * > * { padding: 14px 12px; }
        h3 { color: var(--primary-dark); }
    </style>
</head>
<body class="has-sidebar">
<?php include '../includes/sidebar_nav.php'; ?>

<div class="main-content-wrapper">
    <div class="container main-content py-4">
        <div class="page-hero">
            <div>
                <h2 class="page-hero-title"><i class="fas fa-id-badge"></i> Customer Accounts</h2>
                <p class="page-hero-subtitle">Manage customer login credentials and verification status.</p>
            </div>
            <div class="page-hero-actions">
                <div class="badge bg-light text-dark" style="padding:10px 14px; border:1px solid #e5e7eb; border-radius:10px; font-weight:600;">
                    Total Accounts: <?php echo count($accounts); ?>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Account credentials updated successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card card-plate mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                    <div class="flex-grow-1">
                        <input type="text" id="accountSearch" class="form-control" placeholder="Search by code, name, email, or username...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="accountsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Customer Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($accounts) > 0): ?>
                                <?php foreach ($accounts as $acct): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($acct['customer_code']); ?></td>
                                        <td><?php echo htmlspecialchars($acct['first_name'] . ' ' . $acct['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($acct['email']); ?></td>
                                        <td><?php echo htmlspecialchars($acct['username'] ?? ''); ?></td>
                                        <td>
                                            <?php if ((int)$acct['is_verified'] === 1): ?>
                                                <span class="badge badge-verified">Verified</span>
                                            <?php else: ?>
                                                <span class="badge badge-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($acct['created_at'])); ?></td>
                                        <td class="text-end">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-credentials btn-edit-credentials"
                                                data-id="<?php echo (int)$acct['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($acct['username'] ?? '', ENT_QUOTES); ?>"
                                                data-name="<?php echo htmlspecialchars($acct['first_name'] . ' ' . $acct['last_name'], ENT_QUOTES); ?>">
                                                <i class="fas fa-user-pen"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">No customer accounts found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Credentials Modal -->
<div class="modal fade" id="credentialsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="customer_account_update.php">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Credentials</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_credentials">
                    <input type="hidden" name="id" id="credCustomerId">
                    <div class="mb-3">
                        <label class="form-label text-muted">Customer</label>
                        <div id="credCustomerName" class="fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label for="credUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="credUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="credPassword" class="form-label">New Password <span class="text-muted fw-normal">(leave blank to keep current)</span></label>
                        <input type="password" class="form-control" id="credPassword" name="password" minlength="6" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label for="credPasswordConfirm" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="credPasswordConfirm" name="password_confirm" minlength="6" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const credModalEl = document.getElementById('credentialsModal');
const credModal = credModalEl ? new bootstrap.Modal(credModalEl) : null;

document.querySelectorAll('.btn-edit-credentials').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('credCustomerId').value = btn.dataset.id;
        document.getElementById('credCustomerName').textContent = btn.dataset.name || '';
        document.getElementById('credUsername').value = btn.dataset.username || '';
        document.getElementById('credPassword').value = '';
        document.getElementById('credPasswordConfirm').value = '';
        credModal && credModal.show();
    });
});

// Quick client-side filter for accounts
const accountSearch = document.getElementById('accountSearch');
const accountsTable = document.getElementById('accountsTable');
if (accountSearch && accountsTable) {
    const rows = Array.from(accountsTable.querySelectorAll('tbody tr'));
    accountSearch.addEventListener('input', () => {
        const term = accountSearch.value.toLowerCase();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
}
</script>
</body>
</html>
