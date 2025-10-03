<?php
include 'banco.php';

$stmt = $conn->prepare("SELECT * FROM produtos WHERE id_produto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$produto = $result->fetch_assoc();
if (!$produto) {
    header('Location: estoque.php?err=Produto não encontrado');
    exit;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atualizar Produto</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Atualizar Produto: <?php echo htmlspecialchars($produto['nome']); ?></h1>
        <form action="produto_action.php" method="POST">
            <input type="hidden" name="acao" value="atualizar">
            <input type="hidden" name="id_produto" value="<?php echo $produto['id_produto']; ?>">

            <label for="nome">Nome do Produto:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>

            <label for="marca">Marca:</label>
            <input type="text" id="marca" name="marca" value="<?php echo htmlspecialchars($produto['marca']); ?>">

            <label for="medida">Medida:</label>
            <input type="text" id="medida" name="medida" value="<?php echo htmlspecialchars($produto['medida']); ?>">

            <label for="quantidade_estoque">Quantidade em Estoque:</label>
            <input type="number" id="quantidade_estoque" name="quantidade_estoque" value="<?php echo $produto['quantidade_estoque']; ?>" required>

            <label for="preco_compra">Preço de Compra (R$):</label>
            <input type="number" step="0.01" id="preco_compra" name="preco_compra" value="<?php echo $produto['preco_compra']; ?>" required>
            
            <label for="preco_venda">Preço de Venda (R$):</label>
            <input type="number" step="0.01" id="preco_venda" name="preco_venda" value="<?php echo $produto['preco_venda']; ?>" required>

            <button type="submit">Atualizar Produto</button>
        </form>
        <br>
        <button onclick="location.href='estoque.php'">Cancelar</button>
    </div>
</body>
</html>