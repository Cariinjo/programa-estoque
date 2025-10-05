<?php
session_start();
// Protege a página, permitindo acesso apenas a usuários logados
if (!isset($_SESSION['user_id'])) {
    header('location: login.php?err=' . urlencode('Você precisa fazer login'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle - Borracharia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topo">
        <h2 id="usuario">Bem vindo <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo htmlspecialchars($_SESSION['user_level']); ?>)</h2>
        <button id="sair" onclick="location.href='sair.php'">Sair</button>
    </div>

    <div class="container">
        <h1>Borracharia - Painel de Controle</h1>
        
        <button onclick="location.href='vendas.php'">Registrar Venda</button>
        <button onclick="location.href='clientes.php'">Gerenciar Clientes</button>
        <button onclick="location.href='estoque.php'">Consultar Estoque</button>
    </div>
    <div class="container">
        <?php 
        // Bloco de botões visível apenas para usuários com nível 'Admin'
        if (isset($_SESSION['user_level']) && $_SESSION['user_level'] == 'Admin'): 
        ?>
            <button onclick="location.href='custos.php'">Gerenciar Custos</button>
            <button onclick="location.href='financeiro.php'">Painel Financeiro</button>
            <button onclick="location.href='caixa.php'">Controle de Caixa</button>
            <button onclick="location.href='servicos.php'">Gerenciar Serviços</button>
            <button onclick="location.href='fornecedores.php'">Gerenciar Fornecedores</button>
            <button onclick="location.href='usuarios.php'">Gerenciar Usuários</button>
        <?php endif; ?>
    </div>
</body>
</html>