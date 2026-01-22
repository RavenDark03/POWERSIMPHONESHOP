<?php
require_once 'SimpleSMTP.php';
require_once 'email_config.php';

function sendEmail($to, $subject, $plainBody, $htmlBody = null) {
    try {
        $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
        $smtp->send($to, $subject, $plainBody, SMTP_FROM_EMAIL, SMTP_FROM_NAME, $htmlBody);
        return true;
    } catch (Exception $e) {
        // echo "Email Error: " . $e->getMessage();
        return false;
    }
}
?>
