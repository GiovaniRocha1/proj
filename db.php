<?php

declare(strict_types=1);

function database(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['database']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function get_user_cart(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare(
        'SELECT c.id_produto, c.quantidade, p.estoque
         FROM itens_carrinho c
         INNER JOIN produto p ON p.id_produto = c.id_produto
         WHERE c.id_usuario = ?'
    );
    $statement->execute([$userId]);

    $cart = [];
    $update = $pdo->prepare(
        'UPDATE itens_carrinho SET quantidade = ? WHERE id_usuario = ? AND id_produto = ?'
    );
    $delete = $pdo->prepare(
        'DELETE FROM itens_carrinho WHERE id_usuario = ? AND id_produto = ?'
    );

    foreach ($statement->fetchAll() as $item) {
        $productId = (int) $item['id_produto'];
        $stock = (int) $item['estoque'];
        $quantity = max(0, (int) $item['quantidade']);

        if ($stock < 1 || $quantity < 1) {
            $delete->execute([$userId, $productId]);
            continue;
        }

        $quantity = min($quantity, $stock);
        $cart[$productId] = $quantity;
        if ($quantity !== (int) $item['quantidade']) {
            $update->execute([$quantity, $userId, $productId]);
        }
    }

    return $cart;
}

function set_user_cart_item(PDO $pdo, int $userId, int $productId, int $quantity): void
{
    if ($quantity < 1) {
        $statement = $pdo->prepare(
            'DELETE FROM itens_carrinho WHERE id_usuario = ? AND id_produto = ?'
        );
        $statement->execute([$userId, $productId]);
        return;
    }

    $statement = $pdo->prepare(
        'INSERT INTO itens_carrinho (id_usuario, id_produto, quantidade)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade)'
    );
    $statement->execute([$userId, $productId, $quantity]);
}

function display_category(string $category): string
{
    $labels = [
        'electronics' => 'Eletrônicos',
        'fashion' => 'Moda',
        'fitness' => 'Esportes e fitness',
        'home' => 'Casa',
    ];

    return $labels[strtolower(trim($category))] ?? $category;
}

function format_currency(float $amount): string
{
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

function display_order_status(string $status): string
{
    $labels = [
        'pending' => 'Pendente',
        'processing' => 'Em processamento',
        'shipped' => 'Enviado',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelado',
    ];

    return $labels[strtolower(trim($status))] ?? $status;
}

function display_payment_method(?string $method): string
{
    $labels = [
        'not specified' => 'Não informado',
        'cash on delivery' => 'Pagamento na entrega',
        'bank transfer' => 'Transferência bancária',
        'other' => 'Outro',
    ];
    $method = trim((string) $method);

    return $labels[strtolower($method)] ?? ($method !== '' ? $method : 'Não informado');
}
