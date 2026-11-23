<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Please sign in to manage products.';
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php');
    exit;
}

$userId = (int) $_SESSION['user']['id_usuario'];
$action = (string) ($_POST['action'] ?? '');
$token = (string) ($_POST['csrf_token'] ?? '');
$expectedToken = (string) ($_SESSION['product_form_token'] ?? '');

if ($expectedToken === '' || !hash_equals($expectedToken, $token)) {
    $_SESSION['product_form_error'] = 'Your form expired. Please try again.';
    header('Location: list-product.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);

try {
    $pdo = database();

    if ($action === 'delete') {
        if (!$productId || $productId < 1) {
            throw new RuntimeException('Invalid product.');
        }

        $ownership = $pdo->prepare(
            'SELECT id_produto FROM produto WHERE id_produto = ? AND id_usuario = ?'
        );
        $ownership->execute([$productId, $userId]);
        if (!$ownership->fetch()) {
            throw new RuntimeException('Product not found or you do not have permission to delete it.');
        }

        $orderItem = $pdo->prepare('SELECT id_item FROM itens_pedido WHERE id_produto = ? LIMIT 1');
        $orderItem->execute([$productId]);
        if ($orderItem->fetch()) {
            throw new RuntimeException('This product is part of an order and cannot be deleted.');
        }

        $delete = $pdo->prepare('DELETE FROM produto WHERE id_produto = ? AND id_usuario = ?');
        $delete->execute([$productId, $userId]);
        $_SESSION['product_form_success'] = 'Product deleted.';
        header('Location: account.php');
        exit;
    }

    $name = trim((string) ($_POST['nome'] ?? ''));
    $categoryId = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'estoque', FILTER_VALIDATE_INT);

    if ($name === '' || strlen($name) > 200) {
        throw new RuntimeException('Product name is required and must be at most 200 characters.');
    }
    if (!$categoryId || $categoryId < 1) {
        throw new RuntimeException('Select a valid product category.');
    }
    if ($price === false || $price < 0 || !is_finite((float) $price)) {
        throw new RuntimeException('Enter a valid non-negative price.');
    }
    if ($stock === false || $stock < 0 || $stock > 999999) {
        throw new RuntimeException('Enter a valid stock quantity.');
    }

    $category = $pdo->prepare('SELECT id_categoria FROM categorias WHERE id_categoria = ?');
    $category->execute([$categoryId]);
    if (!$category->fetch()) {
        throw new RuntimeException('Select a valid product category.');
    }

    if ($action === 'create') {
        $statement = $pdo->prepare(
            'INSERT INTO produto (id_usuario, id_categoria, nome, preco, estoque)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$userId, $categoryId, $name, $price, $stock]);
        $_SESSION['product_form_success'] = 'Product created successfully.';
        header('Location: account.php');
        exit;
    }

    if ($action === 'update') {
        if (!$productId || $productId < 1) {
            throw new RuntimeException('Invalid product.');
        }
        $statement = $pdo->prepare(
            'UPDATE produto
             SET id_categoria = ?, nome = ?, preco = ?, estoque = ?
             WHERE id_produto = ? AND id_usuario = ?'
        );
        $statement->execute([$categoryId, $name, $price, $stock, $productId, $userId]);

        if ($statement->rowCount() === 0) {
            $check = $pdo->prepare(
                'SELECT id_produto FROM produto WHERE id_produto = ? AND id_usuario = ?'
            );
            $check->execute([$productId, $userId]);
            if (!$check->fetch()) {
                throw new RuntimeException('Product not found or you do not have permission to edit it.');
            }
        }

        $_SESSION['product_form_success'] = 'Product updated successfully.';
        header('Location: account.php');
        exit;
    }

    throw new RuntimeException('Invalid product action.');
} catch (RuntimeException $error) {
    $_SESSION['product_form_error'] = $error->getMessage();
} catch (Throwable $error) {
    $_SESSION['product_form_error'] = 'Could not save product changes. Please try again.';
}

if ($action === 'update' && $productId) {
    header('Location: edit-product.php?id=' . (int) $productId);
} else {
    header('Location: list-product.php');
}
