<?php
session_start();
// Se o usuário não estiver logado, redireciona
if (!isset($_SESSION['user_id'])) {
    header('location: login.php?err=' . urlencode('Você precisa fazer login'));
    exit();
}

include 'banco.php';

// Busca clientes para o dropdown
$clientes = $conn->query("SELECT id_cliente, nome FROM clientes ORDER BY nome ASC");

// Busca produtos para o dropdown
$produtos = $conn->query("SELECT id_produto, nome, medida, preco_venda, quantidade_estoque FROM produtos WHERE quantidade_estoque > 0 ORDER BY nome ASC");
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
        .servico-inputs { display: flex; gap: 10px; align-items: center; }
        /* Estilo para o asterisco de campo obrigatório */
        #cliente-label.required::after { content: " *"; color: red; }
    </style>
</head>
<body>
    <div class="container2">
        <h1>Registrar Nova Venda</h1>

        <?php if (isset($_GET['err'])): ?>
            <p style="color: red; text-align: center;"><?php echo htmlspecialchars($_GET['err']); ?></p>
        <?php endif; ?>

        <form action="venda_action.php" method="POST" id="form-venda">
            <div class="section-venda">
                <h3 id="cliente-label">1. Selecione o Cliente (Opcional)</h3>
                <select name="id_cliente" id="id_cliente">
                    <option value="">-- Consumidor Final (Padrão) --</option>
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

                <div class="servico-inputs">
                    <input type="text" id="servico-descricao" placeholder="Descrição do Serviço Avulso" style="flex-grow: 1;">
                    <input type="number" id="servico-preco" placeholder="Preço (R$)" step="0.01" min="0">
                    <button type="button" onclick="adicionarServico()">Adicionar Serviço</button>
                </div>
            </div>
            
            <div class="section-venda">
                <h3>3. Itens da Venda</h3>
                <table id="carrinho" width="100%">
                    <thead><tr><th>Item</th><th>Tipo</th><th>Qtd/Preço</th><th>Subtotal</th><th>Ação</th></tr></thead>
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
            <br><br>
            <button type="button" onclick="location.href='index.php'">Cancelar</button>
        </form>
    </div>

<script>
    let itensCarrinho = {};
    let servicoContador = 0; // Contador para dar IDs únicos aos serviços

    function adicionarProduto() {
        const select = document.getElementById('produto-select');
        const option = select.options[select.selectedIndex];
        const id = 'p_' + option.value;
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
            <td>${htmlspecialchars(nome)}</td>
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
        qtdInput.value = ''; 
        select.selectedIndex = 0;
    }

    function adicionarServico() {
        const descricaoInput = document.getElementById('servico-descricao');
        const precoInput = document.getElementById('servico-preco');
        const descricao = descricaoInput.value.trim();
        const preco = parseFloat(precoInput.value);

        if (!descricao || !preco || preco <= 0) {
            return alert('Por favor, preencha a descrição e um preço válido para o serviço.');
        }

        servicoContador++;
        const id = 's_' + servicoContador;
        
        const tabelaBody = document.querySelector('#carrinho tbody');
        const newRow = tabelaBody.insertRow();
        newRow.setAttribute('id', 'row-' + id);
        newRow.innerHTML = `
            <td>${htmlspecialchars(descricao)}</td>
            <td>Serviço</td>
            <td>R$ ${preco.toFixed(2).replace('.', ',')}</td>
            <td>R$ ${preco.toFixed(2).replace('.', ',')}</td>
            <td><button type="button" onclick="removerItem('${id}', ${preco})">Remover</button></td>
        `;

        document.getElementById('itens-hidden').innerHTML += `
            <div id="hidden-item-${id}">
                <input type="hidden" name="servicos[${servicoContador}][descricao]" value="${htmlspecialchars(descricao)}">
                <input type="hidden" name="servicos[${servicoContador}][preco]" value="${preco}">
            </div>
        `;
        
        itensCarrinho[id] = true;
        atualizarTotal(preco);
        
        descricaoInput.value = '';
        precoInput.value = '';
    }

    function removerItem(id, subtotal) {
        document.getElementById('row-' + id).remove();
        document.getElementById('hidden-item-' + id).remove();
        delete itensCarrinho[id];
        atualizarTotal(-subtotal);
    }

    function atualizarTotal(valor) {
        const totalDiv = document.getElementById('total-venda');
        let totalAtual = parseFloat(totalDiv.innerText.replace('Total: R$ ', '').replace(/\./g, '').replace(',', '.')) || 0;
        totalDiv.innerText = `Total: R$ ${(totalAtual + valor).toFixed(2).replace('.', ',')}`;
    }

    function htmlspecialchars(str) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }
    
    // Script para tornar o cliente obrigatório apenas para pagamento 'Fiado'
    document.addEventListener('DOMContentLoaded', () => {
        const metodoPagamentoSelect = document.getElementById('metodo_pagamento');
        const clienteSelect = document.getElementById('id_cliente');
        const clienteLabel = document.getElementById('cliente-label');

        metodoPagamentoSelect.addEventListener('change', function() {
            if (this.value === 'Fiado') {
                clienteSelect.required = true;
                clienteLabel.textContent = '1. Selecione o Cliente (Obrigatório)';
                clienteLabel.classList.add('required');
            } else {
                clienteSelect.required = false;
                clienteLabel.textContent = '1. Selecione o Cliente (Opcional)';
                clienteLabel.classList.remove('required');
            }
        });
    });

    document.getElementById('form-venda').addEventListener('submit', function(e){
        if (Object.keys(itensCarrinho).length === 0) {
            e.preventDefault();
            alert('Você precisa adicionar pelo menos um produto ou serviço à venda.');
        }
    });
</script>
</body>
</html>