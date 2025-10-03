<?php
include 'banco.php';


$stmt = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();

if (!$cliente) {
    header('Location: clientes.php?err=Cliente não encontrado');
    exit;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atualizar Cliente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Atualizar Cliente: <?php echo htmlspecialchars($cliente['nome']); ?></h1>
        <form action="cliente_action.php" method="POST">
            <input type="hidden" name="acao" value="atualizar">
            <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">

            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>

            <label for="telefone">Telefone:</label>
            <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>">

            <label for="endereco">Endereço:</label>
            <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($cliente['endereco']); ?>">

            <label for="saldo_devedor">Saldo Devedor (R$):</label>
            <input type="number" step="0.01" id="saldo_devedor" name="saldo_devedor" value="<?php echo $cliente['saldo_devedor']; ?>" required>

            <button type="submit">Atualizar Cliente</button>
        </form>
        <br>
        <button onclick="location.href='clientes.php'">Cancelar</button>
    </div>
</body>
</html>