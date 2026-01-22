<?php
session_start();
include '../includes/connection.php';
require_once '../includes/send_email.php';

function generateTempPassword($length = 10) {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%^&*';
    $max = strlen($alphabet) - 1;
    $pwd = '';
    for ($i = 0; $i < $length; $i++) {
        $pwd .= $alphabet[random_int(0, $max)];
    }
    return $pwd;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: users.php");
    exit();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit();
}

$current_role = $_SESSION['role'] ?? '';
$can_manage_users = in_array($current_role, ['admin', 'manager']);
if (!$can_manage_users) {
    header("Location: users.php?error=forbidden");
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'create_employee') {
    $name = trim($_POST['name'] ?? '');
    $email_raw = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'staff';
    $status = $_POST['status'] ?? 'approved';

    $email = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
    $allowed_roles = ['admin', 'manager', 'staff'];
    $allowed_status = ['approved', 'pending', 'rejected'];

    if (!$name || !$email || !$username || !in_array($role, $allowed_roles) || !in_array($status, $allowed_status)) {
        header("Location: users.php?error=invalid_input");
        exit();
    }

    // Managers cannot create Admin accounts
    if ($current_role === 'manager' && $role === 'admin') {
        header("Location: users.php?error=forbidden_role");
        exit();
    }

    // Prevent duplicate email/username
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
    $check->bind_param("ss", $email_raw, $username);
    $check->execute();
    $dup = $check->get_result();
    if ($dup && $dup->num_rows > 0) {
        $check->close();
        header("Location: users.php?error=duplicate");
        exit();
    }
    $check->close();

    // Generate temp password and hash it
    $temp_password = generateTempPassword(10);
    $hashed = password_hash($temp_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $hashed, $name, $email_raw, $role, $status);

    if ($stmt->execute()) {
        // Build branded HTML email
        $subject = 'Your Employee Account Credentials';
        ob_start();
        $primary = '#116530';
        $dark = '#0a3d0a';
        $body_text = "Hello {$name},<br><br>Your account has been created. Use the credentials below to log in and please change your password after signing in.";
        $login_url = 'https://your-domain-or-ip/login.php';
        ?>
        <div style="font-family:'Outfit', 'Segoe UI', Arial, sans-serif; background:#f7f9f8; padding:24px; color:#1f2a1f;">
            <div style="max-width:640px; margin:0 auto; background:#fff; border:1px solid #e6e6e6; border-radius:12px; overflow:hidden; box-shadow:0 12px 28px rgba(16,24,40,0.08);">
                <div style="background:linear-gradient(135deg, <?php echo $primary; ?> 0%, <?php echo $dark; ?> 100%); color:#fff; padding:18px 20px;">
                    <div style="font-size:18px; font-weight:700;">Powersim Phoneshop</div>
                    <div style="opacity:0.9; font-size:13px;">Temporary Access Credentials</div>
                </div>
                <div style="padding:20px; font-size:15px; line-height:1.6;">
                    <p style="margin:0 0 12px 0; color:#1f2a1f;">Hi <?php echo htmlspecialchars($name); ?>,</p>
                    <p style="margin:0 0 12px 0; color:#1f2a1f;">Your employee account is ready. Please sign in with the details below and change your password immediately.</p>
                    <div style="background:#f1f6f3; border:1px solid #dbe7e0; border-radius:10px; padding:14px 16px; margin:14px 0;">
                        <div style="margin-bottom:6px;"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></div>
                        <div style="margin-bottom:6px;"><strong>Temporary Password:</strong> <?php echo htmlspecialchars($temp_password); ?></div>
                        <div style="margin-bottom:0;"><strong>Status:</strong> <?php echo ucfirst($status); ?></div>
                    </div>
                    <a href="<?php echo $login_url; ?>" style="display:inline-block; background:<?php echo $primary; ?>; color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-weight:600;">Go to Login</a>
                    <p style="margin:16px 0 0 0; font-size:13px; color:#4b5a4b;">For security, this password will not be shown again. If you did not expect this email, please contact your administrator.</p>
                </div>
            </div>
        </div>
        <?php
        $html_body = ob_get_clean();

        // Also build a plain-text fallback
        $plain_body = "Hello {$name},\n\nYour account has been created.\nUsername: {$username}\nTemporary Password: {$temp_password}\nStatus: " . ucfirst($status) . "\n\nPlease log in and change your password immediately.";

        sendEmail($email_raw, $subject, $plain_body, $html_body);

        header("Location: users.php?created=1");
        exit();
    }

    header("Location: users.php?error=save_failed");
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0 || ($action !== 'approve' && $action !== 'reject')) {
    header("Location: users.php?error=invalid_action");
    exit();
}

if ($action == 'approve') {
    $status = 'approved';
    $subject = "Account Approved";
    $message_body = "Congratulations! Your account has been approved. You can now login.";
} else {
    $status = 'rejected';
    $subject = "Account Rejected";
    $message_body = "We regret to inform you that your registration request has been rejected.";
}

$sql = "UPDATE users SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
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
}

header("Location: users.php?error=update_failed");
exit();
?>
