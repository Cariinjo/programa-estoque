<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Serviço</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Cadastrar Novo Serviço</h1>
        <form action="servico_action.php" method="POST">
            <input type="hidden" name="acao" value="cadastrar">
            <label for="nome">Nome do Serviço:</label>
            <input type="text" id="nome" name="nome" required>
            <label for="preco_venda">Preço de Venda (R$):</label>
            <input type="number" step="0.01" id="preco_venda" name="preco_venda" required>
            <button type="submit">Cadastrar Serviço</button>
        </form>
        <br>
        <button onclick="location.href='servicos.php'">Voltar</button>
    </div>
</body>
</html>