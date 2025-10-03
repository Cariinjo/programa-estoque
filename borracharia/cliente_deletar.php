<?php
include 'banco.php';

$id = $_GET['id'] ?? null;

if ($id && is_numeric($id)) {
    // Para manter a integridade, primeiro deletamos os itens das vendas e depois as vendas do cliente.
    $conn->begin_transaction();
    try {
        // Encontra todas as vendas do cliente
        $sql_find_vendas = "SELECT id_venda FROM vendas WHERE id_cliente = ?";
        $stmt_find = $conn->prepare($sql_find_vendas);
        $stmt_find->bind_param("i", $id);
        $stmt_find->execute();
        $result_vendas = $stmt_find->get_result();
        
        while($venda = $result_vendas->fetch_assoc()) {
            // Deleta os itens de cada venda
            $sql_delete_itens = "DELETE FROM venda_itens WHERE id_venda = ?";
            $stmt_itens = $conn->prepare($sql_delete_itens);
            $stmt_itens->bind_param("i", $venda['id_venda']);
            $stmt_itens->execute();
            $stmt_itens->close();
        }
        $stmt_find->close();

        // Deleta as vendas
        $sql_delete_vendas = "DELETE FROM vendas WHERE id_cliente = ?";
        $stmt_vendas = $conn->prepare($sql_delete_vendas);
        $stmt_vendas->bind_param("i", $id);
        $stmt_vendas->execute();
        $stmt_vendas->close();

        // Finalmente, deleta o cliente
        $sql_delete_cliente = "DELETE FROM clientes WHERE id_cliente = ?";
        $stmt_cliente = $conn->prepare($sql_delete_cliente);
        $stmt_cliente->bind_param("i", $id);
        $stmt_cliente->execute();
        $stmt_cliente->close();

        $conn->commit();
        header('Location: clientes.php?msg=Cliente e seu histórico foram excluídos com sucesso!');

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        header('Location: clientes.php?err=Erro ao excluir o cliente e seu histórico.');
    }
    
} else {
    header('Location: clientes.php');
}

$conn->close();
?>