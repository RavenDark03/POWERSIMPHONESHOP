<?php
session_start();
include 'includes/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$identifier || !$password) {
        header('Location: login.php?error=invalid'); exit();
    }

    // First try users (staff/admin) by email or username
    $sql = "SELECT id, username, password, role, status FROM users WHERE email = ? OR username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['status'] == 'pending') { header("Location: login.php?error=pending"); exit(); }
                if ($user['status'] == 'rejected') { header("Location: login.php?error=rejected"); exit(); }

                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $stmt->close(); $conn->close();
                header("Location: admin/index.php"); exit();
            } else {
                header("Location: login.php?error=invalid"); exit();
            }
        }
        $stmt->close();
    }

    // If not a staff user, try customers (match by email OR username)
    $sql2 = "SELECT id, customer_code, username, email, password, is_verified FROM customers WHERE email = ? OR username = ? LIMIT 1";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $stmt2->bind_param("ss", $identifier, $identifier);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2->num_rows == 1) {
            $cust = $res2->fetch_assoc();
            if (password_verify($password, $cust['password'])) {
                if (empty($cust['is_verified']) || $cust['is_verified'] == 0) {
                    // not verified yet
                    header("Location: login.php?error=pending"); exit();
                }
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $cust['id'];
                $_SESSION['customer_code'] = $cust['customer_code'];
                $_SESSION['username'] = $cust['username'];
                $_SESSION['email'] = $cust['email'];
                $_SESSION['role'] = 'customer';
                $stmt2->close(); $conn->close();
                header('Location: dashboard.php'); exit();
            } else {
                header('Location: login.php?error=invalid'); exit();
            }
        }
        $stmt2->close();
    }

    // default invalid
    header("Location: login.php?error=invalid");
    $conn->close();
    exit();
}
?>