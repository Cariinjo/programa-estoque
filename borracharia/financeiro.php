<?php
session_start();
if (!isset($_SESSION['user_level']) || $_SESSION['user_level'] != 'Admin') die("Acesso negado.");
include 'banco.php';

// --- Lógica do Filtro de Data ---
$data_inicio_str = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim_str = $_GET['data_fim'] ?? date('Y-m-t');

// --- CÁLCULOS (permanecem os mesmos) ---
$sql_receita = "SELECT SUM(valor_total) as total FROM vendas WHERE DATE(data_venda) BETWEEN ? AND ?";
$stmt_receita = $conn->prepare($sql_receita);
$stmt_receita->bind_param('ss', $data_inicio_str, $data_fim_str);
$stmt_receita->execute();
$receita_bruta = $stmt_receita->get_result()->fetch_assoc()['total'] ?? 0;

$sql_cogs = "SELECT SUM(vi.quantidade * p.preco_compra) as total FROM venda_itens vi JOIN produtos p ON vi.id_produto = p.id_produto JOIN vendas v ON vi.id_venda = v.id_venda WHERE DATE(v.data_venda) BETWEEN ? AND ?";
$stmt_cogs = $conn->prepare($sql_cogs);
$stmt_cogs->bind_param('ss', $data_inicio_str, $data_fim_str);
$stmt_cogs->execute();
$cogs = $stmt_cogs->get_result()->fetch_assoc()['total'] ?? 0;

$sql_despesas = "SELECT SUM(valor) as total FROM despesas WHERE data_despesa BETWEEN ? AND ?";
$stmt_despesas = $conn->prepare($sql_despesas);
$stmt_despesas->bind_param('ss', $data_inicio_str, $data_fim_str);
$stmt_despesas->execute();
$gastos_variaveis = $stmt_despesas->get_result()->fetch_assoc()['total'] ?? 0;

$sql_custo_fixo_mensal = "SELECT SUM(valor_mensal) as total FROM custos_fixos";
$custo_fixo_mensal_total = $conn->query($sql_custo_fixo_mensal)->fetch_assoc()['total'] ?? 0;
$diferenca_dias = (new DateTime($data_fim_str))->diff(new DateTime($data_inicio_str))->days + 1;
$custos_fixos_periodo = ($custo_fixo_mensal_total / 30.44) * $diferenca_dias;

$custos_totais = $cogs + $gastos_variaveis + $custos_fixos_periodo;
$lucro_liquido = $receita_bruta - $custos_totais;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Financeiro</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .financeiro-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;}
        .card { background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; }
        .card h3 { margin-top: 0; }
        .card .valor { font-size: 2em; font-weight: bold; margin: 10px 0; }
        .receita { color: #28a745; } .despesa { color: #dc3545; } .lucro { color: #007bff; }
        .filtro-form { background-color: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .charts-container { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px; }
        @media (max-width: 900px) { .charts-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container2">
    <h1>Painel Financeiro</h1>

    <div class="filtro-form">
        <form action="financeiro.php" method="GET">
            <label>De:</label> <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio_str); ?>" required>
            <label>Até:</label> <input type="date" name="data_fim" value="<?php echo htmlspecialchars($data_fim_str); ?>" required>
            <button type="submit">Filtrar</button>
            <a href="financeiro.php" style="text-decoration: none;">Ver Mês Atual</a>
        </form>
    </div>

    <div class="financeiro-grid">
        <div class="card">
            <h3>Receita Bruta</h3>
            <p class="valor receita">R$ <?php echo number_format($receita_bruta, 2, ',', '.'); ?></p>
            <small>Total vendido no período</small>
        </div>
        <div class="card">
            <h3>Custos Totais</h3>
            <p class="valor despesa">R$ <?php echo number_format($custos_totais, 2, ',', '.'); ?></p>
            <small>Produtos + Variáveis + Fixos</small>
        </div>
        <div class="card">
            <h3>Lucro Líquido (Estimado)</h3>
            <p class="valor lucro">R$ <?php echo number_format($lucro_liquido, 2, ',', '.'); ?></p>
            <small>Receita - Custos</small>
        </div>
    </div>

    <div class="charts-container">
        <div>
            <h3>Visão Geral (Receita x Custos x Lucro)</h3>
            <canvas id="resumoGeralChart"></canvas>
        </div>
        <div>
            <h3>Composição dos Custos</h3>
            <canvas id="composicaoCustosChart"></canvas>
        </div>
    </div>
    
    <br><hr><br>
    <button onclick="location.href='index.php'">Voltar ao Início</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Gráfico de Barras: Resumo Geral
    const ctxResumo = document.getElementById('resumoGeralChart').getContext('2d');
    new Chart(ctxResumo, {
        type: 'bar',
        data: {
            labels: ['Resumo do Período'],
            datasets: [
                {
                    label: 'Receita Bruta',
                    data: [<?php echo $receita_bruta; ?>],
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Custos Totais',
                    data: [<?php echo $custos_totais; ?>],
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                },
                 {
                    label: 'Lucro Líquido',
                    data: [<?php echo $lucro_liquido; ?>],
                    backgroundColor: 'rgba(0, 123, 255, 0.7)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });

    // Gráfico de Pizza: Composição dos Custos
    const ctxCustos = document.getElementById('composicaoCustosChart').getContext('2d');
    new Chart(ctxCustos, {
        type: 'doughnut', // ou 'pie'
        data: {
            labels: ['Custo dos Produtos', 'Gastos Variáveis', 'Custos Fixos'],
            datasets: [{
                label: 'Composição dos Custos',
                data: [
                    <?php echo $cogs; ?>,
                    <?php echo $gastos_variaveis; ?>,
                    <?php echo $custos_fixos_periodo; ?>
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)'
                ],
                hoverOffset: 4
            }]
        }
    });
});
</script>

</body>
</html>