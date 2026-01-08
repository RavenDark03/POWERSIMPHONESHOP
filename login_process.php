<?php
session_start();
include 'includes/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, role, status FROM users WHERE username = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['status'] == 'pending') {
                header("Location: login.php?error=pending");
                exit();
            } elseif ($user['status'] == 'rejected') {
                header("Location: login.php?error=rejected");
                exit();
            }

            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: admin/index.php");
            exit();
        } else {
            header("Location: login.php?error=invalid");
        }
    } else {
        header("Location: login.php?error=invalid");
    }

    $stmt->close();
    $conn->close();
}
?>