<?php
session_start();
require_once __DIR__ . '/db.php';

$cartError = $_SESSION['cart_error'] ?? null;
unset($_SESSION['cart_error']);

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartItems = [];
$subtotal = 0.0;
$itemCount = 0;
$pdo = null;

try {
    $pdo = database();
    if (isset($_SESSION['user']['id_usuario'])) {
        $_SESSION['cart'] = get_user_cart($pdo, (int) $_SESSION['user']['id_usuario']);
    }
} catch (Throwable $error) {
    $cartError = 'Não foi possível carregar os itens do carrinho.';
}

if ($_SESSION['cart'] && $pdo instanceof PDO) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    try {
        $statement = $pdo->prepare(
            'SELECT p.id_produto, p.nome, p.preco, p.estoque, c.nome AS categoria
             FROM produto p
             INNER JOIN categorias c ON c.id_categoria = p.id_categoria
             WHERE p.id_produto IN (' . $placeholders . ')'
        );
        $statement->execute($ids);
        $cartItems = $statement->fetchAll();

        foreach ($cartItems as &$item) {
            $id = (int) $item['id_produto'];
            $stock = (int) $item['estoque'];
            if ($stock < 1) {
                unset($_SESSION['cart'][$id]);
                continue;
            }
            $quantity = $stock > 0 ? max(1, (int) ($_SESSION['cart'][$id] ?? 1)) : 0;
            $quantity = min($quantity, $stock);
            $_SESSION['cart'][$id] = $quantity;
            $item['quantidade'] = $quantity;
            $item['line_total'] = (float) $item['preco'] * $quantity;
            $subtotal += $item['line_total'];
            $itemCount += $quantity;
        }
        unset($item);
        $cartItems = array_values(array_filter(
            $cartItems,
            fn($item) => (int) $item['estoque'] > 0
        ));
    } catch (Throwable $error) {
        $cartError = 'Não foi possível carregar os itens do carrinho.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho de compras | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="cart-page">
    <div id="header-placeholder"></div>

    <main class="cart-main">
        <nav class="cart-breadcrumb" aria-label="Caminho de navegação">
            <a href="dashboard.php">Início</a>
            <span>/</span>
            <span>Carrinho de compras</span>
        </nav>

        <div class="cart-heading">
            <div>
                <p class="cart-eyebrow">Sua seleção</p>
                <h1>Carrinho de compras</h1>
            </div>
            <span class="cart-item-total"><?php echo $itemCount . ($itemCount === 1 ? ' item' : ' itens'); ?></span>
        </div>

        <div class="cart-layout">
            <section class="cart-items" aria-label="Itens no seu carrinho">
                <?php if ($cartError): ?>
                    <p class="auth-message auth-error"><?php echo htmlspecialchars($cartError); ?></p>
                <?php endif; ?>
                <?php if (!$cartItems): ?>
                    <div class="cart-empty">
                        <span>&#128722;</span>
                        <h2>Seu carrinho está vazio</h2>
                        <p>Explore a loja e adicione produtos ao seu carrinho.</p>
                        <a href="dashboard.php">Começar a comprar</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <?php
                        $categorySlug = strtolower($item['categoria']);
                        $imageClass = $categorySlug === 'fashion' ? 'cart-image-purple' : ($categorySlug === 'home' ? 'cart-image-green' : 'cart-image-blue');
                        ?>
                        <article class="cart-item" data-price="<?php echo (float) $item['preco']; ?>" data-product-id="<?php echo (int) $item['id_produto']; ?>" data-stock="<?php echo (int) $item['estoque']; ?>">
                            <a class="cart-product-image <?php echo $imageClass; ?>" href="item.php?id=<?php echo (int) $item['id_produto']; ?>" aria-label="Ver <?php echo htmlspecialchars($item['nome']); ?>">
                                <span class="product-emoji" aria-hidden="true"><?php echo $categorySlug === 'fashion' ? '&#128085;' : ($categorySlug === 'home' ? '&#9749;' : ($categorySlug === 'fitness' ? '&#127947;' : '&#128241;')); ?></span>
                            </a>
                            <div class="cart-item-details">
                                <p class="cart-product-category"><?php echo htmlspecialchars(display_category($item['categoria'])); ?></p>
                                <h2><a href="item.php?id=<?php echo (int) $item['id_produto']; ?>"><?php echo htmlspecialchars($item['nome']); ?></a></h2>
                                <p class="cart-product-meta">Categoria: <?php echo htmlspecialchars(display_category($item['categoria'])); ?> <span>•</span> <?php echo (int) $item['estoque']; ?> em estoque</p>
                                <button class="cart-remove" type="button" data-product-id="<?php echo (int) $item['id_produto']; ?>">Remover</button>
                            </div>
                            <div class="cart-item-actions">
                                <div class="cart-quantity" aria-label="Quantidade">
                                    <button type="button" class="cart-minus" aria-label="Diminuir quantidade">−</button>
                                    <input type="number" name="quantidade" value="<?php echo (int) $item['quantidade']; ?>" min="1" max="<?php echo (int) $item['estoque']; ?>" aria-label="Quantidade" data-product-id="<?php echo (int) $item['id_produto']; ?>">
                                    <button type="button" class="cart-plus" aria-label="Aumentar quantidade">+</button>
                                </div>
                                <strong class="cart-line-price"><?php echo format_currency((float) $item['line_total']); ?></strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <aside class="cart-summary" aria-labelledby="summary-title">
                <h2 id="summary-title">Resumo do pedido</h2>

                <dl class="cart-totals">
                    <div>
                        <dt>Subtotal</dt>
                        <dd id="cart-subtotal"><?php echo format_currency($subtotal); ?></dd>
                    </div>
                    <div class="cart-total">
                        <dt>Total</dt>
                        <dd id="cart-total"><?php echo format_currency($subtotal); ?></dd>
                    </div>
                </dl>

                <button type="button" class="checkout-button">Finalizar compra</button>
                <a class="continue-shopping" href="dashboard.php">Continuar comprando</a>

                <div class="checkout-security">
                    <span>&#128274;</span>
                    <p><strong>Resumo do carrinho</strong><small>Calculado com base no preço e na quantidade dos produtos</small></p>
                </div>
            </aside>
        </div>
    </main>

    <script src="JS/load-header.js"></script>
    <script>
        function formatMoney(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
        }

        function updateCart() {
            let subtotal = 0;
            let itemCount = 0;

            document.querySelectorAll('.cart-item').forEach(function (item) {
                const quantityInput = item.querySelector('input');
                const stock = Number(item.dataset.stock);
                const quantity = Math.min(stock, Math.max(1, Number(quantityInput.value) || 1));
                quantityInput.value = quantity;
                const price = Number(item.dataset.price);
                const linePrice = price * quantity;
                subtotal += linePrice;
                itemCount += quantity;
                item.querySelector('.cart-line-price').textContent = formatMoney(linePrice);
            });

            document.getElementById('cart-subtotal').textContent = formatMoney(subtotal);
            document.getElementById('cart-total').textContent = formatMoney(subtotal);
            document.querySelector('.cart-item-total').textContent = itemCount + (itemCount === 1 ? ' item' : ' itens');

            const headerCount = document.querySelector('.cart-count');
            if (headerCount) {
                headerCount.textContent = itemCount;
                headerCount.closest('.cart-link').setAttribute(
                    'aria-label',
                    itemCount === 1 ? '1 item no carrinho' : itemCount + ' itens no carrinho'
                );
            }
        }

        function saveQuantity(input) {
            const item = input.closest('.cart-item');
            const stock = Number(item.dataset.stock);
            const quantity = Math.min(stock, Math.max(1, Number(input.value) || 1));
            input.value = quantity;

            const formData = new URLSearchParams();
            formData.set('action', 'set');
            formData.set('id_produto', input.dataset.productId);
            formData.set('quantidade', quantity);

            fetch('cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString(),
                redirect: 'follow'
            }).then(function () {
                window.location.reload();
            });
        }

        document.querySelectorAll('.cart-quantity').forEach(function (control) {
            const input = control.querySelector('input');
            control.querySelector('.cart-minus').addEventListener('click', function () {
                input.value = Math.max(1, Number(input.value) - 1);
                saveQuantity(input);
            });
            control.querySelector('.cart-plus').addEventListener('click', function () {
                input.value = Math.min(Number(input.max), Number(input.value) + 1);
                saveQuantity(input);
            });
            input.addEventListener('change', function () {
                saveQuantity(input);
            });
        });

        document.querySelectorAll('.cart-remove').forEach(function (button) {
            button.addEventListener('click', function () {
                const formData = new URLSearchParams();
                formData.set('action', 'remove');
                formData.set('id_produto', button.dataset.productId);
                fetch('cart_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString(),
                    redirect: 'follow'
                }).then(function () {
                    window.location.reload();
                });
            });
        });

        document.querySelector('.checkout-button').addEventListener('click', function () {
            window.location.href = 'checkout.php';
        });

        updateCart();
    </script>
</body>
</html>
