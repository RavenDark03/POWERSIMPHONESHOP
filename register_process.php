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
        echo "Passwords do not match.";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Validate role
    $allowed_roles = ['staff', 'manager', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        echo "Invalid role selected.";
        exit();
    }

    // Default status is pending
    $status = 'pending';

    $sql = "INSERT INTO users (name, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $name, $email, $username, $hashed_password, $role, $status);

    if ($stmt->execute()) {
        // Send Email to Admin
        require_once 'includes/send_email.php';
        $admin_email = "matthewmarcsantua@gmail.com"; 
        $subject = "New User Registration";
        $message = "A new user has registered.\n\nName: $name\nEmail: $email\nRole: $role\n\nPlease login to approve or reject this request.";
        
        sendEmail($admin_email, $subject, $message);

        echo "<script>alert('Registration successful! Please wait for Admin approval.'); window.location.href='login.php';</script>";
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>