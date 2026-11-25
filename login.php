<?php
session_start();
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <main class="login-page">
        <section class="login-card" aria-labelledby="login-title">
            <a class="login-brand" href="login.php" aria-label="Página de login da loja virtual">
                <span class="brand-mark">S</span>
                <span>Loja virtual</span>
            </a>

            <div class="login-heading">
                <p class="login-eyebrow">Bem-vindo!</p>
                <h1 id="login-title">Entrar em sua conta</h1>
                <p>Informe suas credenciais para acessar sua conta.</p>
            </div>

            <?php if ($flashError): ?>
                <p class="auth-message auth-error"><?php echo htmlspecialchars($flashError); ?></p>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
                <p class="auth-message auth-success"><?php echo htmlspecialchars($flashSuccess); ?></p>
            <?php endif; ?>

            <form class="login-form" action="login_process.php" method="POST">
                <div class="login-field">
                    <label for="login">Nome de usuário</label>
                    <input type="text" id="login" name="login" placeholder="Digite seu nome de usuário" autocomplete="username" required>
                </div>

                <div class="login-field">
                    <div class="login-label-row">
                        <label for="password">Senha</label>
                    </div>
                    <input type="password" id="password" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required>
                </div>

                <button type="submit" class="login-button">Entrar</button>
            </form>

            <p class="login-footer">Ainda não tem uma conta? <a href="register.php">Criar conta</a></p>
        </section>
    </main>
</body>
</html>