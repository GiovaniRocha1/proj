CREATE DATABASE IF NOT EXISTS online_store

USE online_store;

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NULL,
    idade INT(3) NOT NULL,
    tipo INT(1) NOT NULL,
    telefone VARCHAR(20) NULL,
    PRIMARY KEY (id_usuario)
);

CREATE TABLE IF NOT EXISTS login (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_usuario INT(11) NULL,
    login VARCHAR(40) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_login_login (login),
    KEY idx_login_usuario (id_usuario),
    CONSTRAINT fk_login_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(200) NOT NULL,
    PRIMARY KEY (id_categoria)
);

CREATE TABLE IF NOT EXISTS produto (
    id_produto INT(11) NOT NULL AUTO_INCREMENT,
    id_usuario INT(11) NULL,
    id_categoria INT(11) NULL,
    nome VARCHAR(200) NOT NULL,
    preco FLOAT NOT NULL,
    estoque INT(6) DEFAULT 0,
    PRIMARY KEY (id_produto),
    KEY idx_produto_usuario (id_usuario),
    KEY idx_produto_categoria (id_categoria),
    CONSTRAINT fk_produto_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    CONSTRAINT fk_produto_categoria
        FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
);

CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido INT(11) NOT NULL AUTO_INCREMENT,
    id_usuario INT(11) NULL,
    data_pedido DATE NOT NULL,
    status VARCHAR(50) NOT NULL,
    valor_total FLOAT NOT NULL,
    endereco_entrega VARCHAR(255) NULL,
    cidade_entrega VARCHAR(100) NULL,
    codigo_postal VARCHAR(20) NULL,
    pais_entrega VARCHAR(100) NULL,
    metodo_pagamento VARCHAR(50) NULL,
    PRIMARY KEY (id_pedido),
    KEY idx_pedidos_usuario (id_usuario),
    CONSTRAINT fk_pedidos_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE IF NOT EXISTS itens_pedido (
    id_item INT(11) NOT NULL AUTO_INCREMENT,
    id_pedido INT(11) NULL,
    id_produto INT(11) NULL,
    quantidade INT(7) NULL,
    PRIMARY KEY (id_item),
    KEY idx_itens_pedido_pedido (id_pedido),
    KEY idx_itens_pedido_produto (id_produto),
    CONSTRAINT fk_itens_pedido_pedido
        FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido),
    CONSTRAINT fk_itens_pedido_produto
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
);

CREATE TABLE IF NOT EXISTS itens_carrinho (
    id_usuario INT(11) NOT NULL,
    id_produto INT(11) NOT NULL,
    quantidade INT(7) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_usuario, id_produto),
    KEY idx_itens_carrinho_produto (id_produto),
    CONSTRAINT fk_itens_carrinho_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE,
    CONSTRAINT fk_itens_carrinho_produto
        FOREIGN KEY (id_produto) REFERENCES produto(id_produto)
        ON DELETE CASCADE
);