<?php

declare(strict_types=1);
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

$count = 0;

if (isset($_SESSION['user']['id_usuario'])) {
    try {
        $cart = get_user_cart(database(), (int) $_SESSION['user']['id_usuario']);
        $_SESSION['cart'] = $cart;
        $count = array_sum($cart);
    } catch (Throwable $error) {
        $count = 0;
    }
} else {
    $cart = $_SESSION['cart'] ?? [];
}

if (!isset($_SESSION['user']['id_usuario']) && is_array($cart) && $cart) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        $statement = database()->prepare(
            'SELECT id_produto, estoque FROM produto
             WHERE id_produto IN (' . $placeholders . ')'
        );
        $statement->execute($ids);
        $stockByProduct = [];
        $rows = $statement->fetchAll();

        foreach ($rows as $item) {
            $productId = (int) $item['id_produto'];
            $stock = (int) $item['estoque'];
            $stockByProduct[$productId] = $stock;
            $quantity = max(0, (int) ($cart[$productId] ?? 0));

            if ($stock < 1) {
                unset($cart[$productId]);
                continue;
            }

            $quantity = min($quantity, $stock);
            if ($quantity < 1) {
                unset($cart[$productId]);
                continue;
            }

            $cart[$productId] = $quantity;
            $count += $quantity;
        }

        foreach (array_keys($cart) as $productId) {
            if (!isset($stockByProduct[(int) $productId])) {
                unset($cart[$productId]);
            }
        }
    } catch (Throwable $error) {
        $count = 0;
    }

    $_SESSION['cart'] = $cart;
}

echo json_encode(['count' => $count]);
