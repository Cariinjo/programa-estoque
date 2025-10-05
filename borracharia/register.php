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
    <title>Cadastro - Borracharia</title>
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
        .form-container .link-login {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
     <div class="form-container">
        <h1>Crie sua Conta</h1>
         <?php if (isset($_GET['err_cadastro'])): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($_GET['err_cadastro']); ?></p>
        <?php endif; ?>
        <form action="register_action.php" method="POST">
            <label for="reg-nome">Nome de Usuário:</label>
            <input type="text" id="reg-nome" name="nome" required>
            <br>
            <label for="reg-senha">Senha:</label>
            <input type="password" id="reg-senha" name="senha" required>
            <br>
            <button type="submit">Criar Conta</button>
        </form>
        <div class="link-login">
            <p>Já tem uma conta? <a href="login.php">Faça o login</a></p>
        </div>
    </div>
</body>
</html>