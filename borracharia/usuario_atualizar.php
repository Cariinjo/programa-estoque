<?php
session_start();
// Apenas Admins podem acessar esta página
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') {
    die("Acesso negado.");
}
include 'banco.php';

// Pega o ID do usuário da URL
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: usuarios.php');
    exit();
}

// Busca os dados do usuário que será editado
$stmt = $conn->prepare("SELECT id_usuario, nome, nivel_acesso FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

// Se não encontrar o usuário, volta para a lista
if (!$usuario) {
    header('Location: usuarios.php?err=Usuário não encontrado');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Atualizar Usuário</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Atualizar Usuário</h1>
    <form action="usuario_action.php" method="POST">
        <input type="hidden" name="acao" value="atualizar">
        <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">

        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>

        <label for="senha">Nova Senha:</label>
        <input type="password" id="senha" name="senha" placeholder="Deixe em branco para não alterar">

        <label for="nivel_acesso">Nível de Acesso:</label>
        <select name="nivel_acesso" id="nivel_acesso" required>
            <option value="Funcionario" <?php echo ($usuario['nivel_acesso'] == 'Funcionario') ? 'selected' : ''; ?>>
                Funcionário
            </option>
            <option value="Admin" <?php echo ($usuario['nivel_acesso'] == 'Admin') ? 'selected' : ''; ?>>
                Admin
            </option>
        </select>
        
        <br>
        <button type="submit">Atualizar Usuário</button>
    </form>
    <br>
    <button onclick="location.href='usuarios.php'">Cancelar</button>
</div>
</body>
</html>