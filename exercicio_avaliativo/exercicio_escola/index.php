<?php
session_start();
if (!isset($_SESSION['user_id']))  {
    header('location: login.php?err=' . urlencode('Você precisa fazer login'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escola</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topo">
        <h2 id="usuario">Bem vindo <?php echo $_SESSION['user_name']; ?></h2>
        <button id="sair" onclick="location.href='sair.php'">Sair</button>
    </div>
    <div class="container">
        <h1>Bem-vindo à Escola</h1>
        <button onclick="location.href='cadastrarhtml.php'">Cadastrar Aluno</button>
        <button onclick="location.href='consultar.php'">Consultar Aluno</button>
        <button onclick="location.href='atualizarhtml.php'">Atualizar Aluno</button>
        <button onclick="location.href='deletarhtml.php'">Deletar Aluno</button>
    </div>
</body>
</html>