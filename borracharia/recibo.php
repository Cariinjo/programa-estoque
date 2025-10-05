<?php
include 'banco.php';

$id_venda = $_GET['id'] ?? 0;

if (!$id_venda || !is_numeric($id_venda)) {
    die("ID da venda inválido.");
}

// Buscar dados da venda e do cliente
$stmt_venda = $conn->prepare("SELECT v.*, c.nome as nome_cliente FROM vendas v JOIN clientes c ON v.id_cliente = c.id_cliente WHERE v.id_venda = ?");
$stmt_venda->bind_param("i", $id_venda);
$stmt_venda->execute();
$venda = $stmt_venda->get_result()->fetch_assoc();

if (!$venda) {
    die("Venda não encontrada.");
}

// Buscar produtos da venda
$stmt_itens = $conn->prepare("SELECT vi.*, p.nome as nome_produto, p.medida FROM venda_itens vi JOIN produtos p ON vi.id_produto = p.id_produto WHERE vi.id_venda = ?");
$stmt_itens->bind_param("i", $id_venda);
$stmt_itens->execute();
$itens_venda = $stmt_itens->get_result();

// Buscar serviços da venda
$stmt_servicos = $conn->prepare("SELECT vs.*, s.nome as nome_servico FROM venda_servicos vs JOIN servicos s ON vs.id_servico = s.id_servico WHERE vs.id_venda = ?");
$stmt_servicos->bind_param("i", $id_venda);
$stmt_servicos->execute();
$servicos_venda = $stmt_servicos->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recibo da Venda #<?php echo $venda['id_venda']; ?></title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; }
        .recibo-container { width: 300px; margin: 20px auto; padding: 15px; border: 1px solid #ccc; }
        h1, h2 { text-align: center; margin: 5px 0; }
        p { margin: 3px 0; }
        hr { border: none; border-top: 1px dashed #000; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 2px; }
        .total { font-weight: bold; font-size: 1.1em; }
        .text-right { text-align: right; }
        .print-button { display: block; width: 100px; margin: 20px auto; padding: 10px; text-align: center; }
        @media print {
            .print-button { display: none; }
            .recibo-container { border: none; margin: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Imprimir</button>

    <div class="recibo-container">
        <h1>Sua Borracharia</h1>
        <h2>Recibo de Venda</h2>
        <hr>
        <p><strong>Venda ID:</strong> #<?php echo $venda['id_venda']; ?></p>
        <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></p>
        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($venda['nome_cliente']); ?></p>
        <hr>
        <h3>Itens</h3>
        <table>
            <?php while($item = $itens_venda->fetch_assoc()): ?>
            <tr>
                <td><?php echo $item['quantidade']; ?>x</td>
                <td><?php echo htmlspecialchars($item['nome_produto']); ?></td>
                <td class="text-right">R$ <?php echo number_format($item['quantidade'] * $item['preco_unitario_venda'], 2, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>

            <?php while($servico = $servicos_venda->fetch_assoc()): ?>
            <tr>
                <td>1x</td>
                <td><?php echo htmlspecialchars($servico['nome_servico']); ?></td>
                <td class="text-right">R$ <?php echo number_format($servico['preco_cobrado'], 2, ',', '.'); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <hr>
        <p class="total">
            TOTAL:
            <span style="float:right;">R$ <?php echo number_format($venda['valor_total'], 2, ',', '.'); ?></span>
        </p>
        <hr>
        <p><strong>Pagamento:</strong> <?php echo htmlspecialchars($venda['metodo_pagamento']); ?></p>
        <br>
        <p style="text-align: center;">Obrigado pela preferência!</p>
    </div>
</body>
</html>