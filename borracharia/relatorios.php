<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login.php?err=' . urlencode('Você precisa fazer login'));
    exit();
}
include 'banco.php';

// --- Lógica do Filtro de Data ---
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$where_clause = '';
$params = [];
$types = '';

if ($data_inicio && $data_fim) {
    // Adiciona a condição WHERE para filtrar as vendas dentro do intervalo de datas
    $where_clause = "WHERE DATE(v.data_venda) BETWEEN ? AND ?";
    $params = [$data_inicio, $data_fim];
    $types = 'ss';
}

// --- Cálculos para o Resumo Financeiro (agora com filtro) ---

// 1. Total faturado
$sql_total_faturado = "SELECT SUM(v.valor_total) as total FROM vendas v " . $where_clause;
$stmt = $conn->prepare($sql_total_faturado);
if ($data_inicio && $data_fim) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_faturado = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// 2. Total de vendas
$sql_num_vendas = "SELECT COUNT(v.id_venda) as count FROM vendas v " . $where_clause;
$stmt = $conn->prepare($sql_num_vendas);
if ($data_inicio && $data_fim) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$num_vendas = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
$stmt->close();

// 3. Total a receber (não é afetado pelo filtro de data)
$sql_total_devedor = "SELECT SUM(saldo_devedor) as total FROM clientes";
$total_a_receber = $conn->query($sql_total_devedor)->fetch_assoc()['total'] ?? 0;

// --- Consulta para o Histórico de Vendas (agora com filtro) ---
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


// --- Consulta para Clientes Devedores (não é afetado pelo filtro de data) ---
$sql_devedores = "SELECT id_cliente, nome, telefone, saldo_devedor
                  FROM clientes
                  WHERE saldo_devedor > 0
                  ORDER BY saldo_devedor DESC";
$resultado_devedores = $conn->query($sql_devedores);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema de Borracharia</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .resumo-financeiro { display: flex; justify-content: space-around; margin-bottom: 20px; text-align: center; }
        .resumo-item { padding: 20px; border: 1px solid #ccc; border-radius: 8px; width: 30%; }
        .resumo-item h3 { margin-top: 0; }
        .secao-relatorio { margin-top: 40px; }
        .filtro-form { background-color: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
    </style>
</head>
<body>
    <div class="container2">
        <h1>Painel de Relatórios</h1>

        <div class="filtro-form">
            <form action="relatorios.php" method="GET" style="display: flex; align-items: center; gap: 15px;">
                <label for="data_inicio">De:</label>
                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>" required>
                
                <label for="data_fim">Até:</label>
                <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>" required>
                
                <button type="submit">Filtrar</button>
                <a href="relatorios.php" style="text-decoration: none;">Limpar Filtro</a>
            </form>
        </div>

        <div class="resumo-financeiro">
            <div class="resumo-item">
                <h3>Total Faturado (Período)</h3>
                <h2>R$ <?php echo number_format($total_faturado, 2, ',', '.'); ?></h2>
            </div>
            <div class="resumo-item">
                <h3>Total de Vendas (Período)</h3>
                <h2><?php echo $num_vendas; ?></h2>
            </div>
            <div class="resumo-item">
                <h3>Total a Receber (Geral)</h3>
                <h2 style="color: #c00;">R