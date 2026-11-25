<?php
session_start();
require_once __DIR__ . '/db.php';

$order = $_SESSION['order_success'] ?? null;
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido confirmado | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="cart-page">
    <div id="header-placeholder"></div>
    <main class="cart-main">
        <div class="cart-empty">
            <span>&#10003;</span>
            <?php if ($order): ?>
                <h2>Pedido confirmado</h2>
                <p>Pedido nº <?php echo (int) $order['id_pedido']; ?> — <?php echo format_currency((float) $order['valor_total']); ?></p>
            <?php else: ?>
                <h2>Nenhum pedido recente</h2>
                <p>Comece adicionando produtos ao seu carrinho.</p>
            <?php endif; ?>
            <a href="dashboard.php">Continuar comprando</a>
        </div>
    </main>
    <script src="JS/load-header.js"></script>
</body>
</html>
