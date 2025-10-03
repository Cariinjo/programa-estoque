<?php
include 'banco.php';

$acao = $_POST['acao'] ?? '';
$id = $_POST['id_produto'] ?? null;
$nome = $_POST['nome'] ?? '';
$marca = $_POST['marca'] ?? '';
$medida = $_POST['medida'] ?? '';
$quantidade = $_POST['quantidade_estoque'] ?? 0;
$preco_compra = $_POST['preco_compra'] ?? 0;
$preco_venda = $_POST['preco_venda'] ?? 0;

if ($acao === 'cadastrar') {
    $sql = "INSERT INTO produtos (nome, marca, medida, quantidade_estoque, preco_compra, preco_venda) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiid", $nome, $marca, $medida, $quantidade, $preco_compra, $preco_venda);
    
    if ($stmt->execute()) {
        header('Location: estoque.php?msg=Produto cadastrado com sucesso!');
    } else {
        header('Location: estoque.php?err=Erro ao cadastrar produto.');
    }
    $stmt->close();

} elseif ($acao === 'atualizar' && $id) {
    $sql = "UPDATE produtos SET nome = ?, marca = ?, medida = ?, quantidade_estoque = ?, preco_compra = ?, preco_venda = ? WHERE id_produto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiidi", $nome, $marca, $medida, $quantidade, $preco_compra, $preco_venda, $id);

    if ($stmt->execute()) {
        header('Location: estoque.php?msg=Produto atualizado com sucesso!');
    } else {
        header('Location: estoque.php?err=Erro ao atualizar produto.');
    }
    $stmt->close();
}

$conn->close();
?>