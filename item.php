<?php
session_start();
require_once __DIR__ . '/db.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$relatedProducts = [];
$errorMessage = null;

if (!$productId) {
    http_response_code(404);
    $errorMessage = 'Produto não encontrado.';
} else {
    try {
        $statement = database()->prepare(
            'SELECT p.id_produto, p.nome, p.preco, p.estoque,
                    c.id_categoria, c.nome AS categoria,
                    u.id_usuario, u.nome AS vendedor
             FROM produto p
             INNER JOIN categorias c ON c.id_categoria = p.id_categoria
             INNER JOIN usuario u ON u.id_usuario = p.id_usuario
             WHERE p.id_produto = ?
             LIMIT 1'
        );
        $statement->execute([$productId]);
        $product = $statement->fetch();

        if (!$product) {
            http_response_code(404);
            $errorMessage = 'Produto não encontrado.';
        } else {
            $relatedStatement = database()->prepare(
                'SELECT p.id_produto, p.nome, p.preco, p.estoque, c.nome AS categoria
                 FROM produto p
                 INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                 WHERE p.id_usuario = ? AND p.id_produto != ?
                 ORDER BY p.id_produto DESC
                 LIMIT 4'
            );
            $relatedStatement->execute([(int) $product['id_usuario'], $productId]);
            $relatedProducts = $relatedStatement->fetchAll();
        }
    } catch (Throwable $error) {
        http_response_code(500);
        $errorMessage = 'Não foi possível carregar os detalhes do produto.';
    }
}

