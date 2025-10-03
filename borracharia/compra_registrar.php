<?php include 'banco.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Compra de Fornecedor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Registrar Compra (Entrada de Estoque)</h1>
        <form action="compra_action.php" method="POST">
            <label for="id_produto">Produto:</label>
            <select name="id_produto" id="id_produto" required>
                <option value="">Selecione um produto</option>
                <?php
                $result_produtos = $conn->query("SELECT id_produto, nome, marca, medida FROM produtos ORDER BY nome");
                while ($row = $result_produtos->fetch_assoc()) {
                    echo "<option value='{$row['id_produto']}'>" . htmlspecialchars("{$row['nome']} - {$row['marca']} ({$row['medida']})") . "</option>";
                }
                ?>
            </select>

            <label for="quantidade">Quantidade Comprada:</label>
            <input type="number" id="quantidade" name="quantidade" min="1" required>

            <label for="custo_total">Custo Total da Compra (R$):</label>
            <input type="number" step="0.01" id="custo_total" name="custo_total" required>

            <label for="id_fornecedor">Fornecedor:</label>
            <select name="id_fornecedor" id="id_fornecedor">
                <option value="">-- Opcional --</option>
                <?php
                $result_fornecedores = $conn->query("SELECT id_fornecedor, nome FROM fornecedores ORDER BY nome");
                while ($row = $result_fornecedores->fetch_assoc()) {
                    echo "<option value='{$row['id_fornecedor']}'>" . htmlspecialchars($row['nome']) . "</option>";
                }
                ?>
            </select>

            <button type="submit">Registrar Compra</button>
        </form>
        <br>
        <button onclick="location.href='estoque.php'">Voltar</button>
    </div>
</body>
</html>
<?php $conn->close(); ?>