<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Serviços</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container2">
        <h1>Gerenciamento de Serviços</h1>
        <div class="botoes-acao">
            <button onclick="location.href='servico_cadastrar.php'">Adicionar Novo Serviço</button>
        </div>

        <table id="tabelaServicos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Serviço</th>
                    <th>Preço de Venda</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include 'banco.php';
                $sql = "SELECT * FROM servicos ORDER BY nome ASC";
                $resultado = $conn->query($sql);
                if ($resultado->num_rows > 0) {
                    while ($row = $resultado->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id_servico'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                        echo "<td>R$ " . number_format($row['preco_venda'], 2, ',', '.') . "</td>";
                        echo "<td><a href='servico_atualizar.php?id=" . $row['id_servico'] . "'>Editar</a> | <a href='servico_deletar.php?id=" . $row['id_servico'] . "' onclick='return confirm(\"Tem certeza?\")'>Excluir</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>Nenhum serviço cadastrado.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
        <br>
        <button onclick="location.href='index.php'">Voltar ao Início</button>
    </div>
</body>
</html>