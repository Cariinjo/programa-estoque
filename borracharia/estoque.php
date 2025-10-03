<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Estoque</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container2">
        <h1>Gerenciamento de Estoque</h1>
        <div class="botoes-acao">
            <button onclick="location.href='produto_cadastrar.php'">Adicionar Novo Produto</button>
            <button onclick="location.href='compra_registrar.php'">Registrar Compra (Entrada)</button>
        </div>

        <input type="text" id="campoFiltro" onkeyup="filtrarTabela()" placeholder="Filtrar por nome do produto...">

        <table id="tabelaProdutos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Marca</th>
                    <th>Medida</th>
                    <th>Estoque</th>
                    <th>Preço de Venda</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include 'banco.php';
                $sql = "SELECT * FROM produtos ORDER BY nome ASC";
                $resultado = $conn->query($sql);
                if ($resultado->num_rows > 0) {
                    while ($row = $resultado->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id_produto'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['marca']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['medida']) . "</td>";
                        echo "<td>" . $row['quantidade_estoque'] . "</td>";
                        echo "<td>R$ " . number_format($row['preco_venda'], 2, ',', '.') . "</td>";
                        echo "<td><a href='produto_atualizar.php?id=" . $row['id_produto'] . "'>Editar</a> | <a href='produto_deletar.php?id=" . $row['id_produto'] . "' onclick='return confirm(\"Tem certeza?\")'>Excluir</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>Nenhum produto cadastrado.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
        <br>
        <button onclick="location.href='index.php'">Voltar ao Início</button>
    </div>

    <script>
    function filtrarTabela() {
        var input, filtro, tabela, tr, td, i;
        input = document.getElementById("campoFiltro");
        filtro = input.value.toUpperCase();
        tabela = document.getElementById("tabelaProdutos");
        tr = tabela.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[1]; // Coluna Nome
            if (td) {
                if (td.textContent.toUpperCase().indexOf(filtro) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    </script>
</body>
</html>