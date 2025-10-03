<?php
include 'banco.php';

$id = $_GET['id'] ?? null;

if ($id) {
    // É importante verificar se o produto não está associado a nenhuma venda
    // para manter a integridade do histórico. Uma abordagem simples é apenas deletar.
    // Uma abordagem avançada seria desativar o produto em vez de deletar.
    
    $sql = "DELETE FROM produtos WHERE id_produto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header('Location: estoque.php?msg=Produto excluído com sucesso!');
    } else {
        // A exclusão pode falhar se o produto estiver em uso em `venda_itens` por causa da restrição de chave estrangeira.
        header('Location: estoque.php?err=Erro ao excluir produto. Ele pode estar associado a uma venda existente.');
    }
    $stmt->close();
} else {
    header('Location: estoque.php');
}

$conn->close();
?>