$categoryStyles = [
    'electronics' => 'vendor-blue',
    'fashion' => 'vendor-purple',
    'home' => 'vendor-green',
    'fitness' => 'vendor-yellow',
];
$categoryEmojis = [
    'electronics' => '&#128241;',
    'fashion' => '&#128085;',
    'home' => '&#9749;',
    'fitness' => '&#127947;',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['nome']) : 'Produto'; ?> | Loja virtual</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="item-detail-page">
    <div id="header-placeholder"></div>

    <main class="item-page">
        <nav class="item-breadcrumb" aria-label="Caminho de navegação">
            <a href="dashboard.php">Início</a>
            <span>/</span>
            <?php if ($product): ?>
            <a href="dashboard.php"><?php echo htmlspecialchars(display_category($product['categoria'])); ?></a>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['nome']); ?></span>
            <?php else: ?>
            <span>Produto</span>
            <?php endif; ?>
        </nav>

        <section class="item-card" aria-labelledby="item-title">
            <div class="item-gallery">
                <div class="item-main-image">
                    <?php if ($product): ?>
                    <span class="item-badge"><?php echo htmlspecialchars(display_category($product['categoria'])); ?></span>
                    <?php endif; ?>
                    <span class="item-product-emoji" aria-hidden="true"><?php echo $product ? ($categoryEmojis[strtolower($product['categoria'])] ?? '&#128230;') : '&#128230;'; ?></span>
                </div>
            </div>

            <div class="item-details">
                <?php if ($errorMessage): ?>
                    <p class="item-category">Indisponível</p>
                    <h1 id="item-title"><?php echo htmlspecialchars($errorMessage); ?></h1>
                <?php else: ?>
                <p class="item-category"><?php echo htmlspecialchars(display_category($product['categoria'])); ?></p>
                <h1 id="item-title"><?php echo htmlspecialchars($product['nome']); ?></h1>

                <div class="item-price-row">
                    <p class="item-price"><?php echo format_currency((float) $product['preco']); ?></p>
                </div>

                <form class="add-to-cart-form" action="cart_action.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id_produto" value="<?php echo (int) $product['id_produto']; ?>">
                    <div class="quantity-control">
                        <button type="button" class="quantity-minus" aria-label="Diminuir quantidade">−</button>
                        <input type="number" id="quantity" name="quantidade" value="1" min="1" max="<?php echo max(1, (int) $product['estoque']); ?>" required aria-label="Quantidade">
                        <button type="button" class="quantity-plus" aria-label="Aumentar quantidade">+</button>
                    </div>
                    <button type="submit" class="add-to-cart-button" <?php echo (int) $product['estoque'] < 1 ? 'disabled' : ''; ?>>
                        <?php echo (int) $product['estoque'] < 1 ? 'Sem estoque' : 'Adicionar ao carrinho'; ?>
                    </button>
                </form>

                <div class="item-meta">
                    <div>
                        <span class="meta-icon">&#10003;</span>
                        <p><strong><?php echo (int) $product['estoque'] > 0 ? 'Em estoque' : 'Sem estoque'; ?></strong><small><?php echo (int) $product['estoque']; ?> disponíveis</small></p>
                    </div>
                </div>

                <?php if (isset($_SESSION['user']['id_usuario']) && (int) $_SESSION['user']['id_usuario'] === (int) $product['id_usuario']): ?>
                    <a class="item-edit-link" href="edit-product.php?id=<?php echo (int) $product['id_produto']; ?>">Editar este produto</a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <section class="item-info-section" aria-labelledby="description-title">
            <div>
                <h2 id="description-title">Informações do produto</h2>
                <p>Dados do anúncio cadastrados na loja.</p>
            </div>
            <dl class="item-specs">
                <div><dt>Categoria</dt><dd><?php echo $product ? htmlspecialchars(display_category($product['categoria'])) : 'Indisponível'; ?></dd></div>
                <div><dt>Preço</dt><dd><?php echo $product ? format_currency((float) $product['preco']) : 'Indisponível'; ?></dd></div>
                <div><dt>Estoque</dt><dd><?php echo $product ? (int) $product['estoque'] : 'Indisponível'; ?></dd></div>
            </dl>
        </section>

        <section class="same-vendor-products" aria-labelledby="vendor-title">
            <div class="vendor-heading">
                <div>
                    <h2 id="vendor-title">Mais produtos deste vendedor</h2>
                </div>
                <a href="dashboard.php">Ver todos</a>
            </div>
            <div class="vendor-products">
                <?php foreach ($relatedProducts as $related): ?>
                    <?php
                    $relatedSlug = strtolower($related['categoria']);
                    $relatedStyle = $categoryStyles[$relatedSlug] ?? 'vendor-blue';
                    $relatedEmoji = $categoryEmojis[$relatedSlug] ?? '&#128230;';
                    ?>
                    <article class="vendor-product-card">
                        <a href="item.php?id=<?php echo (int) $related['id_produto']; ?>" aria-label="Ver <?php echo htmlspecialchars($related['nome']); ?>">
                            <div class="vendor-product-image <?php echo $relatedStyle; ?>"><span aria-hidden="true"><?php echo $relatedEmoji; ?></span></div>
                        </a>
                        <p class="vendor-product-category"><?php echo htmlspecialchars(display_category($related['categoria'])); ?></p>
                        <h3 class="vendor-product-name"><?php echo htmlspecialchars($related['nome']); ?></h3>
                        <div class="vendor-product-footer">
                            <strong><?php echo format_currency((float) $related['preco']); ?></strong>
                            <a href="item.php?id=<?php echo (int) $related['id_produto']; ?>">Ver</a>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$relatedProducts): ?>
                    <p class="auth-message">Este vendedor não tem outros anúncios disponíveis.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="JS/load-header.js"></script>
    <script>
        const quantityInput = document.getElementById('quantity');
        document.querySelector('.quantity-minus').addEventListener('click', function () {
            quantityInput.value = Math.max(1, Number(quantityInput.value) - 1);
        });
        document.querySelector('.quantity-plus').addEventListener('click', function () {
            quantityInput.value = Math.min(Number(quantityInput.max), Number(quantityInput.value) + 1);
        });
        quantityInput.addEventListener('change', function () {
            quantityInput.value = Math.min(
                Number(quantityInput.max),
                Math.max(1, Number(quantityInput.value) || 1)
            );
        });

        document.querySelectorAll('.color-option').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('.color-option').forEach(function (item) {
                    item.classList.remove('selected');
                });
                button.classList.add('selected');
            });
        });

        document.querySelectorAll('.item-thumbnail').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('.item-thumbnail').forEach(function (item) {
                    item.classList.remove('active');
                });
                button.classList.add('active');
            });
        });

    </script>
</body>
</html>