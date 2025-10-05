<?php
include 'banco.php';
session_start();
// Apenas usuários logados podem registrar uma venda
if (!isset($_SESSION['user_id'])) {
    exit('Acesso negado.');
}

// Pega os dados do formulário
$id_cliente = $_POST['id_cliente'] ?? null;
$metodo_pagamento = $_POST['metodo_pagamento'] ?? null;
$produtos_venda = $_POST['produtos'] ?? [];
$servicos_venda = $_POST['servicos'] ?? [];

// --- LÓGICA PARA CLIENTE OPCIONAL ---
if (empty($id_cliente)) {
    if ($metodo_pagamento === 'Fiado') {
        // Se for fiado e não tiver cliente, retorna um erro. A validação JS falhou ou foi burlada.
        header('Location: vendas.php?err=' . urlencode('Para vendas no fiado, é obrigatório selecionar um cliente.'));
        exit();
    }
    // Se não for fiado e não tiver cliente, usa o ID do "Consumidor Final"
    // !! IMPORTANTE: Troque o '1' pelo ID do seu cliente "Consumidor Final" !!
    $id_cliente = 1; 
}
// --- FIM DA LÓGICA ---

// Validação final
if (!$id_cliente || !$metodo_pagamento || (empty($produtos_venda) && empty($servicos_venda))) {
    header('Location: vendas.php?err=Dados da venda incompletos.');
    exit();
}

$valor_total_venda = 0;
// Inicia a transação. Ou tudo funciona, ou nada é salvo.
$conn->begin_transaction();

try {
    // --- CALCULA VALOR TOTAL E VERIFICA ESTOQUE DOS PRODUTOS ---
    foreach ($produtos_venda as $id_produto => $item) {
        $quantidade_vendida = (int)$item['quantidade'];

        // Pega os dados mais recentes do produto do banco e bloqueia a linha para a transação
        $stmt_check = $conn->prepare("SELECT preco_venda, quantidade_estoque FROM produtos WHERE id_produto = ? FOR UPDATE");
        $stmt_check->bind_param("i", $id_produto);
        $stmt_check->execute();
        $produto_db = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$produto_db || $quantidade_vendida > $produto_db['quantidade_estoque']) {
            throw new Exception("Estoque insuficiente para o produto ID: {$id_produto}.");
        }
        // Acumula o valor total usando o preço do banco de dados para segurança
        $valor_total_venda += $produto_db['preco_venda'] * $quantidade_vendida;
    }

    // --- SOMA O VALOR DOS SERVIÇOS PERSONALIZADOS AO TOTAL ---
    foreach ($servicos_venda as $servico) {
        if (isset($servico['preco']) && is_numeric($servico['preco'])) {
            $valor_total_venda += (float)$servico['preco'];
        }
    }
    
    // --- 1. INSERE O REGISTRO PRINCIPAL NA TABELA 'vendas' ---
    $sql_venda = "INSERT INTO vendas (id_cliente, valor_total, metodo_pagamento) VALUES (?, ?, ?)";
    $stmt_venda = $conn->prepare($sql_venda);
    $stmt_venda->bind_param("ids", $id_cliente, $valor_total_venda, $metodo_pagamento);
    $stmt_venda->execute();
    $id_nova_venda = $conn->insert_id;
    $stmt_venda->close();

    // --- 2. PROCESSA OS PRODUTOS (INSERE ITENS E ATUALIZA ESTOQUE) ---
    foreach ($produtos_venda as $id_produto => $item) {
        $quantidade_vendida = (int)$item['quantidade'];
        
        $stmt_price = $conn->prepare("SELECT preco_venda FROM produtos WHERE id_produto = ?");
        $stmt_price->bind_param("i", $id_produto);
        $stmt_price->execute();
        $preco_unitario = $stmt_price->get_result()->fetch_assoc()['preco_venda'];
        $stmt_price->close();

        $stmt_item = $conn->prepare("INSERT INTO venda_itens (id_venda, id_produto, quantidade, preco_unitario_venda) VALUES (?, ?, ?, ?)");
        $stmt_item->bind_param("iiid", $id_nova_venda, $id_produto, $quantidade_vendida, $preco_unitario);
        $stmt_item->execute();
        $stmt_item->close();

        $stmt_estoque = $conn->prepare("UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id_produto = ?");
        $stmt_estoque->bind_param("ii", $quantidade_vendida, $id_produto);
        $stmt_estoque->execute();
        $stmt_estoque->close();
    }

    // --- 3. PROCESSA OS SERVIÇOS (SALVA A DESCRIÇÃO E PREÇO PERSONALIZADOS) ---
    foreach ($servicos_venda as $servico) {
        $descricao = $servico['descricao'];
        $preco_cobrado = (float)$servico['preco'];
        
        $stmt_serv = $conn->prepare("INSERT INTO venda_servicos (id_venda, descricao, preco_cobrado) VALUES (?, ?, ?)");
        $stmt_serv->bind_param("isd", $id_nova_venda, $descricao, $preco_cobrado);
        $stmt_serv->execute();
        $stmt_serv->close();
    }

    // --- 4. ATUALIZA SALDO DO CLIENTE OU REGISTRA NO CAIXA ---
    if ($metodo_pagamento === 'Fiado') {
        $stmt_cliente = $conn->prepare("UPDATE clientes SET saldo_devedor = saldo_devedor + ? WHERE id_cliente = ?");
        $stmt_cliente->bind_param("di", $valor_total_venda, $id_cliente);
        $stmt_cliente->execute();
        $stmt_cliente->close();
    } else {
        // Se a venda foi paga (não é fiado), registra a entrada no caixa
        $descricao_caixa = "Recebimento da Venda #" . $id_nova_venda;
        $stmt_caixa = $conn->prepare("INSERT INTO fluxo_caixa (tipo, descricao, valor, id_venda_associada) VALUES ('Entrada', ?, ?, ?)");
        $stmt_caixa->bind_param("sdi", $descricao_caixa, $valor_total_venda, $id_nova_venda);
        $stmt_caixa->execute();
        $stmt_caixa->close();
    }
    
    // Se tudo deu certo até aqui, confirma as alterações no banco de dados
    $conn->commit();
    header('Location: relatorios.php?msg=Venda registrada com sucesso!');

} catch (Exception $e) {
    // Se qualquer etapa falhou, desfaz todas as operações
    $conn->rollback();
    header('Location: vendas.php?err=' . urlencode($e->getMessage()));
}

$conn->close();
?>