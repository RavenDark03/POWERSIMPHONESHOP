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

$items_stmt = $conn->prepare('SELECT items.*, 
    COALESCE(it.name, items.item_type) AS item_type_display,
    COALESCE(ic.name, items.category) AS category_display,
    COALESCE(cond.name, items.item_condition) AS condition_display
    FROM items
    LEFT JOIN item_types it ON it.name = items.item_type
    LEFT JOIN item_categories ic ON ic.name = items.category
    LEFT JOIN item_conditions cond ON cond.name = items.item_condition
    WHERE items.customer_id = ?
    ORDER BY items.created_at DESC');
$items_stmt->bind_param('i', $customer_id);
$items_stmt->execute();
$items = $items_stmt->get_result();
$items_stmt->close();

// Fetch customer's bookings
$bookings_stmt = $conn->prepare('SELECT * FROM bookings WHERE customer_id = ? ORDER BY booking_date DESC, booking_time DESC LIMIT 5');
if ($bookings_stmt) {
    $bookings_stmt->bind_param('i', $customer_id);
    $bookings_stmt->execute();
    $bookings = $bookings_stmt->get_result();
    $bookings_stmt->close();
} else {
    $bookings = null;
}

// Check if customer has an active booking (pending OR confirmed - can only book again after completed/cancelled)
$pending_booking = null;
$pending_check = $conn->prepare("SELECT * FROM bookings WHERE customer_id = ? AND (status = 'pending' OR status = 'confirmed') ORDER BY booking_date ASC LIMIT 1");
if ($pending_check) {
    $pending_check->bind_param('i', $customer_id);
    $pending_check->execute();
    $pending_result = $pending_check->get_result();
    if ($pending_result->num_rows > 0) {
        $pending_booking = $pending_result->fetch_assoc();
    }
    $pending_check->close();
}

// Handle booking submission
$booking_message = '';
$booking_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
    // Check again for active booking (in case of race condition)
    $recheck = $conn->prepare("SELECT id FROM bookings WHERE customer_id = ? AND (status = 'pending' OR status = 'confirmed') LIMIT 1");
    $recheck->bind_param('i', $customer_id);
    $recheck->execute();
    $recheck_result = $recheck->get_result();
    
    if ($recheck_result->num_rows > 0) {
        $booking_error = 'You already have an active appointment. Please wait for it to be completed or cancelled before booking another.';
        $recheck->close();
    } else {
        $recheck->close();
        
        $booking_date = $_POST['booking_date'] ?? '';
        $booking_time = $_POST['booking_time'] ?? '';
        $booking_purpose = $_POST['booking_purpose'] ?? '';
        $booking_notes = $_POST['booking_notes'] ?? '';
        
        // Validate date (must be today or future)
        $today = date('Y-m-d');
        if ($booking_date < $today) {
            $booking_error = 'You cannot book a date in the past.';
        } else {
            // Validate time (must be between 8am and 5pm)
            $time_parts = explode(':', $booking_time);
            $hour = intval($time_parts[0]);
            if ($hour < 8 || $hour >= 17) {
                $booking_error = 'Please select a time between 8:00 AM and 5:00 PM.';
            } else {
                // Insert booking
                $insert_stmt = $conn->prepare('INSERT INTO bookings (customer_id, booking_date, booking_time, purpose, notes, status, created_at) VALUES (?, ?, ?, ?, ?, "pending", NOW())');
                if ($insert_stmt) {
                    $insert_stmt->bind_param('issss', $customer_id, $booking_date, $booking_time, $booking_purpose, $booking_notes);
                    if ($insert_stmt->execute()) {
                        $booking_message = 'Your appointment has been booked successfully!';
                        // Refresh pending booking status
                        $pending_booking = [
                            'id' => $insert_stmt->insert_id,
                            'booking_date' => $booking_date,
                            'booking_time' => $booking_time,
                            'purpose' => $booking_purpose,
                            'notes' => $booking_notes,
                            'status' => 'pending'
                        ];
                    } else {
                        $booking_error = 'Failed to book appointment. Please try again.';
                    }
                    $insert_stmt->close();
                } else {
                    $booking_error = 'Booking system is not available. Please contact support.';
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Using system theme colors */
        :root {
            --primary-color: #0a3d0a;
            --secondary-color: #145214;
            --accent-color: #d4af37;
            --accent-hover: #b5952f;
            --text-color: #333333;
            --text-light: #ffffff;
            --background-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-radius: 12px;
        }
        
        * { box-sizing: border-box; }
        
        body { 
            background: var(--background-color);
            color: var(--text-color); 
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        /* Top Navigation Bar */
        .top-nav {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .top-nav .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-light);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .top-nav .brand i {
            color: var(--accent-color);
            font-size: 1.3rem;
        }
        
        .nav-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .nav-actions a {
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-link {
            background: rgba(255,255,255,0.1);
            color: var(--text-light);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .nav-logout {
            background: var(--accent-color);
            color: var(--primary-color);
        }
        
        .nav-logout:hover {
            background: var(--accent-hover);
        }
        
        /* Main Container */
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }
        
        /* Welcome Card */
        .welcome-card {
            grid-column: span 8;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color), #0f5a24);
            border-radius: var(--border-radius);
            padding: 28px;
            color: var(--text-light);
            box-shadow: 0 8px 24px rgba(10, 61, 10, 0.25);
        }
        
        .welcome-card h1 {
            margin: 0 0 8px;
            font-size: 1.6rem;
            font-weight: 700;
        }
        
        .welcome-card p {
            margin: 0 0 16px;
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
        }
        
        .customer-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .tag i {
            color: var(--accent-color);
        }
        
        /* Quick Stats Card */
        .stats-card {
            grid-column: span 4;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }
        
        .stats-card h3 {
            margin: 0 0 16px;
            font-size: 1rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        /* Action Cards Row */
        .action-cards {
            grid-column: span 12;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .action-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-color: var(--accent-color);
        }
        
        .action-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 12px;
        }
        
        .action-card h4 {
            margin: 0 0 8px;
            font-size: 1.05rem;
            color: var(--text-color);
        }
        
        .action-card p {
            margin: 0;
            color: #666;
            font-size: 0.85rem;
        }
        
        /* Cards */
        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(10,61,10,0.03), rgba(212,175,55,0.02));
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header .subtitle {
            color: #666;
            font-size: 0.85rem;
            font-weight: normal;
        }
        
        .card-body {
            padding: 24px;
        }
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        table th {
            background: #f8f9fa;
            color: var(--primary-color);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        table td {
            color: var(--text-color);
            font-weight: 500;
        }
        
        table tr:hover {
            background: #fafafa;
        }
        
        /* Status Badges */
        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: capitalize;
        }
        
        .status.pawned { background: #ecfdf3; color: #16a34a; }
        .status.pending { background: #fff7ed; color: #ea580c; }
        .status.redeemed { background: #e0f2fe; color: #0284c7; }
        .status.sold { background: #fef2f2; color: #dc2626; }
        .status.for_sale { background: #fef3c7; color: #d97706; }
        .status.confirmed { background: #d1fae5; color: #059669; }
        .status.cancelled { background: #fee2e2; color: #dc2626; }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background: var(--accent-hover);
        }
        
        .btn-secondary {
            background: var(--primary-color);
            color: var(--text-light);
        }
        
        .btn-secondary:hover {
            background: var(--secondary-color);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background: var(--primary-color);
            color: var(--text-light);
        }
        
        .btn-sm {
            padding: 8px 14px;
            font-size: 0.85rem;
        }
        
        /* Booking Form */
        .booking-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group label {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--primary-color);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-actions {
            grid-column: span 2;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 8px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 16px;
        }
        
        .empty-state h4 {
            margin: 0 0 8px;
            color: #444;
        }
        
        .empty-state p {
            margin: 0;
        }
        
        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 1000;
        }
        
        .modal-backdrop.active {
            display: flex;
        }
        
        .modal {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 20px 24px;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, rgba(10,61,10,0.05), rgba(212,175,55,0.03));
        }
        
        .modal-header h4 {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .modal-footer {
            padding: 18px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        /* Two Column Layout for larger screens */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .welcome-card {
                grid-column: span 12;
            }
            .stats-card {
                grid-column: span 12;
            }
            .action-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            .two-columns {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 16px;
            }
            .dashboard-grid {
                gap: 16px;
            }
            .action-cards {
                grid-template-columns: 1fr;
            }
            .booking-form {
                grid-template-columns: 1fr;
            }
            .form-group.full-width {
                grid-column: span 1;
            }
            .form-actions {
                grid-column: span 1;
                flex-direction: column;
            }
            .top-nav {
                flex-direction: column;
                gap: 12px;
                padding: 16px;
            }
            .nav-actions {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Item Summary in Modal */
        .item-summary {
            background: linear-gradient(135deg, rgba(10,61,10,0.05), rgba(212,175,55,0.03));
            border: 1px solid rgba(10,61,10,0.1);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
        }
        
        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .summary-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        
        .summary-value {
            font-weight: 700;
            color: var(--text-color);
        }
        
        .booking-info {
            background: #f0f9f0;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 0.9rem;
            color: var(--primary-color);
        }
        
        .booking-info i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="brand">
            <img src="images/powersim logo.png" alt="Powersim" style="height: 32px; width: auto;">
            <span>Powersim Pawnshop - Customer Portal</span>
        </div>
        <div class="nav-actions">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
            <a href="logout.php" class="nav-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php 
        // Check account status and show notice for pending accounts
        $account_status = $customer['account_status'] ?? 'approved';
        if ($account_status === 'pending'): 
        ?>
        <div class="alert alert-warning" style="background: #fff3cd; color: #856404; padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #ffeeba; display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 1.3rem;"></i>
            <div>
                <strong>Account Pending Verification</strong><br>
                <span style="font-size: 0.9rem;">Your account is awaiting staff approval. Please visit our branch with a valid ID to complete verification before you can pawn items.</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($booking_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($booking_message); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($booking_error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($booking_error); ?>
        </div>
        <?php endif; ?>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h1><i class="fas fa-hand-wave" style="color: var(--accent-color);"></i> Hello, <?php echo htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')); ?>!</h1>
                <p>Welcome to your customer portal. View your pawned items, track due dates, and manage your account.</p>
                <div class="customer-tags">
                    <span class="tag"><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($customer['customer_code'] ?? ''); ?></span>
                    <span class="tag"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['contact_number'] ?? ''); ?></span>
                    <span class="tag"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email'] ?? ''); ?></span>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="stats-card">
                <h3><i class="fas fa-chart-bar"></i> Quick Summary</h3>
                <?php
                // Calculate stats
                $total_items = 0;
                $active_pawns = 0;
                $total_loan = 0;
                $items->data_seek(0);
                while ($item = $items->fetch_assoc()) {
                    $total_items++;
                    if ($item['status'] === 'pawned') {
                        $active_pawns++;
                        $total_loan += $item['loan_amount'];
                    }
                }
                $items->data_seek(0);
                ?>
                <div class="stat-item">
                    <span class="stat-label">Total Items</span>
                    <span class="stat-value"><?php echo $total_items; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Active Pawns</span>
                    <span class="stat-value"><?php echo $active_pawns; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Total Loan Amount</span>
                    <span class="stat-value">₱<?php echo number_format($total_loan, 2); ?></span>
                </div>
            </div>

            <!-- Action Cards -->
            <div class="action-cards">
                <div class="action-card" onclick="document.getElementById('bookingModal').classList.add('active')">
                    <i class="fas fa-calendar-plus"></i>
                    <h4>Book Appointment</h4>
                    <p>Schedule a visit to the pawnshop</p>
                </div>
                <div class="action-card" onclick="document.getElementById('profileModal').classList.add('active')">
                    <i class="fas fa-user-cog"></i>
                    <h4>Update Profile</h4>
                    <p>Edit your contact information</p>
                </div>
                <div class="action-card" onclick="document.getElementById('pawnsModal').classList.add('active')">
                    <i class="fas fa-list-alt"></i>
                    <h4>View Pawns</h4>
                    <p>Check your pawned items status</p>
                </div>
            </div>
        </div>

        </div>

        <!-- My Bookings Section -->
        <?php if ($bookings && $bookings->num_rows > 0): ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <div>
                    <h3><i class="fas fa-history"></i> My Recent Bookings</h3>
                    <span class="subtitle">Your scheduled appointments</span>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                            <td><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></td>
                            <td><?php echo htmlspecialchars($booking['purpose']); ?></td>
                            <td><span class="status <?php echo htmlspecialchars($booking['status']); ?>"><?php echo htmlspecialchars(ucfirst($booking['status'])); ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- View Pawns Modal -->
    <div class="modal-backdrop" id="pawnsModal">
        <div class="modal" style="max-width: 900px; width: 95%;">
            <div class="modal-header">
                <h4><i class="fas fa-ring"></i> Your Pawned Items</h4>
                <button class="btn btn-outline btn-sm" type="button" onclick="closeModal('pawnsModal')" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <?php 
                // Reset items pointer for modal
                if ($items) $items->data_seek(0);
                if ($items && $items->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Loan</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $items->fetch_assoc()): ?>
                                <?php 
                                    $itemType = $row['item_type_display'] ?: $row['item_type'] ?: $row['category_display'];
                                ?>
                                <tr data-item='<?php echo json_encode([
                                    "id" => $row['id'],
                                    "type" => $itemType,
                                    "category" => $row['category_display'],
                                    "desc" => $row['item_description'],
                                    "loan" => number_format($row['loan_amount'],2),
                                    "rate" => $row['interest_rate'],
                                    "due" => $row['due_date'],
                                    "status" => $row['status'],
                                    "serial" => $row['serial_number'],
                                    "condition" => $row['condition_display'],
                                    "created" => date('M d, Y', strtotime($row['created_at'])),
                                ], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>'>
                                    <td><?php echo htmlspecialchars($itemType); ?></td>
                                    <td>₱<?php echo number_format($row['loan_amount'],2); ?></td>
                                    <td><span class="status <?php echo htmlspecialchars($row['status']); ?>"><?php echo htmlspecialchars(str_replace('_',' ',$row['status'])); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    <td>
                                        <button class="btn btn-outline btn-sm" type="button" onclick="openDetails(this)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state" style="padding: 40px 20px; text-align: center;">
                    <i class="fas fa-box-open" style="font-size: 3rem; color: #ccc; margin-bottom: 16px;"></i>
                    <h4 style="margin: 0 0 8px; color: #666;">No Items Yet</h4>
                    <p style="margin: 0; color: #888;">You have no pawned items at the moment.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal-backdrop" id="detailModal">
        <div class="modal">
            <div class="modal-header">
                <h4><i class="fas fa-info-circle"></i> Pawn Details</h4>
                <button class="btn btn-outline btn-sm" type="button" onclick="closeModal('detailModal')">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
            <div class="modal-body">
                <div class="item-summary">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <span class="summary-label">Item</span>
                            <span class="summary-value" id="dItem">—</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Category</span>
                            <span class="summary-value" id="dCategory">—</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Condition</span>
                            <span class="summary-value" id="dCondition">—</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Loan Amount</span>
                            <span class="summary-value" id="dLoan" style="color: var(--primary-color);">—</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Interest Rate</span>
                            <span class="summary-value" id="dRate">—</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Due Date</span>
                            <span class="summary-value" id="dDue">—</span>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top: 16px;">
                    <label>Description</label>
                    <p id="dDesc" style="margin: 0; color: #666;">—</p>
                </div>
                <div class="form-group">
                    <label>Serial Number</label>
                    <p id="dSerial" style="margin: 0; color: #666;">—</p>
                </div>
            </div>
            <div class="modal-footer">
                <a class="btn btn-outline" id="renewLink" href="#"><i class="fas fa-redo"></i> Renew (+30 days)</a>
                <a class="btn btn-secondary" id="payLink" href="#"><i class="fas fa-credit-card"></i> Pay / Redeem</a>
                <button class="btn btn-primary" type="button" onclick="closeModal('detailModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal-backdrop" id="profileModal">
        <form class="modal" method="POST" action="update_profile.php" style="max-width: 650px;">
            <div class="modal-header">
                <h4><i class="fas fa-user-edit"></i> Edit Profile</h4>
                <button class="btn btn-outline btn-sm" type="button" onclick="closeModal('profileModal')" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="booking-form">
                    <div class="form-group">
                        <label>Username</label>
                        <input name="username" id="username_edit" type="text" value="<?php echo htmlspecialchars($customer['username'] ?? ''); ?>" placeholder="Choose a username (not email)">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input name="email" type="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Contact Number</label>
                        <input name="contact_number" id="contact_number_edit" type="text" value="<?php echo htmlspecialchars($customer['contact_number'] ?? ''); ?>" required>
                    </div>
                    
                    <!-- Address Section -->
                    <div class="form-group full-width" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                        <label style="font-size: 1rem; color: var(--primary-color);"><i class="fas fa-map-marker-alt"></i> Present Address</label>
                    </div>
                    <div class="form-group">
                        <label>Unit/House No.</label>
                        <input name="present_house_num" id="present_house_num" type="text" value="<?php echo htmlspecialchars($customer['present_house_num'] ?? ''); ?>" placeholder="e.g. 123" required>
                    </div>
                    <div class="form-group">
                        <label>Street</label>
                        <input name="present_street" id="present_street" type="text" value="<?php echo htmlspecialchars($customer['present_street'] ?? ''); ?>" placeholder="e.g. Main Street" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Subdivision/Village/Purok</label>
                        <input name="present_subdivision" id="present_subdivision" type="text" value="<?php echo htmlspecialchars($customer['present_subdivision'] ?? ''); ?>" placeholder="e.g. Sample Village">
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <select name="present_province_code" id="present_province" required onchange="fetchCities('present')">
                            <option value="">Select Province</option>
                        </select>
                        <input type="hidden" name="present_province" id="present_province_text" value="<?php echo htmlspecialchars($customer['present_province'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>City/Municipality</label>
                        <select name="present_city_code" id="present_city" required onchange="fetchBarangays('present')">
                            <option value="">Select City</option>
                        </select>
                        <input type="hidden" name="present_city" id="present_city_text" value="<?php echo htmlspecialchars($customer['present_city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Barangay</label>
                        <select name="present_barangay_code" id="present_barangay" required onchange="updateZip('present')">
                            <option value="">Select Barangay</option>
                        </select>
                        <input type="hidden" name="present_barangay" id="present_barangay_text" value="<?php echo htmlspecialchars($customer['present_barangay'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>ZIP Code</label>
                        <input name="present_zip" id="present_zip" type="text" placeholder="4-digit ZIP" value="<?php echo htmlspecialchars($customer['present_zip'] ?? ''); ?>" maxlength="4" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" type="button" onclick="closeModal('profileModal')">Cancel</button>
                <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Booking Modal (Full booking form) -->
    <div class="modal-backdrop" id="bookingModal">
        <div class="modal" style="max-width: 550px;">
            <div class="modal-header">
                <h4><i class="fas fa-calendar-plus"></i> Book an Appointment</h4>
                <button class="btn btn-outline btn-sm" type="button" onclick="closeModal('bookingModal')" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="booking-info" style="background: #f0f9ff; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                    <span><strong>Operating Hours:</strong> Monday - Saturday, 8:00 AM - 5:00 PM</span>
                </div>
                
                <?php if ($pending_booking): ?>
                <!-- Show active booking instead of form -->
                <?php 
                $is_confirmed = ($pending_booking['status'] === 'confirmed');
                $bg_color = $is_confirmed ? '#ecfdf5' : '#fff7ed';
                $border_color = $is_confirmed ? '#a7f3d0' : '#fed7aa';
                $icon_bg = $is_confirmed ? '#d1fae5' : '#ffedd5';
                $icon_color = $is_confirmed ? '#059669' : '#ea580c';
                $text_primary = $is_confirmed ? '#047857' : '#c2410c';
                $text_secondary = $is_confirmed ? '#065f46' : '#9a3412';
                $title = $is_confirmed ? 'Your Appointment is Confirmed!' : 'You Have a Pending Appointment';
                $message = $is_confirmed 
                    ? 'Your appointment has been confirmed. Please arrive on time.' 
                    : 'You can only book one appointment at a time. Please wait for your current booking to be confirmed or cancelled.';
                $icon = $is_confirmed ? 'fa-calendar-check' : 'fa-clock';
                ?>
                <div style="background: <?php echo $bg_color; ?>; border: 1px solid <?php echo $border_color; ?>; border-radius: 12px; padding: 24px; text-align: center;">
                    <div style="width: 60px; height: 60px; background: <?php echo $icon_bg; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i class="fas <?php echo $icon; ?>" style="font-size: 1.8rem; color: <?php echo $icon_color; ?>;"></i>
                    </div>
                    <h4 style="margin: 0 0 8px; color: <?php echo $text_primary; ?>;"><?php echo $title; ?></h4>
                    <p style="margin: 0 0 20px; color: <?php echo $text_secondary; ?>; font-size: 0.9rem;"><?php echo $message; ?></p>
                    
                    <div style="background: #fff; border-radius: 10px; padding: 16px; text-align: left; border: 1px solid <?php echo $border_color; ?>;">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: <?php echo $text_secondary; ?>; letter-spacing: 0.04em;">Date</span>
                                <p style="margin: 4px 0 0; font-weight: 700; color: #333; font-size: 0.95rem;">
                                    <i class="fas fa-calendar" style="color: <?php echo $icon_color; ?>; margin-right: 6px;"></i>
                                    <?php echo date('F d, Y', strtotime($pending_booking['booking_date'])); ?>
                                </p>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: <?php echo $text_secondary; ?>; letter-spacing: 0.04em;">Time</span>
                                <p style="margin: 4px 0 0; font-weight: 700; color: #333; font-size: 0.95rem;">
                                    <i class="fas fa-clock" style="color: <?php echo $icon_color; ?>; margin-right: 6px;"></i>
                                    <?php echo date('g:i A', strtotime($pending_booking['booking_time'])); ?>
                                </p>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: <?php echo $text_secondary; ?>; letter-spacing: 0.04em;">Purpose</span>
                                <p style="margin: 4px 0 0; font-weight: 700; color: #333; font-size: 0.95rem;">
                                    <i class="fas fa-clipboard-list" style="color: <?php echo $icon_color; ?>; margin-right: 6px;"></i>
                                    <?php echo htmlspecialchars($pending_booking['purpose']); ?>
                                </p>
                            </div>
                            <div>
                                <span style="font-size: 0.75rem; text-transform: uppercase; color: <?php echo $text_secondary; ?>; letter-spacing: 0.04em;">Status</span>
                                <p style="margin: 4px 0 0;">
                                    <?php if ($is_confirmed): ?>
                                    <span style="background: #d1fae5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">
                                        <i class="fas fa-check-circle"></i> Confirmed
                                    </span>
                                    <?php else: ?>
                                    <span style="background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700;">
                                        <i class="fas fa-hourglass-half"></i> Pending
                                    </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php if (!empty($pending_booking['notes'])): ?>
                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid <?php echo $border_color; ?>;">
                            <span style="font-size: 0.75rem; text-transform: uppercase; color: <?php echo $text_secondary; ?>; letter-spacing: 0.04em;">Notes</span>
                            <p style="margin: 4px 0 0; color: #666;"><?php echo htmlspecialchars($pending_booking['notes']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <p style="margin: 16px 0 0; font-size: 0.85rem; color: <?php echo $text_secondary; ?>;">
                        <?php if ($is_confirmed): ?>
                        <i class="fas fa-info-circle"></i> Please bring a valid ID and all required documents.
                        <?php else: ?>
                        <i class="fas fa-info-circle"></i> You will be notified once your appointment is confirmed.
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <!-- Show booking form -->
                <form method="POST" action="dashboard.php" id="bookingForm">
                    <input type="hidden" name="action" value="book_appointment">
                    <div class="booking-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="booking_date"><i class="fas fa-calendar"></i> Date</label>
                            <input type="date" name="booking_date" id="booking_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="booking_time"><i class="fas fa-clock"></i> Time</label>
                            <select name="booking_time" id="booking_time" required style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;">
                                <option value="">Select Time</option>
                                <option value="08:00">8:00 AM</option>
                                <option value="08:30">8:30 AM</option>
                                <option value="09:00">9:00 AM</option>
                                <option value="09:30">9:30 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="10:30">10:30 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="11:30">11:30 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="12:30">12:30 PM</option>
                                <option value="13:00">1:00 PM</option>
                                <option value="13:30">1:30 PM</option>
                                <option value="14:00">2:00 PM</option>
                                <option value="14:30">2:30 PM</option>
                                <option value="15:00">3:00 PM</option>
                                <option value="15:30">3:30 PM</option>
                                <option value="16:00">4:00 PM</option>
                                <option value="16:30">4:30 PM</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: span 2; margin-bottom: 0;">
                            <label for="booking_purpose"><i class="fas fa-clipboard-list"></i> Purpose of Visit</label>
                            <select name="booking_purpose" id="booking_purpose" required style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px;">
                                <option value="">Select Purpose</option>
                                <option value="New Pawn">New Pawn Transaction</option>
                                <option value="Redeem Item">Redeem Item</option>
                                <option value="Renew Pawn">Renew Pawn</option>
                                <option value="Inquiry">General Inquiry</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: span 2; margin-bottom: 0;">
                            <label for="booking_notes"><i class="fas fa-sticky-note"></i> Additional Notes (Optional)</label>
                            <textarea name="booking_notes" id="booking_notes" placeholder="Any additional information..." style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; min-height: 80px; resize: vertical;"></textarea>
                        </div>
                        <div style="grid-column: span 2; display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                            <button type="button" class="btn btn-outline" onclick="closeModal('bookingModal')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-calendar-plus"></i> Book Appointment
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/zipcodes.js"></script>
    <script>
    const API_BASE = 'https://psgc.gitlab.io/api';
    
    // Booking form validation
    (function() {
        const bookingForm = document.getElementById('bookingForm');
        const bookingDate = document.getElementById('booking_date');
        const bookingTime = document.getElementById('booking_time');
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        if (bookingDate) {
            bookingDate.setAttribute('min', today);
            bookingDate.value = today; // Default to today
        }
        
        // Validate on form submit
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                const selectedDate = new Date(bookingDate.value);
                const todayDate = new Date();
                todayDate.setHours(0, 0, 0, 0);
                
                // Check if date is in the past
                if (selectedDate < todayDate) {
                    e.preventDefault();
                    alert('You cannot book a date in the past. Please select today or a future date.');
                    return false;
                }
                
                // Check if time is within operating hours (8 AM - 5 PM)
                const timeValue = bookingTime.value;
                if (timeValue) {
                    const timeParts = timeValue.split(':');
                    const hour = parseInt(timeParts[0]);
                    
                    if (hour < 8 || hour >= 17) {
                        e.preventDefault();
                        alert('Please select a time between 8:00 AM and 5:00 PM (operating hours).');
                        return false;
                    }
                }
                
                return true;
            });
        }
        
        // Also validate date on change
        if (bookingDate) {
            bookingDate.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const todayDate = new Date();
                todayDate.setHours(0, 0, 0, 0);
                
                if (selectedDate < todayDate) {
                    alert('You cannot book a date in the past.');
                    this.value = today;
                }
            });
        }
    })();
    
    // Apply +63 mask similar to admin form
    (function(){
        const contactInput = document.getElementById('contact_number_edit');
        if (contactInput) {
            if (!contactInput.value.startsWith('+63 ')) {
                contactInput.value = '+63 ' + contactInput.value.replace(/^\+?63\s?|^0/, '');
            }
            contactInput.addEventListener('input', function(){
                let v = this.value.replace(/[^0-9+\s]/g,'');
                v = v.replace(/^0/, '');
                if (!v.startsWith('+63 ')) {
                    v = '+63 ' + v.replace(/^\+?63\s?/, '');
                }
                this.value = v.substring(0, 16);
            });
            contactInput.addEventListener('keydown', function(e) {
                if ((e.key === 'Backspace' || e.key === 'Delete') && this.selectionStart <= 4) {
                    e.preventDefault();
                }
                if (e.key === 'ArrowLeft' && this.selectionStart <= 4) {
                    e.preventDefault();
                }
                if (e.key === 'Home') {
                    e.preventDefault();
                    this.setSelectionRange(4, 4);
                }
            });
            contactInput.addEventListener('click', function() {
                if (this.selectionStart < 4) this.setSelectionRange(4, 4);
            });
        }
    })();

    // Restrict zip inputs to digits only
    (function(){
        const zipInput = document.getElementById('present_zip');
        if (zipInput) {
            zipInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 4);
            });
            zipInput.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) return;
                const allowed = ['Backspace','Tab','ArrowLeft','ArrowRight','Delete','Home','End'];
                if (allowed.includes(e.key)) return;
                if (!/^[0-9]$/.test(e.key)) e.preventDefault();
            });
        }
    })();

    // Initialize PH location dropdowns using API and local JSON
    const ZIP_READY = hydrateFromJson('js/ph_locations.json');
    
    document.addEventListener('DOMContentLoaded', async () => {
        await ZIP_READY;
        loadProvinces('present');
        
        // Add listener to update barangay text when selection changes
        const brgySelect = document.getElementById('present_barangay');
        if (brgySelect) {
            brgySelect.addEventListener('change', function() {
                const brgyText = document.getElementById('present_barangay_text');
                if (brgyText && this.selectedIndex > 0) {
                    brgyText.value = this.options[this.selectedIndex].text;
                }
            });
        }
        
        // Prefill selections
        const provinceVal = <?php echo json_encode($customer['present_province'] ?? ''); ?>;
        const cityVal = <?php echo json_encode($customer['present_city'] ?? ''); ?>;
        const brgyVal = <?php echo json_encode($customer['present_barangay'] ?? ''); ?>;
        
        if (provinceVal) {
            setTimeout(async () => {
                const provinceSelect = document.getElementById('present_province');
                // Find the option with text matching province value
                for (let opt of provinceSelect.options) {
                    if (opt.text.toLowerCase() === provinceVal.toLowerCase()) {
                        provinceSelect.value = opt.value;
                        break;
                    }
                }
                await fetchCities('present');
                
                if (cityVal) {
                    const citySelect = document.getElementById('present_city');
                    for (let opt of citySelect.options) {
                        if (opt.text.toLowerCase() === cityVal.toLowerCase()) {
                            citySelect.value = opt.value;
                            break;
                        }
                    }
                    await fetchBarangays('present');
                    
                    if (brgyVal) {
                        const brgySelect = document.getElementById('present_barangay');
                        for (let opt of brgySelect.options) {
                            if (opt.text.toLowerCase() === brgyVal.toLowerCase()) {
                                brgySelect.value = opt.value;
                                break;
                            }
                        }
                    }
                }
                updateZip('present');
            }, 200);
        }
    });

    // Username quick guard (no @)
    const profileForm = document.querySelector('#profileModal form');
    if (profileForm) {
        profileForm.addEventListener('submit', (e) => {
            const uname = document.getElementById('username_edit').value || '';
            if (uname.includes('@')) {
                e.preventDefault();
                alert('Username cannot be an email. Please remove the @.');
            }
        });
    }

    // Load provinces into dropdown
    async function loadProvinces(type) {
        const select = document.getElementById(type + '_province');
        select.innerHTML = '<option value="">Loading provinces...</option>';
        try {
            const response = await fetch(`${API_BASE}/provinces/`);
            const data = await response.json();
            data.sort((a, b) => a.name.localeCompare(b.name));
            let options = '<option value="">Select Province</option>';
            data.forEach(p => { 
                options += `<option value="${p.code}" data-name="${p.name}">${p.name}</option>`; 
            });
            select.innerHTML = options;
        } catch (e) {
            console.error('Error loading provinces:', e);
            select.innerHTML = '<option value="">Error loading provinces</option>';
        }
    }

    // Fetch cities/municipalities for selected province
    async function fetchCities(type) {
        const provinceSelect = document.getElementById(type + '_province');
        const provinceCode = provinceSelect.value;
        const citySelect = document.getElementById(type + '_city');
        const brgySelect = document.getElementById(type + '_barangay');
        
        // Update hidden province text field
        const provinceText = document.getElementById(type + '_province_text');
        if (provinceText && provinceSelect.selectedIndex > 0) {
            provinceText.value = provinceSelect.options[provinceSelect.selectedIndex].text;
        }

        if(!provinceCode) {
            citySelect.innerHTML = '<option value="">Select Province First</option>';
            brgySelect.innerHTML = '<option value="">Select City First</option>';
            return;
        }
        
        citySelect.innerHTML = '<option value="">Loading cities...</option>';
        brgySelect.innerHTML = '<option value="">Select City First</option>';
        
        try {
            const response = await fetch(`${API_BASE}/provinces/${provinceCode}/cities-municipalities/`);
            const data = await response.json();
            data.sort((a, b) => a.name.localeCompare(b.name));
            let options = '<option value="">Select City/Municipality</option>';
            data.forEach(c => { 
                options += `<option value="${c.code}" data-name="${c.name}">${c.name}</option>`; 
            });
            citySelect.innerHTML = options;
        } catch (e) {
            console.error('Error loading cities:', e);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
        }
    }

    // Fetch barangays for selected city
    async function fetchBarangays(type) {
        const citySelect = document.getElementById(type + '_city');
        const cityCode = citySelect.value;
        const brgySelect = document.getElementById(type + '_barangay');
        
        // Update hidden city text field
        const cityText = document.getElementById(type + '_city_text');
        if (cityText && citySelect.selectedIndex > 0) {
            cityText.value = citySelect.options[citySelect.selectedIndex].text;
        }

        if(!cityCode) {
            brgySelect.innerHTML = '<option value="">Select City First</option>';
            return;
        }
        
        brgySelect.innerHTML = '<option value="">Loading barangays...</option>';
        try {
            const response = await fetch(`${API_BASE}/cities-municipalities/${cityCode}/barangays/`);
            const data = await response.json();
            data.sort((a, b) => a.name.localeCompare(b.name));
            let options = '<option value="">Select Barangay</option>';
            data.forEach(b => { 
                options += `<option value="${b.code}" data-name="${b.name}">${b.name}</option>`; 
            });
            brgySelect.innerHTML = options;
            updateZip(type);
        } catch (e) {
            console.error('Error loading barangays:', e);
            brgySelect.innerHTML = '<option value="">Error loading barangays</option>';
        }
    }

    // Update zip code based on selected city
    function updateZip(type) {
        ZIP_READY.finally(() => {
            const citySelect = document.getElementById(type + '_city');
            const brgySelect = document.getElementById(type + '_barangay');
            const cityName = citySelect.options[citySelect.selectedIndex] ? citySelect.options[citySelect.selectedIndex].text : '';
            const zipInput = document.getElementById(type + '_zip');
            const zip = getZipCode(cityName);
            zipInput.value = zip || '';
            
            // Update hidden barangay text field
            const brgyText = document.getElementById(type + '_barangay_text');
            if (brgyText && brgySelect.selectedIndex > 0) {
                brgyText.value = brgySelect.options[brgySelect.selectedIndex].text;
            }
        });
    }

    function openDetails(btn) {
        const row = btn.closest('tr');
        if (!row) return;
        const data = JSON.parse(row.dataset.item || '{}');
        document.getElementById('dItem').textContent = data.type || '—';
        document.getElementById('dCategory').textContent = data.category || '—';
        document.getElementById('dCondition').textContent = data.condition || '—';
        document.getElementById('dLoan').textContent = '₱' + (data.loan || '0.00');
        document.getElementById('dRate').textContent = (data.rate || 0) + '% / mo';
        document.getElementById('dDue').textContent = data.due || '—';
        document.getElementById('dDesc').textContent = data.desc || '—';
        document.getElementById('dSerial').textContent = data.serial || '—';
        const payLink = document.getElementById('payLink');
        const renewLink = document.getElementById('renewLink');
        payLink.href = 'simulate_payment.php?item_id=' + encodeURIComponent(data.id) + '&type=redeem';
        renewLink.href = 'simulate_payment.php?item_id=' + encodeURIComponent(data.id) + '&type=renew';
        document.getElementById('detailModal').classList.add('active');
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('active');
        }
    }

    document.getElementById('editProfileBtn').addEventListener('click', function() {
        document.getElementById('profileModal').classList.add('active');
    });
    
    // Close modal when clicking outside
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
    </script>
</body>
</html>
