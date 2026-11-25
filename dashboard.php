<?php
session_start();
require_once __DIR__ . '/db.php';

$errorMessage = null;
$products = [];
$categories = [];
$pageSize = 40;
$sortOrders = [
    'featured' => 'p.id_produto DESC',
    'price-low' => 'p.preco ASC, p.id_produto DESC',
    'price-high' => 'p.preco DESC, p.id_produto DESC',
    'newest' => 'p.id_produto DESC',
];
$requestedSort = $_GET['sort'] ?? 'featured';
$sort = is_string($requestedSort) && isset($sortOrders[$requestedSort])
    ? $requestedSort
    : 'featured';
$requestedCategory = $_GET['category'] ?? '';
$categoryFilter = is_string($requestedCategory)
    ? strtolower(trim($requestedCategory))
    : '';
$requestedSearch = $_GET['q'] ?? '';
$searchQuery = is_string($requestedSearch) ? trim($requestedSearch) : '';
$searchQuery = substr($searchQuery, 0, 100);
$currentPage = 1;
$totalPages = 1;
$totalProducts = 0;
$offset = 0;

try {
    $pdo = database();
    $categories = $pdo->query(
        'SELECT DISTINCT nome FROM categorias ORDER BY nome'
    )->fetchAll(PDO::FETCH_COLUMN);

    $conditions = [];
    $parameters = [];
    if ($categoryFilter !== '') {
        $conditions[] = 'LOWER(c.nome) = ?';
        $parameters[] = $categoryFilter;
    }
    if ($searchQuery !== '') {
        $conditions[] = 'LOWER(p.nome) LIKE ?';
        $parameters[] = '%' . strtolower($searchQuery) . '%';
    }
    $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $countStatement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM produto p
         INNER JOIN categorias c ON c.id_categoria = p.id_categoria
         ' . $whereClause
    );
    $countStatement->execute($parameters);
    $totalProducts = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalProducts / $pageSize));

    $requestedPage = $_GET['page'] ?? 1;
    $requestedPage = is_scalar($requestedPage) ? filter_var($requestedPage, FILTER_VALIDATE_INT) : false;
    $currentPage = min($totalPages, max(1, $requestedPage === false ? 1 : (int) $requestedPage));
    $offset = ($currentPage - 1) * $pageSize;

    $statement = $pdo->prepare(
        'SELECT p.id_produto, p.nome, p.preco, p.estoque, c.nome AS categoria
         FROM produto p
         INNER JOIN categorias c ON c.id_categoria = p.id_categoria
         ' . $whereClause . '
         ORDER BY ' . $sortOrders[$sort] . '
         LIMIT ' . $pageSize . ' OFFSET ' . $offset
    );
    $statement->execute($parameters);
    $products = $statement->fetchAll();
} catch (Throwable $error) {
    $errorMessage = 'Não foi possível carregar os produtos.';
}

