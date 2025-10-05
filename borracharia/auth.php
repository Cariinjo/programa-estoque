<?php
session_start();
require 'banco.php';

$nome = $_POST['nome'] ?? '';
$senha = $_POST['senha'] ?? '';

if ($nome === '' || $senha === '') {
    header('location: login.php?err=' . urlencode('Nome e senha são obrigatórios'));
    exit();
}

// A consulta SQL foi atualizada para buscar também o nível de acesso do usuário
$sql = "SELECT id_usuario, senha, nome, nivel_acesso FROM usuarios WHERE nome = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('location: login.php?err=' . urlencode('Erro ao preparar a consulta'));
    exit();
}

$stmt->bind_param("s", $nome);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // ATENÇÃO: Em um sistema de produção real, as senhas devem ser criptografadas.
    // Use password_hash() para salvar e password_verify() para comparar.
    if ($senha === $row['senha']) {

        // Inicia uma sessão segura e armazena os dados do usuário
        session_regenerate_id(true);
        $_SESSION['user_id'] = $row['id_usuario'];
        $_SESSION['user_name'] = $row['nome'];
        $_SESSION['user_level'] = $row['nivel_acesso']; // A permissão do usuário é salva na sessão
        
        header('location: index.php');
        exit();
    } else {
        header('location: login.php?err=' . urlencode('Senha incorreta'));
        exit();
    }
} else {
    header('location: login.php?err=' . urlencode('Usuário não encontrado'));
    exit();
}

$conn->close();
?>