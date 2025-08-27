<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $nome = sanitize($_POST['nome_categoria']);
                    $descricao = sanitize($_POST['descricao']);
                    
                    $stmt = $pdo->prepare("INSERT INTO categorias (nome_categoria, descricao) VALUES (?, ?)");
                    $stmt->execute([$nome, $descricao]);
                    $message = 'Categoria adicionada com sucesso!';
                    break;
                    
                case 'edit':
                    $id = (int)$_POST['id_categoria'];
                    $nome = sanitize($_POST['nome_categoria']);
                    $descricao = sanitize($_POST['descricao']);
                    
                    $stmt = $pdo->prepare("UPDATE categorias SET nome_categoria = ?, descricao = ? WHERE id_categoria = ?");
                    $stmt->execute([$nome, $descricao, $id]);
                    $message = 'Categoria atualizada com sucesso!';
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id_categoria'];
                    
                    // Verificar se há serviços nesta categoria
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM servicos WHERE id_categoria = ?");
                    $stmt->execute([$id]);
                    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                    
                    if ($total > 0) {
                        $message = 'Não é possível excluir esta categoria pois há serviços vinculados a ela.';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id_categoria = ?");
                        $stmt->execute([$id]);
                        $message = 'Categoria excluída com sucesso!';
                    }
                    break;
            }
        } catch (PDOException $e) {
            $message = 'Erro: ' . $e->getMessage();
        }
    }
}

try {
    // Buscar categorias com estatísticas
    $stmt = $pdo->query("
        SELECT 
            c.*,
            COUNT(s.id_servico) as total_servicos
        FROM categorias c
        LEFT JOIN servicos s ON c.id_categoria = s.id_categoria
        GROUP BY c.id_categoria
        ORDER BY c.nome_categoria
    ");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - Admin SENAC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> Admin SENAC</h2>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                <li><a href="profissionais.php"><i class="fas fa-briefcase"></i> Profissionais</a></li>
                <li><a href="servicos.php"><i class="fas fa-cogs"></i> Serviços</a></li>
                <li><a href="categorias.php" class="active"><i class="fas fa-tags"></i> Categorias</a></li>
                <li><a href="orcamentos.php"><i class="fas fa-file-invoice"></i> Orçamentos</a></li>
                <li><a href="avaliacoes.php"><i class="fas fa-star"></i> Avaliações</a></li>
                <li><a href="notificacoes.php"><i class="fas fa-bell"></i> Notificações</a></li>
                <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                <li><a href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <h1>Gerenciar Categorias</h1>
            </div>
            
            <div class="header-right">
                <div class="admin-user">
                    <span>Olá, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="admin-content">
            <?php if ($message): ?>
                <div class="alert <?= strpos($message, 'sucesso') !== false ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulário para Nova Categoria -->
            <div class="form-section">
                <h2><i class="fas fa-plus"></i> Nova Categoria</h2>
                <form method="POST" class="category-form">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="nome_categoria">Nome da Categoria</label>
                        <input type="text" id="nome_categoria" name="nome_categoria" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adicionar Categoria
                    </button>
                </form>
            </div>
            
            <!-- Lista de Categorias -->
            <div class="table-section">
                <h2><i class="fas fa-list"></i> Categorias Existentes</h2>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Total de Serviços</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <tr>
                                        <td><?= $categoria['id_categoria'] ?></td>
                                        <td><?= htmlspecialchars($categoria['nome_categoria']) ?></td>
                                        <td><?= htmlspecialchars($categoria['descricao'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= $categoria['total_servicos'] ?> serviços
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <button class="btn-icon" onclick="editCategory(<?= $categoria['id_categoria'] ?>, '<?= htmlspecialchars($categoria['nome_categoria']) ?>', '<?= htmlspecialchars($categoria['descricao'] ?? '') ?>')" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($categoria['total_servicos'] == 0): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id_categoria" value="<?= $categoria['id_categoria'] ?>">
                                                    <button type="submit" class="btn-icon btn-danger" title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhuma categoria cadastrada</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Edição -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Categoria</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_categoria" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_nome">Nome da Categoria</label>
                    <input type="text" id="edit_nome" name="nome_categoria" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_descricao">Descrição</label>
                    <textarea id="edit_descricao" name="descricao" rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/admin.js"></script>
    <script>
        function editCategory(id, nome, descricao) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_descricao').value = descricao;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>

