<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Define renewal cooldown period in days
define('RENEWAL_COOLDOWN_DAYS', 20);

// Function to check if item can be renewed (20-day constraint)
function canRenew($conn, $item_id) {
    $sql = "SELECT created_at FROM transactions 
            WHERE item_id = ? AND transaction_type = 'renewal' 
            ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No previous renewal, check from pawn date
        return ['can_renew' => true, 'days_remaining' => 0, 'last_date' => null];
    }
    
    $row = $result->fetch_assoc();
    $last_renewal = strtotime($row['created_at']);
    $days_since = floor((time() - $last_renewal) / (24 * 60 * 60));
    $days_remaining = RENEWAL_COOLDOWN_DAYS - $days_since;
    
    return [
        'can_renew' => $days_remaining <= 0,
        'days_remaining' => max(0, $days_remaining),
        'last_date' => $row['created_at']
    ];
}

// Fetch all active pawned items
$sql = "SELECT i.*, 
        c.first_name, c.last_name, c.customer_code, c.contact_number,
        CONCAT(c.first_name, ' ', c.last_name) AS customer_name
        FROM items i 
        JOIN customers c ON i.customer_id = c.id 
        WHERE i.status = 'pawned' 
        ORDER BY i.due_date ASC";
$result = $conn->query($sql);

