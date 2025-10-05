<?php
session_start();
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') die("Acesso negado.");
include 'banco.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gastos Variáveis</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container2">
    <h1>Gastos Variáveis (Avulsos)</h1>
    <p>Cadastre aqui despesas que não são recorrentes, como compras de materiais, manutenções, etc.</p>
    
    <div class="botoes-acao">
        <button onclick="location.href='gasto_variavel_action.php?acao=novo'">Adicionar Novo Gasto</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Valor</th>
                <th>Ações</th> </tr>
        </thead>
        <tbody>
            <?php
            $resultado = $conn->query("SELECT * FROM despesas ORDER BY data_despesa DESC");
            while ($row = $resultado->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . date('d/m/Y', strtotime($row['data_despesa'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['descricao']) . "</td>";
                echo "<td>" . htmlspecialchars($row['categoria']) . "</td>";
                echo "<td>R$ " . number_format($row['valor'], 2, ',', '.') . "</td>";
                // Links para editar e excluir
                echo "<td>
                        <a href='gasto_variavel_action.php?acao=editar&id=" . $row['id_despesa'] . "'>Editar</a> | 
                        <a href='gasto_variavel_action.php?acao=deletar&id=" . $row['id_despesa'] . "' onclick='return confirm(\"Tem certeza?\")'>Excluir</a>
                      </td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    <br>
    <button onclick="location.href='custos.php'">Voltar</button>
</div>
</body>
</html>