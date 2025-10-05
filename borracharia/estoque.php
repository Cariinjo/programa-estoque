<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('location: login.php?err=' . urlencode('Você precisa fazer login'));
    exit();
}
include 'banco.php';

// --- Lógica dos Filtros Avançados ---
$filtro_nome = $_GET['filtro_nome'] ?? '';
$filtro_marca = $_GET['filtro_marca'] ?? '';

$where_clauses = [];
$params = [];
$types = '';

if (!empty($filtro_nome)) {
    $where_clauses[] = "nome LIKE ?";
    $params[] = "%" . $filtro_nome . "%";
    $types .= 's';
}
if (!empty($filtro_marca)) {
    $where_clauses[] = "marca LIKE ?";
    $params[] = "%" . $filtro_marca . "%";
    $types .= 's';
}

$sql = "SELECT * FROM produtos";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY nome ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estoque (Editável)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filtro-avancado { background-color: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; }
        .editable { cursor: pointer; background-color: #f9f9f9; }
        .editable:hover { background-color: #e8f0fe; }
    </style>
</head>
<body>
    <div class="container2">
        <h1>Estoque de Produtos (Editável)</h1>
        
        <?php if ($_SESSION['user_level'] == 'Admin'): ?>
            <div class="botoes-acao">
                <button onclick="location.href='produto_cadastrar.php'">Adicionar Novo Produto</button>
            </div>
        <?php endif; ?>

        <div class="filtro-avancado">
            <strong>Filtrar por:</strong>
            <form action="estoque.php" method="GET" style="display: flex; gap: 15px;">
                <input type="text" name="filtro_nome" placeholder="Nome do produto..." value="<?php echo htmlspecialchars($filtro_nome); ?>">
                <input type="text" name="filtro_marca" placeholder="Marca..." value="<?php echo htmlspecialchars($filtro_marca); ?>">
                <button type="submit">Filtrar</button>
                <a href="estoque.php">Limpar</a>
            </form>
        </div>

        <table id="tabelaProdutos" data-table="produtos">
            <thead>
                <tr>
                    <th>ID</th><th>Nome</th><th>Marca</th><th>Medida</th><th>Estoque</th><th>Preço Venda</th>
                    <?php if ($_SESSION['user_level'] == 'Admin') echo '<th>Ações</th>'; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id_produto']; ?></td>
                            <td class="editable" data-id="<?php echo $row['id_produto']; ?>" data-column="nome"><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td class="editable" data-id="<?php echo $row['id_produto']; ?>" data-column="marca"><?php echo htmlspecialchars($row['marca']); ?></td>
                            <td class="editable" data-id="<?php echo $row['id_produto']; ?>" data-column="medida"><?php echo htmlspecialchars($row['medida']); ?></td>
                            <td class="editable" data-id="<?php echo $row['id_produto']; ?>" data-column="quantidade_estoque"><?php echo $row['quantidade_estoque']; ?></td>
                            <td class="editable" data-id="<?php echo $row['id_produto']; ?>" data-column="preco_venda">R$ <?php echo number_format($row['preco_venda'], 2, ',', '.'); ?></td>
                            
                            <?php if ($_SESSION['user_level'] == 'Admin'): ?>
                                <td>
                                    <a href="produto_atualizar.php?id=<?php echo $row['id_produto']; ?>">Editar</a> | 
                                    <a href="produto_deletar.php?id=<?php echo $row['id_produto']; ?>" onclick='return confirm("Tem certeza?")'>Excluir</a>
                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">Nenhum produto encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <br>
        <button onclick="location.href='index.php'">Voltar ao Início</button>
    </div>

<script>
// O script de edição inline permanece o mesmo
// Apenas executa o script de edição se for Admin
<?php if ($_SESSION['user_level'] == 'Admin'): ?>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('tabelaProdutos');
    
    table.addEventListener('click', (event) => {
        if (event.target.classList.contains('editable')) {
            makeEditable(event.target);
        }
    });

    function makeEditable(cell) {
        if (cell.querySelector('input')) return;

        const originalValue = cell.textContent.replace('R$ ', '').replace(/\./g, '').replace(',', '.').trim();
        const input = document.createElement('input');
        input.type = 'text';
        input.value = originalValue;
        input.style.width = (cell.clientWidth - 10) + 'px';
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();

        const save = () => {
            const newValue = input.value;
            if (newValue === originalValue) {
                location.reload();
                return;
            }
            sendUpdate(cell, newValue, originalValue);
        };

        input.addEventListener('blur', save);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                save();
            } else if (e.key === 'Escape') {
                location.reload();
            }
        });
    }

    function sendUpdate(cell, value, originalValue) {
        const id = cell.dataset.id;
        const column = cell.dataset.column;
        const table = cell.closest('table').dataset.table;

        const formData = new FormData();
        formData.append('id', id);
        formData.append('column', column);
        formData.append('value', value);
        formData.append('table', table);

        fetch('inline_edit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            } else {
                alert(data.message);
                location.reload();
            }
        })
        .catch(error => {
            alert('Erro de conexão.');
            location.reload();
        });
    }
});
<?php endif; ?>
</script>

</body>
</html>