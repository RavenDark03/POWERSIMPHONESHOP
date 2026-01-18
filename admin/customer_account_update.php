<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Location: ../login.php');
    exit();
}

function redirect_with_message(array $params): void {
    $query = http_build_query($params);
    header('Location: customer_accounts.php' . ($query ? ('?' . $query) : ''));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_message(['error' => 'Invalid request method.']);
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

if ($id <= 0) {
    redirect_with_message(['error' => 'Invalid customer id.']);
}

// Unified handler: update username and optionally password
if ($action === 'update_credentials' || $action === 'update_username' || $action === 'update_password') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($username === '') {
        redirect_with_message(['error' => 'Username is required.']);
    }

    $normalizedUsername = strtolower($username);

    // Check username uniqueness
    $checkStmt = $conn->prepare('SELECT id FROM customers WHERE username = ? AND id <> ? LIMIT 1');
    $checkStmt->bind_param('si', $normalizedUsername, $id);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    if ($checkRes && $checkRes->num_rows > 0) {
        $checkStmt->close();
        redirect_with_message(['error' => 'Username already in use.']);
    }
    $checkStmt->close();

    $sql = '';
    $types = '';
    $params = [];

    // Decide if password will be updated
    $shouldUpdatePassword = ($password !== '');
    if ($shouldUpdatePassword) {
        if (strlen($password) < 6) {
            redirect_with_message(['error' => 'Password must be at least 6 characters.']);
        }
        if ($password !== $confirm) {
            redirect_with_message(['error' => 'Passwords do not match.']);
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $sql = 'UPDATE customers SET username = ?, password = ? WHERE id = ?';
        $types = 'ssi';
        $params = [$normalizedUsername, $hashed, $id];
    } else {
        $sql = 'UPDATE customers SET username = ? WHERE id = ?';
        $types = 'si';
        $params = [$normalizedUsername, $id];
    }

    $updateStmt = $conn->prepare($sql);
    $updateStmt->bind_param($types, ...$params);
    if ($updateStmt->execute()) {
        $updateStmt->close();
        redirect_with_message(['updated' => 1]);
    }
    $updateStmt->close();
    redirect_with_message(['error' => 'Failed to update credentials.']);
}

redirect_with_message(['error' => 'Unsupported action.']);
