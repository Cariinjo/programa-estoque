<?php
session_start();
if (!isset($_SESSION['user_id']))  {
    header('location: login.php?err=' . urlencode('Você precisa fazer login'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<body>
    <div class="container">
        <h1>Borracharia - Painel de Controle</h1>
        <button onclick="location.href='caixa.php'">Controle de Caixa</button>
        <button onclick="location.href='fornecedores.php'">Gerenciar Fornecedores</button> <button onclick="location.href='estoque.php'">Gerenciar Estoque</button>
        <button onclick="location.href='servicos.php'">Gerenciar Serviços</button>
        <button onclick="location.href='clientes.php'">Gerenciar Clientes</button>
        <button onclick="location.href='vendas.php'">Registrar Venda</button>
        <button onclick="location.href='relatorios.php'">Relatórios</button>
    </div>
</body>
</html>