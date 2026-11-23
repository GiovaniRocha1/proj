<?php
session_start();
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <main class="login-page register-page">
        <section class="login-card register-card" aria-labelledby="register-title">
            <a class="login-brand" href="index.html" aria-label="ShopZone home">
                <span class="brand-mark">S</span>
                <span>ShopZone</span>
            </a>

            <div class="login-heading">
                <p class="login-eyebrow">Join ShopZone</p>
                <h1 id="register-title">Create your account</h1>
                <p>Enter your details to start shopping.</p>
            </div>

            <?php if ($flashError): ?>
                <p class="auth-message auth-error"><?php echo htmlspecialchars($flashError); ?></p>
            <?php endif; ?>

            <form class="login-form register-form" action="register_process.php" method="POST">
                <div class="login-field">
                    <label for="nome">Full name</label>
                    <input type="text" id="nome" name="nome" placeholder="Enter your name" autocomplete="name" required>
                </div>

                <div class="login-field">
                    <label for="idade">Age</label>
                    <input type="number" id="idade" name="idade" placeholder="Enter your age" min="1" max="999" required>
                </div>

                <div class="login-field">
                    <label for="cidade">City</label>
                    <input type="text" id="cidade" name="cidade" placeholder="Enter your city" autocomplete="address-level2" maxlength="100" required>
                </div>

                <div class="login-field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
                </div>

                <div class="login-field">
                    <label for="login">Username</label>
                    <input type="text" id="login" name="login" placeholder="Choose a username" autocomplete="username" required>
                </div>

                <div class="login-field">
                    <label for="telefone">Phone <span>(optional)</span></label>
                    <input type="tel" id="telefone" name="telefone" placeholder="Enter your phone number" autocomplete="tel">
                </div>

                <input type="hidden" name="tipo" value="1">

                <div class="login-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="senha" placeholder="Create a password" autocomplete="new-password" minlength="6" required>
                </div>

                <button type="submit" class="login-button">Create account</button>
            </form>

            <p class="login-footer">Already have an account? <a href="login.php">Sign in</a></p>
        </section>
    </main>
</body>
</html>
