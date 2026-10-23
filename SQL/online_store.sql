CREATE DATABASE IF NOT EXISTS online_store;

CREATE TABLE usuario (
	id_usuario INT(11) PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    idade INT(3) NOT NULL,
    tipo INT(1) NOT NULL,
    telefone INT(15) NULL
);

CREATE TABLE login (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
	id_usuario INT(11),
    login VARCHAR(40) NOT NULL,
    senha VARCHAR(40) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE produto (
	id_produto INT(11) PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT(11),
    nome VARCHAR(200) NOT NULL,
    preco FLOAT(11) NOT NULL,
    estoque INT(6) DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);