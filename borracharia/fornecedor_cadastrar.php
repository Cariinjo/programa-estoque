<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Cadastrar Fornecedor</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container">
    <h1>Cadastrar Novo Fornecedor</h1>
    <form action="fornecedor_action.php" method="POST">
        <input type="hidden" name="acao" value="cadastrar">
        <label>Nome da Empresa:</label><input type="text" name="nome" required>
        <label>Nome do Contato:</label><input type="text" name="contato_nome">
        <label>Telefone:</label><input type="text" name="telefone">
        <label>Email:</label><input type="email" name="email">
        <button type="submit">Cadastrar Fornecedor</button>
    </form>
    <br><button onclick="location.href='fornecedores.php'">Voltar</button>
</div>
</body></html>