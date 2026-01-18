<?php
function render_temp_credentials_email($name, $username, $tempPassword, $loginUrl) {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safePass = htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8');
    $safeUrl  = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Your Account Credentials</title>
<style>
    body { margin:0; padding:0; background:#f3f5f8; font-family:'Segoe UI', Arial, sans-serif; }
    .wrapper { width:100%; padding:32px 0; }
    .card { max-width:640px; margin:0 auto; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 12px 32px rgba(0,0,0,0.12); border:1px solid #eef1f5; }
    .header { background: linear-gradient(135deg, #0a7f4f 0%, #0a6abf 50%, #d4af37 100%); padding:22px 26px; color:#fff; }
    .header h1 { margin:0; font-size:22px; letter-spacing:0.4px; font-weight:700; }
    .body { padding:26px 28px; color:#243238; line-height:1.65; }
    .body p { margin:0 0 16px; }
    .pill { display:inline-block; padding:11px 14px; border-radius:12px; background:#f9fbff; border:1px solid #e3ecf7; font-weight:700; letter-spacing:0.3px; color:#0a6abf; }
    .pill + .pill { margin-top:8px; }
    .cta { display:inline-block; margin-top:14px; padding:12px 18px; background:#0a7f4f; color:#fff; text-decoration:none; border-radius:12px; font-weight:700; letter-spacing:0.2px; box-shadow:0 8px 18px rgba(10,127,79,0.18); }
    .cta:hover { background:#0c915a; }
    .footer { padding:18px 24px 22px; font-size:12px; color:#5c6b76; background:#fbfbfd; border-top:1px solid #edf0f4; }
</style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <h1>Welcome to Powersim Pawnshop</h1>
        </div>
        <div class="body">
            <p>Hi {$safeName},</p>
            <p>Your account has been created. Use the credentials below to sign in and update your password after logging in.</p>
            <p class="pill">Username: {$safeUser}</p><br>
            <p class="pill">Temporary Password: {$safePass}</p>
            <p><a class="cta" href="{$safeUrl}">Go to Login</a></p>
            <p>If you did not request this, please ignore this email or contact support.</p>
        </div>
        <div class="footer">
            This is an automated message. Do not reply.
        </div>
    </div>
</div>
</body>
</html>
HTML;
}
?>
