<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Parâmetros de busca e paginação
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    // Construir query de busca
    $whereClause = '';
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE u.nome LIKE ? OR u.email LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Buscar usuários
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            p.id_profissional,
            p.area_atuacao
        FROM usuarios u
        LEFT JOIN profissionais p ON u.id_usuario = p.id_usuario
        $whereClause
        ORDER BY u.data_cadastro DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total para paginação
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios u $whereClause");
    $stmt->execute($params);
    $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalUsuarios / $perPage);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar usuários: " . $e->getMessage());
    $usuarios = [];
    $totalUsuarios = 0;
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin SENAC</title>
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
                <li><a href="usuarios.php" class="active"><i class="fas fa-users"></i> Usuários</a></li>
                <li><a href="profissionais.php"><i class="fas fa-briefcase"></i> Profissionais</a></li>
                <li><a href="servicos.php"><i class="fas fa-cogs"></i> Serviços</a></li>
                <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
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
                <h1>Gerenciar Usuários</h1>
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
            <!-- Filtros e Busca -->
            <div class="content-header">
                <div class="search-box">
                    <form method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Buscar por nome ou email..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="content-stats">
                    <span>Total: <?= number_format($totalUsuarios) ?> usuários</span>
                </div>
            </div>
            
            <!-- Tabela de Usuários -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Tipo</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= $usuario['id_usuario'] ?></td>
                                    <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                    <td><?= htmlspecialchars($usuario['email']) ?></td>
                                    <td><?= htmlspecialchars($usuario['telefone'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($usuario['id_profissional']): ?>
                                            <span class="badge badge-success">Profissional</span>
                                            <small><?= htmlspecialchars($usuario['area_atuacao']) ?></small>
                                        <?php else: ?>
                                            <span class="badge badge-primary">Cliente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($usuario['data_cadastro'])) ?></td>
                                    <td class="actions">
                                        <button class="btn-icon" onclick="viewUser(<?= $usuario['id_usuario'] ?>)" title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-icon btn-danger" onclick="deleteUser(<?= $usuario['id_usuario'] ?>)" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Nenhum usuário encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                           class="page-link <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="page-link">
                            Próxima <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/admin.js"></script>
    <script>
        function viewUser(userId) {
            alert('Visualizar usuário ID: ' + userId);
        }
        
        function deleteUser(userId) {
            if (confirm("Tem certeza que deseja excluir este usuário? Isso removerá todos os dados associados (serviços, orçamentos, mensagens, etc.).")) {
                fetch("../api/delete-user.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ user_id: userId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Recarrega a página para refletir a mudança
                    } else {
                        alert("Erro: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Erro ao excluir usuário:", error);
                    alert("Erro ao excluir usuário. Verifique o console para mais detalhes.");
                });
            }
        }
    </script>
</body>
</html>

