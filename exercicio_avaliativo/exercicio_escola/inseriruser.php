<?php
include 'banco.php';

$id_usuario = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dados do Usuario
    $nome = $_POST['nome'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if ($nome && $senha) {
        // Insere o usuario
        $sql_usuario = "INSERT INTO usuarios (nome, senha) VALUES (?, ?)";
        $stmt_usuario = $conn->prepare($sql_usuario);
        $stmt_usuario->bind_param("ss", $nome, $senha);
        $stmt_usuario->execute();
        $id_usuario = $stmt_usuario->insert_id;
        $stmt_usuario->close();
        $conn->close();

        // Redireciona para a página principal
        echo '<script>alert("Usuário cadastrado com sucesso!");</script>';
        header('Location: principal.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Usuário</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Cadastrar Usuário</h1>
        <form method="POST">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>

            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>

            <button type="submit" formmethod="POST">Cadastrar</button>
        </form>
    </div>
    <button onclick="location.href='principal.php'">Voltar</button>
</body>
</html>
