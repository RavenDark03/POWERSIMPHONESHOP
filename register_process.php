<?php
include 'includes/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and validate inputs
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $confirm_email = trim($_POST['confirm_email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
        $username = trim($_POST['username'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        echo "<script>alert('Please fill required fields.'); window.history.back();</script>";
        $conn->close();
        exit();
    }

    if (strtolower($email) !== strtolower($confirm_email)) {
        echo "<script>alert('Emails do not match.'); window.history.back();</script>";
        $conn->close();
        exit();
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        $conn->close();
        exit();
    }
    
        // validate username
        if (empty($username) || !preg_match('/^[A-Za-z0-9._-]{3,20}$/', $username)) {
            echo "<script>alert('Invalid username. Use 3-20 characters: letters, numbers, dot, underscore or dash.'); window.history.back();</script>";
            $conn->close();
            exit();
        }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email address.'); window.history.back();</script>";
        $conn->close();
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Pre-insert uniqueness check for email (friendly error)
    $chk = $conn->prepare('SELECT id FROM customers WHERE email = ? LIMIT 1');
    if ($chk) {
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            echo "<script>alert('This email is already registered. Please login or use another email.'); window.history.back();</script>";
            $chk->close();
            $conn->close();
            exit();
        }
        $chk->close();
    }
    
        // check uniqueness against users table
        $chk = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $chk->bind_param('ss', $username, $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            echo "<script>alert('Username or email already in use by staff account.'); window.history.back();</script>";
            $chk->close();
            $conn->close();
            exit();
        }
        $chk->close();
    
        // ensure username/email uniqueness in customers
        $stmt = $conn->prepare('SELECT id FROM customers WHERE username = ? OR email = ? LIMIT 1');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo "<script>alert('Username or email already registered.'); window.history.back();</script>";
            $stmt->close();
            $conn->close();
            exit();
        }
        $stmt->close();

    // Generate customer code
    $customer_code = 'CUST' . time() . rand(100,999);

    // For online registrations
    $registration_source = 'online';
    $is_verified = 0; // online users must complete KYC / verify email
    $username = $email; // default username equals email

    // Create verification token and expiry
    try {
        $verification_token = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $verification_token = bin2hex(openssl_random_pseudo_bytes(16));
    }
    $verification_expires = date('Y-m-d H:i:s', strtotime('+2 days'));

    $sql = "INSERT INTO customers (customer_code, first_name, last_name, birthdate, email, username, password, registration_source, is_verified, verification_token, verification_expires) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ssssssssiss', $customer_code, $first_name, $last_name, $birthdate, $email, $username, $hashed_password, $registration_source, $is_verified, $verification_token, $verification_expires);
        if ($stmt->execute()) {
            // send verification email to customer
            if (file_exists(__DIR__ . '/includes/send_email.php')) {
                require_once __DIR__ . '/includes/send_email.php';
                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $base = $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
                $verify_url = rtrim($base, '/') . '/verify.php?token=' . $verification_token;
                $subject = 'Please verify your email address';
                $message = "Hello $first_name $last_name,\n\nThank you for registering. Please confirm your email address by clicking the link below:\n\n$verify_url\n\nThis link will expire in 48 hours.\n\nIf you did not register, please ignore this email.";
                @sendEmail($email, $subject, $message);
            }

            echo "<script>alert('Registration successful! Please check your email to verify your account.'); window.location.href='login.php';</script>";
        } else {
            error_log('Execution failed: ' . $stmt->error);
            if ($conn->errno === 1062) {
                echo "<script>alert('Registration failed. The email is already registered.'); window.history.back();</script>";
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
    header('Location: register.php');
    exit();
}
?>