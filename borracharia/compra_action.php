<?php
include 'banco.php';

$id_produto = $_POST['id_produto'] ?? 0;
$quantidade = $_POST['quantidade'] ?? 0;
$custo_total = $_POST['custo_total'] ?? 0;
$id_fornecedor = $_POST['id_fornecedor'] ?? null;
// Se o fornecedor não for selecionado, salva como NULL
if (empty($id_fornecedor)) {
    $id_fornecedor = null;
}

if ($id_produto > 0 && $quantidade > 0 && $custo_total > 0) {
    $conn->begin_transaction();
    try {
        // 1. Insere o registro na tabela de compras (histórico)
        $sql_compra = "INSERT INTO compras (id_produto, quantidade, custo_total, id_fornecedor) VALUES (?, ?, ?, ?)";
        $stmt_compra = $conn->prepare($sql_compra);
        $stmt_compra->bind_param("iidi", $id_produto, $quantidade, $custo_total, $id_fornecedor);
        $stmt_compra->execute();
        $stmt_compra->close();

        // 2. Atualiza a quantidade em estoque do produto
        $sql_update_estoque = "UPDATE produtos SET quantidade_estoque = quantidade_estoque + ? WHERE id_produto = ?";
        $stmt_update = $conn->prepare($sql_update_estoque);
        $stmt_update->bind_param("ii", $quantidade, $id_produto);
        $stmt_update->execute();
        $stmt_update->close();

        // 3. Registra a saída no caixa
        $descricao_caixa = "Compra de estoque: " . $quantidade . "x (ID Prod: " . $id_produto . ")";
        $stmt_caixa = $conn->prepare("INSERT INTO fluxo_caixa (tipo, descricao, valor) VALUES ('Saída', ?, ?)");
        $stmt_caixa->bind_param("sd", $descricao_caixa, $custo_total);
        $stmt_caixa->execute();
        $stmt_caixa->close();

        $conn->commit();
        header('Location: estoque.php?msg=Compra registrada com sucesso!');

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        header('Location: estoque.php?err=Erro ao registrar a compra.');
    }
} else {
    header('Location: compra_registrar.php?err=Dados inválidos.');
}

$conn->close();
?>