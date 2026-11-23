<?php

declare(strict_types=1);
session_start();

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$nome = trim((string) ($_POST['nome'] ?? ''));
$cidade = trim((string) ($_POST['cidade'] ?? ''));
$idade = filter_input(INPUT_POST, 'idade', FILTER_VALIDATE_INT);
$email = trim((string) ($_POST['email'] ?? ''));
$login = trim((string) ($_POST['login'] ?? ''));
$senha = (string) ($_POST['senha'] ?? '');
$telefone = trim((string) ($_POST['telefone'] ?? ''));
$tipo = filter_input(INPUT_POST, 'tipo', FILTER_VALIDATE_INT) ?: 1;

$errors = [];
if ($nome === '' || strlen($nome) > 100) {
    $errors[] = 'Enter a valid name.';
}
if ($idade === false || $idade < 1 || $idade > 999) {
    $errors[] = 'Enter a valid age.';
}
if ($cidade === '' || strlen($cidade) > 100) {
    $errors[] = 'Enter a valid city.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    $errors[] = 'Enter a valid email.';
}
if ($login === '' || strlen($login) > 40) {
    $errors[] = 'Enter a valid username.';
}
if (strlen($senha) < 6) {
    $errors[] = 'Password must contain at least 6 characters.';
}
if (strlen($telefone) > 20) {
    $errors[] = 'Phone number is too long.';
}

if ($errors) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: register.php');
    exit;
}

try {
    $pdo = database();

    $statement = $pdo->prepare('SELECT id FROM login WHERE login = ?');
    $statement->execute([$login]);
    if ($statement->fetch()) {
        $_SESSION['flash_error'] = 'That username is already in use.';
        header('Location: register.php');
        exit;
    }

    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'INSERT INTO usuario (nome, email, cidade, idade, tipo, telefone) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $statement->execute([$nome, $email, $cidade, $idade, $tipo, $telefone !== '' ? $telefone : null]);
    $idUsuario = (int) $pdo->lastInsertId();

    $statement = $pdo->prepare(
        'INSERT INTO login (id_usuario, login, senha) VALUES (?, ?, ?)'
    );
    $statement->execute([$idUsuario, $login, password_hash($senha, PASSWORD_DEFAULT)]);

    $pdo->commit();

    $_SESSION['flash_success'] = 'Account created successfully. Please sign in.';
    header('Location: login.php');
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Could not create the account. Please try again.';
    header('Location: register.php');
}
