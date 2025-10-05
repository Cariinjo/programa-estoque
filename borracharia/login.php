<?php
session_start();
// Se o usuário já estiver logado, redireciona para o painel principal
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
    <title>Login - Borracharia</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container {
            width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .form-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container form {
            display: flex;
            flex-direction: column;
        }
        .form-container .link-cadastro {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Login</h1>
        <?php if (isset($_GET['err'])): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($_GET['err']); ?></p>
        <?php endif; ?>
         <?php if (isset($_GET['msg_cadastro'])): // Mensagem de sucesso vinda do cadastro ?>
            <p style="color: green; text-align: center;"><?php echo htmlspecialchars($_GET['msg_cadastro']); ?></p>
        <?php endif; ?>
        <form action="auth.php" method="POST">
            <label for="login-nome">Nome de Usuário:</label>
            <input type="text" id="login-nome" name="nome" required>
            <br>
            <label for="login-senha">Senha:</label>
            <input type="password" id="login-senha" name="senha" required>
            <br>
            <button type="submit">Entrar</button>
        </form>
        <div class="link-cadastro">
            <p>Não tem uma conta? <a href="register.php">Cadastre-se aqui</a></p>
        </div>
    </div>
</body>
</html>