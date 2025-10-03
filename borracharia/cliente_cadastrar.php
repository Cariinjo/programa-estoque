<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Cliente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Cadastrar Novo Cliente</h1>
        <form action="cliente_action.php" method="POST">
            <input type="hidden" name="acao" value="cadastrar">

            <label for="nome">Nome Completo:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="telefone">Telefone:</label>
            <input type="text" id="telefone" name="telefone">

            <label for="endereco">Endereço:</label>
            <input type="text" id="endereco" name="endereco">
            
            <p>O saldo devedor inicial é sempre zero.</p>

            <button type="submit">Cadastrar Cliente</button>
        </form>
        <br>
        <button onclick="location.href='clientes.php'">Voltar</button>
    </div>
</body>
</html>