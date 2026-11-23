<?php
session_start();
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-title">
            <a class="login-brand" href="index.html" aria-label="ShopZone home">
                <span class="brand-mark">S</span>
                <span>ShopZone</span>
            </a>

            <div class="login-heading">
                <p class="login-eyebrow">Welcome back</p>
                <h1 id="login-title">Sign in to your account</h1>
                <p>Enter your details to continue shopping.</p>
            </div>

            <?php if ($flashError): ?>
                <p class="auth-message auth-error"><?php echo htmlspecialchars($flashError); ?></p>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
                <p class="auth-message auth-success"><?php echo htmlspecialchars($flashSuccess); ?></p>
            <?php endif; ?>

            <form class="login-form" action="login_process.php" method="POST">
                <div class="login-field">
                    <label for="login">Login</label>
                    <input type="text" id="login" name="login" placeholder="Enter your login" autocomplete="email" required>
                </div>

                <div class="login-field">
                    <div class="login-label-row">
                        <label for="password">Password</label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>
                    <input type="password" id="password" name="senha" placeholder="Enter your password" autocomplete="current-password" required>
                </div>

                <label class="remember-row">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>

                <button type="submit" class="login-button">Sign in</button>
            </form>

            <p class="login-footer">Don't have an account? <a href="register.php">Create account</a></p>
        </section>
    </main>
</body>
</html>