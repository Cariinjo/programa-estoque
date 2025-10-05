<?php
require 'banco.php';

$nome = $_POST['nome'] ?? '';
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($nome) || empty($senha)) {
    // Redireciona de volta para a página de cadastro com erro
    header('Location: register.php?err_cadastro=' . urlencode('Nome e senha são obrigatórios.'));
    exit();
}

// 1. Verificar se o nome de usuário já existe
$sql_check = "SELECT id_usuario FROM usuarios WHERE nome = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("s", $nome);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    // Se o usuário já existe, redireciona para a página de cadastro com erro
    header('Location: register.php?err_cadastro=' . urlencode('Este nome de usuário já está em uso.'));
    $stmt_check->close();
    $conn->close();
    exit();
}
$stmt_check->close();

// 2. Se o usuário não existe, insere no banco de dados
$nivel_acesso = 'Funcionario'; // Padrão para novos cadastros

$sql_insert = "INSERT INTO usuarios (nome, senha, nivel_acesso) VALUES (?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("sss", $nome, $senha, $nivel_acesso);

if ($stmt_insert->execute()) {
    // Sucesso, redireciona para a página de login com mensagem de sucesso
    header('Location: login.php?msg_cadastro=' . urlencode('Conta criada com sucesso! Faça o login.'));
} else {
    // Erro, redireciona para a página de cadastro com erro
    header('Location: register.php?err_cadastro=' . urlencode('Ocorreu um erro ao criar a conta.'));
}

$stmt_insert->close();
$conn->close();
?>