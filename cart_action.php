<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$action = (string) ($_POST['action'] ?? '');
$productId = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!$productId || $productId < 1) {
    $_SESSION['cart_error'] = 'O produto selecionado é inválido.';
    header('Location: cart.php');
    exit;
}

try {
    $pdo = database();
    $statement = $pdo->prepare(
        'SELECT id_produto, estoque FROM produto WHERE id_produto = ? LIMIT 1'
    );
    $statement->execute([$productId]);
    $product = $statement->fetch();

    if (!$product) {
        $_SESSION['cart_error'] = 'O produto selecionado não foi encontrado.';
        header('Location: cart.php');
        exit;
    }

    $stock = (int) $product['estoque'];

    if ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);
    } elseif ($action === 'set') {
        if ($stock < 1) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['cart_error'] = 'Este produto está sem estoque.';
        } else {
            $requested = $quantity === false ? 1 : (int) $quantity;
            $requested = max(1, $requested);
            $_SESSION['cart'][$productId] = min($requested, $stock);
            if ($requested > $stock) {
                $unitLabel = $stock === 1 ? 'unidade' : 'unidades';
                $_SESSION['cart_error'] = 'Há apenas ' . $stock . ' ' . $unitLabel . ' deste produto em estoque.';
            }
        }
    } elseif ($action === 'add') {
        if ($stock < 1) {
            unset($_SESSION['cart'][$productId]);
            $_SESSION['cart_error'] = 'Este produto está sem estoque.';
        } else {
            $requested = $quantity === false ? 1 : (int) $quantity;
            $requested = max(1, $requested);
            $current = (int) ($_SESSION['cart'][$productId] ?? 0);
            $newQuantity = min($current + $requested, $stock);
            $_SESSION['cart'][$productId] = $newQuantity;
            if ($current + $requested > $stock) {
                $unitLabel = $stock === 1 ? 'unidade' : 'unidades';
                $_SESSION['cart_error'] = 'Há apenas ' . $stock . ' ' . $unitLabel . ' deste produto em estoque.';
            }
        }
    } else {
        $_SESSION['cart_error'] = 'Ação inválida para o carrinho.';
    }

    if (isset($_SESSION['user']['id_usuario']) && in_array($action, ['add', 'set', 'remove'], true)) {
        set_user_cart_item(
            $pdo,
            (int) $_SESSION['user']['id_usuario'],
            $productId,
            (int) ($_SESSION['cart'][$productId] ?? 0)
        );
    }
} catch (Throwable $error) {
    $_SESSION['cart_error'] = 'Não foi possível atualizar o carrinho.';
}

header('Location: cart.php');
