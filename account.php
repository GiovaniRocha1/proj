<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['id_usuario'])) {
    $_SESSION['flash_error'] = 'Entre na sua conta para ver seu perfil.';
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
    $productError = 'Não foi possível carregar seus anúncios.';
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha conta | Online Store</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="account-page">
    <div id="header-placeholder"></div>

    <main class="account-main">
        <nav class="account-breadcrumb" aria-label="Caminho de navegação">
            <a href="dashboard.php">Início</a>
            <span>/</span>
            <span>Minha conta</span>
        </nav>

        <div class="account-heading">
            <div>
                <p class="account-eyebrow">Área pessoal</p>
                <h1>Minha conta</h1>
            </div>
            <a class="account-edit-button" href="list-product.php">Anunciar produto</a>
        </div>

        <div class="account-layout">
            <aside class="account-profile-card" aria-labelledby="profile-name">
                <div class="account-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div class="account-profile-heading">
                    <h2 id="profile-name"><?php echo htmlspecialchars($user['nome']); ?></h2>
                    <span>Tipo de usuário: <?php echo (int) $user['tipo']; ?></span>
                </div>

                <dl class="account-details">
                    <div>
                        <dt>Nome</dt>
                        <dd><?php echo htmlspecialchars($user['nome']); ?></dd>
                    </div>
                    <div>
                        <dt>Cidade</dt>
                        <dd><?php echo htmlspecialchars((string) (($user['cidade'] ?? '') !== '' ? $user['cidade'] : 'Não informada')); ?></dd>
                    </div>
                    <div>
                        <dt>Idade</dt>
                        <dd><?php echo (int) $user['idade']; ?></dd>
                    </div>
                    <div>
                        <dt>E-mail</dt>
                        <dd><?php echo htmlspecialchars($user['email']); ?></dd>
                    </div>
                    <div>
                        <dt>Nome de usuário</dt>
                        <dd><?php echo htmlspecialchars($user['login']); ?></dd>
                    </div>
                    <div>
                        <dt>Telefone</dt>
                        <dd><?php echo htmlspecialchars((string) (($user['telefone'] ?? '') !== '' ? $user['telefone'] : 'Não informado')); ?></dd>
                    </div>
                    <div>
                        <dt>Tipo</dt>
                        <dd><?php echo (int) $user['tipo']; ?></dd>
                    </div>
                </dl>

                <a class="account-list-button" href="list-product.php">Anunciar um produto</a>
            </aside>

            <section class="account-selling" aria-labelledby="selling-title">
                <div class="account-section-heading">
                    <div>
                        <p class="account-section-eyebrow">Atividade de vendedor</p>
                        <h2 id="selling-title">Meus anúncios</h2>
                    </div>
                    <a href="list-product.php">Anunciar produto</a>
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
                            <h2>Você ainda não anunciou produtos</h2>
                            <p>Crie um anúncio para começar a vender.</p>
                            <a href="list-product.php">Anunciar produto</a>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $slug = strtolower($product['categoria']);
                        $imageClass = $slug === 'fashion' ? 'selling-purple' : ($slug === 'home' ? 'selling-green' : ($slug === 'fitness' ? 'selling-yellow' : 'selling-blue'));
                        $emoji = $slug === 'fashion' ? '&#128085;' : ($slug === 'home' ? '&#9749;' : ($slug === 'fitness' ? '&#127947;' : '&#128241;'));
                        ?>
                        <article class="selling-item">
                            <a class="selling-image <?php echo $imageClass; ?>" href="item.php?id=<?php echo (int) $product['id_produto']; ?>" aria-label="Ver <?php echo htmlspecialchars($product['nome']); ?>">
                                <span aria-hidden="true"><?php echo $emoji; ?></span>
                            </a>
                            <div class="selling-info">
                                <p><?php echo htmlspecialchars(display_category($product['categoria'])); ?></p>
                                <h3><?php echo htmlspecialchars($product['nome']); ?></h3>
                            </div>
                            <div class="selling-stock">
                                <strong><?php echo format_currency((float) $product['preco']); ?></strong>
                                <span><?php echo (int) $product['estoque']; ?> em estoque</span>
                            </div>
                            <a class="selling-edit" href="edit-product.php?id=<?php echo (int) $product['id_produto']; ?>">Editar</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <script src="JS/load-header.js"></script>
</body>
</html>
