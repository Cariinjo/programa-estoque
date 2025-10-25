<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

try {
    // Buscar informações do usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: logout.php');
        exit;
    }
    
    // Verificar se é profissional
    $isProfessional = false;
    $professional = null;
    if ($userType === 'profissional') {
        $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $professional = $stmt->fetch(PDO::FETCH_ASSOC);
        $isProfessional = true;
    }
    
    // Estatísticas do cliente
    $stats = [];
    
    // Orçamentos recebidos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE id_cliente = ?");
    $stmt->execute([$userId]);
    $stats['orcamentos_recebidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Serviços contratados (aceitos)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE id_cliente = ? AND status = 'aceito'");
    $stmt->execute([$userId]);
    $stats['servicos_contratados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Serviços concluídos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE id_cliente = ? AND status = 'concluido'");
    $stmt->execute([$userId]);
    $stats['servicos_concluidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Notificações não lidas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE id_usuario_destino = ? AND lida = FALSE");
    $stmt->execute([$userId]);
    $stats['notificacoes_nao_lidas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Últimos orçamentos
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, s.preco, 
               u.nome as profissional_nome, p.area_atuacao
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN profissionais prof ON o.id_profissional = prof.id_profissional
        JOIN usuarios u ON prof.id_usuario = u.id_usuario
        WHERE o.id_cliente = ?
        ORDER BY o.data_solicitacao DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentQuotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Notificações recentes
    $stmt = $pdo->prepare("
        SELECT * FROM notificacoes 
        WHERE id_usuario_destino = ? 
        ORDER BY data_criacao DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
    $error = "Erro ao carregar dashboard.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= htmlspecialchars($user['nome']) ?> - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .dashboard-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .dashboard-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-icon.purple { background: linear-gradient(45deg, #6c5ce7, #a29bfe); }
        .stat-icon.green { background: linear-gradient(45deg, #00b894, #55efc4); }
        .stat-icon.orange { background: linear-gradient(45deg, #e17055, #fd79a8); }
        .stat-icon.blue { background: linear-gradient(45deg, #0984e3, #74b9ff); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .sidebar-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .section-header h3 {
            margin: 0;
            color: #2d3436;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-content {
            padding: 1.5rem;
        }
        
        .quote-item {
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .quote-item:hover {
            border-color: #6c5ce7;
        }
        
        .quote-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .quote-title {
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.25rem;
        }
        
        .quote-professional {
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .quote-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pendente {
            background: #ffeaa7;
            color: #d63031;
        }
        
        .status-aceito {
            background: #d1f2eb;
            color: #00b894;
        }
        
        .status-concluido {
            background: #d1f2eb;
            color: #00b894;
        }
        
        .status-recusado {
            background: #fab1a0;
            color: #e17055;
        }
        
        .quote-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
        }
        
        .quote-price {
            font-weight: bold;
            color: #6c5ce7;
        }
        
        .quote-date {
            color: #636e72;
            font-size: 0.8rem;
        }
        
        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: #e3f2fd;
            border-left: 4px solid #6c5ce7;
        }
        
        .notification-message {
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        
        .notification-time {
            color: #636e72;
            font-size: 0.8rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: transform 0.3s ease;
            text-align: center;
            justify-content: center;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #636e72;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-header h1 {
                font-size: 1.5rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="dashboard-container">
            <!-- Header do Dashboard -->
            <div class="dashboard-header">
                <h1><i class="fas fa-tachometer-alt"></i> Olá, <?= htmlspecialchars($user['nome']) ?>!</h1>
                <p>Bem-vindo ao seu dashboard. Aqui você pode acompanhar seus serviços e orçamentos.</p>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-number"><?= $stats['orcamentos_recebidos'] ?></div>
                    <div class="stat-label">Orçamentos Recebidos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-number"><?= $stats['servicos_contratados'] ?></div>
                    <div class="stat-label">Serviços Contratados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $stats['servicos_concluidos'] ?></div>
                    <div class="stat-label">Serviços Concluídos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-number"><?= $stats['notificacoes_nao_lidas'] ?></div>
                    <div class="stat-label">Notificações</div>
                </div>
            </div>
            
            <!-- Conteúdo Principal -->
            <div class="dashboard-content">
                <!-- Orçamentos Recentes -->
                <div class="main-content">
                    <div class="section-header">
                        <h3><i class="fas fa-history"></i> Últimos Orçamentos Recebidos</h3>
                    </div>
                    <div class="section-content">
                        <?php if (empty($recentQuotes)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file-invoice"></i>
                                <p>Você ainda não recebeu nenhum orçamento.</p>
                                <a href="servicos.php" class="action-button" style="display: inline-flex; margin-top: 1rem;">
                                    <i class="fas fa-search"></i> Buscar Serviços
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentQuotes as $quote): ?>
                                <div class="quote-item">
                                    <div class="quote-header">
                                        <div>
                                            <div class="quote-title"><?= htmlspecialchars($quote['servico_titulo']) ?></div>
                                            <div class="quote-professional">Por: <?= htmlspecialchars($quote['profissional_nome']) ?> - <?= htmlspecialchars($quote['area_atuacao']) ?></div>
                                        </div>
                                        <span class="quote-status status-<?= $quote['status'] ?>">
                                            <?= ucfirst($quote['status']) ?>
                                        </span>
                                    </div>
                                    <div class="quote-details">
                                        <span class="quote-price">R$ <?= number_format($quote['valor_proposto'] ?: $quote['preco'], 2, ',', '.') ?></span>
                                        <span class="quote-date"><?= date('d/m/Y', strtotime($quote['data_solicitacao'])) ?></span>
                                    </div>
                                    <?php if ($quote['status'] === 'aceito'): ?>
                                        <div style="margin-top: 0.5rem;">
                                            <a href="chat.php?id=<?= $quote['id_orcamento'] ?>" class="action-button" style="display: inline-flex; font-size: 0.8rem; padding: 0.5rem 1rem;">
                                                <i class="fas fa-comments"></i> Conversar
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div style="text-align: center; margin-top: 1rem;">
                                <a href="meus-orcamentos.php" class="action-button" style="display: inline-flex;">
                                    <i class="fas fa-list"></i> Ver Todos os Orçamentos Recebidos
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Notificações -->
                <div class="sidebar-content">
                    <div class="section-header">
                        <h3><i class="fas fa-bell"></i> Notificações</h3>
                    </div>
                    <div class="section-content" style="padding: 0;">
                        <?php if (empty($recentNotifications)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bell-slash"></i>
                                <p>Nenhuma notificação.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentNotifications as $notification): ?>
                                <div class="notification-item <?= !$notification['lida'] ? 'unread' : '' ?>">
                                    <div class="notification-message"><?= htmlspecialchars($notification['mensagem']) ?></div>
                                    <div class="notification-time"><?= date('d/m/Y H:i', strtotime($notification['data_criacao'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div style="padding: 1rem; text-align: center; border-top: 1px solid #eee;">
                                <a href="notificacoes.php" style="color: #6c5ce7; text-decoration: none;">
                                    Ver todas as notificações
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Ações Rápidas -->
            <div class="quick-actions">
                <a href="servicos.php" class="action-button">
                    <i class="fas fa-search"></i> Buscar Serviços
                </a>
                <a href="profissionais.php" class="action-button">
                    <i class="fas fa-users"></i> Ver Profissionais
                </a>
                <a href="meus-orcamentos.php" class="action-button">
                    <i class="fas fa-file-invoice"></i> Orçamentos Recebidos
                </a>
                <a href="editar-perfil-cliente.php" class="action-button">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </a>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        // Atualizar notificações a cada 30 segundos
        setInterval(function() {
            fetch('api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Atualizar contador de notificações não lidas
                        const unreadCount = data.notifications.filter(n => !n.lida).length;
                        const notificationBadge = document.querySelector('.notification-badge');
                        if (notificationBadge) {
                            notificationBadge.textContent = unreadCount;
                            notificationBadge.style.display = unreadCount > 0 ? 'block' : 'none';
                        }
                    }
                })
                .catch(error => console.error('Erro ao atualizar notificações:', error));
        }, 30000);
    </script>
</body>
</html>

