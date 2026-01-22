<?php
session_start();
include '../includes/connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_id']) && isset($_POST['status'])) {
    $customer_id = intval($_POST['customer_id']);
    $status = $_POST['status'];
    
    // Validate status value
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($status, $allowed_statuses)) {
        header("Location: customers.php?error=invalid_status");
        exit();
    }
    
    // Update the customer's account status
    $stmt = $conn->prepare("UPDATE customers SET account_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $customer_id);
    
    if ($stmt->execute()) {
        // Optionally send email notification to customer
        if ($status === 'approved' || $status === 'rejected') {
            // Get customer email
            $email_stmt = $conn->prepare("SELECT email, first_name, last_name FROM customers WHERE id = ?");
            $email_stmt->bind_param("i", $customer_id);
            $email_stmt->execute();
            $customer = $email_stmt->get_result()->fetch_assoc();
            $email_stmt->close();
            
            if ($customer && !empty($customer['email'])) {
                if (file_exists(__DIR__ . '/../includes/send_email.php')) {
                    require_once __DIR__ . '/../includes/send_email.php';
                    
                    $name = $customer['first_name'] . ' ' . $customer['last_name'];
                    
                    if ($status === 'approved') {
                        $subject = 'Your Powersim Account Has Been Approved!';
                        $message = "Hello $name,\n\nGreat news! Your account has been approved. You can now log in to your customer portal.\n\nThank you for choosing Powersim Pawnshop.\n\nBest regards,\nPowersim Pawnshop Team";
                    } else {
                        $subject = 'Powersim Account Registration Update';
                        $message = "Hello $name,\n\nWe regret to inform you that your account registration could not be approved at this time.\n\nIf you have any questions, please contact our support team.\n\nBest regards,\nPowersim Pawnshop Team";
                    }
                    
                    @sendEmail($customer['email'], $subject, $message);
                }
            }
        }
        
        header("Location: view_customer.php?id=" . $customer_id . "&status_updated=" . $status);
    } else {
        header("Location: view_customer.php?id=" . $customer_id . "&error=update_failed");
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

// If not POST, redirect back
header("Location: customers.php");
exit();
?>
