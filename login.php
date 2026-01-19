<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Powersim Phoneshop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">

</head>
<body class="landing">
    <header>
        <div class="container">
            <a href="index.php" class="logo-container">
                <img src="images/powersim logo.png" alt="Powersim Phoneshop" class="logo-img">
                <span class="logo-text">Powersim Phoneshop</span>
            </a>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <form action="login_process.php" method="post" class="login-form">
            <h2>Login</h2>
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'pending'): ?>
                    <div style="background-color:#fff3cd;color:#856404;padding:10px;border-radius:5px;margin-bottom:15px;border:1px solid #ffeeba;text-align:center;">Account is pending approval. Please wait for Admin.</div>
                <?php elseif ($_GET['error'] === 'rejected'): ?>
                    <div style="background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;border:1px solid #f5c6cb;text-align:center;">Your account has been rejected. Contact support.</div>
                <?php else: ?>
                    <div style="background-color:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;border:1px solid #f5c6cb;text-align:center;">Invalid email or password.</div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="form-group">
                <label for="identifier">Email or Username</label>
                <input type="text" name="identifier" id="identifier">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>
            <button type="submit" class="btn">Login</button>
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </div>

    <?php
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['loggedin'])) {
    ?>
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

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        if (togglePassword && password) {
            togglePassword.addEventListener('click', function (e) {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>