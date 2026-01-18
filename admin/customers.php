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
            echo "<tr>";
            echo "<td style='font-weight:600; color:#0a3d0a;'>" . $row["customer_code"] . "</td>";
            echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
            echo "<td>" . $row["contact_number"] . "</td>";
            echo "<td>" . $row["present_city"] . ", " . $row["present_province"] . "</td>";
            echo "<td>" . $row["id_type"] . "</td>";
            echo "<td style='text-align: center;'>
                    <a href='view_customer.php?id=" . $row["id"] . "' class='action-icon view-icon' title='View'><i class='fas fa-eye'></i></a>
                    <a href='edit_customer.php?id=" . $row["id"] . "' class='action-icon edit-icon' title='Edit'><i class='fas fa-pen-to-square'></i></a>
                    <a href='delete_customer.php?id=" . $row["id"] . "' class='action-icon delete-icon' title='Delete' onclick='return confirm(\"Are you sure you want to delete this customer?\")'><i class='fas fa-trash'></i></a>
                  </td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>No customers found matching your search.</td></tr>";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .action-icon { margin: 0 5px; font-size: 1.1rem; transition: transform 0.2s; display: inline-block; }
        .action-icon:hover { transform: scale(1.2); }
        .view-icon { color: #17a2b8; }
        .edit-icon { color: #28a745; }
        .delete-icon { color: #dc3545; }
        th { text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.85rem; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
        <div class="container main-content">

        <div style="margin-bottom: 20px;">
            <h2 style="margin-bottom: 5px;">Customer Management</h2>
            <p style="color: #666; font-size: 0.9rem;">Manage your customer database</p>
        </div>
        
        <div style="display: flex; gap: 20px; align-items: flex-start;">
            <div style="flex: 1;">
                <!-- Search Bar -->
                <div style="margin-bottom: 15px;">
                    <input type="text" id="customerSearch" placeholder="Search by Name, ID, or Contact..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); font-size: 0.95rem;">
                </div>

                <table class="customer-table" style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Full Name</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th>ID Type</th>
                            <th></th> <!-- Blank Header for Actions -->
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <?php
                        $sql = "SELECT * FROM customers WHERE is_deleted = 0 ORDER BY created_at DESC";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td style='font-weight:600; color:#0a3d0a;'>" . $row["customer_code"] . "</td>";
                                echo "<td>" . $row["first_name"] . " " . $row["last_name"] . "</td>";
                                echo "<td>" . $row["contact_number"] . "</td>";
                                echo "<td>" . $row["present_city"] . ", " . $row["present_province"] . "</td>";
                                echo "<td>" . $row["id_type"] . "</td>";
                                echo "<td style='text-align: center;'>
                                        <a href='view_customer.php?id=" . $row["id"] . "' class='action-icon view-icon' title='View'><i class='fas fa-eye'></i></a>
                                        <a href='edit_customer.php?id=" . $row["id"] . "' class='action-icon edit-icon' title='Edit'><i class='fas fa-pen-to-square'></i></a>
                                        <a href='delete_customer.php?id=" . $row["id"] . "' class='action-icon delete-icon' title='Delete' onclick='return confirm(\"Are you sure you want to delete this customer?\")'><i class='fas fa-trash'></i></a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>No customers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div style="width: 200px; padding-top: 65px;"> <!-- Added padding-top to align with table -->
                <a href="add_customer.php" class="btn"><i class="fas fa-plus"></i> Add New Customer</a>
                <div style="margin-top: 20px; background: #fff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    <h4 style="margin: 0 0 10px 0; color: #0a3d0a; font-size: 0.9rem;">Quick Stats</h4>
                    <p style="margin: 0; color: #666; font-size: 0.85rem;">
                        <strong>Total:</strong> <?php echo $result->num_rows; ?> Customers
                    </p>
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