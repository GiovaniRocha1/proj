<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Please sign in to edit products.';
    header('Location: login.php');
    exit;
}

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$categories = [];
$formError = $_SESSION['product_form_error'] ?? null;
unset($_SESSION['product_form_error']);

try {
    $pdo = database();
    $categoryQuery = $pdo->query('SELECT id_categoria, nome FROM categorias ORDER BY nome');
    $categories = $categoryQuery->fetchAll();

    if (!$productId || $productId < 1) {
        http_response_code(404);
        $formError = 'Product not found.';
    } else {
        $statement = $pdo->prepare(
            'SELECT id_produto, id_categoria, nome, preco, estoque
             FROM produto WHERE id_produto = ? AND id_usuario = ? LIMIT 1'
        );
        $statement->execute([$productId, (int) $_SESSION['user']['id_usuario']]);
        $product = $statement->fetch();
        if (!$product) {
            http_response_code(404);
            $formError = 'Product not found or you do not have permission to edit it.';
        }
    }
} catch (Throwable $error) {
    $formError = 'Could not load product details.';
}

$_SESSION['product_form_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit product | ShopZone</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="product-editor-page">
    <div id="header-placeholder"></div>

    <main class="product-editor-main">
        <nav class="editor-breadcrumb" aria-label="Breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span>/</span>
            <span><?php echo $product ? htmlspecialchars($product['nome']) : 'Edit product'; ?></span>
        </nav>

        <div class="editor-heading">
            <div>
                <p class="editor-eyebrow">Product catalog</p>
                <h1>Edit product</h1>
                <p>Update the product details and availability.</p>
            </div>
            <a class="editor-back-button" href="dashboard.php">Cancel</a>
        </div>

        <?php if ($formError): ?>
            <p class="auth-message auth-error"><?php echo htmlspecialchars($formError); ?></p>
        <?php endif; ?>
        <?php if ($product): ?>
        <form class="product-editor-form" action="product_process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['product_form_token']); ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id_produto" value="<?php echo (int) $product['id_produto']; ?>">
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
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($product['nome']); ?>" maxlength="200" required>
                        </div>

                        <div class="product-field">
                            <label for="categoria">Category</label>
                            <select id="categoria" name="id_categoria" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['id_categoria']; ?>" <?php echo (int) $category['id_categoria'] === (int) $product['id_categoria'] ? 'selected' : ''; ?>>
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
                                <input type="number" id="preco" name="preco" value="<?php echo htmlspecialchars((string) $product['preco']); ?>" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div class="product-field">
                            <label for="estoque">Stock quantity</label>
                            <input type="number" id="estoque" name="estoque" value="<?php echo (int) $product['estoque']; ?>" min="0" required>
                        </div>

                    </div>
                </aside>

            </div>

            <div class="editor-actions">
                <p><span>&#9432;</span> Changes will update the store catalog.</p>
                <div>
                    <button type="submit" class="editor-delete-button" name="action" value="delete" formnovalidate>Delete product</button>
                    <a class="editor-secondary-button" href="dashboard.php">Cancel</a>
                    <button type="submit" class="submit-product-button">Save changes</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </main>

    <script src="JS/load-header.js"></script>
    <script>
        const productForm = document.querySelector('.product-editor-form');
        if (productForm) {
            productForm.addEventListener('submit', function (event) {
                if (event.submitter && event.submitter.value === 'delete' && !window.confirm('Delete this product?')) {
                    event.preventDefault();
                }
            });
        }
    </script>
</body>
</html>
