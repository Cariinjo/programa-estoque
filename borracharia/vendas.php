<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login.php?err=Você precisa fazer login');
    exit();
}
include 'banco.php';

// Buscar clientes
$clientes = $conn->query("SELECT id_cliente, nome FROM clientes ORDER BY nome ASC");

// Buscar produtos
$produtos = $conn->query("SELECT id_produto, nome, medida, preco_venda, quantidade_estoque FROM produtos WHERE quantidade_estoque > 0 ORDER BY nome ASC");

// --- NOVO: Buscar serviços ---
$servicos = $conn->query("SELECT id_servico, nome, preco_venda FROM servicos ORDER BY nome ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nova Venda</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .section-venda { border: 1px solid #ccc; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .section-venda h3 { margin-top: 0; }
        #total-venda { font-size: 1.5em; font-weight: bold; text-align: right; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container2">
        <h1>Registrar Nova Venda</h1>

        <form action="venda_action.php" method="POST" id="form-venda">
            <div class="section-venda">
                <h3>1. Selecione o Cliente</h3>
                <select name="id_cliente" id="id_cliente" required>
                    <option value="">-- Escolha um cliente --</option>
                    <?php while($cliente = $clientes->fetch_assoc()): ?>
                        <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['nome']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="section-venda">
                <h3>2. Adicione Itens à Venda</h3>
                <select id="produto-select">
                    <option value="">-- Adicionar um Produto --</option>
                    <?php while($produto = $produtos->fetch_assoc()): ?>
                        <option value="<?php echo $produto['id_produto']; ?>" data-preco="<?php echo $produto['preco_venda']; ?>" data-estoque="<?php echo $produto['quantidade_estoque']; ?>" data-nome="<?php echo htmlspecialchars($produto['nome'] . ' (' . $produto['medida'] . ')'); ?>">
                            <?php echo htmlspecialchars($produto['nome'] . ' (' . $produto['medida'] . ')'); ?> - Estoque: <?php echo $produto['quantidade_estoque']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="number" id="quantidade" placeholder="Qtd" min="1" style="width: 60px;">
                <button type="button" onclick="adicionarProduto()">Adicionar Produto</button>
                <hr style="margin: 15px 0;">
                <select id="servico-select">
                     <option value="">-- Adicionar um Serviço --</option>
                    <?php while($servico = $servicos->fetch_assoc()): ?>
                        <option value="<?php echo $servico['id_servico']; ?>" data-preco="<?php echo $servico['preco_venda']; ?>" data-nome="<?php echo htmlspecialchars($servico['nome']); ?>">
                            <?php echo htmlspecialchars($servico['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="button" onclick="adicionarServico()">Adicionar Serviço</button>
            </div>
            
            <div class="section-venda">
                <h3>3. Itens da Venda</h3>
                <table id="carrinho" width="100%">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Tipo</th>
                            <th>Qtd/Preço</th>
                            <th>Subtotal</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div id="total-venda">Total: R$ 0,00</div>
            </div>
            
            <div id="itens-hidden"></div>

            <div class="section-venda">
                <h3>4. Pagamento</h3>
                <select name="metodo_pagamento" id="metodo_pagamento" required>
                    <option value="Dinheiro">Dinheiro</option>
                    <option value="Cartão de Crédito">Cartão de Crédito</option>
                    <option value="Cartão de Débito">Cartão de Débito</option>
                    <option value="PIX">PIX</option>
                    <option value="Fiado">Fiado (Anotar na conta)</option>
                </select>
            </div>

            <button type="submit" style="padding: 15px; font-size: 1.2em;">Finalizar Venda</button>
        </form>
    </div>

<script>
    let itensCarrinho = {};

    function adicionarProduto() {
        const select = document.getElementById('produto-select');
        const option = select.options[select.selectedIndex];
        const id = 'p_' + option.value; // 'p' para produto
        const qtdInput = document.getElementById('quantidade');
        const quantidade = parseInt(qtdInput.value);

        if (!option.value || !quantidade || quantidade <= 0) return alert('Selecione um produto e a quantidade.');
        if (quantidade > parseInt(option.getAttribute('data-estoque'))) return alert('Estoque insuficiente.');
        if (itensCarrinho[id]) return alert('Produto já adicionado.');

        const nome = option.getAttribute('data-nome');
        const preco = parseFloat(option.getAttribute('data-preco'));
        const subtotal = quantidade * preco;
        
        const tabelaBody = document.querySelector('#carrinho tbody');
        const newRow = tabelaBody.insertRow();
        newRow.setAttribute('id', 'row-' + id);
        newRow.innerHTML = `
            <td>${nome}</td>
            <td>Produto</td>
            <td>${quantidade} x R$ ${preco.toFixed(2).replace('.', ',')}</td>
            <td>R$ ${subtotal.toFixed(2).replace('.', ',')}</td>
            <td><button type="button" onclick="removerItem('${id}', ${subtotal})">Remover</button></td>
        `;
        
        document.getElementById('itens-hidden').innerHTML += `
            <div id="hidden-item-${id}">
                <input type="hidden" name="produtos[${option.value}][quantidade]" value="${quantidade}">
            </div>
        `;
        itensCarrinho[id] = true;
        atualizarTotal(subtotal);
        qtdInput.value = ''; select.selectedIndex = 0;
    }

    // NOVA FUNÇÃO
    function adicionarServico() {
        const select = document.getElementById('servico-select');
        const option = select.options[select.selectedIndex];
        const id = 's_' + option.value; // 's' para serviço
        
        if (!option.value) return alert('Selecione um serviço.');
        if (itensCarrinho[id]) return alert('Serviço já adicionado.');

        const nome = option.getAttribute('data-nome');
        const preco = parseFloat(option.getAttribute('data-preco'));
        
        const tabelaBody = document.querySelector('#carrinho tbody');
        const newRow = tabelaBody.insertRow();
        newRow.setAttribute('id', 'row-' + id);
        newRow.innerHTML = `
            <td>${nome}</td>
            <td>Serviço</td>
            <td>R$ ${preco.toFixed(2).replace('.', ',')}</td>
            <td>R$ ${preco.toFixed(2).replace('.', ',')}</td>
            <td><button type="button" onclick="removerItem('${id}', ${preco})">Remover</button></td>
        `;

        document.getElementById('itens-hidden').innerHTML += `
            <div id="hidden-item-${id}">
                <input type="hidden" name="servicos[]" value="${option.value}">
            </div>
        `;
        itensCarrinho[id] = true;
        atualizarTotal(preco);
        select.selectedIndex = 0;
    }

    function removerItem(id, subtotal) {
        document.getElementById('row-' + id).remove();
        document.getElementById('hidden-item-' + id).remove();
        delete itensCarrinho[id];
        atualizarTotal(-subtotal);
    }

    function atualizarTotal(valor) {
        const totalDiv = document.getElementById('total-venda');
        let totalAtual = parseFloat(totalDiv.innerText.replace('Total: R$ ', '').replace('.', '').replace(',', '.')) || 0;
        totalDiv.innerText = `Total: R$ ${(totalAtual + valor).toFixed(2).replace('.', ',')}`;
    }
</script>
</body>
</html>