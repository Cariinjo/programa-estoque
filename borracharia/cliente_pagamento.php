<?php
include 'banco.php';
$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT nome, saldo_devedor FROM clientes WHERE id_cliente = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
if (!$cliente) { header('Location: clientes.php'); exit; }
?>
<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Receber Pagamento</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container">
    <h1>Receber Pagamento de Dívida</h1>
    <h3>Cliente: <?php echo htmlspecialchars($cliente['nome']); ?></h3>
    <p>Saldo Devedor Atual: <strong style="color:red;">R$ <?php echo number_format($cliente['saldo_devedor'], 2, ',', '.'); ?></strong></p>
    <form action="cliente_pagamento_action.php" method="POST">
        <input type="hidden" name="id_cliente" value="<?php echo $id; ?>">
        <label for="valor_pago">Valor Recebido (R$):</label>
        <input type="number" step="0.01" name="valor_pago" max="<?php echo $cliente['saldo_devedor']; ?>" required>
        <button type="submit">Confirmar Recebimento</button>
    </form>
    <br><button onclick="location.href='clientes.php'">Voltar</button>
</div>
</body></html>