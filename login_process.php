<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$login = trim((string) ($_POST['login'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');

if ($login === '' || $senha === '') {
    $_SESSION['flash_error'] = 'Enter your login and password.';
    header('Location: login.php');
    exit;
}

try {
    $pdo = database();
    $statement = $pdo->prepare(
        'SELECT l.id, l.id_usuario, l.login, l.senha, u.nome, u.email, u.cidade, u.idade, u.tipo, u.telefone
         FROM login l
         INNER JOIN usuario u ON u.id_usuario = l.id_usuario
         WHERE l.login = ?
         LIMIT 1'
    );
    $statement->execute([$login]);
    $account = $statement->fetch();

    if (!$account || !password_verify($senha, $account['senha'])) {
        $_SESSION['flash_error'] = 'Invalid login or password.';
        header('Location: login.php');
        exit;
    }

    $guestCart = !isset($_SESSION['user']) && is_array($_SESSION['cart'] ?? null)
        ? $_SESSION['cart']
        : [];
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id_usuario' => (int) $account['id_usuario'],
        'nome' => $account['nome'],
        'email' => $account['email'],
        'cidade' => $account['cidade'],
        'idade' => (int) $account['idade'],
        'tipo' => (int) $account['tipo'],
        'telefone' => $account['telefone'],
        'login' => $account['login'],
    ];

    try {
        $pdo->beginTransaction();
        $cart = get_user_cart($pdo, (int) $account['id_usuario']);
        $guestIds = array_filter(array_map('intval', array_keys($guestCart)));
        if ($guestIds) {
            $placeholders = implode(',', array_fill(0, count($guestIds), '?'));
            $products = $pdo->prepare(
                'SELECT id_produto, estoque FROM produto WHERE id_produto IN (' . $placeholders . ')'
            );
            $products->execute(array_values($guestIds));
            foreach ($products->fetchAll() as $product) {
                $productId = (int) $product['id_produto'];
                $stock = (int) $product['estoque'];
                $requested = max(0, (int) ($guestCart[$productId] ?? 0));
                $quantity = min($stock, ($cart[$productId] ?? 0) + $requested);
                if ($quantity > 0) {
                    $cart[$productId] = $quantity;
                }
            }
        }

        $pdo->prepare('DELETE FROM itens_carrinho WHERE id_usuario = ?')
            ->execute([(int) $account['id_usuario']]);
        foreach ($cart as $productId => $quantity) {
            set_user_cart_item($pdo, (int) $account['id_usuario'], (int) $productId, (int) $quantity);
        }
        $pdo->commit();
        $_SESSION['cart'] = $cart;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        unset($_SESSION['user']);
        $_SESSION['cart'] = $guestCart;
        $_SESSION['flash_error'] = 'Could not load your saved cart. Please try again.';
        header('Location: login.php');
        exit;
    }

    header('Location: dashboard.php');
} catch (Throwable $error) {
    $_SESSION['flash_error'] = 'Could not sign in. Please try again.';
    header('Location: login.php');
}
