<?php
include 'banco.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .saldo-devedor {
            color: #c00;
            font-weight: bold;
        }
        .saldo-pago {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container2">
        <h1>Gerenciamento de Clientes</h1>
        <div class="botoes-acao">
            <button onclick="location.href='cliente_cadastrar.php'">Adicionar Novo Cliente</button>
        </div>

        <input type="text" id="campoFiltro" onkeyup="filtrarTabela()" placeholder="Filtrar por nome do cliente...">

        <table id="tabelaClientes">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>Endereço</th>
                    <th>Saldo Devedor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM clientes ORDER BY nome ASC";
                $resultado = $conn->query($sql);
                if ($resultado->num_rows > 0) {
                    while ($row = $resultado->fetch_assoc()) {
                        $saldo_classe = $row['saldo_devedor'] > 0 ? 'saldo-devedor' : 'saldo-pago';
                        // ... (dentro do loop while que exibe os clientes) ...
echo "<td>
        <a href='cliente_atualizar.php?id=" . $row['id_cliente'] . "'>Editar</a> | ";

// NOVO: Adiciona link para pagamento se houver dívida
if ($row['saldo_devedor'] > 0) {
    echo "<a href='cliente_pagamento.php?id=" . $row['id_cliente'] . "' style='color:green;font-weight:bold;'>Receber Pagamento</a> | ";
}

echo "<a href='cliente_deletar.php?id=" . $row['id_cliente'] . "' onclick='...'>Excluir</a>
      </td>";
// ... (resto do loop) ...
                        echo "<tr>";
                        echo "<td>" . $row['id_cliente'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['telefone']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['endereco']) . "</td>";
                        echo "<td class='$saldo_classe'>R$ " . number_format($row['saldo_devedor'], 2, ',', '.') . "</td>";
                        echo "<td>
                                <a href='cliente_atualizar.php?id=" . $row['id_cliente'] . "'>Editar</a> | 
                                <a href='cliente_deletar.php?id=" . $row['id_cliente'] . "' onclick='return confirm(\"Atenção! Deletar um cliente também removerá seu histórico de vendas. Deseja continuar?\")'>Excluir</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>Nenhum cliente cadastrado.</td></tr>";
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
        tabela = document.getElementById("tabelaClientes");
        tr = tabela.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("td")[1]; // Coluna Nome
            if (td && (td.textContent || td.innerText).toUpperCase().indexOf(filtro) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
    </script>
</body>
</html>