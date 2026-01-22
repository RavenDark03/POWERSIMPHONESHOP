<?php
// Include the existing connection file found in the codebase
require_once 'includes/connection.php';

// Configuration for the Admin Account
$admin_username = 'adminMatt';
$admin_password = 'admin123'; // This will be hashed
$admin_email    = 'mattadmin@example.com';
$admin_name     = 'System Administrator';
$admin_role     = 'admin';
$admin_status   = 'approved'; // Must be 'approved' to bypass login checks

echo "<h3>Admin Account Seeder</h3>";

// 1. Check if an admin with this username or email already exists
$check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
$stmt = $conn->prepare($check_sql);

if ($stmt) {
    $stmt->bind_param("ss", $admin_username, $admin_email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<p style='color: orange;'>An account with username '<strong>$admin_username</strong>' or email '<strong>$admin_email</strong>' already exists.</p>";
    } else {
        // 2. Hash the password (REQUIRED for login_process.php)
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

        // 3. Insert the new admin user
        $insert_sql = "INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);

        if ($insert_stmt) {
            $insert_stmt->bind_param("ssssss", 
                $admin_username, 
                $hashed_password, 
                $admin_name, 
                $admin_email, 
                $admin_role, 
                $admin_status
            );

            if ($insert_stmt->execute()) {
                echo "<p style='color: green;'><strong>Success!</strong> Admin account created.</p>";
                echo "<ul>";
                echo "<li>Username: <strong>$admin_username</strong></li>";
                echo "<li>Password: <strong>$admin_password</strong></li>";
                echo "</ul>";
                echo "<p><a href='login.php'>Go to Login Page</a></p>";
            } else {
                echo "<p style='color: red;'>Error inserting admin: " . $insert_stmt->error . "</p>";
            }
            $insert_stmt->close();
        } else {
            echo "<p style='color: red;'>Error preparing insert statement: " . $conn->error . "</p>";
        }
    }
    $stmt->close();
} else {
    echo "<p style='color: red;'>Error checking existing users: " . $conn->error . "</p>";
}

$conn->close();
?>