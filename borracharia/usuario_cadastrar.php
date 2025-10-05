<?php
session_start();
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') die("Acesso negado.");
?>
<!DOCTYPE html><html lang="pt-br"><head><title>Cadastrar Usuário</title><link rel="stylesheet" href="style.css"></head><body>
<div class="container">
    <h1>Cadastrar Novo Usuário</h1>
    <form action="usuario_action.php" method="POST">
        <input type="hidden" name="acao" value="cadastrar">
        <label>Nome:</label><input type="text" name="nome" required>
        <label>Senha:</label><input type="password" name="senha" required>
        <label>Nível de Acesso:</label>
        <select name="nivel_acesso" required>
            <option value="Funcionario">Funcionário</option>
            <option value="Admin">Admin</option>
        </select>
        <button type="submit">Cadastrar</button>
    </form>
    <br><button onclick="location.href='usuarios.php'">Voltar</button>
</div>
</body></html>