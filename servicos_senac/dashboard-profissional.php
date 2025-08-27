<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Verificar se é profissional
if ($userType !== 'profissional') {
    header('Location: dashboard.php');
    exit;
}

try {
    // Buscar informações do usuário e profissional
    $stmt = $pdo->prepare("
        SELECT u.*, p.* 
        FROM usuarios u 
        JOIN profissionais p ON u.id_usuario = p.id_usuario 
        WHERE u.id_usuario = ?
    ");
    $stmt->execute([$userId]);
    $professional = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$professional) {
        header('Location: logout.php');
        exit;
    }
    
    // Estatísticas do profissional
    $stats = [];
    
    // Serviços cadastrados
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM servicos WHERE id_profissional = ?");
    $stmt->execute([$professional['id_profissional']]);
    $stats['servicos_cadastrados'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Orçamentos recebidos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE id_profissional = ?");
    $stmt->execute([$professional['id_profissional']]);
    $stats['orcamentos_recebidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Serviços em andamento (aceitos)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE id_profissional = ? AND status = 'aceito'");
    $stmt->execute([$professional['id_profissional']]);
    $stats['servicos_andamento'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Serviços concluídos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orcamentos WHERE id_profissional = ? AND status = 'concluido'");
    $stmt->execute([$professional['id_profissional']]);
    $stats['servicos_concluidos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Receita total (serviços concluídos)
    $stmt = $pdo->prepare("SELECT SUM(valor_proposto) as total FROM orcamentos WHERE id_profissional = ? AND status = 'concluido'");
    $stmt->execute([$professional['id_profissional']]);
    $stats['receita_total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    
    // Notificações não lidas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE id_usuario_destino = ? AND lida = FALSE");
    $stmt->execute([$userId]);
    $stats['notificacoes_nao_lidas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Últimos orçamentos recebidos
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, s.preco, 
               u.nome as cliente_nome, u.email as cliente_email
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN usuarios u ON o.id_cliente = u.id_usuario
        WHERE o.id_profissional = ?
        ORDER BY o.data_solicitacao DESC
        LIMIT 5
    ");
    $stmt->execute([$professional['id_profissional']]);
    $recentQuotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Serviços mais populares
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(o.id_orcamento) as total_orcamentos
        FROM servicos s
        LEFT JOIN orcamentos o ON s.id_servico = o.id_servico
        WHERE s.id_profissional = ?
        GROUP BY s.id_servico
        ORDER BY total_orcamentos DESC, s.media_avaliacao DESC
        LIMIT 3
    ");
    $stmt->execute([$professional['id_profissional']]);
    $popularServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Notificações recentes
    $stmt = $pdo->prepare("
        SELECT * FROM notificacoes 
        WHERE id_usuario_destino = ? 
        ORDER BY data_criacao DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Avaliações recentes
    $stmt = $pdo->prepare("
        SELECT a.*, s.titulo as servico_titulo, u.nome as cliente_nome
        FROM avaliacoes a
        JOIN servicos s ON a.id_servico = s.id_servico
        JOIN usuarios u ON a.id_cliente = u.id_usuario
        WHERE s.id_profissional = ?
        ORDER BY a.data_avaliacao DESC
        LIMIT 3
    ");
    $stmt->execute([$professional['id_profissional']]);
    $recentReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro no dashboard profissional: " . $e->getMessage());
    $error = "Erro ao carregar dashboard.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profissional - <?= htmlspecialchars($professional['nome']) ?> - Serviços SENAC</title>
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
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .professional-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .header-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .header-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .stars {
            color: #ffd700;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        .stat-icon.gold { background: linear-gradient(45deg, #fdcb6e, #e84393); }
        .stat-icon.red { background: linear-gradient(45deg, #d63031, #e84393); }
        
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
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .sidebar-content {
            display: flex;
            flex-direction: column;
            gap: 2rem;
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
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .quote-title {
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.25rem;
        }
        
        .quote-client {
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
        
        .quote-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .btn-accept {
            background: #00b894;
            color: white;
        }
        
        .btn-reject {
            background: #e17055;
            color: white;
        }
        
        .btn-chat {
            background: #6c5ce7;
            color: white;
        }
        
        .service-item {
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .service-title {
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        
        .service-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #636e72;
        }
        
        .service-price {
            font-weight: bold;
            color: #6c5ce7;
        }
        
        .review-item {
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .review-client {
            font-weight: bold;
            color: #2d3436;
        }
        
        .review-rating {
            color: #ffd700;
        }
        
        .review-text {
            color: #636e72;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .review-service {
            font-size: 0.8rem;
            color: #636e72;
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
            
            .dashboard-header {
                flex-direction: column;
                text-align: center;
            }
            
            .header-info h1 {
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
                <div class="professional-avatar">
                    <?= strtoupper(substr($professional['nome'], 0, 1)) ?>
                </div>
                <div class="header-info">
                    <h1><i class="fas fa-briefcase"></i> <?= htmlspecialchars($professional['nome']) ?></h1>
                    <p><?= htmlspecialchars($professional['area_atuacao']) ?></p>
                    <div class="rating-display">
                        <div class="stars">
                            <?php
                            $rating = $professional['media_avaliacao'];
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
                        <span><?= number_format($rating, 1) ?> (<?= $professional['total_avaliacoes'] ?> avaliações)</span>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-number"><?= $stats['servicos_cadastrados'] ?></div>
                    <div class="stat-label">Serviços Cadastrados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-number"><?= $stats['orcamentos_recebidos'] ?></div>
                    <div class="stat-label">Orçamentos Recebidos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?= $stats['servicos_andamento'] ?></div>
                    <div class="stat-label">Em Andamento</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= $stats['servicos_concluidos'] ?></div>
                    <div class="stat-label">Concluídos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon gold">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-number">R$ <?= number_format($stats['receita_total'], 0, ',', '.') ?></div>
                    <div class="stat-label">Receita Total</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-number"><?= $stats['notificacoes_nao_lidas'] ?></div>
                    <div class="stat-label">Notificações</div>
                </div>
            </div>
            
            <!-- Conteúdo Principal -->
            <div class="dashboard-content">
                <!-- Conteúdo Principal -->
                <div class="main-content">
                    <!-- Orçamentos Recentes -->
                    <div class="content-card">
                        <div class="section-header">
                            <h3><i class="fas fa-inbox"></i> Últimos Orçamentos Recebidos</h3>
                        </div>
                        <div class="section-content">
                            <?php if (empty($recentQuotes)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>Nenhum orçamento recebido ainda.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentQuotes as $quote): ?>
                                    <div class="quote-item">
                                        <div class="quote-header">
                                            <div>
                                                <div class="quote-title"><?= htmlspecialchars($quote['servico_titulo']) ?></div>
                                                <div class="quote-client">Cliente: <?= htmlspecialchars($quote['cliente_nome']) ?></div>
                                            </div>
                                            <span class="quote-status status-<?= $quote['status'] ?>">
                                                <?= ucfirst($quote['status']) ?>
                                            </span>
                                        </div>
                                        <div class="quote-details">
                                            <span class="quote-price">R$ <?= number_format($quote['valor_proposto'] ?: $quote['preco'], 2, ',', '.') ?></span>
                                            <span class="quote-date"><?= date('d/m/Y', strtotime($quote['data_solicitacao'])) ?></span>
                                        </div>
                                        
                                        <?php if ($quote['status'] === 'pendente'): ?>
                                            <div class="quote-actions">
                                                <button class="btn-small btn-accept" onclick="updateQuoteStatus(<?= $quote['id_orcamento'] ?>, 'aceito')">
                                                    <i class="fas fa-check"></i> Aceitar
                                                </button>
                                                <button class="btn-small btn-reject" onclick="updateQuoteStatus(<?= $quote['id_orcamento'] ?>, 'recusado')">
                                                    <i class="fas fa-times"></i> Recusar
                                                </button>
                                            </div>
                                        <?php elseif ($quote['status'] === 'aceito'): ?>
                                            <div class="quote-actions">
                                                <a href="chat.php?id=<?= $quote['id_orcamento'] ?>" class="btn-small btn-chat">
                                                    <i class="fas fa-comments"></i> Conversar
                                                </a>
                                                <button class="btn-small btn-accept" onclick="updateQuoteStatus(<?= $quote['id_orcamento'] ?>, 'concluido')">
                                                    <i class="fas fa-check-circle"></i> Concluir
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div style="text-align: center; margin-top: 1rem;">
                                    <a href="meus-orcamentos-profissional.php" class="action-button" style="display: inline-flex;">
                                        <i class="fas fa-list"></i> Ver Todos os Orçamentos
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Serviços Populares -->
                    <div class="content-card">
                        <div class="section-header">
                            <h3><i class="fas fa-star"></i> Seus Serviços Mais Populares</h3>
                        </div>
                        <div class="section-content">
                            <?php if (empty($popularServices)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-plus-circle"></i>
                                    <p>Você ainda não cadastrou nenhum serviço.</p>
                                    <a href="cadastrar-servico.php" class="action-button" style="display: inline-flex; margin-top: 1rem;">
                                        <i class="fas fa-plus"></i> Cadastrar Serviço
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($popularServices as $service): ?>
                                    <div class="service-item">
                                        <div class="service-title"><?= htmlspecialchars($service['titulo']) ?></div>
                                        <div class="service-stats">
                                            <span><?= $service['total_orcamentos'] ?> orçamentos solicitados</span>
                                            <span class="service-price">R$ <?= number_format($service['preco'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div style="text-align: center; margin-top: 1rem;">
                                    <a href="meus-servicos.php" class="action-button" style="display: inline-flex;">
                                        <i class="fas fa-cogs"></i> Gerenciar Serviços
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="sidebar-content">
                    <!-- Notificações -->
                    <div class="content-card">
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
                    
                    <!-- Avaliações Recentes -->
                    <div class="content-card">
                        <div class="section-header">
                            <h3><i class="fas fa-comments"></i> Avaliações Recentes</h3>
                        </div>
                        <div class="section-content">
                            <?php if (empty($recentReviews)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-star"></i>
                                    <p>Nenhuma avaliação ainda.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentReviews as $review): ?>
                                    <div class="review-item">
                                        <div class="review-header">
                                            <span class="review-client"><?= htmlspecialchars($review['cliente_nome']) ?></span>
                                            <div class="review-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?= $i <= $review['nota'] ? '' : ' opacity-25' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="review-text"><?= htmlspecialchars($review['comentario']) ?></div>
                                        <div class="review-service"><?= htmlspecialchars($review['servico_titulo']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div style="text-align: center; margin-top: 1rem;">
                                    <a href="minhas-avaliacoes.php" style="color: #6c5ce7; text-decoration: none;">
                                        Ver todas as avaliações
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ações Rápidas -->
            <div class="quick-actions">
                <a href="cadastrar-servico.php" class="action-button">
                    <i class="fas fa-plus"></i> Novo Serviço
                </a>
                <a href="meus-servicos.php" class="action-button">
                    <i class="fas fa-cogs"></i> Meus Serviços
                </a>
                <a href="meus-orcamentos-profissional.php" class="action-button">
                    <i class="fas fa-inbox"></i> Orçamentos
                </a>
                <a href="perfil-profissional.php" class="action-button">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </a>
                <a href="minhas-avaliacoes.php" class="action-button">
                    <i class="fas fa-star"></i> Avaliações
                </a>
                <a href="relatorios.php" class="action-button">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        // Função para atualizar status do orçamento
        function updateQuoteStatus(quoteId, status) {
            if (confirm(`Tem certeza que deseja ${status === 'aceito' ? 'aceitar' : status === 'recusado' ? 'recusar' : 'concluir'} este orçamento?`)) {
                fetch('api/update-quote-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        quote_id: quoteId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + (data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao atualizar orçamento.');
                });
            }
        }
        
        // Atualizar notificações a cada 30 segundos
        setInterval(function() {
            fetch('api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
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

