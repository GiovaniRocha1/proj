<?php
session_start();
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <main class="login-page register-page">
        <section class="login-card register-card" aria-labelledby="register-title">
            <a class="login-brand" href="login.php" aria-label="Página de login da Online Store">
                <span class="brand-mark">S</span>
                <span>Online Store</span>
            </a>

            <div class="login-heading">
                <p class="login-eyebrow">Faça parte da Online Store</p>
                <h1 id="register-title">Crie sua conta</h1>
                <p>Informe seus dados para começar a comprar.</p>
            </div>

            <?php if ($flashError): ?>
                <p class="auth-message auth-error"><?php echo htmlspecialchars($flashError); ?></p>
            <?php endif; ?>

            <form class="login-form register-form" action="register_process.php" method="POST">
                <div class="login-field">
                    <label for="nome">Nome completo</label>
                    <input type="text" id="nome" name="nome" placeholder="Digite seu nome" autocomplete="name" required>
                </div>

                <div class="login-field">
                    <label for="idade">Idade</label>
                    <input type="number" id="idade" name="idade" placeholder="Digite sua idade" min="1" max="999" required>
                </div>

                <div class="login-field">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" placeholder="Digite sua cidade" autocomplete="address-level2" maxlength="100" required>
                </div>

                <div class="login-field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="voce@exemplo.com" autocomplete="email" required>
                </div>

                <div class="login-field">
                    <label for="login">Nome de usuário</label>
                    <input type="text" id="login" name="login" placeholder="Escolha um nome de usuário" autocomplete="username" required>
                </div>

                <div class="login-field">
                    <label for="telefone">Telefone <span>(opcional)</span></label>
                    <input type="tel" id="telefone" name="telefone" placeholder="Digite seu telefone" autocomplete="tel">
                </div>

                <input type="hidden" name="tipo" value="1">

                <div class="login-field">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="senha" placeholder="Crie uma senha" autocomplete="new-password" minlength="6" required>
                </div>

                <button type="submit" class="login-button">Criar conta</button>
            </form>

            <p class="login-footer">Já tem uma conta? <a href="login.php">Entrar</a></p>
        </section>
    </main>
</body>
</html>
