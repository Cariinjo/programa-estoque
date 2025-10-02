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
    <title>Cadastrar Aluno</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topo">
        <h2 id="usuario">Bem vindo <?php echo $_SESSION['user_name']; ?></h2>
        <button id="sair" onclick="location.href='index.php'">Sair</button>
    </div>
    <div class="container">
    <h1>Cadastrar Aluno</h1>
    <form action="cadastrar.php" method="POST">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
        <br>
        <label for="turma">Turma:</label>
        <input type="text" id="turma" name="turma" required>
        <br>
        <label for="ano">Ano:</label>
        <input type="number" id="ano" name="ano" required>
        <br>

        <h2>Notas</h2>
        <label for="portugues">Português:</label>
        <input type="number"  id="portugues" name="portugues" required>
        <br>
        <label for="matematica">Matemática:</label>
        <input type="number"  id="matematica" name="matematica" required>
        <br>
        <label for="quimica">Química:</label>
        <input type="number"  id="quimica" name="quimica" required>
        <br>
        <label for="fisica">Física:</label>
        <input type="number"  id="fisica" name="fisica" required>
        <br>
        <label for="historia">História:</label>
        <input type="number"  id="historia" name="historia" required>
        <br>
        <label for="geografia">Geografia:</label>
        <input type="number"  id="geografia" name="geografia" required>
        <br>
        <label for="ed_fisica">Educação Física:</label>
        <input type="text" id="ed_fisica" name="ed_fisica" required>
        <br>
        <label for="ensino_religioso">Ensino Religioso:</label>
        <input type="text" id="ensino_religioso" name="ensino_religioso" required>
        <br>

        <button type="submit" value="Cadastrar">Cadastrar</button>
    </form>
    </div>
</body>
</html>