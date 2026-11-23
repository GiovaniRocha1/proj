<?php
session_start();
require_once __DIR__ . '/db.php';

$errorMessage = null;
$products = [];

try {
    $statement = database()->prepare(
        'SELECT p.id_produto, p.nome, p.preco, p.estoque, c.nome AS categoria
         FROM produto p
         INNER JOIN categorias c ON c.id_categoria = p.id_categoria
         ORDER BY p.id_produto DESC'
    );
    $statement->execute();
    $products = $statement->fetchAll();
} catch (Throwable $error) {
    $errorMessage = 'Could not load products from the database.';
}

$categoryStyles = [
    'electronics' => 'product-image-blue',
    'fashion' => 'product-image-purple',
    'home' => 'product-image-green',
    'fitness' => 'product-image-coral',
];
$categoryEmojis = [
    'electronics' => '&#128241;',
    'fashion' => '&#128085;',
    'home' => '&#9749;',
    'fitness' => '&#127947;',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="store-page">
    <div id="header-placeholder"></div>

    <main class="store-main">
        <section class="store-hero" aria-labelledby="store-title">
            <div>
                <p class="store-eyebrow">Welcome to ShopZone</p>
                <h1 id="store-title">Find something you love</h1>
                <p>Explore popular products and everyday essentials.</p>
            </div>
            <div class="store-offer">
                <span>Special offer</span>
                <strong>Up to 30% off</strong>
            </div>
        </section>

        <section class="catalog" aria-labelledby="catalog-title">
            <div class="catalog-heading">
                <div>
                    <p class="catalog-eyebrow">Marketplace</p>
                    <h2 id="catalog-title">Featured products</h2>
                </div>
                <label class="sort-control">
                    <span>Sort:</span>
                    <select aria-label="Sort products">
                        <option>Featured</option>
                        <option>Lowest price</option>
                        <option>Highest price</option>
                        <option>Newest</option>
                    </select>
                </label>
            </div>

            <div class="filter-row" aria-label="Product filters">
                <button class="filter-chip active" type="button" data-category="all">All</button>
                <?php foreach (array_unique(array_map(fn($product) => strtolower($product['categoria']), $products)) as $categoryName): ?>
                    <button class="filter-chip" type="button" data-category="<?php echo htmlspecialchars($categoryName); ?>">
                        <?php echo htmlspecialchars(ucfirst($categoryName)); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="product-grid">
                <?php if ($errorMessage): ?>
                    <p class="auth-message auth-error"><?php echo htmlspecialchars($errorMessage); ?></p>
                <?php elseif (!$products): ?>
                    <p class="auth-message">No products are currently listed.</p>
                <?php else: ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php
                        $categorySlug = strtolower($product['categoria']);
                        $imageClass = $categoryStyles[$categorySlug] ?? 'product-image-blue';
                        $emoji = $categoryEmojis[$categorySlug] ?? '&#128230;';
                        ?>
                        <article class="product-card" data-category="<?php echo htmlspecialchars($categorySlug); ?>" data-product-id="<?php echo (int) $product['id_produto']; ?>" role="link" tabindex="0" aria-label="View <?php echo htmlspecialchars($product['nome']); ?>">
                            <div class="product-image <?php echo $imageClass; ?>">
                                <?php if ($index === 0): ?>
                                    <span class="product-badge">Latest</span>
                                <?php endif; ?>
                                <span class="product-emoji" aria-hidden="true"><?php echo $emoji; ?></span>
                                <button class="favorite-button" type="button" aria-label="Add <?php echo htmlspecialchars($product['nome']); ?> to favorites">&#9825;</button>
                            </div>
                            <div class="product-content">
                                <p class="product-category"><?php echo htmlspecialchars($product['categoria']); ?></p>
                                <h3><?php echo htmlspecialchars($product['nome']); ?></h3>
                                <div class="product-footer">
                                    <strong>$<?php echo number_format((float) $product['preco'], 2); ?></strong>
                                    <span class="product-stock"><?php echo (int) $product['estoque']; ?> in stock</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script src="JS/load-header.js"></script>
    <script>
        const filterButtons = document.querySelectorAll('.filter-chip');
        const productCards = document.querySelectorAll('.product-card');
        const cartCount = document.querySelector('.cart-count');

        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                filterButtons.forEach(function (item) {
                    item.classList.remove('active');
                });
                button.classList.add('active');

                const category = button.dataset.category;
                productCards.forEach(function (card) {
                    card.classList.toggle('hidden', category !== 'all' && card.dataset.category !== category);
                });
            });
        });

        document.querySelectorAll('.favorite-button').forEach(function (button) {
            button.addEventListener('click', function () {
                const active = button.classList.toggle('active');
                button.innerHTML = active ? '&#9829;' : '&#9825;';
            });
        });

        productCards.forEach(function (card) {
            card.addEventListener('click', function (event) {
                if (event.target.closest('.favorite-button')) {
                    return;
                }
                window.location.href = 'item.php?id=' + encodeURIComponent(card.dataset.productId);
            });
            card.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    window.location.href = 'item.php?id=' + encodeURIComponent(card.dataset.productId);
                }
            });
        });

        document.addEventListener('input', function (event) {
            if (!event.target.matches('.search input')) {
                return;
            }

            const query = event.target.value.trim().toLowerCase();
            productCards.forEach(function (card) {
                const title = card.querySelector('h3').textContent.toLowerCase();
                card.classList.toggle('hidden', query.length > 0 && !title.includes(query));
            });
        });

        document.addEventListener('submit', function (event) {
            if (event.target.matches('.search')) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>