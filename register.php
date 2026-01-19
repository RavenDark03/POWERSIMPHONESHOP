<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Powersim Phoneshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <div class="container">
            <a href="index.php" class="logo-container">
                <img src="images/powersim logo.png" alt="Powersim Phoneshop" class="logo-img">
                <span class="logo-text">Powersim Phoneshop</span>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <form id="registerForm" action="register_process.php" method="post" class="login-form">
            <h2>Register</h2>

            <div class="form-row">
                <div class="form-group" style="flex:1; min-width:140px;">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" required>
                </div>
                <div class="form-group" style="flex:1; min-width:140px;">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" required>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required pattern="[A-Za-z0-9._-]{3,20}" title="3-20 characters: letters, numbers, dot, underscore or dash">
                <small style="display:block;color:#666;margin-top:6px;">Choose a unique username (3-20 chars, letters/numbers/._-)</small>
            </div>

            <div class="form-group">
                <label for="birthdate">Birthdate</label>
                <input type="date" name="birthdate" id="birthdate" required max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="form-group">
                <label for="confirm_email">Confirm Email</label>
                <input type="email" name="confirm_email" id="confirm_email" required>
                <small id="emailMatch" style="display:block; margin-top:6px; color:#c00;">&nbsp;</small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" aria-describedby="passwordHelp" required>
                <div id="pw-meter" style="height:8px;background:#eee;border-radius:6px;margin-top:8px;overflow:hidden;"><div id="pw-meter-bar" style="height:100%;width:0%;background:#d9534f;transition:width .2s;"></div></div>
                <div id="passwordHelp" style="font-size:13px;margin-top:8px;color:#666;">Use at least 8 characters including uppercase, lowercase, number and symbol.</div>

                <ul id="pw-checklist" style="list-style:none;padding-left:0;margin-top:10px;">
                    <li data-check="length" style="color:#c00">&#10060; At least 8 characters</li>
                    <li data-check="lower" style="color:#c00">&#10060; Lowercase letter</li>
                    <li data-check="upper" style="color:#c00">&#10060; Uppercase letter</li>
                    <li data-check="number" style="color:#c00">&#10060; Number</li>
                    <li data-check="symbol" style="color:#c00">&#10060; Symbol (e.g. !@#$%)</li>
                </ul>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
                <small id="pwMatch" style="display:block; margin-top:6px; color:#c00">&nbsp;</small>
            </div>

            <input type="hidden" name="register_source" value="online">
            <button type="submit" class="btn">Create Account</button>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>

    <script>
        (function(){
            const pwd = document.getElementById('password');
            const confirmPwd = document.getElementById('confirm_password');
            const pwBar = document.getElementById('pw-meter-bar');
            const checklist = document.querySelectorAll('#pw-checklist li');
            const email = document.getElementById('email');
            const confirmEmail = document.getElementById('confirm_email');
            const emailMatch = document.getElementById('emailMatch');
            const pwMatch = document.getElementById('pwMatch');

            function checkPasswordStrength(value){
                const checks = {
                    length: value.length >= 8,
                    lower: /[a-z]/.test(value),
                    upper: /[A-Z]/.test(value),
                    number: /[0-9]/.test(value),
                    symbol: /[!@#\$%\^&\*\(\)\[\]\{\}<>\?\/~`'";:\\|.,+=_-]/.test(value)
                };
                let passed = 0;
                Object.keys(checks).forEach(k => { if (checks[k]) passed++; });

                const pct = Math.round((passed / Object.keys(checks).length) * 100);
                pwBar.style.width = pct + '%';
                if (pct < 40) pwBar.style.background = '#d9534f';
                else if (pct < 80) pwBar.style.background = '#f0ad4e';
                else pwBar.style.background = '#5cb85c';

                checklist.forEach(li => {
                    const key = li.getAttribute('data-check');
                    if (checks[key]) { li.style.color = '#2a9d8f'; li.innerHTML = '\u2714 ' + li.textContent.replace(/^\u2714 |^\u2716 /,''); }
                    else { li.style.color = '#c00'; li.innerHTML = '\u2716 ' + li.textContent.replace(/^\u2714 |^\u2716 /,''); }
                });

                return passed === Object.keys(checks).length;
            }

            pwd.addEventListener('input', function(){
                checkPasswordStrength(this.value);
                // realtime match
                if (confirmPwd.value.length) {
                    pwMatch.textContent = (this.value === confirmPwd.value) ? 'Passwords match' : 'Passwords do not match';
                    pwMatch.style.color = (this.value === confirmPwd.value) ? '#2a9d8f' : '#c00';
                } else pwMatch.textContent = '';
            });

            confirmPwd.addEventListener('input', function(){
                pwMatch.textContent = (pwd.value === this.value) ? 'Passwords match' : 'Passwords do not match';
                pwMatch.style.color = (pwd.value === this.value) ? '#2a9d8f' : '#c00';
            });

            function checkEmails(){
                if (!email.value || !confirmEmail.value) { emailMatch.textContent = '' ; return true; }
                const ok = email.value.trim().toLowerCase() === confirmEmail.value.trim().toLowerCase();
                emailMatch.textContent = ok ? 'Emails match' : 'Emails do not match';
                emailMatch.style.color = ok ? '#2a9d8f' : '#c00';
                return ok;
            }
            email.addEventListener('input', checkEmails);
            confirmEmail.addEventListener('input', checkEmails);

            document.getElementById('registerForm').addEventListener('submit', function(e){
                // client-side final validation
                const emailsOk = checkEmails();
                const pwOk = checkPasswordStrength(pwd.value);
                const pwMatchOk = pwd.value === confirmPwd.value;
                if (!emailsOk) { e.preventDefault(); confirmEmail.focus(); return false; }
                if (!pwOk) { e.preventDefault(); pwd.focus(); alert('Password does not meet all requirements.'); return false; }
                if (!pwMatchOk) { e.preventDefault(); confirmPwd.focus(); alert('Passwords do not match.'); return false; }
                return true;
            });
        })();
    </script>

    <?php if (empty($_SESSION['loggedin'])) { ?>
    <footer>
        <div class="container">
            <div class="footer-contact">
                <p><i class="fas fa-phone-alt"></i> 0910-809-9699</p>
                <p><a href="https://www.facebook.com/PowerSimPhoneshopOfficial" target="_blank"><i class="fab fa-facebook"></i> Powersim Phoneshop Gadget Trading Inc.</a></p>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2026 Powersim Phoneshop Gadget Trading Inc. Baliuag. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    <?php } ?>
</body>
</html>