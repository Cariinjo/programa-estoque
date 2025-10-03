<?php
include 'banco.php';
date_default_timezone_set('America/Sao_Paulo');

$data_filtro = $_GET['data'] ?? date('Y-m-d');

// Calcula o resumo do dia
$sql_resumo = "SELECT tipo, SUM(valor) as total FROM fluxo_caixa WHERE DATE(data) = ? GROUP BY tipo";
$stmt_resumo = $conn->prepare($sql_resumo);
$stmt_resumo->bind_param('s', $data_filtro);
$stmt_resumo->execute();
$resumo = $stmt_resumo->get_result();

$entradas = 0;
$saidas = 0;
while ($row = $resumo->fetch_assoc()) {
    if ($row['tipo'] == 'Entrada') $entradas = $row['total'];
    if ($row['tipo'] == 'Saída') $saidas = $row['total'];
}
$saldo_dia = $entradas - $saidas;

// Busca os lançamentos do dia
$sql_lancamentos = "SELECT * FROM fluxo_caixa WHERE DATE(data) = ? ORDER BY data DESC";
$stmt_lancamentos = $conn->prepare($sql_lancamentos);
$stmt_lancamentos->bind_param('s', $data_filtro);
$stmt_lancamentos->execute();
$lancamentos = $stmt_lancamentos->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><title>Controle de Caixa</title><link rel="stylesheet" href="style.css">
    <style>
        .caixa-container { display: flex; gap: 30px; }
        .caixa-resumo, .caixa-forms { flex: 1; }
        .resumo-dia { display: flex; justify-content: space-around; text-align: center; margin-bottom: 20px; }
        .resumo-item h3 { margin-bottom: 5px; }
        .resumo-item .valor { font-size: 1.8em; font-weight: bold; }
        #valor-entrada { color: green; } #valor-saida { color: red; } #valor-saldo { color: blue; }
        .form-lancamento { border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container2">
    <h1>Controle de Caixa</h1>
    <form action="caixa.php" method="GET">
        <label for="data">Visualizar dia:</label>
        <input type="date" name="data" value="<?php echo $data_filtro; ?>" onchange="this.form.submit()">
    </form>
    <hr>
    <div class="caixa-container">
        <div class="caixa-resumo">
            <h3>Resumo de <?php echo date('d/m/Y', strtotime($data_filtro)); ?></h3>
            <div class="resumo-dia">
                <div class="resumo-item"><h3 style="color:green;">Entradas</h3><span class="valor" id="valor-entrada">R$ <?php echo number_format($entradas, 2, ',', '.'); ?></span></div>
                <div class="resumo-item"><h3 style="color:red;">Saídas</h3><span class="valor" id="valor-saida">R$ <?php echo number_format($saidas, 2, ',', '.'); ?></span></div>
                <div class="resumo-item"><h3 style="color:blue;">Saldo</h3><span class="valor" id="valor-saldo">R$ <?php echo number_format($saldo_dia, 2, ',', '.'); ?></span></div>
            </div>
            <h4>Lançamentos do Dia</h4>
            <table width="100%">
                <thead><tr><th>Hora</th><th>Descrição</th><th>Valor</th></tr></thead>
                <tbody>
                    <?php while($lanc = $lancamentos->fetch_assoc()): ?>
                    <tr style="color: <?php echo $lanc['tipo'] == 'Entrada' ? 'green' : 'red'; ?>;">
                        <td><?php echo date('H:i', strtotime($lanc['data'])); ?></td>
                        <td><?php echo htmlspecialchars($lanc['descricao']); ?></td>
                        <td>R$ <?php echo number_format($lanc['valor'], 2, ',', '.'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="caixa-forms">
            <h3>Registrar Lançamento Manual</h3>
            <div class="form-lancamento">
                <h4>Nova Entrada</h4>
                <form action="caixa_action.php" method="POST">
                    <input type="hidden" name="tipo" value="Entrada">
                    <label>Descrição:</label><input type="text" name="descricao" required>
                    <label>Valor (R$):</label><input type="number" step="0.01" name="valor" required>
                    <button type="submit">Registrar Entrada</button>
                </form>
            </div>
            <div class="form-lancamento">
                <h4>Nova Saída</h4>
                <form action="caixa_action.php" method="POST">
                    <input type="hidden" name="tipo" value="Saída">
                    <label>Descrição:</label><input type="text" name="descricao" required>
                    <label>Valor (R$):</label><input type="number" step="0.01" name="valor" required>
                    <button type="submit">Registrar Saída</button>
                </form>
            </div>
        </div>
    </div>
    <br><button onclick="location.href='index.php'">Voltar ao Início</button>
</div>
</body>
</html>