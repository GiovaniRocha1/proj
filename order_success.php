<?php
session_start();

$order = $_SESSION['order_success'] ?? null;
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order confirmed | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="cart-page">
    <div id="header-placeholder"></div>
    <main class="cart-main">
        <div class="cart-empty">
            <span>&#10003;</span>
            <?php if ($order): ?>
                <h2>Order confirmed</h2>
                <p>Order #<?php echo (int) $order['id_pedido']; ?> — $<?php echo number_format((float) $order['valor_total'], 2); ?></p>
            <?php else: ?>
                <h2>No recent order</h2>
                <p>Start by adding items to your cart.</p>
            <?php endif; ?>
            <a href="dashboard.php">Continue shopping</a>
        </div>
    </main>
    <script src="JS/load-header.js"></script>
</body>
</html>
