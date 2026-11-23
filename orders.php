<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Please sign in to view your orders.';
    header('Location: login.php');
    exit;
}

$orders = [];
$ordersById = [];
$errorMessage = null;

try {
    $pdo = database();
    $statement = $pdo->prepare(
        'SELECT id_pedido, data_pedido, status, valor_total,
                endereco_entrega, cidade_entrega, codigo_postal, pais_entrega, metodo_pagamento
         FROM pedidos
         WHERE id_usuario = ?
         ORDER BY data_pedido DESC, id_pedido DESC'
    );
    $statement->execute([(int) $_SESSION['user']['id_usuario']]);
    $orders = $statement->fetchAll();

    if ($orders) {
        $orderIds = array_map('intval', array_column($orders, 'id_pedido'));
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemStatement = $pdo->prepare(
            'SELECT ip.id_pedido, ip.quantidade, p.nome AS produto_nome, p.preco
             FROM itens_pedido ip
             INNER JOIN produto p ON p.id_produto = ip.id_produto
             WHERE ip.id_pedido IN (' . $placeholders . ')
             ORDER BY ip.id_item'
        );
        $itemStatement->execute($orderIds);
        foreach ($itemStatement->fetchAll() as $item) {
            $ordersById[(int) $item['id_pedido']][] = $item;
        }
    }
} catch (Throwable $error) {
    $errorMessage = 'Could not load your orders. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My orders | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="orders-page">
    <div id="header-placeholder"></div>

    <main class="orders-main">
        <nav class="orders-breadcrumb" aria-label="Breadcrumb">
            <a href="dashboard.php">Home</a>
            <span>/</span>
            <span>My orders</span>
        </nav>

        <div class="orders-heading">
            <div>
                <p class="orders-eyebrow">Purchase history</p>
                <h1>My orders</h1>
                <p>Review your recent orders and the items in each one.</p>
            </div>
            <a href="account.php">My account</a>
        </div>

        <?php if ($errorMessage): ?>
            <p class="auth-message auth-error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php elseif (!$orders): ?>
            <section class="orders-empty">
                <span aria-hidden="true">&#128230;</span>
                <h2>No orders yet</h2>
                <p>When you place an order, its details will appear here.</p>
                <a href="dashboard.php">Browse products</a>
            </section>
        <?php else: ?>
            <section class="orders-list" aria-label="Your orders">
                <?php foreach ($orders as $order): ?>
                    <?php $orderId = (int) $order['id_pedido']; ?>
                    <article class="order-card">
                        <header class="order-card-header">
                            <div>
                                <span class="order-label">Order</span>
                                <h2>#<?php echo $orderId; ?></h2>
                            </div>
                            <span class="order-status"><?php echo htmlspecialchars($order['status']); ?></span>
                        </header>

                        <div class="order-meta">
                            <div>
                                <span>Order date</span>
                                <strong><?php echo htmlspecialchars($order['data_pedido']); ?></strong>
                            </div>
                            <div>
                                <span>Total</span>
                                <strong>$<?php echo number_format((float) $order['valor_total'], 2); ?></strong>
                            </div>
                            <div>
                                <span>Delivery address</span>
                                <strong><?php
                                    $deliveryAddress = array_filter([
                                        $order['endereco_entrega'],
                                        $order['cidade_entrega'],
                                        $order['codigo_postal'],
                                        $order['pais_entrega'],
                                    ], static fn($value) => $value !== null && $value !== '');
                                    echo $deliveryAddress
                                        ? htmlspecialchars(implode(', ', $deliveryAddress))
                                        : 'Not recorded';
                                ?></strong>
                            </div>
                            <div>
                                <span>Payment method</span>
                                <strong><?php echo htmlspecialchars((string) ($order['metodo_pagamento'] ?: 'Not recorded')); ?></strong>
                            </div>
                        </div>

                        <div class="order-items">
                            <?php foreach ($ordersById[$orderId] ?? [] as $item): ?>
                                <div class="order-item">
                                    <span class="order-item-icon" aria-hidden="true">&#128230;</span>
                                    <span class="order-item-name"><?php echo htmlspecialchars($item['produto_nome']); ?></span>
                                    <span class="order-item-quantity">Qty: <?php echo (int) $item['quantidade']; ?></span>
                                    <strong>$<?php echo number_format((float) $item['preco'] * (int) $item['quantidade'], 2); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

    <script src="JS/load-header.js"></script>
</body>
</html>