$baseQuery = [];
if ($sort !== 'featured') {
    $baseQuery['sort'] = $sort;
}
if ($categoryFilter !== '') {
    $baseQuery['category'] = $categoryFilter;
}
if ($searchQuery !== '') {
    $baseQuery['q'] = $searchQuery;
}
$pageUrl = static function (int $page) use ($baseQuery): string {
    return 'dashboard.php?' . http_build_query($baseQuery + ['page' => $page]);
};
$filterUrl = static function (?string $category) use ($sort, $searchQuery): string {
    $query = [];
    if ($sort !== 'featured') {
        $query['sort'] = $sort;
    }
    if ($category !== null) {
        $query['category'] = $category;
    }
    if ($searchQuery !== '') {
        $query['q'] = $searchQuery;
    }

    return 'dashboard.php' . ($query ? '?' . http_build_query($query) : '');
};
$firstProduct = $totalProducts > 0 ? $offset + 1 : 0;
$lastProduct = $offset + count($products);

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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja virtual | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="store-page">
    <div id="header-placeholder"></div>

    <main class="store-main">

        <section class="catalog" aria-labelledby="catalog-title">
            <div class="catalog-heading">
                <div>
                    <h2 id="catalog-title">Anúncios recentes</h2>
                </div>
                <label class="sort-control">
                    <span>Ordenar:</span>
                    <select id="product-sort" aria-label="Ordenar produtos">
                        <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Destaque</option>
                        <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Menor preço</option>
                        <option value="price-high" <?php echo $sort === 'price-high' ? 'selected' : ''; ?>>Maior preço</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mais recentes</option>
                    </select>
                </label>
            </div>

            <div class="filter-row" aria-label="Filtros de produtos">
                <a class="filter-chip <?php echo $categoryFilter === '' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($filterUrl(null)); ?>">Todas</a>
                <?php foreach ($categories as $categoryName): ?>
                    <?php $categorySlug = strtolower($categoryName); ?>
                    <a class="filter-chip <?php echo $categoryFilter === $categorySlug ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($filterUrl($categorySlug)); ?>">
                        <?php echo htmlspecialchars(display_category($categoryName)); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="product-grid">
                <?php if ($errorMessage): ?>
                    <p class="auth-message auth-error"><?php echo htmlspecialchars($errorMessage); ?></p>
                <?php elseif (!$products): ?>
                    <p class="auth-message"><?php echo $searchQuery !== '' || $categoryFilter !== '' ? 'Nenhum produto encontrado com esses filtros.' : 'Nenhum produto anunciado no momento.'; ?></p>
                <?php else: ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php
                        $categorySlug = strtolower($product['categoria']);
                        $imageClass = $categoryStyles[$categorySlug] ?? 'product-image-blue';
                        $emoji = $categoryEmojis[$categorySlug] ?? '&#128230;';
                        ?>
                        <article class="product-card" data-category="<?php echo htmlspecialchars($categorySlug); ?>" data-product-id="<?php echo (int) $product['id_produto']; ?>" data-price="<?php echo (float) $product['preco']; ?>" role="link" tabindex="0" aria-label="Ver <?php echo htmlspecialchars($product['nome']); ?>">
                            <div class="product-image <?php echo $imageClass; ?>">
                                <?php if ($index === 0 && $currentPage === 1 && in_array($sort, ['featured', 'newest'], true)): ?>
                                    <span class="product-badge">Recente</span>
                                <?php endif; ?>
                                <span class="product-emoji" aria-hidden="true"><?php echo $emoji; ?></span>
                            </div>
                            <div class="product-content">
                                <p class="product-category"><?php echo htmlspecialchars(display_category($product['categoria'])); ?></p>
                                <h3><?php echo htmlspecialchars($product['nome']); ?></h3>
                                <div class="product-footer">
                                    <strong><?php echo format_currency((float) $product['preco']); ?></strong>
                                    <span class="product-stock"><?php echo (int) $product['estoque']; ?> em estoque</span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (!$errorMessage && $totalProducts > 0): ?>
                <div class="catalog-pagination">
                    <p class="pagination-summary">Exibindo <?php echo $firstProduct; ?>–<?php echo $lastProduct; ?> de <?php echo $totalProducts; ?> produtos</p>
                    <?php if ($totalPages > 1): ?>
                        <nav class="pagination-controls" aria-label="Paginação do catálogo">
                            <?php if ($currentPage > 1): ?>
                                <a class="pagination-link" href="<?php echo htmlspecialchars($pageUrl($currentPage - 1)); ?>" aria-label="Página anterior">Anterior</a>
                            <?php else: ?>
                                <span class="pagination-link disabled" aria-disabled="true">Anterior</span>
                            <?php endif; ?>
                            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                                <?php if ($pageNumber === $currentPage): ?>
                                    <span class="pagination-link current" aria-current="page"><?php echo $pageNumber; ?></span>
                                <?php else: ?>
                                    <a class="pagination-link" href="<?php echo htmlspecialchars($pageUrl($pageNumber)); ?>" aria-label="Ir para a página <?php echo $pageNumber; ?>"><?php echo $pageNumber; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span class="pagination-status">Página <?php echo $currentPage; ?> de <?php echo $totalPages; ?></span>
                            <?php if ($currentPage < $totalPages): ?>
                                <a class="pagination-link" href="<?php echo htmlspecialchars($pageUrl($currentPage + 1)); ?>" aria-label="Próxima página">Próxima</a>
                            <?php else: ?>
                                <span class="pagination-link disabled" aria-disabled="true">Próxima</span>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="JS/load-header.js"></script>
    <script>
        const productCards = document.querySelectorAll('.product-card');
        const sortSelect = document.querySelector('#product-sort');

        sortSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            if (sortSelect.value === 'featured') {
                url.searchParams.delete('sort');
            } else {
                url.searchParams.set('sort', sortSelect.value);
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });

        productCards.forEach(function (card) {
            card.addEventListener('click', function () {
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
    </script>
</body>
</html>