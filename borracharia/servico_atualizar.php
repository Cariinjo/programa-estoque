<?php
include 'banco.php';
$id = $_GET['id'] ?? null;
$stmt = $conn->prepare("SELECT * FROM servicos WHERE id_servico = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$servico = $stmt->get_result()->fetch_assoc();
if (!$servico) { header('Location: servicos.php'); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atualizar Serviço</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Atualizar Serviço</h1>
        <form action="servico_action.php" method="POST">
            <input type="hidden" name="acao" value="atualizar">
            <input type="hidden" name="id_servico" value="<?php echo $servico['id_servico']; ?>">
            <label for="nome">Nome do Serviço:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($servico['nome']); ?>" required>
            <label for="preco_venda">Preço de Venda (R$):</label>
            <input type="number" step="0.01" id="preco_venda" name="preco_venda" value="<?php echo $servico['preco_venda']; ?>" required>
            <button type="submit">Atualizar</button>
        </form>
        <br>
        <button onclick="location.href='servicos.php'">Cancelar</button>
    </div>
</body>
</html>