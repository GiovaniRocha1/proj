<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Please sign in before checkout.';
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
    $checkoutError = 'Could not load checkout information.';
}

if (!$cart && !$checkoutError) {
    $_SESSION['checkout_error'] = 'Your cart is empty.';
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
        $checkoutError = 'Could not load checkout information.';
    }
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="checkout-page">
    <div id="header-placeholder"></div>

    <main class="checkout-main">
        <nav class="checkout-breadcrumb" aria-label="Breadcrumb">
            <a href="cart.php">Cart</a>
            <span>/</span>
            <span>Checkout</span>
        </nav>

        <div class="checkout-heading">
            <div>
                <p class="checkout-eyebrow">Order review</p>
                <h1>Complete your order</h1>
            </div>
            <div class="checkout-progress" aria-label="Checkout progress">
                <span class="active">1. Order details</span>
                <span>2. Review</span>
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
                            <h2 id="customer-heading">Customer</h2>
                            <p>Order information linked to the logged-in user.</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <div class="checkout-field full-width">
                            <label for="nome">Name</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" readonly>
                        </div>
                        <div class="checkout-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="checkout-field">
                            <label for="login">Login</label>
                            <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($user['login']); ?>" readonly>
                        </div>
                    </div>
                </section>

                <section class="checkout-panel" aria-labelledby="order-heading">
                    <div class="checkout-panel-heading">
                        <span class="checkout-step">2</span>
                        <div>
                            <h2 id="order-heading">Order details</h2>
                            <p>Fields stored in the pedidos table.</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <div class="checkout-field">
                            <label for="data-pedido">Order date</label>
                            <input type="date" id="data-pedido" name="data_pedido" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                        <div class="checkout-field">
                            <label for="status">Status</label>
                            <input type="text" id="status" name="status" value="Pending" readonly>
                        </div>
                        <div class="checkout-field full-width">
                            <label for="valor-total">Total value</label>
                            <input type="number" id="valor-total" name="valor_total" value="<?php echo number_format($subtotal, 2, '.', ''); ?>" readonly>
                        </div>
                    </div>
                </section>

                <section class="checkout-panel" aria-labelledby="delivery-heading">
                    <div class="checkout-panel-heading">
                        <span class="checkout-step">3</span>
                        <div>
                            <h2 id="delivery-heading">Delivery and payment</h2>
                            <p>These details are saved with your order. No payment is processed here.</p>
                        </div>
                    </div>
                    <div class="checkout-fields">
                        <div class="checkout-field full-width">
                            <label for="endereco-entrega">Street address</label>
                            <input type="text" id="endereco-entrega" name="endereco_entrega" maxlength="255" autocomplete="street-address" required>
                        </div>
                        <div class="checkout-field">
                            <label for="cidade-entrega">City</label>
                            <input type="text" id="cidade-entrega" name="cidade_entrega" value="<?php echo htmlspecialchars((string) ($user['cidade'] ?? '')); ?>" maxlength="100" autocomplete="address-level2" required>
                        </div>
                        <div class="checkout-field">
                            <label for="codigo-postal">Postal code</label>
                            <input type="text" id="codigo-postal" name="codigo_postal" maxlength="20" autocomplete="postal-code" required>
                        </div>
                        <div class="checkout-field">
                            <label for="pais-entrega">Country</label>
                            <input type="text" id="pais-entrega" name="pais_entrega" maxlength="100" autocomplete="country-name" required>
                        </div>
                        <div class="checkout-field">
                            <label for="metodo-pagamento">Payment method</label>
                            <select id="metodo-pagamento" name="metodo_pagamento" required>
                                <option value="Not specified">Not specified</option>
                                <option value="Cash on delivery">Cash on delivery</option>
                                <option value="Bank transfer">Bank transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="checkout-summary" aria-labelledby="order-summary-heading">
                <h2 id="order-summary-heading">Order summary</h2>

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
                                <small><?php echo htmlspecialchars($item['categoria']); ?></small>
                            </div>
                            <b>$<?php echo number_format((float) $item['line_total'], 2); ?></b>
                        </div>
                    <?php endforeach; ?>
                </div>

                <dl class="checkout-totals">
                    <div>
                        <dt>Subtotal</dt>
                        <dd>$<?php echo number_format($subtotal, 2); ?></dd>
                    </div>
                    <div class="checkout-total">
                        <dt>Total</dt>
                        <dd>$<?php echo number_format($subtotal, 2); ?></dd>
                    </div>
                </dl>

                <button type="submit" class="place-order-button" <?php echo !$checkoutItems ? 'disabled' : ''; ?>>Place order</button>
                <a class="back-to-cart" href="cart.php">Back to cart</a>

                <div class="checkout-security">
                    <span>&#128274;</span>
                    <p><strong>Order information</strong><small>Stored using the database tables</small></p>
                </div>
            </aside>
        </form>
    </main>

    <script src="JS/load-header.js"></script>
</body>
</html>
