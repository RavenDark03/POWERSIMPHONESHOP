<?php
include 'includes/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        $conn->close();
        exit();
    }

    // Validate role
    $allowed_roles = ['staff', 'manager', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        echo "<script>alert('Invalid role selected.'); window.history.back();</script>";
        $conn->close();
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Default status is pending
    $status = 'pending';

    $sql = "INSERT INTO users (name, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssssss", $name, $email, $username, $hashed_password, $role, $status);

        if ($stmt->execute()) {
            // Send Email to Admin
            require_once 'includes/send_email.php';
            $admin_email = "matthewmarcsantua@gmail.com"; 
            $subject = "New User Registration";
            $message = "A new user has registered.\n\nName: $name\nEmail: $email\nRole: $role\n\nPlease login to approve or reject this request.";
            
            sendEmail($admin_email, $subject, $message);

            echo "<script>alert('Registration successful! Please wait for Admin approval.'); window.location.href='login.php';</script>";
        } else {
            error_log('Execution failed: ' . $stmt->error);
            // Check for duplicate entry
            if ($conn->errno === 1062) {
                echo "<script>alert('Registration failed. The username or email is already taken.'); window.history.back();</script>";
            } else {
                echo "<script>alert('An error occurred during registration. Please try again.'); window.history.back();</script>";
            }
        }
        $stmt->close();
    } else {
        error_log('Prepare failed: ' . $conn->error);
        echo "<script>alert('An error occurred during registration. Please try again.'); window.history.back();</script>";
    }

    $conn->close();
} else {
    // Redirect if not a POST request
    header('Location: register.php');
    exit();
}
?>