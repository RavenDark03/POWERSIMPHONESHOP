<?php
require_once 'SimpleSMTP.php';
require_once 'email_config.php';

function sendEmail($to, $subject, $body) {
    try {
        $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
        $smtp->send($to, $subject, $body, SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        return true;
    } catch (Exception $e) {
        // Log error or display it
        // echo "Email Error: " . $e->getMessage();
        return false;
    }
}
?>
