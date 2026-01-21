<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
$can_manage_users = in_array($_SESSION['role'] ?? '', ['admin', 'manager']);
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
    <style>
        .form-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:10px; }
        .form-col { flex:1; min-width:220px; }
        .form-col input, .form-col select { width:100%; box-sizing:border-box; }
        .status-badge { padding:4px 10px; border-radius:999px; font-weight:700; font-size:0.86rem; display:inline-block; }
        .status-approved { background:#e8f5e9; color:#1b5e20; }
        .status-pending { background:#fff7e6; color:#c47f00; }
        .status-rejected { background:#ffebee; color:#c62828; }
        .notice { padding:10px 12px; border-radius:8px; margin-bottom:12px; }
        .notice-success { background:#e8f5e9; color:#1b5e20; border:1px solid #c8e6c9; }
        .notice-error { background:#ffebee; color:#c62828; border:1px solid #ffcdd2; }
        .create-user-card { border:1px solid #e6e6e6; border-radius:16px; box-shadow:0 10px 24px rgba(16,24,40,0.08); overflow:hidden; background:#fff; }
        .create-user-head { background:linear-gradient(135deg, #116530 0%, #0a3d0a 100%); color:#fff; padding:16px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .create-user-head h3 { margin:0; font-size:1.1rem; }
        .create-user-head p { margin:2px 0 0; opacity:0.9; font-size:0.9rem; }
        .create-user-body { padding:16px 18px 18px; }
        .badge-soft { background:rgba(255,255,255,0.18); color:#fff; border:1px solid rgba(255,255,255,0.35); padding:4px 10px; border-radius:999px; font-size:0.8rem; }
        .input-soft { background:#f8faf8; border:1px solid #dfe5de; border-radius:10px; padding:12px; font-size:0.97rem; transition:all 0.15s ease; }
        .input-soft:focus { outline:none; border-color:#116530; box-shadow:0 0 0 3px rgba(17,101,48,0.12); background:#fff; }
        label { font-weight:600; color:#2c3a2f; }
        .btn-gold { background:#d4ad32; color:#fff; border:none; border-radius:10px; padding:12px 16px; font-weight:700; box-shadow:0 8px 16px rgba(0,0,0,0.08); transition:transform 0.12s ease, box-shadow 0.12s ease; }
        .btn-gold:hover { transform:translateY(-1px); box-shadow:0 10px 20px rgba(0,0,0,0.12); }
        .btn-ghost { background:#f4f4f4; color:#2f3b2f; border:none; border-radius:10px; padding:12px 16px; font-weight:600; }
    </style>
</head>
<body class="has-sidebar">
    <?php include '../includes/sidebar_nav.php'; ?>

    <header>
        <div class="container"></div>
    </header>

    <div class="main-content-wrapper">
    <div class="container">
        <div class="page-hero">
            <div>
                <h2 class="page-hero-title"><i class="fas fa-user-gear"></i> User Management</h2>
                <p class="page-hero-subtitle">Review user accounts and approvals.</p>
            </div>
        </div>
        <?php
            $notice = '';
            if (isset($_GET['created'])) $notice = '<div class="notice notice-success">Employee account created.</div>';
            elseif (isset($_GET['error'])) $notice = '<div class="notice notice-error">Action failed: ' . htmlspecialchars($_GET['error']) . '.</div>';
        ?>
        <?php if ($notice) echo $notice; ?>
        <?php if ($can_manage_users) { ?>
        <div class="create-user-card" style="margin-bottom:18px;">
            <div class="create-user-head">
                <div>
                    <h3>Create Employee Account</h3>
                    <p>We’ll email a temporary password. Ask the employee to change it on first login.</p>
                </div>
                <span class="badge-soft">Admin &amp; Manager</span>
            </div>
            <div class="create-user-body">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
                    <div style="color:#444; font-size:0.94rem;">Fill in the details, and the system will send a temp password to their email.</div>
                    <button type="button" id="toggleCreateForm" class="btn btn-outline-secondary" style="white-space:nowrap;">Show/Hide Form</button>
                </div>
                <form id="createUserForm" action="user_action.php" method="post" style="display:none; margin-top:8px;">
                    <input type="hidden" name="action" value="create_employee">
                    <div class="form-row">
                        <div class="form-col">
                            <label>Full Name</label>
                            <input type="text" name="name" class="input-soft" required>
                        </div>
                        <div class="form-col">
                            <label>Email</label>
                            <input type="email" name="email" class="input-soft" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Username</label>
                            <input type="text" name="username" class="input-soft" required>
                        </div>
                        <div class="form-col">
                            <label>Role</label>
                            <select name="role" class="input-soft" required>
                                <?php
                                    $role_options = [
                                        'admin' => 'Admin',
                                        'manager' => 'Manager',
                                        'staff' => 'Staff'
                                    ];
                                    foreach ($role_options as $value => $label) {
                                        if (($_SESSION['role'] ?? '') === 'manager' && $value === 'admin') continue; // managers cannot elevate to admin
                                        echo "<option value='{$value}'>" . $label . "</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Status</label>
                            <select name="status" class="input-soft" required>
                                <option value="approved" selected>Approved</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:12px;">
                        <button type="submit" class="btn-gold" style="flex:1;">Create &amp; Send Temp Password</button>
                        <button type="button" id="cancelCreateForm" class="btn-ghost" style="flex:1;">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <?php } ?>
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
                        
                        $status_class = 'status-pending';
                        if ($row["status"] == 'approved') $status_class = 'status-approved';
                        elseif ($row["status"] == 'rejected') $status_class = 'status-rejected';
                        
                        echo "<td><span class='status-badge {$status_class}'>" . ucfirst($row["status"]) . "</span></td>";
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('toggleCreateForm');
            const cancelBtn = document.getElementById('cancelCreateForm');
            const form = document.getElementById('createUserForm');

            if (toggleBtn && form) {
                toggleBtn.addEventListener('click', () => {
                    form.style.display = form.style.display === 'none' ? 'block' : 'none';
                });
            }
            if (cancelBtn && form) {
                cancelBtn.addEventListener('click', () => {
                    form.reset();
                    form.style.display = 'none';
                });
            }

        });
    </script>
