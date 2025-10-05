<?php
session_start();

// 1. Verificação de Permissão de Administrador
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') {
    // Se não for admin, exibe uma mensagem clara e interrompe o script.
    die("Acesso negado. Apenas administradores podem visualizar esta página.");
}

include 'banco.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Usuários</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container2">
    <h1>Gerenciamento de Usuários</h1>
    <div class="botoes-acao">
        <button onclick="location.href='usuario_cadastrar.php'">Adicionar Novo Usuário</button>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Nível de Acesso</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT id_usuario, nome, nivel_acesso FROM usuarios ORDER BY nome";
            $resultado = $conn->query($sql);

            // 2. Verifica se a consulta retornou algum usuário
            if ($resultado && $resultado->num_rows > 0) {
                // Se encontrou usuários, percorre a lista e exibe na tabela
                while ($row = $resultado->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id_usuario'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                    echo "<td>" . $row['nivel_acesso'] . "</td>";
                    echo "<td><a href='usuario_atualizar.php?id=" . $row['id_usuario'] . "'>Editar</a>";
                    
                    // Lógica para impedir que o admin se auto-delete
                    if ($_SESSION['user_id'] != $row['id_usuario']) {
                        echo " | <a href='usuario_deletar.php?id=" . $row['id_usuario'] . "' onclick='return confirm(\"Tem certeza?\")'>Excluir</a>";
                    }
                    echo "</td></tr>";
                }
            } else {
                // 3. Se não encontrou usuários, exibe uma mensagem amigável
                echo "<tr><td colspan='4'>Nenhum usuário cadastrado no sistema.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <br>
    <button onclick="location.href='index.php'">Voltar</button>
</div>
</body>
</html>