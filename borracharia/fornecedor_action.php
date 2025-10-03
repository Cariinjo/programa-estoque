<?php
include 'banco.php';
$acao = $_POST['acao'] ?? '';
$nome = $_POST['nome'] ?? '';
$contato = $_POST['contato_nome'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$email = $_POST['email'] ?? '';
$id = $_POST['id_fornecedor'] ?? null;

if ($acao === 'cadastrar' && !empty($nome)) {
    $sql = "INSERT INTO fornecedores (nome, contato_nome, telefone, email) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nome, $contato, $telefone, $email);
    if ($stmt->execute()) header('Location: fornecedores.php?msg=Fornecedor cadastrado!');
} elseif ($acao === 'atualizar' && $id) {
    $sql = "UPDATE fornecedores SET nome = ?, contato_nome = ?, telefone = ?, email = ? WHERE id_fornecedor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nome, $contato, $telefone, $email, $id);
    if ($stmt->execute()) header('Location: fornecedores.php?msg=Fornecedor atualizado!');
}
header('Location: fornecedores.php');
?>