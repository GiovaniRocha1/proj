<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Please sign in before checkout.';
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$address = trim((string) ($_POST['endereco_entrega'] ?? ''));
$city = trim((string) ($_POST['cidade_entrega'] ?? ''));
$postalCode = trim((string) ($_POST['codigo_postal'] ?? ''));
$country = trim((string) ($_POST['pais_entrega'] ?? ''));
$paymentMethod = (string) ($_POST['metodo_pagamento'] ?? '');
$paymentMethods = ['Not specified', 'Cash on delivery', 'Bank transfer', 'Other'];

if (
    $address === '' || strlen($address) > 255 ||
    $city === '' || strlen($city) > 100 ||
    $postalCode === '' || strlen($postalCode) > 20 ||
    $country === '' || strlen($country) > 100 ||
    !in_array($paymentMethod, $paymentMethods, true)
) {
    $_SESSION['checkout_error'] = 'Enter valid delivery and payment details.';
    header('Location: checkout.php');
    exit;
}

try {
    $pdo = database();
    $pdo->beginTransaction();

    $cartStatement = $pdo->prepare(
        'SELECT id_produto, quantidade FROM itens_carrinho
         WHERE id_usuario = ? FOR UPDATE'
    );
    $cartStatement->execute([(int) $_SESSION['user']['id_usuario']]);
    $cartRows = $cartStatement->fetchAll();
    if (!$cartRows) {
        throw new RuntimeException('Your cart is empty.');
    }

    $total = 0.0;
    $orderItems = [];

    foreach ($cartRows as $cartRow) {
        $productId = (int) $cartRow['id_produto'];
        $requestedQuantity = (int) $cartRow['quantidade'];

        $statement = $pdo->prepare(
            'SELECT id_produto, nome, preco, estoque
             FROM produto
             WHERE id_produto = ?
             LIMIT 1
             FOR UPDATE'
        );
        $statement->execute([$productId]);
        $product = $statement->fetch();

        if (!$product) {
            throw new RuntimeException('A product in your cart no longer exists.');
        }

        $stock = (int) $product['estoque'];
        $quantity = max(1, $requestedQuantity);
        if ($quantity > $stock) {
            throw new RuntimeException(
                'Only ' . $stock . ' units of ' . $product['nome'] . ' are available.'
            );
        }

        $lineTotal = (float) $product['preco'] * $quantity;
        $total += $lineTotal;
        $orderItems[] = [
            'id_produto' => $productId,
            'quantidade' => $quantity,
        ];
    }

    $statement = $pdo->prepare(
        'INSERT INTO pedidos (
            id_usuario, data_pedido, status, valor_total,
            endereco_entrega, cidade_entrega, codigo_postal, pais_entrega, metodo_pagamento
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([
        (int) $_SESSION['user']['id_usuario'],
        date('Y-m-d'),
        'Pending',
        $total,
        $address,
        $city,
        $postalCode,
        $country,
        $paymentMethod,
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStatement = $pdo->prepare(
        'INSERT INTO itens_pedido (id_pedido, id_produto, quantidade)
         VALUES (?, ?, ?)'
    );
    $stockStatement = $pdo->prepare(
        'UPDATE produto
         SET estoque = estoque - ?
         WHERE id_produto = ? AND estoque >= ?'
    );

    foreach ($orderItems as $item) {
        $itemStatement->execute([
            $orderId,
            $item['id_produto'],
            $item['quantidade'],
        ]);

        $stockStatement->execute([
            $item['quantidade'],
            $item['id_produto'],
            $item['quantidade'],
        ]);

        if ($stockStatement->rowCount() !== 1) {
            throw new RuntimeException('Stock changed before checkout could finish.');
        }
    }

    $pdo->prepare('DELETE FROM itens_carrinho WHERE id_usuario = ?')
        ->execute([(int) $_SESSION['user']['id_usuario']]);
    $pdo->commit();
    $_SESSION['cart'] = [];
    $_SESSION['order_success'] = [
        'id_pedido' => $orderId,
        'valor_total' => $total,
    ];

    header('Location: order_success.php');
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['checkout_error'] = $error instanceof RuntimeException
        ? $error->getMessage()
        : 'Could not place the order. Please try again.';
    header('Location: checkout.php');
}
