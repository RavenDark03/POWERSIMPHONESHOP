<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $booking_id = intval($_POST['booking_id'] ?? 0);
    
    if ($action === 'update_status' && $booking_id > 0) {
        $new_status = $_POST['status'] ?? '';
        $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
        
        if (in_array($new_status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $booking_id);
            $stmt->execute();
            $stmt->close();
            
            header("Location: appointments.php?msg=updated");
            exit();
        }
    }
}

// Filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? '';

// Build query
$where_clauses = [];
$params = [];
$types = "";

if ($status_filter !== 'all') {
    $where_clauses[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $where_clauses[] = "b.booking_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$sql = "SELECT b.*, c.first_name, c.last_name, c.customer_code, c.contact_number, c.email 
        FROM bookings b 
        JOIN customers c ON b.customer_id = c.id 
        $where_sql 
        ORDER BY b.booking_date ASC, b.booking_time ASC";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

// Get counts for badges
$count_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM bookings";
$counts = $conn->query($count_sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h2 {
            margin: 0;
            color: #116530;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header p {
            margin: 4px 0 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            background: #fff;
            padding: 12px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .filter-tab {
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid #e5e7eb;
            background: #f8f9fa;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .filter-tab:hover {
            background: #e8f5e9;
            border-color: #c8e6c9;
        }
        .filter-tab.active {
            background: #116530;
            color: #fff;
            border-color: #116530;
        }
        .filter-tab .badge {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
        }
        .filter-tab.active .badge {
            background: rgba(255,255,255,0.3);
        }
        .filter-tab:not(.active) .badge {
            background: #e5e7eb;
            color: #666;
        }
        
        /* Date Filter */
        .date-filter {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
        }
        .date-filter input[type="date"] {
            padding: 10px 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .date-filter .btn-clear {
            padding: 10px 14px;
            background: #f8f9fa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            color: #666;
            text-decoration: none;
            font-size: 0.85rem;
        }
        
        /* Appointment Cards */
        .appointments-grid {
            display: grid;
            gap: 16px;
        }
        .appointment-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all 0.2s;
        }
        .appointment-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .appointment-card.pending {
            border-left: 4px solid #f59e0b;
        }
        .appointment-card.confirmed {
            border-left: 4px solid #10b981;
        }
        .appointment-card.completed {
            border-left: 4px solid #3b82f6;
        }
        .appointment-card.cancelled {
            border-left: 4px solid #ef4444;
            opacity: 0.7;
        }
        
        .card-header {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #f0f0f0;
            background: #fafafa;
        }
        .customer-info h3 {
            margin: 0 0 4px;
            font-size: 1.05rem;
            color: #333;
        }
        .customer-info .customer-code {
            color: #888;
            font-size: 0.85rem;
        }
        .appointment-datetime {
            text-align: right;
        }
        .appointment-date {
            font-weight: 700;
            color: #116530;
            font-size: 1rem;
        }
        .appointment-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        .card-body {
            padding: 16px 20px;
        }
        .detail-row {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            font-size: 0.9rem;
        }
        .detail-item i {
            color: #888;
            width: 18px;
        }
        .purpose-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #e8f5e9;
            color: #116530;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .notes-section {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
        }
        .notes-section label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 0.04em;
            display: block;
            margin-bottom: 4px;
        }
        .notes-section p {
            margin: 0;
            color: #555;
            font-size: 0.9rem;
        }
        
        .card-footer {
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
            border-top: 1px solid #f0f0f0;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }
        .status-badge.confirmed {
            background: #d1fae5;
            color: #059669;
        }
        .status-badge.completed {
            background: #dbeafe;
            color: #2563eb;
        }
        .status-badge.cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-confirm {
            background: #10b981;
            color: #fff;
        }
        .btn-confirm:hover {
            background: #059669;
        }
        .btn-complete {
            background: #3b82f6;
            color: #fff;
        }
        .btn-complete:hover {
            background: #2563eb;
        }
        .btn-cancel {
            background: #f8f9fa;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .btn-cancel:hover {
            background: #fee2e2;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            margin: 0 0 8px;
            color: #666;
        }
        .empty-state p {
            margin: 0;
            color: #888;
        }
        
        /* Alert */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        /* Today Highlight */
        .today-badge {
            background: #fef3c7;
            color: #d97706;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 8px;
        }
        
        @media (max-width: 768px) {
            .filter-tabs {
                flex-direction: column;
            }
            .date-filter {
                margin-left: 0;
                width: 100%;
            }
            .card-header {
                flex-direction: column;
                gap: 12px;
            }
            .appointment-datetime {
                text-align: left;
            }
            .card-footer {
                flex-direction: column;
                gap: 12px;
            }
            .action-buttons {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <div class="main-content-wrapper">
        <div class="container main-content">
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-calendar-alt"></i> Appointments Management</h2>
                    <p>View and manage customer appointment bookings</p>
                </div>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Appointment status updated successfully.
            </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="appointments.php" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All
                    <span class="badge"><?php echo $counts['total'] ?? 0; ?></span>
                </a>
                <a href="appointments.php?status=pending" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                    <span class="badge"><?php echo $counts['pending'] ?? 0; ?></span>
                </a>
                <a href="appointments.php?status=confirmed" class="filter-tab <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Confirmed
                    <span class="badge"><?php echo $counts['confirmed'] ?? 0; ?></span>
                </a>
                <a href="appointments.php?status=completed" class="filter-tab <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-flag-checkered"></i> Completed
                    <span class="badge"><?php echo $counts['completed'] ?? 0; ?></span>
                </a>
                <a href="appointments.php?status=cancelled" class="filter-tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Cancelled
                    <span class="badge"><?php echo $counts['cancelled'] ?? 0; ?></span>
                </a>
                
                <div class="date-filter">
                    <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                        <?php if ($status_filter !== 'all'): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <?php endif; ?>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" onchange="this.form.submit()">
                        <?php if ($date_filter): ?>
                        <a href="appointments.php<?php echo $status_filter !== 'all' ? '?status=' . htmlspecialchars($status_filter) : ''; ?>" class="btn-clear">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Appointments Grid -->
            <div class="appointments-grid">
                <?php if ($bookings->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Appointments Found</h3>
                    <p>There are no appointments matching your filters.</p>
                </div>
                <?php else: ?>
                
                <?php while ($booking = $bookings->fetch_assoc()): 
                    $isToday = $booking['booking_date'] === date('Y-m-d');
                ?>
                <div class="appointment-card <?php echo htmlspecialchars($booking['status']); ?>">
                    <div class="card-header">
                        <div class="customer-info">
                            <h3>
                                <i class="fas fa-user" style="color: #888; margin-right: 6px;"></i>
                                <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                            </h3>
                            <span class="customer-code">
                                <i class="fas fa-id-badge"></i> <?php echo htmlspecialchars($booking['customer_code']); ?>
                            </span>
                        </div>
                        <div class="appointment-datetime">
                            <div class="appointment-date">
                                <i class="fas fa-calendar"></i> 
                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                <?php if ($isToday): ?>
                                <span class="today-badge">TODAY</span>
                                <?php endif; ?>
                            </div>
                            <div class="appointment-time">
                                <i class="fas fa-clock"></i> 
                                <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="detail-row">
                            <div class="detail-item">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($booking['contact_number']); ?>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($booking['email']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <span class="purpose-badge">
                                <i class="fas fa-clipboard-list"></i>
                                <?php echo htmlspecialchars($booking['purpose']); ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($booking['notes'])): ?>
                        <div class="notes-section">
                            <label>Notes</label>
                            <p><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <span class="status-badge <?php echo htmlspecialchars($booking['status']); ?>">
                            <?php 
                            $statusIcons = [
                                'pending' => 'fas fa-hourglass-half',
                                'confirmed' => 'fas fa-check',
                                'completed' => 'fas fa-flag-checkered',
                                'cancelled' => 'fas fa-ban'
                            ];
                            ?>
                            <i class="<?php echo $statusIcons[$booking['status']] ?? 'fas fa-circle'; ?>"></i>
                            <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                        </span>
                        
                        <div class="action-buttons">
                            <?php if ($booking['status'] === 'pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="btn-action btn-confirm" onclick="return confirm('Confirm this appointment?');">
                                    <i class="fas fa-check"></i> Confirm
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn-action btn-cancel" onclick="return confirm('Cancel this appointment?');">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </form>
                            <?php elseif ($booking['status'] === 'confirmed'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="btn-action btn-complete" onclick="return confirm('Mark this appointment as completed?');">
                                    <i class="fas fa-flag-checkered"></i> Complete
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="btn-action btn-cancel" onclick="return confirm('Cancel this appointment?');">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
