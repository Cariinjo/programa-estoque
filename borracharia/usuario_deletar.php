<?php
session_start();
include 'banco.php';

// Verificação 1: Garante que apenas usuários logados e com nível 'Admin' podem executar este script.
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') {
    die("Acesso negado. Você não tem permissão para realizar esta ação.");
}

// Pega o ID do usuário que será deletado a partir da URL.
$id_para_deletar = $_GET['id'] ?? 0;

// Validação básica para garantir que o ID é um número válido.
if (!is_numeric($id_para_deletar) || $id_para_deletar <= 0) {
    header('Location: usuarios.php?err=ID de usuário inválido.');
    exit();
}

// Verificação 2: Impede que um administrador exclua a sua própria conta.
if ($id_para_deletar == $_SESSION['user_id']) {
    header('Location: usuarios.php?err=' . urlencode('Você não pode excluir sua própria conta enquanto está logado.'));
    exit();
}

// Prepara e executa a exclusão de forma segura com prepared statements.
$sql = "DELETE FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_para_deletar);

if ($stmt->execute()) {
    // Se a exclusão for bem-sucedida, redireciona de volta para a lista com uma mensagem de sucesso.
    header('Location: usuarios.php?msg=Usuário excluído com sucesso!');
} else {
    // Se ocorrer um erro, redireciona com uma mensagem de erro.
    header('Location: usuarios.php?err=Erro ao excluir o usuário.');
}

$stmt->close();
$conn->close();
?>