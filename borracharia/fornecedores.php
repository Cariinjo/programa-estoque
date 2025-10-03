<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><title>Gerenciar Fornecedores</title><link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container2">
    <h1>Gerenciamento de Fornecedores</h1>
    <div class="botoes-acao">
        <button onclick="location.href='fornecedor_cadastrar.php'">Adicionar Novo Fornecedor</button>
    </div>
    <table id="tabelaFornecedores">
        <thead><tr><th>ID</th><th>Nome da Empresa</th><th>Contato</th><th>Telefone</th><th>Ações</th></tr></thead>
        <tbody>
            <?php
            include 'banco.php';
            $resultado = $conn->query("SELECT * FROM fornecedores ORDER BY nome ASC");
            if ($resultado->num_rows > 0) {
                while ($row = $resultado->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['id_fornecedor'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['contato_nome']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['telefone']) . "</td>";
                    echo "<td><a href='fornecedor_atualizar.php?id=" . $row['id_fornecedor'] . "'>Editar</a> | <a href='fornecedor_deletar.php?id=" . $row['id_fornecedor'] . "' onclick='return confirm(\"Tem certeza?\")'>Excluir</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>Nenhum fornecedor cadastrado.</td></tr>";
            }
            $conn->close();
            ?>
        </tbody>
    </table>
    <br><button onclick="location.href='index.php'">Voltar ao Início</button>
</div>
</body>
</html>