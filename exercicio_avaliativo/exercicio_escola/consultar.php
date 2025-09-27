<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Alunos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container2">
        <h1>Alunos Cadastrados</h1>

        <input type="text" id="campoFiltro" onkeyup="filtrarTabela()" placeholder="Digite um nome para filtrar...">

        <table id="tabelaAlunos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Turma</th>
                    <th>Ano</th>
                    <th>Português</th>
                    <th>Matemática</th>
                    <th>Química</th>
                    <th>Física</th>
                    <th>História</th>
                    <th>Geografia</th>
                    <th>Ed. Física</th>
                    <th>Ens. Religioso</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include 'banco.php';

                // Junta as tabelas alunos e materias
                $sql = "SELECT a.id, a.nome, a.turma, a.ano, m.portugues, m.matematica, m.quimica, m.fisica, m.historia, m.geografia, m.ed_fisica, m.ensino_religioso 
                        FROM alunos a
                        LEFT JOIN materias m ON a.id = m.id_aluno
                        ORDER BY a.nome ASC";
                $resultado = $conn->query($sql);

                if ($resultado->num_rows > 0) {
                    while ($row = $resultado->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['turma']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['ano']) . "</td>";
                        echo "<td>" . ($row['portugues'] ?? 'N/A') . "</td>";
                        echo "<td>" . ($row['matematica'] ?? 'N/A') . "</td>";
                        echo "<td>" . ($row['quimica'] ?? 'N/A') . "</td>";
                        echo "<td>" . ($row['fisica'] ?? 'N/A') . "</td>";
                        echo "<td>" . ($row['historia'] ?? 'N/A') . "</td>";
                        echo "<td>" . ($row['geografia'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['ed_fisica'] ?? 'N/A') . "</td>";
                        echo "<td>" . htmlspecialchars($row['ensino_religioso'] ?? 'N/A') . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='12'>Nenhum aluno cadastrado.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>

        <div class="botoes">
            <button id="voltar" onclick="location.href='index.html'">Voltar</button>
        </div>
    </div>

    <script>
    function filtrarTabela() {
        var input, filtro, tabela, tr, td, i, valorDoTexto;
        input = document.getElementById("campoFiltro");
        filtro = input.value.toUpperCase();
        tabela = document.getElementById("tabelaAlunos");
        tr = tabela.getElementsByTagName("tbody")[0].getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[1]; // Coluna Nome
            if (td) {
                valorDoTexto = td.textContent || td.innerText;
                if (valorDoTexto.toUpperCase().indexOf(filtro) > -1) {
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