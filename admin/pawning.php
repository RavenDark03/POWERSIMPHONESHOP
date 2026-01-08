<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['ajax'])) {
    if (!empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $id_search = preg_replace('/[^0-9]/', '', $search);
        
        $term = "'%$search%'";
        // Search in items (description, brand, model, type, serial) OR customer name
        // Constraint: WHERE items.status = 'pawned'
        
        $search_clause = "(items.item_description LIKE $term OR items.brand LIKE $term OR items.model LIKE $term OR items.item_type LIKE $term OR items.serial_number LIKE $term OR CONCAT(customers.first_name, ' ', customers.last_name) LIKE $term)";
        
        if (!empty($id_search)) {
            $search_clause .= " OR items.id = '$id_search'";
        }
        
        $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id WHERE items.status = 'pawned' AND ($search_clause) ORDER BY items.created_at DESC";
    } else {
        $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id WHERE items.status = 'pawned' ORDER BY items.created_at DESC";
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='font-weight:600; color:#0a3d0a;'>" . sprintf("PT-%06d", $row['id']) . "</td>";
            echo "<td>" . $row['customer_name'] . "</td>";
            echo "<td style='vertical-align: middle;'>";

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

            if (!empty($displayName)) {
                echo "<div style='font-weight: 500; marginBottom: 2px;'>" . $displayName . "</div>";
            }
            echo "<div style='font-size:0.8rem; color:#666;'>" . (!empty($row['item_type']) ? $row['item_type'] : $row['category']) . "</div>";
            echo "</td>";
            echo "<td style='font-weight:500;'>₱" . number_format($row['loan_amount'], 2) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
            echo "<td><span style='background:#e8f5e9; color:#2e7d32; padding:3px 8px; border-radius:12px; font-size:0.8rem; font-weight:500;'>" . ucfirst($row['status']) . "</span></td>";
            echo "<td style='text-align: center;'>
                    <a href='view_pawn.php?id=" . $row['id'] . "' class='action-icon view-icon' title='View Details'><i class='fas fa-eye'></i></a>
                    <a href='renew_pawn.php?id=" . $row['id'] . "' class='action-icon edit-icon' title='Renew'><i class='fas fa-sync-alt'></i></a>
                    <a href='redeem_pawn.php?id=" . $row['id'] . "' class='action-icon view-icon' title='Redeem' style='color:#28a745;'><i class='fas fa-hand-holding-usd'></i></a>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>No pawned items found matching your search.</td></tr>";
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
    <title>Pawning</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .action-icon { margin: 0 5px; font-size: 1.1rem; transition: transform 0.2s; display: inline-block; }
        .action-icon:hover { transform: scale(1.2); }
        .view-icon { color: #17a2b8; }
        .edit-icon { color: #28a745; }
        .delete-icon { color: #dc3545; }
        th { text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.85rem; }
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
                    <li><a href="pawning.php" style="color: #d4af37;">Pawning</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container main-content">
        <div style="margin-bottom: 20px;">
            <h2 style="margin-bottom: 5px;">Pawned Items</h2>
            <p style="color: #666; font-size: 0.9rem;">Manage active pawn transactions</p>
        </div>

        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="flex: 1;">
                <!-- Search Bar -->
                <div style="margin-bottom: 15px;">
                     <input type="text" id="pawnSearch" placeholder="Search by ID, Item, or Customer..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-size: 0.95rem;">
                </div>

                <table class="customer-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Item</th>
                            <th>Loan Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="pawnTableBody">
                        <?php
                        // Fetch pawned items
                        $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id WHERE items.status = 'pawned' ORDER BY items.created_at DESC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td style='font-weight:600; color:#0a3d0a;'>" . sprintf("PT-%06d", $row['id']) . "</td>";
                                echo "<td>" . $row['customer_name'] . "</td>";
                                echo "<td style='vertical-align: middle;'>";

                                $displayName = "";
                                $subText = "";

                                // Prioirty 1: Brand + Model (Common for Gadgets)
                                if (!empty($row['brand']) || !empty($row['model'])) {
                                    $displayName = trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''));
                                    $subText = $row['item_type']; // e.g. Smartphone
                                } 
                                // Priority 2: Purity + Item Type (Common for Jewelry)
                                elseif (!empty($row['purity'])) {
                                    $displayName = $row['purity'] . ' ' . $row['item_type'];
                                    $subText = $row['weight_grams'] . 'g ' . $row['category']; 
                                }
                                // Fallback: Description or Item Type
                                else {
                                    $displayName = !empty($row['item_description']) ? $row['item_description'] : $row['item_type'];
                                    $subText = $row['category'];
                                }

                                if (!empty($displayName)) {
                                    echo "<div style='font-weight: 600; font-size: 0.95rem; color: #333; margin-bottom: 3px;'>" . $displayName . "</div>";
                                }
                                if (!empty($row['serial_number'])) {
                                     echo "<div style='font-size: 0.8rem; color: #666; font-family: monospace;'>SN: " . $row['serial_number'] . "</div>";
                                } else {
                                     echo "<div style='font-size: 0.8rem; color: #777;'>" . $subText . "</div>";
                                }
                                echo "</td>";
                                echo "<td style='font-weight:500;'>₱" . number_format($row['loan_amount'], 2) . "</td>";
                                echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
                                echo "<td><span style='background:#e8f5e9; color:#2e7d32; padding:3px 8px; border-radius:12px; font-size:0.8rem; font-weight:500;'>" . ucfirst($row['status']) . "</span></td>";
                                echo "<td style='text-align: center;'>
                                        <a href='view_pawn.php?id=" . $row['id'] . "' class='action-icon view-icon' title='View Details'><i class='fas fa-eye'></i></a>
                                        <a href='renew_pawn.php?id=" . $row['id'] . "' class='action-icon edit-icon' title='Renew'><i class='fas fa-sync-alt'></i></a>
                                        <a href='redeem_pawn.php?id=" . $row['id'] . "' class='action-icon view-icon' title='Redeem'><i class='fas fa-hand-holding-usd'></i></a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>No pawned items found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div style="width: 200px; padding-top: 65px;"> <!-- Added padding-top to align with table -->
                <a href="new_pawn.php" class="btn"><i class="fas fa-plus"></i> New Pawn</a>
                <div style="margin-top: 20px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <h4 style="margin: 0 0 10px 0; color: #0a3d0a; font-size: 0.9rem;">Quick Stats</h4>
                    <p style="margin: 0; color: #666; font-size: 0.85rem;">
                        <strong>Active:</strong> <?php echo $result->num_rows; ?> Items
                    </p>
                </div>
            </div>
        </div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('pawnSearch');
        const tableBody = document.getElementById('pawnTableBody');
        let debounceTimer;

        function fetchResults() {
            const searchQuery = searchInput.value;
            
            fetch(`pawning.php?ajax=1&search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.text())
                .then(html => {
                    tableBody.innerHTML = html;
                })
                .catch(error => console.error('Error fetching pawn items:', error));
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchResults, 300);
        });
    });
    </script>
</body>
</html>