<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}
?>
<?php
// ... Top of file ...
// Extract the table row logic into a reusable block or handle AJAX request here
if (isset($_GET['ajax'])) {
    $conditions = [];
    
    if (!empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $id_search = preg_replace('/[^0-9]/', '', $search); // Clean for ID search
        
        $term = "'%$search%'";
        // Search by Description, Brand, Model, Serial, Customer Name
        $clause = "(items.item_description LIKE $term OR items.brand LIKE $term OR items.model LIKE $term OR items.serial_number LIKE $term OR CONCAT(customers.first_name, ' ', customers.last_name) LIKE $term)";
        
        // Search by ID if numeric part exists
        if (!empty($id_search)) {
            $clause = "($clause OR items.id = '$id_search')";
        }
        
        $conditions[] = $clause;
    }

    if (!empty($_GET['status']) && $_GET['status'] != 'all') {
        $status = $conn->real_escape_string($_GET['status']);
        $conditions[] = "items.status = '$status'";
    }

    $sql = "SELECT items.*, items.category_id, items.item_type_id, items.condition_id, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name,
                COALESCE(ic.name, items.category) AS category_display,
                COALESCE(it.name, items.item_type) AS item_type_display,
                COALESCE(cond.name, items.item_condition) AS item_condition_display,
                COALESCE(n.notes, items.item_description) AS item_description_display
            FROM items
            JOIN customers ON items.customer_id = customers.id
            LEFT JOIN item_categories ic ON (ic.id = items.category_id OR ic.name = items.category)
            LEFT JOIN item_types it ON (it.id = items.item_type_id OR it.name = items.item_type)
            LEFT JOIN item_conditions cond ON (cond.id = items.condition_id OR cond.name = items.item_condition)
            LEFT JOIN item_notes n ON n.item_id = items.id";
    
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY items.created_at DESC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
            echo "<td>" . $row['customer_name'] . "</td>";
            echo "<td style='vertical-align: middle;'>";
            
            $displayName = $row['item_description_display']; // Default to description/notes (notes table preferred)

            if (empty($displayName) || strlen($displayName) < 5) {
                if (!empty($row['brand'])) {
                    $displayName = $row['brand'] . ' ' . $row['model'];
                } elseif (!empty($row['item_type_display'])) {
                    $displayName = $row['item_type_display'];
                    if (!empty($row['purity'])) {
                        $displayName = $row['purity'] . ' ' . $displayName;
                    }
                }
            }

                if (!empty($displayName)) {
                    echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . $displayName . "</div>";
                }
                $meta = '';
                if (!empty($row['item_type_id'])) { $meta .= 'type_id:' . $row['item_type_id']; }
                if (!empty($row['category_id'])) { $meta .= ($meta ? ' | ' : '') . 'category_id:' . $row['category_id']; }
                if (!empty($row['condition_id'])) { $meta .= ($meta ? ' | ' : '') . 'condition_id:' . $row['condition_id']; }
                $sub = !empty($row['item_type_display']) ? $row['item_type_display'] : $row['category_display'];
                echo "<div style='font-size:0.85rem; color:#666;'>" . $sub . (empty($meta) ? '' : " <span style='color:#999;'>[".$meta."]</span>") . "</div>";
            echo "</td>";
            echo "<td>" . number_format($row['loan_amount'], 2) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
            $statusClass = 'status-' . $row['status'];
            echo "<td><span class='status-pill " . $statusClass . "'>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</span></td>";
            echo "<td>";
                if ($row['status'] == 'pawned') {
                    // Active status removed - no display needed
                } elseif ($row['status'] == 'redeemed') {
                    echo "<span class='action-pill' style='background:#e9f9f1; color:#1f7a3b;'>Redeemed</span>";
                } elseif ($row['status'] == 'for_sale') {
                echo "<form action='change_status.php' method='POST' style='display:inline-flex; align-items:center; gap:5px;'>
                                <input type='hidden' name='id' value='" . $row['id'] . "'>
                                <input type='hidden' name='status' value='sold'>
                                <input type='number' name='sale_price' placeholder='Price' step='0.01' required style='width: 80px; padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;'>
                                <button type='submit' class='btn' style='padding: 4px 10px; font-size: 0.8rem; height: auto; min-width: auto;'>Sell</button>
                            </form>";
            } elseif ($row['status'] == 'sold') {
                echo "<span class='action-pill' style='background:#fdecea; color:#a62b2b;'>Sold</span>";
            }

            // ARCHIVE BUTTON (Admin Only)
            if ($_SESSION['role'] === 'admin') {
                echo " <a href='archive_item.php?id=" . $row['id'] . "' class='action-icon delete-icon' title='Archive Item' onclick=\"return confirm('Archive this item? It will be hidden from active lists without deleting history.');\"><i class='fas fa-box-archive'></i></a>";
            }

            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7'>No items found matching your search</td></tr>";
    }
    exit(); // Stop execution here for AJAX calls
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .action-icon { margin: 0 5px; font-size: 1.1rem; transition: transform 0.2s; display: inline-block; }
        .action-icon:hover { transform: scale(1.2); }
        .view-icon { color: #17a2b8; }
        .edit-icon { color: #28a745; }
        .delete-icon { color: #a67c00; }

        /* Pill-style status indicators */
        .status-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px; /* pill */
            font-weight: 600;
            font-size: 0.85rem;
            color: #23312a;
            background: #f0f0f0;
            box-shadow: none;
        }
        .status-pawned { background: #e8f7ee; color: #20723a; }
        .status-redeemed { background: #e9f9f1; color: #1f7a3b; }
        .status-for_sale { background: #fff7e6; color: #a35400; }
        .status-sold { background: #fdecea; color: #a62b2b; }
        .status-archived { background: #f0f0f0; color: #666; }
        /* Small action pill used for inline action labels like 'Active' */
        .action-pill { display:inline-block; padding:4px 10px; border-radius:999px; font-weight:600; font-size:0.85rem; }
        .action-active { background:#2b6ef6; color:#fff; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container">
        <h2>Inventory Management</h2>
        <div class="search-container" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <form id="inventoryFilterForm" method="GET" action="inventory.php" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <label for="search" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Search</label>
                    <input type="text" name="search" id="search" placeholder="Item Name, ID, or Customer" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="width: 200px;">
                    <label for="status" style="display: block; margin-bottom: 8px; font-weight: 500; color: #333;">Status</label>
                    <select name="status" id="status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="all">All Statuses</option>
                        <option value="pawned" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pawned') ? 'selected' : ''; ?>>Pawned</option>
                        <option value="for_sale" <?php echo (isset($_GET['status']) && $_GET['status'] == 'for_sale') ? 'selected' : ''; ?>>For Sale</option>
                        <option value="sold" <?php echo (isset($_GET['status']) && $_GET['status'] == 'sold') ? 'selected' : ''; ?>>Sold</option>
                        <option value="redeemed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'redeemed') ? 'selected' : ''; ?>>Redeemed</option>
                        <option value="archived" <?php echo (isset($_GET['status']) && $_GET['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>

            </form>
        </div>

        <table class="customer-table">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Loan Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>        </th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                <?php
                // Initial Load (Non-AJAX)
                // Reuse the same logic logic by calling the query or simply copying the block above.
                // For simplicity in this single-file edit, I will copy the logic structure one last time to ensure it works even if JS is disabled.
                // Ideally, this should be refactored into a function.
                
                $conditions = [];
                if (!empty($_GET['search'])) {
                    $search = $conn->real_escape_string($_GET['search']);
                    $id_search = preg_replace('/[^0-9]/', '', $search);
                    $term = "'%$search%'";
                    $clause = "(items.item_description LIKE $term OR items.brand LIKE $term OR items.model LIKE $term OR items.serial_number LIKE $term OR CONCAT(customers.first_name, ' ', customers.last_name) LIKE $term)";
                    if (!empty($id_search)) { $clause = "($clause OR items.id = '$id_search')"; }
                    $conditions[] = $clause;
                }
                if (!empty($_GET['status']) && $_GET['status'] != 'all') {
                    $status = $conn->real_escape_string($_GET['status']);
                    $conditions[] = "items.status = '$status'";
                }

                $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name,
                    COALESCE(ic.name, items.category) AS category_display,
                    COALESCE(it.name, items.item_type) AS item_type_display,
                    COALESCE(cond.name, items.item_condition) AS item_condition_display,
                    COALESCE(n.notes, items.item_description) AS item_description_display
                FROM items
                JOIN customers ON items.customer_id = customers.id
                LEFT JOIN item_categories ic ON ic.name = items.category
                LEFT JOIN item_types it ON it.name = items.item_type
                LEFT JOIN item_conditions cond ON cond.name = items.item_condition
                LEFT JOIN item_notes n ON n.item_id = items.id";
                if (count($conditions) > 0) { $sql .= " WHERE " . implode(' AND ', $conditions); }
                $sql .= " ORDER BY items.created_at DESC";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                        echo "<td>" . $row['customer_name'] . "</td>";
                        echo "<td style='vertical-align: middle;'>";
                        
                        $displayName = $row['item_description_display'];
                        if (empty($displayName) || strlen($displayName) < 5) {
                            if (!empty($row['brand'])) {
                                $displayName = $row['brand'] . ' ' . $row['model'];
                            } elseif (!empty($row['item_type_display'])) {
                                $displayName = $row['item_type_display'];
                                if (!empty($row['purity'])) { $displayName = $row['purity'] . ' ' . $displayName; }
                            }
                        }

                        if (!empty($displayName)) {
                            echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . $displayName . "</div>";
                        }
                        echo "<div style='font-size:0.85rem; color:#666;'>" . (!empty($row['item_type_display']) ? $row['item_type_display'] : $row['category_display']) . "</div>";
                        echo "</td>";
                        echo "<td>" . number_format($row['loan_amount'], 2) . "</td>";
                        echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
                        $statusClass = 'status-' . $row['status'];
                        echo "<td><span class='status-pill " . $statusClass . "'>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</span></td>";
                        echo "<td>";
                        if ($row['status'] == 'pawned') {
                             // Active status removed - no display needed
                        } elseif ($row['status'] == 'for_sale') {
                            echo "<form action='change_status.php' method='POST' style='display:inline-flex; align-items:center; gap:5px;'>
                                <input type='hidden' name='id' value='" . $row['id'] . "'>
                                <input type='hidden' name='status' value='sold'>
                                <input type='number' name='sale_price' placeholder='Price' step='0.01' required style='width: 80px; padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;'>
                                <button type='submit' class='btn' style='padding: 4px 10px; font-size: 0.8rem; height: auto; min-width: auto;'>Sell</button>
                            </form>";
                        }

                        // DELETE BUTTON (Admin Only)
                        if ($_SESSION['role'] === 'admin') {
                            echo " <a href='delete_item.php?id=" . $row['id'] . "' class='action-icon delete-icon' title='Delete Item' onclick=\"return confirm('Are you sure you want to delete this item? This will permanently remove all associated transaction history.');\"><i class='fas fa-trash'></i></a>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No items found matching your search</td></tr>";
                }
                ?>
            </tbody>
        </table>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        const statusSelect = document.getElementById('status');
        const tableBody = document.getElementById('inventoryTableBody');
        const form = document.getElementById('inventoryFilterForm');

        let debounceTimer;

        function fetchResults() {
            const searchQuery = searchInput.value;
            const statusValue = statusSelect.value;
            
            // Update URL
            const url = new URL(window.location);
            if (searchQuery) url.searchParams.set('search', searchQuery); else url.searchParams.delete('search');
            if (statusValue !== 'all') url.searchParams.set('status', statusValue); else url.searchParams.delete('status');
            window.history.pushState({}, '', url);

            // Fetch Data
            fetch(`inventory.php?ajax=1&search=${encodeURIComponent(searchQuery)}&status=${encodeURIComponent(statusValue)}`)
                .then(response => response.text())
                .then(html => {
                    tableBody.innerHTML = html;
                })
                .catch(error => console.error('Error fetching inventory:', error));
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchResults, 300);
        });

        statusSelect.addEventListener('change', function() {
            fetchResults();
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            fetchResults();
        });
    });
    </script>
</body>
</html>