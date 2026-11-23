<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Please sign in to list a product.';
    header('Location: login.php');
    exit;
}

$_SESSION['product_form_token'] = bin2hex(random_bytes(32));
$categories = [];
$formError = $_SESSION['product_form_error'] ?? null;
unset($_SESSION['product_form_error']);

try {
    $categories = database()->query('SELECT id_categoria, nome FROM categorias ORDER BY nome')->fetchAll();
} catch (Throwable $error) {
    $formError = 'Could not load product categories.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="product-editor-page">
    <div id="header-placeholder"></div>

    <main class="product-editor-main">
        <nav class="editor-breadcrumb" aria-label="Breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span>/</span>
            <span>New product</span>
        </nav>

        <div class="editor-heading">
            <div>
                <p class="editor-eyebrow">Product catalog</p>
                <h1>Create new product</h1>
                <p>Add a product to your online store.</p>
            </div>
            <a class="editor-back-button" href="dashboard.php">Cancel</a>
        </div>

        <?php if ($formError): ?>
            <p class="auth-message auth-error"><?php echo htmlspecialchars($formError); ?></p>
        <?php endif; ?>
        <form class="product-editor-form" action="product_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['product_form_token']); ?>">
            <input type="hidden" name="action" value="create">
            <div class="editor-layout">
                <section class="editor-panel" aria-labelledby="details-heading">
                    <div class="editor-panel-heading">
                        <span class="editor-step">1</span>
                        <div>
                            <h2 id="details-heading">Product details</h2>
                            <p>Basic information customers will see.</p>
                        </div>
                    </div>

                    <div class="product-fields">
                        <div class="product-field full-width">
                            <label for="nome">Product name</label>
                            <input type="text" id="nome" name="nome" placeholder="e.g. Wireless Headphones" required>
                        </div>

                        <div class="product-field">
                            <label for="categoria">Category</label>
                            <select id="categoria" name="id_categoria" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['id_categoria']; ?>">
                                        <?php echo htmlspecialchars($category['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </section>

                <aside class="editor-panel editor-pricing" aria-labelledby="pricing-heading">
                    <div class="editor-panel-heading">
                        <span class="editor-step">2</span>
                        <div>
                            <h2 id="pricing-heading">Pricing & inventory</h2>
                            <p>Set availability and cost.</p>
                        </div>
                    </div>

                    <div class="product-fields compact-fields">
                        <div class="product-field">
                            <label for="preco">Price</label>
                            <div class="input-prefix">
                                <span>$</span>
                                <input type="number" id="preco" name="preco" placeholder="0.00" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div class="product-field">
                            <label for="estoque">Stock quantity</label>
                            <input type="number" id="estoque" name="estoque" placeholder="0" min="0" required>
                        </div>

                    </div>
                </aside>

            </div>

            <div class="editor-actions">
                <p><span>&#9432;</span> Product will be saved to the store catalog.</p>
                <div>
                    <a class="editor-secondary-button" href="dashboard.php">Cancel</a>
                    <button type="submit" class="submit-product-button">Create product</button>
                </div>
            </div>
        </form>
    </main>

    <script src="JS/load-header.js"></script>
</body>
</html>