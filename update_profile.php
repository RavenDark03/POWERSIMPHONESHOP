<?php
session_start();
include 'includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: login.php');
    exit();
}

$customer_id = (int) $_SESSION['id'];


$fields = [
    'username' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'email' => FILTER_SANITIZE_EMAIL,
    'contact_number' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'present_house_num' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'present_street' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'present_subdivision' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'present_barangay' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'present_city' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'present_province' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
    'present_zip' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
];

$input = [];
foreach ($fields as $key => $filter) {
    $input[$key] = isset($_POST[$key]) ? trim(filter_var($_POST[$key], $filter)) : '';
}

// Basic validation mirroring add_customer
if (!empty($input['username']) && strpos($input['username'], '@') !== false) {
    header('Location: dashboard.php?error=username_email');
    exit();
}

if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    header('Location: dashboard.php?error=invalid_email');
    exit();
}

if (empty($input['contact_number'])) {
    header('Location: dashboard.php?error=contact_required');
    exit();
}

// Normalize contact to +63 format
$input['contact_number'] = preg_replace('/[^0-9]/', '', $input['contact_number']);
$input['contact_number'] = ltrim($input['contact_number'], '0');
if (strpos($input['contact_number'], '63') === 0) {
    $input['contact_number'] = '+63 ' . substr($input['contact_number'], 2);
} elseif (strpos($input['contact_number'], '+63') === 0) {
    $input['contact_number'] = '+63 ' . substr($input['contact_number'], 3);
} else {
    $input['contact_number'] = '+63 ' . $input['contact_number'];
}

if (empty($input['present_barangay']) || empty($input['present_city']) || empty($input['present_province'])) {
    header('Location: dashboard.php?error=address_required');
    exit();
}

$sql = "UPDATE customers
        SET username = ?, email = ?, contact_number = ?, 
            present_house_num = ?, present_street = ?, present_subdivision = ?,
            present_barangay = ?, present_city = ?, present_province = ?, present_zip = ?
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssssssssssi',
    $input['username'],
    $input['email'],
    $input['contact_number'],
    $input['present_house_num'],
    $input['present_street'],
    $input['present_subdivision'],
    $input['present_barangay'],
    $input['present_city'],
    $input['present_province'],
    $input['present_zip'],
    $customer_id
);
$stmt->execute();
$stmt->close();

header('Location: dashboard.php?updated=1');
exit();
