<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Entre na sua conta para anunciar um produto.';
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
    $formError = 'Não foi possível carregar as categorias de produtos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anunciar produto | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="product-editor-page">
    <div id="header-placeholder"></div>

    <main class="product-editor-main">
        <nav class="editor-breadcrumb" aria-label="Caminho de navegação">
            <a href="dashboard.php">Loja</a>
            <span>/</span>
            <span>Novo produto</span>
        </nav>

        <div class="editor-heading">
            <div>
                <p class="editor-eyebrow">Catálogo de produtos</p>
                <h1>Anunciar um produto</h1>
                <p>Adicione um produto à loja.</p>
            </div>
            <a class="editor-back-button" href="dashboard.php">Cancelar</a>
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
                            <h2 id="details-heading">Dados do produto</h2>
                            <p>Informações básicas que os clientes verão.</p>
                        </div>
                    </div>

                    <div class="product-fields">
                        <div class="product-field full-width">
                            <label for="nome">Nome do produto</label>
                            <input type="text" id="nome" name="nome" placeholder="Ex.: Fones de ouvido sem fio" required>
                        </div>

                        <div class="product-field">
                            <label for="categoria">Categoria</label>
                            <select id="categoria" name="id_categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['id_categoria']; ?>">
                                        <?php echo htmlspecialchars(display_category($category['nome'])); ?>
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
                            <h2 id="pricing-heading">Preço e estoque</h2>
                            <p>Informe o valor e a quantidade disponível.</p>
                        </div>
                    </div>

                    <div class="product-fields compact-fields">
                        <div class="product-field">
                            <label for="preco">Preço</label>
                            <div class="input-prefix">
                                <span>R$</span>
                                <input type="number" id="preco" name="preco" placeholder="0.00" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div class="product-field">
                            <label for="estoque">Quantidade em estoque</label>
                            <input type="number" id="estoque" name="estoque" placeholder="0" min="0" required>
                        </div>

                    </div>
                </aside>

            </div>

            <div class="editor-actions">
                <p><span>&#9432;</span> O produto será salvo no catálogo da loja.</p>
                <div>
                    <a class="editor-secondary-button" href="dashboard.php">Cancelar</a>
                    <button type="submit" class="submit-product-button">Criar anúncio</button>
                </div>
            </div>
        </form>
    </main>

    <script src="JS/load-header.js"></script>
</body>
</html>