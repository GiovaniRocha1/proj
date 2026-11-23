<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$config = require __DIR__ . '/config.php';

try {
    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['host'], $config['port']),
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $server->exec('CREATE DATABASE IF NOT EXISTS online_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $server->exec('USE online_store');

    $server->exec("
        CREATE TABLE IF NOT EXISTS usuario (
            id_usuario INT(11) PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            cidade VARCHAR(100) NULL,
            idade INT(3) NOT NULL,
            tipo INT(1) NOT NULL,
            telefone VARCHAR(20) NULL
        )
    ");

    $server->exec("
        CREATE TABLE IF NOT EXISTS login (
            id INT(11) PRIMARY KEY AUTO_INCREMENT,
            id_usuario INT(11),
            login VARCHAR(40) NOT NULL UNIQUE,
            senha VARCHAR(255) NOT NULL,
            FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
                ON DELETE CASCADE
        )
    ");

    $server->exec("
        CREATE TABLE IF NOT EXISTS categorias (
            id_categoria INT(11) PRIMARY KEY AUTO_INCREMENT,
            nome VARCHAR(200) NOT NULL
        )
    ");

    $server->exec("
        CREATE TABLE IF NOT EXISTS produto (
            id_produto INT(11) PRIMARY KEY AUTO_INCREMENT,
            id_usuario INT(11),
            id_categoria INT(11),
            nome VARCHAR(200) NOT NULL,
            preco FLOAT(11) NOT NULL,
            estoque INT(6) DEFAULT 0,
            FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
            FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        )
    ");

    $server->exec("
        CREATE TABLE IF NOT EXISTS pedidos (
            id_pedido INT(11) PRIMARY KEY AUTO_INCREMENT,
            id_usuario INT(11),
            data_pedido DATE NOT NULL,
            status VARCHAR(50) NOT NULL,
            valor_total FLOAT NOT NULL,
            endereco_entrega VARCHAR(255) NULL,
            cidade_entrega VARCHAR(100) NULL,
            codigo_postal VARCHAR(20) NULL,
            pais_entrega VARCHAR(100) NULL,
            metodo_pagamento VARCHAR(50) NULL,
            FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        )
    ");

    $server->exec("
        CREATE TABLE IF NOT EXISTS itens_pedido (
            id_item INT(11) PRIMARY KEY AUTO_INCREMENT,
            id_pedido INT(11),
            id_produto INT(11),
            quantidade INT(7),
            FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido),
            FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
        )
    ");

    $server->exec('ALTER TABLE login MODIFY senha VARCHAR(255) NOT NULL');
    $server->exec('ALTER TABLE usuario MODIFY telefone VARCHAR(20) NULL');

    $columnExists = static function (string $table, string $column) use ($server): bool {
        $statement = $server->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $statement->execute([$table, $column]);
        return (int) $statement->fetchColumn() > 0;
    };

    $schemaUpdates = [
        'usuario' => [
            'cidade' => 'ALTER TABLE usuario ADD cidade VARCHAR(100) NULL',
        ],
        'pedidos' => [
            'endereco_entrega' => 'ALTER TABLE pedidos ADD endereco_entrega VARCHAR(255) NULL',
            'cidade_entrega' => 'ALTER TABLE pedidos ADD cidade_entrega VARCHAR(100) NULL',
            'codigo_postal' => 'ALTER TABLE pedidos ADD codigo_postal VARCHAR(20) NULL',
            'pais_entrega' => 'ALTER TABLE pedidos ADD pais_entrega VARCHAR(100) NULL',
            'metodo_pagamento' => 'ALTER TABLE pedidos ADD metodo_pagamento VARCHAR(50) NULL',
        ],
    ];
    foreach ($schemaUpdates as $table => $columns) {
        foreach ($columns as $column => $sql) {
            if (!$columnExists($table, $column)) {
                $server->exec($sql);
            }
        }
    }

    $server->exec("
        CREATE TABLE IF NOT EXISTS itens_carrinho (
            id_usuario INT(11) NOT NULL,
            id_produto INT(11) NOT NULL,
            quantidade INT(7) NOT NULL DEFAULT 1,
            PRIMARY KEY (id_usuario, id_produto),
            FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
            FOREIGN KEY (id_produto) REFERENCES produto(id_produto) ON DELETE CASCADE
        )
    ");

    $count = (int) $server->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    if ($count === 0) {
        $server->prepare(
            'INSERT INTO usuario (nome, email, idade, tipo, telefone) VALUES (?, ?, ?, ?, ?)'
        )->execute(['Alex Morgan', 'alex.morgan@example.com', 28, 1, '5551234567']);

        $id = (int) $server->lastInsertId();
        $server->prepare(
            'INSERT INTO login (id_usuario, login, senha) VALUES (?, ?, ?)'
        )->execute([$id, 'alexm', password_hash('Password123', PASSWORD_DEFAULT)]);
    }

    $categoryCount = (int) $server->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
    if ($categoryCount === 0) {
        $categoryNames = ['Electronics', 'Fashion', 'Home', 'Fitness'];
        $categoryStatement = $server->prepare('INSERT INTO categorias (nome) VALUES (?)');
        foreach ($categoryNames as $categoryName) {
            $categoryStatement->execute([$categoryName]);
        }
    }

    $productCount = (int) $server->query(
        'SELECT COUNT(*) FROM produto WHERE id_usuario = 1'
    )->fetchColumn();
    if ($productCount === 0) {
        $categories = [];
        foreach ($server->query('SELECT id_categoria, nome FROM categorias')->fetchAll(PDO::FETCH_ASSOC) as $category) {
            $categories[strtolower($category['nome'])] = (int) $category['id_categoria'];
        }

        $products = [
            ['Wireless Headphones', 'electronics', 89.99, 42],
            ['Classic Cotton Shirt', 'fashion', 39.99, 28],
            ['Smart Fitness Watch', 'fitness', 129.99, 16],
            ['Ceramic Mug Set', 'home', 24.99, 3],
            ['Compact Smartphone', 'electronics', 499.99, 20],
            ['Everyday Sneakers', 'fashion', 74.99, 11],
            ['Modern Table Lamp', 'home', 49.99, 8],
            ['Adjustable Weights', 'fitness', 59.99, 24],
        ];

        $productStatement = $server->prepare(
            'INSERT INTO produto (id_usuario, id_categoria, nome, preco, estoque) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($products as $product) {
            $productStatement->execute([
                1,
                $categories[$product[1]],
                $product[0],
                $product[2],
                $product[3],
            ]);
        }
    }

    echo "Database setup completed. Existing records were preserved.";
} catch (Throwable $error) {
    http_response_code(500);
    echo 'Database setup failed: ' . htmlspecialchars($error->getMessage());
}
