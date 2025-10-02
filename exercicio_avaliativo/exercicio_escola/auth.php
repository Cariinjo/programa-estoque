<?php
session_start();
require 'banco.php';

$nome = $_POST['nome'] ?? '';
$senha = $_POST['senha'] ?? '';

if ($nome === '' || $senha === '') {
    header('location: login.php?err=' . urlencode('Nome e senha são obrigatórios'));
    exit();
}

$sql = "SELECT id_usuario, senha, nome FROM usuarios WHERE nome = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('location: login.php?err=' . urlencode('Erro ao preparar a consulta'));
    exit();
}

$stmt->bind_param("s", $nome);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($senha === $row['senha']) {

        session_regenerate_id(true);
        $_SESSION['user_id'] = $row['id_usuario'];
        $_SESSION['user_name'] = $row['nome'];
        header('location: index.php');
        exit();
    }
    header('location: login.php?err=' . urlencode('Senha incorreta'));
    exit();
}
