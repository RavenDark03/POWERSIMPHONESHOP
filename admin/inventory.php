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

    $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id";
    
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
            
            $displayName = $row['item_description']; // Default to description/notes

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
                echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . $displayName . "</div>";
            }
            echo "<div style='font-size:0.85rem; color:#666;'>" . (!empty($row['item_type']) ? $row['item_type'] : $row['category']) . "</div>";
            echo "</td>";
            echo "<td>" . number_format($row['loan_amount'], 2) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
            echo "<td><span style='background:#f0f0f0; padding:2px 6px; border-radius:4px; font-weight:500; font-size:0.85rem;'>" . ucfirst($row['status']) . "</span></td>";
            echo "<td>";
            if ($row['status'] == 'pawned') {
                    echo "<span style='color: #888; font-size: 0.8rem;'>Active</span>";
            } elseif ($row['status'] == 'redeemed') {
                    echo "<span style='color: #2e7d32; font-weight:bold; font-size: 0.8rem;'>Redeemed</span>";
            } elseif ($row['status'] == 'for_sale') {
                echo "<form action='change_status.php' method='POST' style='display:inline-flex; align-items:center; gap:5px;'>
                                <input type='hidden' name='id' value='" . $row['id'] . "'>
                                <input type='hidden' name='status' value='sold'>
                                <input type='number' name='sale_price' placeholder='Price' step='0.01' required style='width: 80px; padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;'>
                                <button type='submit' class='btn' style='padding: 4px 10px; font-size: 0.8rem; height: auto; min-width: auto;'>Sell</button>
                            </form>";
            } elseif ($row['status'] == 'sold') {
                echo "<span style='color: #d32f2f; font-weight:bold; font-size: 0.8rem;'>Sold</span>";
            }

            // DELETE BUTTON (Admin Only)
            if ($_SESSION['role'] === 'admin') {
                echo " <a href='delete_item.php?id=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this item? This will permanently remove all associated transaction history.');\" style='color: #d32f2f; margin-left:10px; text-decoration:none;' title='Delete Item'>&#128465;</a>";
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
                    <th>Actions</th>
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

                $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name FROM items JOIN customers ON items.customer_id = customers.id";
                if (count($conditions) > 0) { $sql .= " WHERE " . implode(' AND ', $conditions); }
                $sql .= " ORDER BY items.created_at DESC";

                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                        echo "<td>" . $row['customer_name'] . "</td>";
                        echo "<td style='vertical-align: middle;'>";
                        
                        $displayName = $row['item_description'];
                        if (empty($displayName) || strlen($displayName) < 5) {
                            if (!empty($row['brand'])) {
                                $displayName = $row['brand'] . ' ' . $row['model'];
                            } elseif (!empty($row['item_type'])) {
                                $displayName = $row['item_type'];
                                if (!empty($row['purity'])) { $displayName = $row['purity'] . ' ' . $displayName; }
                            }
                        }

                        if (!empty($displayName)) {
                            echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . $displayName . "</div>";
                        }
                        echo "<div style='font-size:0.85rem; color:#666;'>" . (!empty($row['item_type']) ? $row['item_type'] : $row['category']) . "</div>";
                        echo "</td>";
                        echo "<td>" . number_format($row['loan_amount'], 2) . "</td>";
                        echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
                        echo "<td><span style='background:#f0f0f0; padding:2px 6px; border-radius:4px; font-weight:500; font-size:0.85rem;'>" . ucfirst($row['status']) . "</span></td>";
                        echo "<td>";
                        if ($row['status'] == 'pawned') {
                             echo "<span style='color: #888; font-size: 0.8rem;'>Active</span>";
                        } elseif ($row['status'] == 'redeemed') {
                             echo "<span style='color: #2e7d32; font-weight:bold; font-size: 0.8rem;'>Redeemed</span>";
                        } elseif ($row['status'] == 'for_sale') {
                            echo "<form action='change_status.php' method='POST' style='display:inline-flex; align-items:center; gap:5px;'>
                                <input type='hidden' name='id' value='" . $row['id'] . "'>
                                <input type='hidden' name='status' value='sold'>
                                <input type='number' name='sale_price' placeholder='Price' step='0.01' required style='width: 80px; padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;'>
                                <button type='submit' class='btn' style='padding: 4px 10px; font-size: 0.8rem; height: auto; min-width: auto;'>Sell</button>
                            </form>";
                        } elseif ($row['status'] == 'sold') {
                            echo "<span style='color: #d32f2f; font-weight:bold; font-size: 0.8rem;'>Sold</span>";
                        }

                        // DELETE BUTTON (Admin Only)
                        if ($_SESSION['role'] === 'admin') {
                            echo " <a href='delete_item.php?id=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this item? This will permanently remove all associated transaction history.');\" style='color: #d32f2f; margin-left:10px; text-decoration:none;' title='Delete Item'>&#128465;</a>";
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