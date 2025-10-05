<?php
session_start();
// Apenas Admins podem acessar esta funcionalidade
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') {
    die("Acesso negado.");
}
include 'banco.php';

$acao = $_POST['acao'] ?? '';

// --- LÓGICA PARA CADASTRAR UM NOVO USUÁRIO ---
if ($acao === 'cadastrar') {
    $nome = $_POST['nome'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $nivel_acesso = $_POST['nivel_acesso'] ?? 'Funcionario';

    if (empty($nome) || empty($senha)) {
        header('Location: usuario_cadastrar.php?err=Nome e senha são obrigatórios');
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nome, senha, nivel_acesso) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nome, $senha_hash, $nivel_acesso);
    
    if ($stmt->execute()) {
        header('Location: usuarios.php?msg=Usuário cadastrado com sucesso!');
    } else {
        header('Location: usuario_cadastrar.php?err=Erro ao cadastrar usuário.');
    }
    $stmt->close();

// --- LÓGICA PARA ATUALIZAR UM USUÁRIO EXISTENTE ---
} elseif ($acao === 'atualizar') {
    $id = $_POST['id_usuario'] ?? 0;
    $nome = $_POST['nome'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $nivel_acesso = $_POST['nivel_acesso'] ?? 'Funcionario';

    if (empty($nome) || !$id) {
        header('Location: usuarios.php?err=Dados inválidos.');
        exit();
    }

    // Se o campo senha NÃO estiver vazio, atualiza a senha.
    if (!empty($senha)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET nome = ?, senha = ?, nivel_acesso = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nome, $senha_hash, $nivel_acesso, $id);
    } else {
        // Se o campo senha ESTIVER vazio, atualiza tudo, MENOS a senha.
        $sql = "UPDATE usuarios SET nome = ?, nivel_acesso = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nome, $nivel_acesso, $id);
    }

    if ($stmt->execute()) {
        header('Location: usuarios.php?msg=Usuário atualizado com sucesso!');
    } else {
        header('Location: usuarios.php?err=Erro ao atualizar usuário.');
    }
    $stmt->close();
}

$conn->close();
?>