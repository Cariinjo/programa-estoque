<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

try {
    // Buscar orçamentos
    $stmt = $pdo->query("
        SELECT 
            o.*,
            s.titulo as servico_titulo,
            u_cliente.nome as cliente_nome,
            u_profissional.nome as profissional_nome
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN usuarios u_cliente ON o.id_cliente = u_cliente.id_usuario
        JOIN profissionais p ON o.id_profissional = p.id_profissional
        JOIN usuarios u_profissional ON p.id_usuario = u_profissional.id_usuario
        ORDER BY o.data_solicitacao DESC
        LIMIT 50
    ");
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as total
        FROM orcamentos 
        GROUP BY status
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar orçamentos: " . $e->getMessage());
    $orcamentos = [];
    $stats = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Orçamentos - Admin SENAC</title>
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
                <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                <li><a href="orcamentos.php" class="active"><i class="fas fa-file-invoice"></i> Orçamentos</a></li>
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
                <h1>Gerenciar Orçamentos</h1>
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
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['pendente'] ?? 0 ?></h3>
                        <p>Pendentes</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['aceito'] ?? 0 ?></h3>
                        <p>Aceitos</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['recusado'] ?? 0 ?></h3>
                        <p>Recusados</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['concluido'] ?? 0 ?></h3>
                        <p>Concluídos</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Orçamentos -->
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Serviço</th>
                            <th>Cliente</th>
                            <th>Profissional</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orcamentos)): ?>
                            <?php foreach ($orcamentos as $orcamento): ?>
                                <tr>
                                    <td><?= $orcamento['id_orcamento'] ?></td>
                                    <td><?= htmlspecialchars($orcamento['servico_titulo']) ?></td>
                                    <td><?= htmlspecialchars($orcamento['cliente_nome']) ?></td>
                                    <td><?= htmlspecialchars($orcamento['profissional_nome']) ?></td>
                                    <td>
                                        <?php if ($orcamento['valor_proposto']): ?>
                                            R$ <?= number_format($orcamento['valor_proposto'], 2, ',', '.') ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch ($orcamento['status']) {
                                            case 'pendente':
                                                $statusClass = 'badge-warning';
                                                $statusText = 'Pendente';
                                                break;
                                            case 'aceito':
                                                $statusClass = 'badge-success';
                                                $statusText = 'Aceito';
                                                break;
                                            case 'recusado':
                                                $statusClass = 'badge-danger';
                                                $statusText = 'Recusado';
                                                break;
                                            case 'concluido':
                                                $statusClass = 'badge-info';
                                                $statusText = 'Concluído';
                                                break;
                                            case 'cancelado':
                                                $statusClass = 'badge-secondary';
                                                $statusText = 'Cancelado';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></td>
                                    <td class="actions">
                                        <button class="btn-icon" onclick="viewBudget(<?= $orcamento['id_orcamento'] ?>)" title="Ver detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Nenhum orçamento encontrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="js/admin.js"></script>
    <script>
        function viewBudget(budgetId) {
            alert('Ver detalhes do orçamento ID: ' + budgetId);
        }
    </script>
</body>
</html>

