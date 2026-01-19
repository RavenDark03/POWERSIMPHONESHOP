<?php
include '../includes/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $status = 'approved';
        $subject = "Account Approved";
        $message_body = "Congratulations! Your account has been approved. You can now login.";
    } elseif ($action == 'reject') {
        $status = 'rejected';
        $subject = "Account Rejected";
        $message_body = "We regret to inform you that your registration request has been rejected.";
    } else {
        echo "Invalid action.";
        exit();
    }

    $sql = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        // Fetch user email
        require_once '../includes/send_email.php';
        $user_sql = "SELECT email, name FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows == 1) {
            $user = $user_result->fetch_assoc();
            $to = $user['email'];
            $message = "Hello " . $user['name'] . ",\n\n" . $message_body;

            sendEmail($to, $subject, $message);
        }
        
        header("Location: users.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
