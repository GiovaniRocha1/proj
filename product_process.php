<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Entre na sua conta para gerenciar produtos.';
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
    $_SESSION['product_form_error'] = 'O formulário expirou. Tente novamente.';
    header('Location: list-product.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);

try {
    $pdo = database();

    if ($action === 'delete') {
        if (!$productId || $productId < 1) {
            throw new RuntimeException('Produto inválido.');
        }

        $ownership = $pdo->prepare(
            'SELECT id_produto FROM produto WHERE id_produto = ? AND id_usuario = ?'
        );
        $ownership->execute([$productId, $userId]);
        if (!$ownership->fetch()) {
            throw new RuntimeException('Produto não encontrado ou você não tem permissão para excluí-lo.');
        }

        $orderItem = $pdo->prepare('SELECT id_item FROM itens_pedido WHERE id_produto = ? LIMIT 1');
        $orderItem->execute([$productId]);
        if ($orderItem->fetch()) {
            throw new RuntimeException('Este produto faz parte de um pedido e não pode ser excluído.');
        }

        $delete = $pdo->prepare('DELETE FROM produto WHERE id_produto = ? AND id_usuario = ?');
        $delete->execute([$productId, $userId]);
        $_SESSION['product_form_success'] = 'Produto excluído.';
        header('Location: account.php');
        exit;
    }

    $name = trim((string) ($_POST['nome'] ?? ''));
    $categoryId = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
    $stock = filter_input(INPUT_POST, 'estoque', FILTER_VALIDATE_INT);

    if ($name === '' || strlen($name) > 200) {
        throw new RuntimeException('Informe um nome para o produto com no máximo 200 caracteres.');
    }
    if (!$categoryId || $categoryId < 1) {
        throw new RuntimeException('Selecione uma categoria válida.');
    }
    if ($price === false || $price < 0 || !is_finite((float) $price)) {
        throw new RuntimeException('Informe um preço válido, igual ou superior a zero.');
    }
    if ($stock === false || $stock < 0 || $stock > 999999) {
        throw new RuntimeException('Informe uma quantidade válida para o estoque.');
    }

    $category = $pdo->prepare('SELECT id_categoria FROM categorias WHERE id_categoria = ?');
    $category->execute([$categoryId]);
    if (!$category->fetch()) {
        throw new RuntimeException('Selecione uma categoria válida.');
    }

    if ($action === 'create') {
        $statement = $pdo->prepare(
            'INSERT INTO produto (id_usuario, id_categoria, nome, preco, estoque)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$userId, $categoryId, $name, $price, $stock]);
        $_SESSION['product_form_success'] = 'Produto anunciado com sucesso.';
        header('Location: account.php');
        exit;
    }

    if ($action === 'update') {
        if (!$productId || $productId < 1) {
            throw new RuntimeException('Produto inválido.');
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
                throw new RuntimeException('Produto não encontrado ou você não tem permissão para editá-lo.');
            }
        }

        $_SESSION['product_form_success'] = 'Produto atualizado com sucesso.';
        header('Location: account.php');
        exit;
    }

    throw new RuntimeException('Ação de produto inválida.');
} catch (RuntimeException $error) {
    $_SESSION['product_form_error'] = $error->getMessage();
} catch (Throwable $error) {
    $_SESSION['product_form_error'] = 'Não foi possível salvar as alterações do produto. Tente novamente.';
}

if ($action === 'update' && $productId) {
    header('Location: edit-product.php?id=' . (int) $productId);
} else {
    header('Location: list-product.php');
}
