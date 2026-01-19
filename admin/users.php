<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Powersim Phoneshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container">
        <h2>User Management</h2>
        <table class="customer-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td style='font-weight:600; color:#0a3d0a;'>" . sprintf("EMP-%03d", $row["id"]) . "</td>";
                        echo "<td>" . $row["name"] . "</td>";
                        echo "<td>" . $row["email"] . "</td>";
                        echo "<td>" . $row["username"] . "</td>";
                        echo "<td>" . ucfirst($row["role"]) . "</td>";
                        
                        $status_color = 'black';
                        if ($row["status"] == 'pending') $status_color = 'orange';
                        elseif ($row["status"] == 'approved') $status_color = 'green';
                        elseif ($row["status"] == 'rejected') $status_color = 'red';
                        
                        echo "<td style='color: $status_color; font-weight: bold;'>" . ucfirst($row["status"]) . "</td>";
                        echo "<td>";
                        
                        // View/Edit Button
                        echo "<a href='view_user.php?id=" . $row["id"] . "' style='color: #17a2b8; margin-right: 10px; font-size: 1.1rem;' title='View Account'><i class='fas fa-eye'></i></a>";

                        if ($row["status"] == 'pending') {
                            echo "<form action='user_action.php' method='post' style='display:inline;'>
                                    <input type='hidden' name='id' value='" . $row["id"] . "'>
                                    <input type='hidden' name='action' value='approve'>
                                    <button type='submit' style='background:none; border:none; padding:0; cursor:pointer; color:green; font-size:1.1rem; margin-right:5px;' title='Approve'><i class='fas fa-check-circle'></i></button>
                                  </form> ";
                            echo "<form action='user_action.php' method='post' style='display:inline;'>
                                    <input type='hidden' name='id' value='" . $row["id"] . "'>
                                    <input type='hidden' name='action' value='reject'>
                                    <button type='submit' style='background:none; border:none; padding:0; cursor:pointer; color:red; font-size:1.1rem;' title='Reject'><i class='fas fa-times-circle'></i></button>
                                  </form>";
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No users found</td></tr>";
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
    </div>
