<?php
include 'banco.php';
session_start();
if (!isset($_SESSION['user_id'])) exit('Acesso negado.');

$id_cliente = $_POST['id_cliente'] ?? null;
$metodo_pagamento = $_POST['metodo_pagamento'] ?? null;
$produtos_venda = $_POST['produtos'] ?? [];
$servicos_venda = $_POST['servicos'] ?? []; // NOVO

if (!$id_cliente || !$metodo_pagamento || (empty($produtos_venda) && empty($servicos_venda))) {
    header('Location: vendas.php?err=Dados da venda incompletos.');
    exit();
}

$valor_total_venda = 0;
$conn->begin_transaction();

try {
    // --- CALCULA VALOR TOTAL E VERIFICA ESTOQUE DOS PRODUTOS ---
    foreach ($produtos_venda as $id_produto => $item) {
        $quantidade_vendida = (int)$item['quantidade'];
        $stmt_check = $conn->prepare("SELECT preco_venda, quantidade_estoque FROM produtos WHERE id_produto = ? FOR UPDATE");
        $stmt_check->bind_param("i", $id_produto);
        $stmt_check->execute();
        $produto_db = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        if (!$produto_db || $quantidade_vendida > $produto_db['quantidade_estoque']) {
            throw new Exception("Estoque insuficiente para o produto ID: {$id_produto}.");
        }
        $valor_total_venda += $produto_db['preco_venda'] * $quantidade_vendida;
    }

    // --- NOVO: SOMA O VALOR DOS SERVIÇOS AO TOTAL ---
    foreach ($servicos_venda as $id_servico) {
        $stmt_check_serv = $conn->prepare("SELECT preco_venda FROM servicos WHERE id_servico = ?");
        $stmt_check_serv->bind_param("i", $id_servico);
        $stmt_check_serv->execute();
        $servico_db = $stmt_check_serv->get_result()->fetch_assoc();
        $stmt_check_serv->close();
        if ($servico_db) {
            $valor_total_venda += $servico_db['preco_venda'];
        }
    }
    
    // --- 1. INSERE O REGISTRO NA TABELA 'vendas' ---
    $sql_venda = "INSERT INTO vendas (id_cliente, valor_total, metodo_pagamento) VALUES (?, ?, ?)";
    $stmt_venda = $conn->prepare($sql_venda);
    $stmt_venda->bind_param("ids", $id_cliente, $valor_total_venda, $metodo_pagamento);
    $stmt_venda->execute();
    $id_nova_venda = $conn->insert_id;
    $stmt_venda->close();

    // --- 2. PROCESSA OS PRODUTOS (INSERE ITENS E ATUALIZA ESTOQUE) ---
    foreach ($produtos_venda as $id_produto => $item) {
        $quantidade_vendida = (int)$item['quantidade'];
        // Pega o preço novamente para garantir consistência
        $stmt_price = $conn->prepare("SELECT preco_venda FROM produtos WHERE id_produto = ?");
        $stmt_price->bind_param("i", $id_produto);
        $stmt_price->execute();
        $preco_unitario = $stmt_price->get_result()->fetch_assoc()['preco_venda'];
        $stmt_price->close();

        // Insere o item da venda
        $stmt_item = $conn->prepare("INSERT INTO venda_itens (id_venda, id_produto, quantidade, preco_unitario_venda) VALUES (?, ?, ?, ?)");
        $stmt_item->bind_param("iiid", $id_nova_venda, $id_produto, $quantidade_vendida, $preco_unitario);
        $stmt_item->execute();
        $stmt_item->close();

        // Atualiza estoque
        $stmt_estoque = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id_produto = ?");
        $stmt_estoque->bind_param("ii", $quantidade_vendida, $id_produto);
        $stmt_estoque->execute();
        $stmt_estoque->close();
    }

    // --- 3. NOVO: PROCESSA OS SERVIÇOS (INSERE NA TABELA venda_servicos) ---
    foreach ($servicos_venda as $id_servico) {
        // Pega o preço novamente
        $stmt_price = $conn->prepare("SELECT preco_venda FROM servicos WHERE id_servico = ?");
        $stmt_price->bind_param("i", $id_servico);
        $stmt_price->execute();
        $preco_cobrado = $stmt_price->get_result()->fetch_assoc()['preco_venda'];
        $stmt_price->close();
        
        $stmt_serv = $conn->prepare("INSERT INTO venda_servicos (id_venda, id_servico, preco_cobrado) VALUES (?, ?, ?)");
        $stmt_serv->bind_param("iid", $id_nova_venda, $id_servico, $preco_cobrado);
        $stmt_serv->execute();
        $stmt_serv->close();
    }

    // --- 4. ATUALIZA SALDO DO CLIENTE (se for 'Fiado') ---
    if ($metodo_pagamento === 'Fiado') {
        $stmt_cliente = $conn->prepare("UPDATE clientes SET saldo_devedor = saldo_devedor + ? WHERE id_cliente = ?");
        $stmt_cliente->bind_param("di", $valor_total_venda, $id_cliente);
        $stmt_cliente->execute();
        $stmt_cliente->close();
    }
    
    $conn->commit();
    header('Location: relatorios.php?msg=Venda registrada com sucesso!');
} catch (Exception $e) {
    $conn->rollback();
    header('Location: vendas.php?err=' . urlencode($e->getMessage()));
}

// ... (código existente no início) ...

try {
    // ... (todo o código de cálculo e inserção de vendas/itens/serviços) ...
    
    // --- 4. ATUALIZA SALDO DO CLIENTE (se for 'Fiado') ---
    if ($metodo_pagamento === 'Fiado') {
        // ... (código de update do saldo devedor) ...
    } else {
        // NOVO: SE A VENDA FOI PAGA, REGISTRA A ENTRADA NO CAIXA
        $descricao_caixa = "Recebimento da Venda #" . $id_nova_venda;
        $stmt_caixa = $conn->prepare("INSERT INTO fluxo_caixa (tipo, descricao, valor, id_venda_associada) VALUES ('Entrada', ?, ?, ?)");
        $stmt_caixa->bind_param("sdi", $descricao_caixa, $valor_total_venda, $id_nova_venda);
        $stmt_caixa->execute();
        $stmt_caixa->close();
    }
    
    $conn->commit();
    header('Location: relatorios.php?msg=Venda registrada com sucesso!');

} catch (Exception $e) {
    // ... (código do catch) ...
}

$conn->close();
?>
