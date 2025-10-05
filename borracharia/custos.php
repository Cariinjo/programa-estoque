<?php
// --- CÓDIGO DE DEPURAÇÃO ADICIONADO ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIM DO CÓDIGO DE DEPURAÇÃO ---

session_start();
// Apenas Admins podem acessar esta página
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') {
    die("Acesso negado.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Custos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Gerenciamento de Custos</h1>
    <p>Selecione o tipo de custo que deseja gerenciar.</p>
    <button onclick="location.href='custos_fixos.php'">Custos Fixos (Mensais)</button>
    <button onclick="location.href='gastos_variaveis.php'">Gastos Variáveis (Avulsos)</button>
    <br><br>
    <hr>
    <button onclick="location.href='index.php'">Voltar ao Painel Principal</button>
</div>
</body>
</html>