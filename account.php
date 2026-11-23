<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Please sign in to view your account.';
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$products = [];
$productError = null;
$successMessage = $_SESSION['product_form_success'] ?? null;
unset($_SESSION['product_form_success']);

try {
    $statement = database()->prepare(
        'SELECT p.id_produto, p.nome, p.preco, p.estoque, c.nome AS categoria
         FROM produto p
         INNER JOIN categorias c ON c.id_categoria = p.id_categoria
         WHERE p.id_usuario = ?
         ORDER BY p.id_produto DESC'
    );
    $statement->execute([(int) $user['id_usuario']]);
    $products = $statement->fetchAll();
} catch (Throwable $error) {
    $productError = 'Could not load your product listings.';
}

$initials = '';
foreach (preg_split('/\s+/', trim((string) $user['nome'])) as $part) {
    if ($part !== '') {
        $initials .= strtoupper(substr($part, 0, 1));
    }
}
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My account | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="account-page">
    <div id="header-placeholder"></div>

    <main class="account-main">
        <nav class="account-breadcrumb" aria-label="Breadcrumb">
            <a href="dashboard.php">Home</a>
            <span>/</span>
            <span>My account</span>
        </nav>

        <div class="account-heading">
            <div>
                <p class="account-eyebrow">Personal dashboard</p>
                <h1>My account</h1>
            </div>
            <a class="account-edit-button" href="list-product.php">Add product</a>
        </div>

        <div class="account-layout">
            <aside class="account-profile-card" aria-labelledby="profile-name">
                <div class="account-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="account-profile-heading">
                    <h2 id="profile-name"><?php echo htmlspecialchars($user['nome']); ?></h2>
                    <span>User type: <?php echo (int) $user['tipo']; ?></span>
                </div>

                <dl class="account-details">
                    <div>
                        <dt>Name</dt>
                        <dd><?php echo htmlspecialchars($user['nome']); ?></dd>
                    </div>
                    <div>
                        <dt>City</dt>
                        <dd><?php echo htmlspecialchars((string) ($user['cidade'] ?? 'Not provided')); ?></dd>
                    </div>
                    <div>
                        <dt>Age</dt>
                        <dd><?php echo (int) $user['idade']; ?></dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><?php echo htmlspecialchars($user['email']); ?></dd>
                    </div>
                    <div>
                        <dt>Username</dt>
                        <dd><?php echo htmlspecialchars($user['login']); ?></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd><?php echo htmlspecialchars((string) ($user['telefone'] ?? 'Not provided')); ?></dd>
                    </div>
                    <div>
                        <dt>Type</dt>
                        <dd><?php echo (int) $user['tipo']; ?></dd>
                    </div>
                </dl>

                <a class="account-list-button" href="list-product.php">List a new product</a>
            </aside>

            <section class="account-selling" aria-labelledby="selling-title">
                <div class="account-section-heading">
                    <div>
                        <p class="account-section-eyebrow">Seller activity</p>
                        <h2 id="selling-title">Items up for selling</h2>
                    </div>
                    <a href="list-product.php">Add product</a>
                </div>

                <?php if ($successMessage): ?>
                    <p class="auth-message auth-success"><?php echo htmlspecialchars($successMessage); ?></p>
                <?php endif; ?>
                <?php if ($productError): ?>
                    <p class="auth-message auth-error"><?php echo htmlspecialchars($productError); ?></p>
                <?php endif; ?>
                <div class="selling-list">
                    <?php if (!$productError && !$products): ?>
                        <div class="cart-empty">
                            <span>&#128230;</span>
                            <h2>No products listed yet</h2>
                            <p>Create a listing to start selling.</p>
                            <a href="list-product.php">List a product</a>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $slug = strtolower($product['categoria']);
                        $imageClass = $slug === 'fashion' ? 'selling-purple' : ($slug === 'home' ? 'selling-green' : ($slug === 'fitness' ? 'selling-yellow' : 'selling-blue'));
                        $emoji = $slug === 'fashion' ? '&#128085;' : ($slug === 'home' ? '&#9749;' : ($slug === 'fitness' ? '&#127947;' : '&#128241;'));
                        ?>
                        <article class="selling-item">
                            <a class="selling-image <?php echo $imageClass; ?>" href="item.php?id=<?php echo (int) $product['id_produto']; ?>" aria-label="View <?php echo htmlspecialchars($product['nome']); ?>">
                                <span aria-hidden="true"><?php echo $emoji; ?></span>
                            </a>
                            <div class="selling-info">
                                <p><?php echo htmlspecialchars($product['categoria']); ?></p>
                                <h3><?php echo htmlspecialchars($product['nome']); ?></h3>
                            </div>
                            <div class="selling-stock">
                                <strong>$<?php echo number_format((float) $product['preco'], 2); ?></strong>
                                <span><?php echo (int) $product['estoque']; ?> in stock</span>
                            </div>
                            <a class="selling-edit" href="edit-product.php?id=<?php echo (int) $product['id_produto']; ?>">Edit</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <script src="JS/load-header.js"></script>
</body>
</html>
