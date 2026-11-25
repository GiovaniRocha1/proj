<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Entre na sua conta para finalizar a compra.';
    header('Location: login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$checkoutItems = [];
$subtotal = 0.0;
$checkoutError = $_SESSION['checkout_error'] ?? null;
unset($_SESSION['checkout_error']);

try {
    $pdo = database();
    $cart = get_user_cart($pdo, (int) $_SESSION['user']['id_usuario']);
    $_SESSION['cart'] = $cart;
} catch (Throwable $error) {
    $checkoutError = 'Não foi possível carregar os dados da compra.';
}

if (!$cart && !$checkoutError) {
    $_SESSION['checkout_error'] = 'Seu carrinho está vazio.';
    header('Location: cart.php');
    exit;
}

if ($cart) {
    $ids = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        $statement = $pdo->prepare(
            'SELECT p.id_produto, p.nome, p.preco, p.estoque, c.nome AS categoria
             FROM produto p
             INNER JOIN categorias c ON c.id_categoria = p.id_categoria
             WHERE p.id_produto IN (' . $placeholders . ')'
        );
        $statement->execute($ids);
        $checkoutItems = $statement->fetchAll();

        foreach ($checkoutItems as &$item) {
            $id = (int) $item['id_produto'];
            $stock = (int) $item['estoque'];
            $quantity = min(max(1, (int) ($cart[$id] ?? 1)), $stock);
            $_SESSION['cart'][$id] = $quantity;
            $item['quantidade'] = $quantity;
            $item['line_total'] = (float) $item['preco'] * $quantity;
            $subtotal += $item['line_total'];
        }
        unset($item);
    } catch (Throwable $error) {
        $checkoutError = 'Não foi possível carregar os dados da compra.';
    }
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar compra | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="checkout-page">
    <div id="header-placeholder"></div>

    <main class="checkout-main">
        <nav class="checkout-breadcrumb" aria-label="Caminho de navegação">
            <a href="cart.php">Carrinho</a>
            <span>/</span>
            <span>Finalizar compra</span>
        </nav>

        <div class="checkout-heading">
            <div>
                <p class="checkout-eyebrow">Revisão do pedido</p>
                <h1>Conclua sua compra</h1>
            </div>
            <div class="checkout-progress" aria-label="Etapas da compra">
                <span class="active">1. Dados do pedido</span>
                <span>2. Revisão</span>
            </div>
        </div>

        <?php if ($checkoutError): ?>
            <p class="auth-message auth-error"><?php echo htmlspecialchars($checkoutError); ?></p>
        <?php endif; ?>
        <form class="checkout-layout" id="checkout-form" action="checkout_process.php" method="POST">
            <div class="checkout-form-column">
                <section class="checkout-panel" aria-labelledby="customer-heading">
                    <div class="checkout-panel-heading">
                        <span class="checkout-step">1</span>
                        <div>
                            <h2 id="customer-heading">Cliente</h2>
                            <p>Informações do pedido vinculadas à sua conta.</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <div class="checkout-field full-width">
                            <label for="nome">Nome</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" readonly>
                        </div>
                        <div class="checkout-field">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="checkout-field">
                            <label for="login">Usuário</label>
                            <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($user['login']); ?>" readonly>
                        </div>
                    </div>
                </section>

                <section class="checkout-panel" aria-labelledby="order-heading">
                    <div class="checkout-panel-heading">
                        <span class="checkout-step">2</span>
                        <div>
                            <h2 id="order-heading">Dados do pedido</h2>
                            <p>Informações que serão salvas no seu pedido.</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <div class="checkout-field">
                            <label for="data-pedido">Data do pedido</label>
                            <input type="date" id="data-pedido" name="data_pedido" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                        <div class="checkout-field">
                            <label for="status">Status</label>
                            <input type="text" id="status" name="status" value="Pendente" readonly>
                        </div>
                        <div class="checkout-field full-width">
                            <label for="valor-total">Valor total</label>
                            <input type="number" id="valor-total" name="valor_total" value="<?php echo number_format($subtotal, 2, '.', ''); ?>" readonly>
                        </div>
                    </div>
                </section>

                <section class="checkout-panel" aria-labelledby="delivery-heading">
                    <div class="checkout-panel-heading">
                        <span class="checkout-step">3</span>
                        <div>
                            <h2 id="delivery-heading">Entrega e pagamento</h2>
                            <p>Esses dados serão salvos com o pedido. Nenhum pagamento será processado nesta etapa.</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <div class="checkout-field full-width">
                            <label for="endereco-entrega">Endereço</label>
                            <input type="text" id="endereco-entrega" name="endereco_entrega" maxlength="255" autocomplete="street-address" required>
                        </div>
                        <div class="checkout-field">
                            <label for="cidade-entrega">Cidade</label>
                            <input type="text" id="cidade-entrega" name="cidade_entrega" value="<?php echo htmlspecialchars((string) ($user['cidade'] ?? '')); ?>" maxlength="100" autocomplete="address-level2" required>
                        </div>
                        <div class="checkout-field">
                            <label for="codigo-postal">CEP</label>
                            <input type="text" id="codigo-postal" name="codigo_postal" maxlength="20" autocomplete="postal-code" required>
                        </div>
                        <div class="checkout-field">
                            <label for="pais-entrega">País</label>
                            <input type="text" id="pais-entrega" name="pais_entrega" value="Brasil" maxlength="100" autocomplete="country-name" required>
                        </div>
                        <div class="checkout-field">
                            <label for="metodo-pagamento">Forma de pagamento</label>
                            <select id="metodo-pagamento" name="metodo_pagamento" required>
                                <option value="Not specified">Não informado</option>
                                <option value="Cash on delivery">Pagamento na entrega</option>
                                <option value="Bank transfer">Transferência bancária</option>
                                <option value="Other">Outro</option>
                            </select>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="order-summary-heading">
                <h2 id="order-summary-heading">Resumo do pedido</h2>

                <div class="checkout-products">
                    <?php foreach ($checkoutItems as $item): ?>
                        <?php
                        $categorySlug = strtolower($item['categoria']);
                        $imageClass = $categorySlug === 'fashion'
                            ? 'checkout-image-purple'
                            : ($categorySlug === 'home' ? 'checkout-image-green' : 'checkout-image-blue');
                        ?>
                        <div class="checkout-product">
                            <div class="checkout-product-image <?php echo $imageClass; ?>">
                                <span aria-hidden="true"><?php echo $categorySlug === 'fashion' ? '&#128085;' : ($categorySlug === 'home' ? '&#9749;' : ($categorySlug === 'fitness' ? '&#127947;' : '&#128241;')); ?></span>
                                <span><?php echo (int) $item['quantidade']; ?></span>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($item['nome']); ?></strong>
                                <small><?php echo htmlspecialchars(display_category($item['categoria'])); ?></small>
                            </div>
                            <b><?php echo format_currency((float) $item['line_total']); ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>

                <dl class="checkout-totals">
                    <div>
                        <dt>Subtotal</dt>
                        <dd><?php echo format_currency($subtotal); ?></dd>
                    </div>
                    <div class="checkout-total">
                        <dt>Total</dt>
                        <dd><?php echo format_currency($subtotal); ?></dd>
                    </div>
                </dl>

                <button type="submit" class="place-order-button" <?php echo !$checkoutItems ? 'disabled' : ''; ?>>Confirmar pedido</button>
                <a class="back-to-cart" href="cart.php">Voltar ao carrinho</a>

                <div class="checkout-security">
                    <span>&#128274;</span>
                    <p><strong>Dados do pedido</strong><small>Armazenados com segurança</small></p>
                </div>
            </aside>
        </form>
    </main>

    <script src="JS/load-header.js"></script>
</body>
</html>
