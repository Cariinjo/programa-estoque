<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login.php?err=' . urlencode('Você precisa fazer login'));
    exit();
}
// Verificação de permissão de Admin para esta página
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') {
    die("Acesso negado. Você não tem permissão para acessar esta página.");
}
include 'banco.php';

// --- Lógica do Filtro de Data ---
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$where_clause = '';
$params = [];
$types = '';

if ($data_inicio && $data_fim) {
    $where_clause = "WHERE DATE(v.data_venda) BETWEEN ? AND ?";
    $params = [$data_inicio, $data_fim];
    $types = 'ss';
}

// --- Consultas ao banco de dados (resumo, histórico, devedores) ---
// (O restante do código PHP para buscar os dados no banco permanece o mesmo)
$sql_vendas = "SELECT v.id_venda, v.data_venda, v.valor_total, v.metodo_pagamento, c.nome as nome_cliente
               FROM vendas v
               JOIN clientes c ON v.id_cliente = c.id_cliente "
               . $where_clause .
               " ORDER BY v.data_venda DESC";
$stmt = $conn->prepare($sql_vendas);
if ($data_inicio && $data_fim) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado_vendas = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema de Borracharia</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* (styles permanecem os mesmos) */
    </style>
</head>
<body>
    <div class="container2">
        <h1>Painel de Relatórios</h1>

        <div class="secao-relatorio">
            <h2>Histórico de Vendas <?php if($data_inicio) echo " (Filtrado)"; ?></h2>
            <table id="tabelaVendas">
                <thead>
                    <tr>
                        <th>ID Venda</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Valor Total</th>
                        <th>Pagamento</th>
                        <th>Ações</th> </tr>
                </thead>
                <tbody>
                    <?php if ($resultado_vendas && $resultado_vendas->num_rows > 0): ?>
                        <?php while ($venda = $resultado_vendas->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $venda['id_venda']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></td>
                                <td><?php echo htmlspecialchars($venda['nome_cliente']); ?></td>
                                <td>R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($venda['metodo_pagamento']); ?></td>
                                <td>
                                    <a href="recibo.php?id=<?php echo $venda['id_venda']; ?>" target="_blank">Imprimir Recibo</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Nenhuma venda encontrada para o período selecionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="botoes">
            <button id="voltar" onclick="location.href='index.php'">Voltar ao Início</button>
        </div>
    </div>
</body>
</html>
<?php
if(isset($conn)) $conn->close();
?>