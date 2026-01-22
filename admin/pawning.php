<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

function build_pawn_query($conn, $searchTerm = '') {
    $where = "items.status = 'pawned'";

    if ($searchTerm !== '') {
        $safeTerm = $conn->real_escape_string($searchTerm);
        $idSearch = preg_replace('/[^0-9]/', '', $searchTerm);
        $like = "'%$safeTerm%'";

        $clauses = [];
        $clauses[] = "(items.item_description LIKE $like OR items.brand LIKE $like OR items.model LIKE $like OR items.item_type LIKE $like OR items.serial_number LIKE $like OR CONCAT(customers.first_name, ' ', customers.last_name) LIKE $like)";
        if (!empty($idSearch)) {
            $clauses[] = "items.id = '$idSearch'";
        }

        $where .= ' AND (' . implode(' OR ', $clauses) . ')';
    }

    $query = "SELECT items.*, CONCAT(customers.first_name, ' ', customers.last_name) AS customer_name,
                COALESCE(ic.name, items.category) AS category_display,
                COALESCE(it.name, items.item_type) AS item_type_display,
                COALESCE(cond.name, items.item_condition) AS item_condition_display,
                COALESCE(n.notes, items.item_description) AS item_description_display
            FROM items
            JOIN customers ON items.customer_id = customers.id
            LEFT JOIN item_categories ic ON ic.name = items.category
            LEFT JOIN item_types it ON it.name = items.item_type
            LEFT JOIN item_conditions cond ON cond.name = items.item_condition
            LEFT JOIN item_notes n ON n.item_id = items.id
            WHERE $where
            ORDER BY items.created_at DESC";

    return $query;
}

function render_pawn_row($row) {
    ob_start();
    ?>
    <tr>
        <td class="fw-semibold text-success"><?php echo sprintf('PT-%06d', $row['id']); ?></td>
        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
        <td><?php echo htmlspecialchars($row['category_display']); ?></td>
        <td><?php echo htmlspecialchars($row['item_type_display']); ?></td>
        <td><?php echo htmlspecialchars($row['item_condition_display']); ?></td>
        <td class="fw-semibold">₱<?php echo number_format($row['loan_amount'], 2); ?></td>
        <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
        <td><span class="status-pill">Pawned</span></td>
        <td class="text-center">
            <a href="view_pawn.php?id=<?php echo $row['id']; ?>" class="action-icon view-icon" title="View Details"><i class="fas fa-eye"></i></a>
            <a href="pawn_processing.php" class="action-icon edit-icon" title="Process Redemption/Renewal"><i class="fas fa-cash-register"></i></a>
        </td>
    </tr>
    <?php
    return ob_get_clean();
}

// AJAX request: return just table rows
if (isset($_GET['ajax'])) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $result = $conn->query(build_pawn_query($conn, $search));

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo render_pawn_row($row);
        }
    } else {
        echo "<tr><td colspan='9' class='text-center py-3 text-muted'>No pawned items found.</td></tr>";
    }
    exit();
}

// Initial load query
$pawnResult = $conn->query(build_pawn_query($conn));
$totalActive = ($pawnResult) ? $pawnResult->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pawning</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .status-pill { display: inline-block; padding: 5px 12px; border-radius: 14px; font-size: 0.83rem; font-weight: 600; background: #e8f5e9; color: #1f7a3b; }
        .action-icon { margin: 0 6px; font-size: 1.05rem; transition: transform 0.2s ease, color 0.2s ease; color: #0a3d0a; display: inline-block; }
        .action-icon:hover { transform: scale(1.12); color: #145214; }
        .view-icon { color: #0a6abf; }
        .edit-icon { color: #0a6abf; }
        .redeem-icon { color: #1f7a3b; }
        .redeem-icon:hover { color: #145214; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
        <div class="container main-content py-4">
            <div class="page-hero">
                <div>
                    <h2 class="page-hero-title"><i class="fas fa-hand-holding-dollar"></i> Pawning</h2>
                    <p class="page-hero-subtitle">Manage active pawn tickets.</p>
                </div>
                <div class="page-hero-actions">
                    <div class="badge bg-light text-dark" style="padding:10px 14px; border:1px solid #e5e7eb; border-radius:10px; font-weight:600;">
                        Active Tickets: <?php echo $totalActive; ?>
                    </div>
                    <a href="new_pawn.php" class="btn btn-primary btn-sm"><i class="fas fa-balance-scale"></i> New Appraisal</a>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="flex-grow-1">
                            <input type="text" id="pawnSearch" class="form-control" placeholder="Search by description, brand, model, serial, customer, or PT#">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Customer</th>
                                    <th>Category</th>
                                    <th>Item Type</th>
                                    <th>Condition</th>
                                    <th>Loan Amount</th>
                                    <th>Due Date</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pawnTableBody">
                                <?php
                                if ($pawnResult && $pawnResult->num_rows > 0) {
                                    while ($row = $pawnResult->fetch_assoc()) {
                                        echo render_pawn_row($row);
                                    }
                                } else {
                                    echo "<tr><td colspan='9' class='text-center py-3 text-muted'>No pawned items found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('pawnSearch');
        const tableBody = document.getElementById('pawnTableBody');
        let debounceTimer;

        function fetchResults() {
            const searchQuery = searchInput.value || '';
            fetch(`pawning.php?ajax=1&search=${encodeURIComponent(searchQuery)}`)
                .then(response => response.text())
                .then(html => {
                    tableBody.innerHTML = html;
                })
                .catch(error => console.error('Error fetching pawn items:', error));
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchResults, 250);
        });
    });
    </script>
</body>
</html>
