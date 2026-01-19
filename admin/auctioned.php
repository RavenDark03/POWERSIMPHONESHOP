<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

// Handle AJAX search/filter request
if (isset($_GET['ajax'])) {
    $conditions = [];
    
    if (!empty($_GET['search'])) {
        $search = $conn->real_escape_string($_GET['search']);
        $id_search = preg_replace('/[^0-9]/', '', $search);
        
        $term = "'%$search%'";
        $clause = "(items.item_description LIKE $term OR items.brand LIKE $term OR items.model LIKE $term OR items.serial_number LIKE $term OR CONCAT(customers.first_name, ' ', customers.last_name) LIKE $term)";
        
        if (!empty($id_search)) {
            $clause = "($clause OR items.id = '$id_search')";
        }
        
        $conditions[] = $clause;
    }

    // Auctioned items status
    $conditions[] = "items.status = 'auctioned'";

    $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name
            FROM items
            JOIN customers ON items.customer_id = customers.id";
    
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY items.created_at DESC";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
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
                echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . htmlspecialchars($displayName) . "</div>";
            }
            echo "<div style='font-size:0.85rem; color:#666;'>" . htmlspecialchars($row['category']) . "</div>";
            echo "</td>";
            echo "<td>₱" . number_format($row['loan_amount'], 2) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
            
            // Auction status
            $statusClass = 'status-auctioned';
            echo "<td><span class='status-pill " . $statusClass . "'><i class='fas fa-gavel'></i> Auctioned</span></td>";
            
            echo "<td>";
            // Show action form to mark as sold
            echo "<form action='change_status.php' method='POST' style='display:inline-flex; align-items:center; gap:5px;'>";
            echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
            echo "<input type='hidden' name='status' value='sold'>";
            echo "<input type='number' name='sale_price' placeholder='Price' step='0.01' required style='width: 80px; padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;'>";
            echo "<button type='submit' class='btn' style='padding: 4px 10px; font-size: 0.8rem; height: auto; min-width: auto;'>Sold</button>";
            echo "</form>";

            // Archive button (admin only)
            if ($_SESSION['role'] === 'admin') {
                echo " <a href='archive_item.php?id=" . $row['id'] . "' class='action-icon delete-icon' title='Archive Item' onclick=\"return confirm('Archive this item? It will be hidden from active lists without deleting history.');\"><i class='fas fa-box-archive'></i></a>";
            }

            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center; padding:15px; color:#777;'>No auctioned items found</td></tr>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auctioned Items</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --primary-color: #0a3d0a;
            --secondary-color: #145214;
            --accent-color: #d4af37;
            --accent-hover: #b5952f;
        }

        body { font-family: 'Outfit', system-ui, -apple-system, sans-serif; }

        .action-icon { margin: 0 5px; font-size: 1.1rem; transition: transform 0.2s; display: inline-block; }
        .action-icon:hover { transform: scale(1.2); }
        .delete-icon { color: #a67c00; }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #fff;
            background: #f0f0f0;
        }

        .status-auctioned {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
        }

        .status-auctioned i { font-size: 0.9rem; }

        /* Container and header styling */
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .main-content { padding: 30px 0; }

        /* Section header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section-header h2 {
            margin: 0;
            color: var(--primary-color);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            padding: 10px 15px;
            border: 1.5px solid #dbeafe;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Outfit', sans-serif;
            min-width: 250px;
            transition: all 0.2s;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .btn-primary {
            background: var(--accent-color);
            color: #0b132b;
            border: 1px solid var(--accent-hover);
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        /* Table styling */
        .table-responsive {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: linear-gradient(135deg, rgba(10, 61, 10, 0.05), rgba(212, 175, 55, 0.03));
            border-bottom: 2px solid var(--primary-color);
        }

        table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 700;
            color: var(--primary-color);
            font-size: 0.9rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            color: #333;
            font-size: 0.95rem;
        }

        table tbody tr {
            transition: background-color 0.2s;
        }

        table tbody tr:hover {
            background-color: rgba(10, 61, 10, 0.02);
        }

        table tbody tr:last-child td {
            border-bottom: none;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #666;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 12px;
            opacity: 0.3;
        }

        .empty-state p {
            margin: 0;
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                flex-direction: column;
            }

            .search-box {
                min-width: 100%;
            }

            .btn-primary {
                width: 100%;
                justify-content: center;
            }

            table {
                font-size: 0.85rem;
            }

            table th, table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container main-content">
        <div class="section-header">
            <div>
                <h2><i class="fas fa-gavel"></i> Auctioned Items</h2>
                <p>Manage and track items that are up for auction</p>
            </div>
            <div class="header-actions">
                <input type="text" id="searchInput" class="search-box" placeholder="Search by Item, Customer, ID..." aria-label="Search items">
            </div>
        </div>

        <div class="table-responsive">
            <table id="auctionedTable">
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
                <tbody>
                    <?php
                    // Initial load - show auctioned items
                    $sql = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name
                            FROM items
                            JOIN customers ON items.customer_id = customers.id
                            WHERE items.status = 'auctioned'
                            ORDER BY items.created_at DESC";
                    
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . sprintf("PT-%06d", $row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['customer_name']) . "</td>";
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
                                echo "<div style='font-weight: 500; margin-bottom: 2px;'>" . htmlspecialchars($displayName) . "</div>";
                            }
                            echo "<div style='font-size:0.85rem; color:#666;'>" . htmlspecialchars($row['category']) . "</div>";
                            echo "</td>";
                            echo "<td>₱" . number_format($row['loan_amount'], 2) . "</td>";
                            echo "<td>" . date('M d, Y', strtotime($row['due_date'])) . "</td>";
                            
                            echo "<td><span class='status-pill status-auctioned'><i class='fas fa-gavel'></i> Auctioned</span></td>";
                            
                            echo "<td>";
                            echo "<form action='change_status.php' method='POST' style='display:inline-flex; align-items:center; gap:5px;'>";
                            echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                            echo "<input type='hidden' name='status' value='sold'>";
                            echo "<input type='number' name='sale_price' placeholder='Price' step='0.01' required style='width: 80px; padding: 4px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;'>";
                            echo "<button type='submit' class='btn' style='padding: 4px 10px; font-size: 0.8rem; height: auto; min-width: auto;'>Sold</button>";
                            echo "</form>";

                            if ($_SESSION['role'] === 'admin') {
                                echo " <a href='archive_item.php?id=" . $row['id'] . "' class='action-icon delete-icon' title='Archive Item' onclick=\"return confirm('Archive this item? It will be hidden from active lists without deleting history.');\"><i class='fas fa-box-archive'></i></a>";
                            }

                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'><div class='empty-state'><i class='fas fa-box-open'></i><p>No auctioned items found</p></div></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const auctionedTable = document.getElementById('auctionedTable');

        // Debounce function for search
        function debounce(fn, delay) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn(...args), delay);
            };
        }

        // Perform search
        const performSearch = debounce(function() {
            const searchTerm = searchInput.value.trim();
            
            fetch(`auctioned.php?ajax=1&search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.text())
                .then(html => {
                    const tbody = auctionedTable.querySelector('tbody');
                    if (html.trim() === '') {
                        tbody.innerHTML = "<tr><td colspan='7'><div class='empty-state'><i class='fas fa-search'></i><p>No results found</p></div></td></tr>";
                    } else {
                        tbody.innerHTML = html;
                    }
                })
                .catch(error => console.error('Search error:', error));
        }, 300);

        searchInput.addEventListener('input', performSearch);
    });
    </script>
</body>
</html>
