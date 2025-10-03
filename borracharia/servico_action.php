<?php
include 'banco.php';
$acao = $_POST['acao'] ?? '';

if ($acao === 'cadastrar') {
    $nome = $_POST['nome'] ?? '';
    $preco_venda = $_POST['preco_venda'] ?? 0;
    $sql = "INSERT INTO servicos (nome, preco_venda) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sd", $nome, $preco_venda);
    if ($stmt->execute()) header('Location: servicos.php?msg=Serviço cadastrado!');
    else header('Location: servicos.php?err=Erro ao cadastrar.');
    $stmt->close();
} elseif ($acao === 'atualizar') {
    $id = $_POST['id_servico'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $preco_venda = $_POST['preco_venda'] ?? 0;
    $sql = "UPDATE servicos SET nome = ?, preco_venda = ? WHERE id_servico = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdi", $nome, $preco_venda, $id);
    if ($stmt->execute()) header('Location: servicos.php?msg=Serviço atualizado!');
    else header('Location: servicos.php?err=Erro ao atualizar.');
    $stmt->close();
}
$conn->close();
?>