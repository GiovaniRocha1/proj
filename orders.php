<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Entre na sua conta para ver seus pedidos.';
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
    $errorMessage = 'Não foi possível carregar seus pedidos. Tente novamente mais tarde.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus pedidos | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="orders-page">
    <div id="header-placeholder"></div>

    <main class="orders-main">
        <nav class="orders-breadcrumb" aria-label="Caminho de navegação">
            <a href="dashboard.php">Início</a>
            <span>/</span>
            <span>Meus pedidos</span>
        </nav>

        <div class="orders-heading">
            <div>
                <p class="orders-eyebrow">Histórico de compras</p>
                <h1>Meus pedidos</h1>
                <p>Acompanhe seus pedidos recentes e os produtos de cada compra.</p>
            </div>
            <a href="account.php">Minha conta</a>
        </div>

        <?php if ($errorMessage): ?>
            <p class="auth-message auth-error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php elseif (!$orders): ?>
            <section class="orders-empty">
                <span aria-hidden="true">&#128230;</span>
                <h2>Você ainda não fez pedidos</h2>
                <p>Quando fizer uma compra, os detalhes do pedido aparecerão aqui.</p>
                <a href="dashboard.php">Ver produtos</a>
            </section>
        <?php else: ?>
            <section class="orders-list" aria-label="Seus pedidos">
                <?php foreach ($orders as $order): ?>
                    <?php $orderId = (int) $order['id_pedido']; ?>
                    <article class="order-card">
                        <header class="order-card-header">
                            <div>
                                <span class="order-label">Pedido</span>
                                <h2>#<?php echo $orderId; ?></h2>
                            </div>
                            <span class="order-status"><?php echo htmlspecialchars(display_order_status($order['status'])); ?></span>
                        </header>

                        <div class="order-meta">
                            <div>
                                <span>Data do pedido</span>
                                <strong><?php echo date('d/m/Y', strtotime($order['data_pedido'])); ?></strong>
                            </div>
                            <div>
                                <span>Total</span>
                                <strong><?php echo format_currency((float) $order['valor_total']); ?></strong>
                            </div>
                            <div>
                                <span>Endereço de entrega</span>
                                <strong><?php
                                    $deliveryAddress = array_filter([
                                        $order['endereco_entrega'],
                                        $order['cidade_entrega'],
                                        $order['codigo_postal'],
                                        $order['pais_entrega'],
                                    ], static fn($value) => $value !== null && $value !== '');
                                    echo $deliveryAddress
                                        ? htmlspecialchars(implode(', ', $deliveryAddress))
                                        : 'Não informado';
                                ?></strong>
                            </div>
                            <div>
                                <span>Forma de pagamento</span>
                                <strong><?php echo htmlspecialchars(display_payment_method($order['metodo_pagamento'])); ?></strong>
                            </div>
                        </div>

                        <div class="order-items">
                            <?php foreach ($ordersById[$orderId] ?? [] as $item): ?>
                                <div class="order-item">
                                    <span class="order-item-icon" aria-hidden="true">&#128230;</span>
                                    <span class="order-item-name"><?php echo htmlspecialchars($item['produto_nome']); ?></span>
                                    <span class="order-item-quantity">Qtd.: <?php echo (int) $item['quantidade']; ?></span>
                                    <strong><?php echo format_currency((float) $item['preco'] * (int) $item['quantidade']); ?></strong>
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
