<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php if (!empty($_GET['err'])): ?>
            <p style="color: red;"><?= htmlspecialchars($_GET['err']); ?></p>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <label>Nome:<br>
                <input type="text" name="nome" required>
            </label><br><br>

            <label>Senha:<br>
                <input type="password" name="senha" required>
            </label><br><br>

            <button type="submit">Entrar</button>
        </form>
    </div>

    <button onclick="location.href='principal.php'">Voltar</button>
</body>
</html>