// Count stats
$totalActive = $result ? $result->num_rows : 0;
$overdueCount = 0;
$items_data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['renewal_status'] = canRenew($conn, $row['id']);
        if (strtotime($row['due_date']) < time()) {
            $overdueCount++;
        }
        $items_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawn Processing - Powersim</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .stat-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e6e6e6; }
        .stat-card h3 { margin: 0 0 5px; font-size: 2rem; font-weight: 700; }
        .stat-card p { margin: 0; color: #666; font-size: 0.85rem; }
        .stat-card.overdue { border-left: 4px solid #dc3545; }
        .stat-card.active { border-left: 4px solid #28a745; }
        
        .pawn-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 15px; overflow: hidden; border: 1px solid #e6e6e6; }
        .pawn-card.overdue { border-left: 4px solid #dc3545; }
        .pawn-card-header { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #e6e6e6; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .pawn-card-body { padding: 20px; }
        
        .customer-badge { background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .ticket-badge { background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .detail-item label { display: block; font-size: 0.75rem; text-transform: uppercase; color: #888; letter-spacing: 0.5px; margin-bottom: 3px; }
        .detail-item span { font-size: 0.95rem; font-weight: 500; color: #333; }
        
        .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .btn-redeem { background: #28a745; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-redeem:hover { background: #218838; color: #fff; }
        .btn-renew { background: #1565c0; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-renew:hover { background: #0d47a1; color: #fff; }
        .btn-renew:disabled, .btn-renew.disabled { background: #ccc; color: #666; cursor: not-allowed; }
        .btn-view { background: #f5f5f5; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-view:hover { background: #e8e8e8; color: #333; }
        
        .cooldown-notice { background: #fff3cd; color: #856404; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; }
        
        .overdue-badge { background: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .due-soon-badge { background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        
        .search-box { position: relative; }
        .search-box input { padding-left: 40px; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }
        
        .filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 20px; border: 1px solid #ddd; background: #fff; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; }
        .filter-tab:hover { border-color: #0a3d0a; }
        .filter-tab.active { background: #0a3d0a; color: #fff; border-color: #0a3d0a; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #888; }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; color: #ddd; }
        
        /* Modal styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: #fff; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 20px; border-bottom: 1px solid #e6e6e6; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #888; }
        .modal-body { padding: 20px; }
        .modal-footer { padding: 15px 20px; border-top: 1px solid #e6e6e6; display: flex; gap: 10px; justify-content: flex-end; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <div class="main-content-wrapper">
        <div class="container main-content py-4">
            <div class="page-hero">
                <div>
                    <h2 class="page-hero-title"><i class="fas fa-cash-register"></i> Pawn Processing</h2>
                    <p class="page-hero-subtitle">Process redemptions and renewals for active pawn tickets.</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card active">
                        <h3 style="color: #28a745;"><?php echo $totalActive; ?></h3>
                        <p><i class="fas fa-ticket-alt"></i> Active Pawn Tickets</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card overdue">
                        <h3 style="color: #dc3545;"><?php echo $overdueCount; ?></h3>
                        <p><i class="fas fa-exclamation-triangle"></i> Overdue Items</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <h3 style="color: #1565c0;"><?php echo RENEWAL_COOLDOWN_DAYS; ?></h3>
                        <p><i class="fas fa-clock"></i> Days Between Renewals</p>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search by ticket #, customer name, or item...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="filter-tabs justify-content-md-end">
                                <button class="filter-tab active" data-filter="all">All</button>
                                <button class="filter-tab" data-filter="overdue">Overdue</button>
                                <button class="filter-tab" data-filter="due-soon">Due Soon</button>
                                <button class="filter-tab" data-filter="can-renew">Can Renew</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pawn Items List -->
            <div id="pawnItemsList">
                <?php if (empty($items_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Active Pawn Tickets</h3>
                    <p>There are no active pawn tickets to process.</p>
                </div>
                <?php else: ?>
                
                <?php foreach ($items_data as $item): 
                    $isOverdue = strtotime($item['due_date']) < time();
                    $daysUntilDue = floor((strtotime($item['due_date']) - time()) / (24 * 60 * 60));
                    $isDueSoon = !$isOverdue && $daysUntilDue <= 7;
                    $canRenewItem = $item['renewal_status']['can_renew'];
                    
                    // Calculate redemption amount
                    $principal = (float)$item['loan_amount'];
                    $interest_rate = (float)$item['interest_rate'];
                    $created_ts = strtotime($item['created_at'] ?? 'now');
                    $months_elapsed = max(1, ceil((time() - $created_ts) / (30 * 24 * 60 * 60)));
                    $total_interest = round($principal * ($interest_rate / 100) * $months_elapsed, 2);
                    $redemption_amount = $principal + $total_interest + 5; // +5 service fee
                ?>
                <div class="pawn-card <?php echo $isOverdue ? 'overdue' : ''; ?>" 
                     data-search="<?php echo strtolower($item['ticket_number'] . ' ' . $item['customer_name'] . ' ' . $item['item_description'] . ' ' . $item['brand'] . ' ' . $item['model']); ?>"
                     data-overdue="<?php echo $isOverdue ? '1' : '0'; ?>"
                     data-due-soon="<?php echo $isDueSoon ? '1' : '0'; ?>"
                     data-can-renew="<?php echo $canRenewItem ? '1' : '0'; ?>">
                    <div class="pawn-card-header">
                        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <span class="ticket-badge"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars($item['ticket_number'] ?? 'PT-' . $item['id']); ?></span>
                            <span class="customer-badge"><i class="fas fa-user"></i> <?php echo htmlspecialchars($item['customer_name']); ?></span>
                            <?php if ($isOverdue): ?>
                            <span class="overdue-badge"><i class="fas fa-exclamation-circle"></i> Overdue</span>
                            <?php elseif ($isDueSoon): ?>
                            <span class="due-soon-badge"><i class="fas fa-clock"></i> Due in <?php echo $daysUntilDue; ?> day(s)</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span style="font-weight: 700; font-size: 1.1rem; color: #2e7d32;">₱<?php echo number_format($item['loan_amount'], 2); ?></span>
                        </div>
                    </div>
                    <div class="pawn-card-body">
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>Item</label>
                                <span><?php echo htmlspecialchars($item['category'] . ' - ' . ($item['item_type'] ?? 'N/A')); ?></span>
                            </div>
                            <?php if ($item['brand']): ?>
                            <div class="detail-item">
                                <label>Brand / Model</label>
                                <span><?php echo htmlspecialchars($item['brand'] . ' ' . ($item['model'] ?? '')); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <label>Due Date</label>
                                <span style="<?php echo $isOverdue ? 'color: #dc3545; font-weight: 700;' : ''; ?>"><?php echo date('M d, Y', strtotime($item['due_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Interest Rate</label>
                                <span><?php echo number_format($item['interest_rate'], 2); ?>%</span>
                            </div>
                            <div class="detail-item">
                                <label>Redemption Amount</label>
                                <span style="color: #1565c0; font-weight: 700;">₱<?php echo number_format($redemption_amount, 2); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>Contact</label>
                                <span><?php echo htmlspecialchars($item['contact_number']); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!$canRenewItem): ?>
                        <div class="cooldown-notice">
                            <i class="fas fa-hourglass-half"></i>
                            Renewal available in <?php echo $item['renewal_status']['days_remaining']; ?> day(s)
                            <small>(Last renewed: <?php echo date('M d, Y', strtotime($item['renewal_status']['last_date'])); ?>)</small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="action-buttons">
                            <a href="view_pawn.php?id=<?php echo $item['id']; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <button type="button" class="btn-redeem" onclick="openRedeemModal(<?php echo htmlspecialchars(json_encode([
                                'id' => $item['id'],
                                'customer_name' => $item['customer_name'],
                                'item' => $item['category'] . ' - ' . ($item['brand'] ?? '') . ' ' . ($item['model'] ?? ''),
                                'loan_amount' => $item['loan_amount'],
                                'interest' => $total_interest,
                                'service_fee' => 5,
                                'total' => $redemption_amount
                            ])); ?>)">
                                <i class="fas fa-hand-holding-usd"></i> Redeem
                            </button>
                            <?php if ($canRenewItem): ?>
                            <a href="renew_pawn.php?id=<?php echo $item['id']; ?>" class="btn-renew">
                                <i class="fas fa-sync-alt"></i> Renew
                            </a>
                            <?php else: ?>
                            <button class="btn-renew disabled" disabled title="Renewal available in <?php echo $item['renewal_status']['days_remaining']; ?> days">
                                <i class="fas fa-sync-alt"></i> Renew (<?php echo $item['renewal_status']['days_remaining']; ?>d)
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Redeem Modal -->
    <div class="modal-overlay" id="redeemModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-hand-holding-usd" style="color: #28a745;"></i> Confirm Redemption</h3>
                <button class="modal-close" onclick="closeRedeemModal()">&times;</button>
            </div>
            <form action="pawn_process.php" method="POST">
                <input type="hidden" name="action" value="redeem_pawn">
                <input type="hidden" name="id" id="redeem_item_id">
                <div class="modal-body">
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <p style="margin: 5px 0;"><strong>Customer:</strong> <span id="redeem_customer"></span></p>
                        <p style="margin: 5px 0;"><strong>Item:</strong> <span id="redeem_item"></span></p>
                    </div>
                    
                    <div style="border: 1px solid #e6e6e6; border-radius: 8px; overflow: hidden;">
                        <div style="padding: 12px 15px; display: flex; justify-content: space-between; border-bottom: 1px solid #e6e6e6;">
                            <span>Principal Amount</span>
                            <span id="redeem_principal">₱0.00</span>
                        </div>
                        <div style="padding: 12px 15px; display: flex; justify-content: space-between; border-bottom: 1px solid #e6e6e6;">
                            <span>Interest</span>
                            <span id="redeem_interest">₱0.00</span>
                        </div>
                        <div style="padding: 12px 15px; display: flex; justify-content: space-between; border-bottom: 1px solid #e6e6e6;">
                            <span>Service Fee</span>
                            <span id="redeem_service">₱5.00</span>
                        </div>
                        <div style="padding: 15px; display: flex; justify-content: space-between; background: #e8f5e9; font-weight: 700; font-size: 1.1rem;">
                            <span>Total Redemption</span>
                            <span id="redeem_total" style="color: #2e7d32;">₱0.00</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeRedeemModal()">Cancel</button>
                    <button type="submit" class="btn-redeem">
                        <i class="fas fa-check"></i> Confirm Redemption
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.pawn-card').forEach(card => {
                const searchData = card.dataset.search;
                card.style.display = searchData.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                document.querySelectorAll('.pawn-card').forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else if (filter === 'overdue') {
                        card.style.display = card.dataset.overdue === '1' ? 'block' : 'none';
                    } else if (filter === 'due-soon') {
                        card.style.display = card.dataset.dueSoon === '1' ? 'block' : 'none';
                    } else if (filter === 'can-renew') {
                        card.style.display = card.dataset.canRenew === '1' ? 'block' : 'none';
                    }
                });
            });
        });

        // Redeem modal functions
        function openRedeemModal(data) {
            document.getElementById('redeem_item_id').value = data.id;
            document.getElementById('redeem_customer').textContent = data.customer_name;
            document.getElementById('redeem_item').textContent = data.item;
            document.getElementById('redeem_principal').textContent = '₱' + parseFloat(data.loan_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('redeem_interest').textContent = '₱' + parseFloat(data.interest).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('redeem_service').textContent = '₱' + parseFloat(data.service_fee).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('redeem_total').textContent = '₱' + parseFloat(data.total).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('redeemModal').classList.add('active');
        }

        function closeRedeemModal() {
            document.getElementById('redeemModal').classList.remove('active');
        }

        // Close modal on overlay click
        document.getElementById('redeemModal').addEventListener('click', function(e) {
            if (e.target === this) closeRedeemModal();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
