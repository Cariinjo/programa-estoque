<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Parâmetros de busca e filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    // Buscar categorias para filtro
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome_categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir query de busca
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(s.titulo LIKE ? OR s.descricao LIKE ? OR u.nome LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($categoria > 0) {
        $whereConditions[] = "s.id_categoria = ?";
        $params[] = $categoria;
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Buscar serviços
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.nome_categoria,
            u.nome as nome_profissional,
            p.area_atuacao
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        $whereClause
        ORDER BY s.data_criacao DESC
        LIMIT $perPage OFFSET $offset
    ");
    $stmt->execute($params);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total para paginação
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        $whereClause
    ");
    $stmt->execute($params);
    $totalServicos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalServicos / $perPage);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar serviços: " . $e->getMessage());
    $servicos = [];
    $categorias = [];
    $totalServicos = 0;
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Serviços - Admin SENAC</title>
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
                <li><a href="servicos.php" class="active"><i class="fas fa-cogs"></i> Serviços</a></li>
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
                <h1>Gerenciar Serviços</h1>
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
                <div class="filters">
                    <form method="GET" class="filter-form">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Buscar serviços..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="filter-select">
                            <select name="categoria">
                                <option value="">Todas as categorias</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id_categoria'] ?>" <?= $categoria == $cat['id_categoria'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nome_categoria']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </form>
                </div>
                
                <div class="content-stats">
                    <span>Total: <?= number_format($totalServicos) ?> serviços</span>
                </div>
            </div>
            
            <!-- Tabela de Serviços -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Profissional</th>
                            <th>Preço</th>
                            <th>Avaliação</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($servicos)): ?>
                            <?php foreach ($servicos as $servico): ?>
                                <tr>
                                    <td><?= $servico['id_servico'] ?></td>
                                    <td>
                                        <div class="service-title">
                                            <?= htmlspecialchars($servico['titulo']) ?>
                                            <small><?= htmlspecialchars(substr($servico['descricao'], 0, 50)) ?>...</small>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($servico['nome_categoria']) ?></td>
                                    <td>
                                        <div class="professional-info">
                                            <?= htmlspecialchars($servico['nome_profissional']) ?>
                                            <small><?= htmlspecialchars($servico['area_atuacao']) ?></small>
                                        </div>
                                    </td>
                                    <td>R$ <?= number_format($servico['preco'], 2, ',', '.') ?></td>
                                    <td>
                                        <div class="rating">
                                            <span class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $servico['media_avaliacao'] ? 'active' : '' ?>"></i>
                                                <?php endfor; ?>
                                            </span>
                                            <small>(<?= $servico['total_avaliacoes'] ?>)</small>
                                        </div>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($servico['data_criacao'])) ?></td>
                                    <td class="actions">
                                        <a href="../servico-detalhes.php?id=<?= $servico["id_servico"] ?>" 
                                           class="btn-icon" title="Ver detalhes" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="editar-servico.php?id=<?= $servico["id_servico"] ?>" 
                                           class="btn-icon" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn-icon btn-danger" 
                                                onclick="deleteService(<?= $servico["id_servico"] ?>)" 
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum serviço encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&categoria=<?= $categoria ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&categoria=<?= $categoria ?>" 
                           class="page-link <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&categoria=<?= $categoria ?>" class="page-link">
                            Próxima <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/admin.js"></script>
    <script>
        function deleteService(serviceId) {
            if (confirm("Tem certeza que deseja excluir este serviço? Isso removerá todos os orçamentos associados.")) {
                fetch("../api/delete-service.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ service_id: serviceId }),
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
                    console.error("Erro ao excluir serviço:", error);
                    alert("Erro ao excluir serviço. Verifique o console para mais detalhes.");
                });
            }
        }
    </script>
</body>
</html>

