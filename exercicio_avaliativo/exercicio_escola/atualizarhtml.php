<?php
session_start();
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
    <title>Atualizar Aluno</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topo">
        <h2 id="usuario">Bem vindo <?php echo $_SESSION['user_name']; ?></h2>
        <button id="sair" onclick="location.href='index.php'">Sair</button>
    </div>
    <div class="container">
        <h1>Atualizar Aluno</h1>
        <p>Digite o ID do aluno que você deseja atualizar. Você pode ver o ID na página de consulta.</p>
        <form action="atualizar.php" method="POST">
            <label for="id">ID do Aluno:</label>
            <br>
            <input type="number" id="id" name="id" required>
            <br><br>
            <button type="submit">Buscar Aluno</button>
        </form>
        <br>
    </div>
</body>
</html>