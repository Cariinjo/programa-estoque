<?php
include 'banco.php';
$id_cliente = $_POST['id_cliente'] ?? 0;
$valor_pago = $_POST['valor_pago'] ?? 0;

if ($id_cliente > 0 && $valor_pago > 0) {
    $conn->begin_transaction();
    try {
        // 1. Abate o valor da dívida do cliente
        $sql_update = "UPDATE clientes SET saldo_devedor = saldo_devedor - ? WHERE id_cliente = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('di', $valor_pago, $id_cliente);
        $stmt_update->execute();

        // 2. Registra a entrada no fluxo de caixa
        $stmt_cliente = $conn->prepare("SELECT nome FROM clientes WHERE id_cliente = ?");
        $stmt_cliente->bind_param('i', $id_cliente);
        $stmt_cliente->execute();
        $nome_cliente = $stmt_cliente->get_result()->fetch_assoc()['nome'];
        
        $descricao = "Recebimento de dívida do cliente: " . $nome_cliente;
        $sql_caixa = "INSERT INTO fluxo_caixa (tipo, descricao, valor) VALUES ('Entrada', ?, ?)";
        $stmt_caixa = $conn->prepare($sql_caixa);
        $stmt_caixa->bind_param('sd', $descricao, $valor_pago);
        $stmt_caixa->execute();

        $conn->commit();
        header('Location: clientes.php?msg=Pagamento recebido com sucesso!');
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: clientes.php?err=Erro ao processar pagamento.');
    }
} else {
    header('Location: clientes.php?err=Dados inválidos.');
}
?>