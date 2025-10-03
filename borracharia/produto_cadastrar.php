<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Produto</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Cadastrar Novo Produto</h1>
        <form action="produto_action.php" method="POST">
            <input type="hidden" name="acao" value="cadastrar">

            <label for="nome">Nome do Produto:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="marca">Marca:</label>
            <input type="text" id="marca" name="marca">

            <label for="medida">Medida (ex: 205/55 R16):</label>
            <input type="text" id="medida" name="medida">

            <label for="quantidade_estoque">Quantidade Inicial:</label>
            <input type="number" id="quantidade_estoque" name="quantidade_estoque" required>

            <label for="preco_compra">Preço de Compra (R$):</label>
            <input type="number" step="0.01" id="preco_compra" name="preco_compra" required>
            
            <label for="preco_venda">Preço de Venda (R$):</label>
            <input type="number" step="0.01" id="preco_venda" name="preco_venda" required>

            <button type="submit">Cadastrar Produto</button>
        </form>
        <br>
        <button onclick="location.href='estoque.php'">Voltar</button>
    </div>
</body>
</html>