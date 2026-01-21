<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}


if (isset($_GET['ajax'])) {
    $search = "";
    if (!empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $sql = "SELECT * FROM customers WHERE is_deleted = 0 AND (
                customer_code LIKE '%$search%' OR 
                first_name LIKE '%$search%' OR 
                last_name LIKE '%$search%' OR 
                CONCAT(first_name, ' ', last_name) LIKE '%$search%' OR
                contact_number LIKE '%$search%')
                ORDER BY created_at DESC";
    } else {
        $sql = "SELECT * FROM customers WHERE is_deleted = 0 ORDER BY created_at DESC";
    }
    
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $status = $row['account_status'] ?? 'approved';
            $statusIcon = $status === 'approved' ? 'fa-check-circle' : ($status === 'pending' ? 'fa-clock' : 'fa-times-circle');
            echo "<tr>";
            echo "<td style='font-weight:600; color:#0a3d0a;'>" . $row["customer_code"] . "</td>";
            echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
            echo "<td>" . $row["contact_number"] . "</td>";
            echo "<td>" . $row["present_city"] . ", " . $row["present_province"] . "</td>";
            echo "<td><span class='status-badge " . $status . "'><i class='fas " . $statusIcon . "'></i> " . ucfirst($status) . "</span></td>";
            echo "<td class='text-center'>
                    <a href='view_customer.php?id=" . $row["id"] . "' class='action-icon view-icon' title='View'><i class='fas fa-eye'></i></a>
                    <a href='edit_customer.php?id=" . $row["id"] . "' class='action-icon edit-icon' title='Edit'><i class='fas fa-pen-to-square'></i></a>
                    <a href='delete_customer.php?id=" . $row["id"] . "' class='action-icon delete-icon' title='Delete' onclick='return confirm(\"Are you sure you want to delete this customer?\")'><i class='fas fa-trash'></i></a>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center py-3 text-muted'>No customers found matching your search.</td></tr>";
    }
    exit();
}
// Top of file continues...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Powersim Phoneshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .action-icon { margin: 0 5px; font-size: 1.1rem; transition: transform 0.2s; display: inline-block; }
        .action-icon:hover { transform: scale(1.2); }
        .view-icon { color: #17a2b8; }
        .edit-icon { color: #28a745; }
        .delete-icon { color: #dc3545; }
        th { text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.85rem; }
        /* Account Status Badges */
        .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; text-transform: capitalize; }
        .status-badge.approved { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.rejected { background: #fee2e2; color: #991b1b; }
        .status-badge i { font-size: 0.7rem; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <?php
        $customersSql = "SELECT * FROM customers WHERE is_deleted = 0 ORDER BY created_at DESC";
        $customersResult = $conn->query($customersSql);
        $totalCustomers = $customersResult ? $customersResult->num_rows : 0;
    ?>

    <div class="main-content-wrapper">
        <div class="container main-content py-4">
            <div class="page-hero">
                <div>
                    <h2 class="page-hero-title"><i class="fas fa-users"></i> Customer Management</h2>
                    <p class="page-hero-subtitle">Manage your customer database.</p>
                </div>
                <div class="page-hero-actions">
                    <div class="badge bg-light text-dark" style="padding:10px 14px; border:1px solid #e5e7eb; border-radius:10px; font-weight:600;">
                        Total Customers: <?php echo $totalCustomers; ?>
                    </div>
                    <a href="add_customer.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Customer</a>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="flex-grow-1">
                            <input type="text" id="customerSearch" class="form-control" placeholder="Search by Name, ID, or Contact...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Customer ID</th>
                                    <th>Full Name</th>
                                    <th>Contact</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customerTableBody">
                                <?php
                                if ($customersResult && $customersResult->num_rows > 0) {
                                    while($row = $customersResult->fetch_assoc()) {
                                        $status = $row['account_status'] ?? 'approved';
                                        $statusIcon = $status === 'approved' ? 'fa-check-circle' : ($status === 'pending' ? 'fa-clock' : 'fa-times-circle');
                                        echo "<tr>";
                                        echo "<td style='font-weight:600; color:#0a3d0a;'>" . $row["customer_code"] . "</td>";
                                        echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
                                        echo "<td>" . $row["contact_number"] . "</td>";
                                        echo "<td>" . $row["present_city"] . ", " . $row["present_province"] . "</td>";
                                        echo "<td><span class='status-badge " . $status . "'><i class='fas " . $statusIcon . "'></i> " . ucfirst($status) . "</span></td>";
                                        echo "<td class='text-center'>
                                                <a href='view_customer.php?id=" . $row["id"] . "' class='action-icon view-icon' title='View'><i class='fas fa-eye'></i></a>
                                                <a href='edit_customer.php?id=" . $row["id"] . "' class='action-icon edit-icon' title='Edit'><i class='fas fa-pen-to-square'></i></a>
                                                <a href='delete_customer.php?id=" . $row["id"] . "' class='action-icon delete-icon' title='Delete' onclick='return confirm(\"Are you sure you want to delete this customer?\")'><i class='fas fa-trash'></i></a>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='text-center py-3 text-muted'>No customers found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

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
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('customerSearch');
        const tableBody = document.getElementById('customerTableBody');
        let debounceTimer;

        function fetchResults() {
            const searchQuery = searchInput.value;
            
            fetch(`customers.php?ajax=1&search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.text())
                .then(html => {
                    tableBody.innerHTML = html;
                })
                .catch(error => console.error('Error fetching customers:', error));
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchResults, 300);
        });
    });
    </script>
</body>
</html>