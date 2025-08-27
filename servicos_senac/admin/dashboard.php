<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Buscar estatísticas
try {
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de profissionais
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM profissionais");
    $totalProfissionais = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de serviços
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM servicos");
    $totalServicos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de orçamentos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orcamentos");
    $totalOrcamentos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Orçamentos por status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total 
        FROM orcamentos 
        GROUP BY status
    ");
    $orcamentosPorStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimos usuários cadastrados
    $stmt = $pdo->query("
        SELECT nome, email, data_cadastro 
        FROM usuarios 
        ORDER BY data_cadastro DESC 
        LIMIT 5
    ");
    $ultimosUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Serviços mais bem avaliados
    $stmt = $pdo->query("
        SELECT s.titulo, s.media_avaliacao, s.total_avaliacoes, u.nome as profissional
        FROM servicos s
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        ORDER BY s.media_avaliacao DESC, s.total_avaliacoes DESC
        LIMIT 5
    ");
    $servicosDestaque = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $totalUsuarios = $totalProfissionais = $totalServicos = $totalOrcamentos = 0;
    $orcamentosPorStatus = $ultimosUsuarios = $servicosDestaque = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Serviços SENAC</title>
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
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
            <a href="#" onclick="confirmarLogout()" title="Sair do sistema">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <h1>Dashboard</h1>
            </div>
            
            <div class="header-right">
                <div class="admin-user">
                    <span>Olá, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <div class="admin-content">
            <!-- Estatísticas Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalUsuarios) ?></h3>
                        <p>Total de Usuários</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalProfissionais) ?></h3>
                        <p>Profissionais</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalServicos) ?></h3>
                        <p>Serviços Cadastrados</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($totalOrcamentos) ?></h3>
                        <p>Orçamentos</p>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Tables -->
            <div class="dashboard-grid">
                <!-- Orçamentos por Status -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Orçamentos por Status</h3>
                    </div>
                    <div class="card-content">
                        <div class="status-chart">
                            <?php foreach ($orcamentosPorStatus as $status): ?>
                            <div class="status-item">
                                <span class="status-label"><?= ucfirst($status['status']) ?></span>
                                <span class="status-count"><?= $status['total'] ?></span>
                                <div class="status-bar">
                                    <div class="status-fill" style="width: <?= ($status['total'] / $totalOrcamentos) * 100 ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Últimos Usuários -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Últimos Usuários Cadastrados</h3>
                        <a href="usuarios.php" class="card-action">Ver todos</a>
                    </div>
                    <div class="card-content">
                        <div class="user-list">
                            <?php foreach ($ultimosUsuarios as $usuario): ?>
                            <div class="user-item">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                </div>
                                <div class="user-info">
                                    <h4><?= htmlspecialchars($usuario['nome']) ?></h4>
                                    <p><?= htmlspecialchars($usuario['email']) ?></p>
                                    <small><?= date('d/m/Y H:i', strtotime($usuario['data_cadastro'])) ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Serviços em Destaque -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Serviços Mais Bem Avaliados</h3>
                        <a href="servicos.php" class="card-action">Ver todos</a>
                    </div>
                    <div class="card-content">
                        <div class="service-list">
                            <?php foreach ($servicosDestaque as $servico): ?>
                            <div class="service-item">
                                <div class="service-info">
                                    <h4><?= htmlspecialchars($servico['titulo']) ?></h4>
                                    <p>Por: <?= htmlspecialchars($servico['profissional']) ?></p>
                                    <div class="service-rating">
                                        <div class="stars">
                                            <?php
                                            $rating = $servico['media_avaliacao'];
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } elseif ($i - 0.5 <= $rating) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <span>(<?= $servico['total_avaliacoes'] ?> avaliações)</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Ações Rápidas -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Ações Rápidas</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-actions">
                            <a href="usuarios.php?action=add" class="quick-action">
                                <i class="fas fa-user-plus"></i>
                                <span>Adicionar Usuário</span>
                            </a>
                            
                            <a href="categorias.php?action=add" class="quick-action">
                                <i class="fas fa-plus"></i>
                                <span>Nova Categoria</span>
                            </a>
                            
                            <a href="notificacoes.php?action=send" class="quick-action">
                                <i class="fas fa-bell"></i>
                                <span>Enviar Notificação</span>
                            </a>
                            
                            <a href="relatorios.php" class="quick-action">
                                <i class="fas fa-download"></i>
                                <span>Gerar Relatório</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script src="../js/main.js"></script>
    <script src="js/admin.js"></script>
    
    <script>
        function confirmarLogout() {
            if (confirm('Tem certeza que deseja sair do painel administrativo?')) {
                // Mostrar loading
                const link = event.target.closest('a');
                const originalText = link.innerHTML;
                link.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saindo...';
                link.style.pointerEvents = 'none';
                
                // Redirecionar após um breve delay
                setTimeout(() => {
                    window.location.href = '../logout.php';
                }, 500);
            }
        }
        
        // Adicionar confirmação de logout em todas as páginas do admin
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se há outros links de logout na página
            const logoutLinks = document.querySelectorAll('a[href*="logout"]');
            logoutLinks.forEach(link => {
                if (!link.onclick) {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        confirmarLogout();
                    });
                }
            });
        });
    </script>
</body>
</html>

