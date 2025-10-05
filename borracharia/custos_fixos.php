<?php
// --- CÓDIGO DE DEPURAÇÃO ADICIONADO ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIM DO CÓDIGO DE DEPURAÇÃO ---

session_start();
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') die("Acesso negado.");
include 'banco.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Custos Fixos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container2">
    <h1>Custos Fixos Mensais</h1>
    <p>Declare aqui todos os seus custos que se repetem mensalmente.</p>
    <div class="botoes-acao"><button onclick="location.href='custo_fixo_action.php?acao=novo'">Adicionar Novo Custo Fixo</button></div>
    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Valor Mensal</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $resultado = $conn->query("SELECT * FROM custos_fixos ORDER BY descricao ASC");
            if ($resultado) {
                while ($row = $resultado->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['descricao']) . "</td>";
                    echo "<td>R$ " . number_format($row['valor_mensal'], 2, ',', '.') . "</td>";
                    echo "<td><a href='custo_fixo_action.php?acao=editar&id=" . $row['id_custo_fixo'] . "'>Editar</a> | <a href='custo_fixo_action.php?acao=deletar&id=" . $row['id_custo_fixo'] . "' onclick='return confirm(\"Tem certeza?\")'>Excluir</a></td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>
    <br>
    <button onclick="location.href='custos.php'">Voltar</button>
</div>
</body>
</html>