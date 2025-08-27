<?php
require_once 'includes/config.php';

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Buscar dados do prestador
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome, u.email, u.telefone, u.whatsapp, u.endereco_completo, u.cep,
               c.nome_cidade, c.uf
        FROM profissionais p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN cidades_senac_mg c ON p.cidade_id = c.id_cidade
        WHERE u.id_usuario = ?
    ");
    $stmt->execute([$user_id]);
    $prestador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prestador) {
        header('Location: login.php');
        exit;
    }
    
    // Estatísticas do prestador
    $stats = [];
    
    // Total de serviços
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE id_profissional = ?");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['total_servicos'] = $stmt->fetchColumn();
    
    // Serviços ativos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE id_profissional = ? AND status_servico = 'ativo'");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['servicos_ativos'] = $stmt->fetchColumn();
    
    // Total de orçamentos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos WHERE id_profissional = ?");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['total_orcamentos'] = $stmt->fetchColumn();
    
    // Orçamentos pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos WHERE id_profissional = ? AND status = 'pendente'");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['orcamentos_pendentes'] = $stmt->fetchColumn();
    
    // Avaliação média
    $stmt = $pdo->prepare("
        SELECT AVG(media_avaliacao) as media, SUM(total_avaliacoes) as total
        FROM servicos 
        WHERE id_profissional = ? AND total_avaliacoes > 0
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['media_avaliacao'] = $avaliacao['media'] ? round($avaliacao['media'], 1) : 0;
    $stats['total_avaliacoes'] = $avaliacao['total'] ?? 0;
    
    // Receita estimada (orçamentos aceitos)
    $stmt = $pdo->prepare("
        SELECT SUM(valor_proposto) 
        FROM orcamentos 
        WHERE id_profissional = ? AND status IN ('aceito', 'concluido')
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['receita_estimada'] = $stmt->fetchColumn() ?? 0;
    
    // Últimos orçamentos
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, u.nome as cliente_nome
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN usuarios u ON o.id_cliente = u.id_usuario
        WHERE o.id_profissional = ?
        ORDER BY o.data_solicitacao DESC
        LIMIT 5
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $ultimos_orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Serviços mais procurados
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(o.id_orcamento) as total_orcamentos
        FROM servicos s
        LEFT JOIN orcamentos o ON s.id_servico = o.id_servico
        WHERE s.id_profissional = ?
        GROUP BY s.id_servico
        ORDER BY total_orcamentos DESC, s.visualizacoes DESC
        LIMIT 3
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $servicos_populares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro no dashboard prestador: " . $e->getMessage());
    $stats = array_fill_keys(['total_servicos', 'servicos_ativos', 'total_orcamentos', 'orcamentos_pendentes', 'media_avaliacao', 'total_avaliacoes', 'receita_estimada'], 0);
    $ultimos_orcamentos = [];
    $servicos_populares = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Prestador - <?= htmlspecialchars($prestador['nome']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-100px) translateY(-100px); }
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .prestador-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .welcome-text h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .welcome-text p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .quick-stat {
            background: rgba(255,255,255,0.15);
            padding: 1rem;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .quick-stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .quick-stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #f0f0f0;
        }
        
        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3436;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .card-action {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #636e72;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .orcamento-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .orcamento-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
        }
        
        .orcamento-info h4 {
            margin: 0 0 0.25rem 0;
            color: #2d3436;
            font-size: 0.95rem;
        }
        
        .orcamento-info p {
            margin: 0;
            color: #636e72;
            font-size: 0.85rem;
        }
        
        .orcamento-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-aceito {
            background: #d4edda;
            color: #155724;
        }
        
        .status-recusado {
            background: #f8d7da;
            color: #721c24;
        }
        
        .servico-popular {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .servico-popular:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        
        .servico-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .servico-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2d3436;
            font-size: 1rem;
        }
        
        .servico-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: #636e72;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .action-btn.secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        
        .action-btn.secondary:hover {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .welcome-section {
                flex-direction: column;
                text-align: center;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header-novo.php'; ?>
    
    <div class="dashboard-container">
        <!-- Header do Dashboard -->
        <div class="dashboard-header">
            <div class="header-content">
                <div class="welcome-section">
                    <div class="prestador-avatar">
                        <?= strtoupper(substr($prestador['nome'], 0, 2)) ?>
                    </div>
                    <div class="welcome-text">
                        <h1>Olá, <?= htmlspecialchars($prestador['nome']) ?>!</h1>
                        <p>Bem-vindo ao seu dashboard de prestador de serviços</p>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($prestador['nome_cidade'] ?? 'Cidade não informada') ?>, <?= htmlspecialchars($prestador['uf'] ?? 'MG') ?></p>
                    </div>
                </div>
                
                <div class="quick-stats">
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $stats['servicos_ativos'] ?></div>
                        <div class="quick-stat-label">Serviços Ativos</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $stats['orcamentos_pendentes'] ?></div>
                        <div class="quick-stat-label">Orçamentos Pendentes</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $stats['media_avaliacao'] ?></div>
                        <div class="quick-stat-label">Avaliação Média</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number">R$ <?= number_format($stats['receita_estimada'], 0, ',', '.') ?></div>
                        <div class="quick-stat-label">Receita Estimada</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas Detalhadas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-number"><?= $stats['total_servicos'] ?></div>
                <div class="stat-label">Total de Serviços</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-number"><?= $stats['total_orcamentos'] ?></div>
                <div class="stat-label">Total de Orçamentos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?= $stats['total_avaliacoes'] ?></div>
                <div class="stat-label">Total de Avaliações</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?= $prestador['disponibilidade'] === 'disponivel' ? 'Disponível' : 'Ocupado' ?></div>
                <div class="stat-label">Status Atual</div>
            </div>
        </div>
        
        <!-- Grid Principal -->
        <div class="dashboard-grid">
            <!-- Últimos Orçamentos -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-file-invoice"></i>
                        Últimos Orçamentos
                    </h2>
                    <a href="orcamentos.php" class="card-action">Ver todos</a>
                </div>
                
                <?php if (!empty($ultimos_orcamentos)): ?>
                    <?php foreach ($ultimos_orcamentos as $orcamento): ?>
                        <div class="orcamento-item">
                            <div class="orcamento-info">
                                <h4><?= htmlspecialchars($orcamento['servico_titulo']) ?></h4>
                                <p>Cliente: <?= htmlspecialchars($orcamento['cliente_nome']) ?></p>
                                <p><?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></p>
                            </div>
                            <div class="orcamento-status status-<?= $orcamento['status'] ?>">
                                <?= ucfirst($orcamento['status']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #636e72; padding: 2rem;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                        Nenhum orçamento ainda
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Serviços Populares -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-fire"></i>
                        Serviços Populares
                    </h2>
                    <a href="meus-servicos.php" class="card-action">Ver todos</a>
                </div>
                
                <?php if (!empty($servicos_populares)): ?>
                    <?php foreach ($servicos_populares as $servico): ?>
                        <div class="servico-popular">
                            <div class="servico-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="servico-info">
                                <h4><?= htmlspecialchars($servico['titulo']) ?></h4>
                                <div class="servico-meta">
                                    <span><i class="fas fa-eye"></i> <?= $servico['visualizacoes'] ?? 0 ?></span>
                                    <span><i class="fas fa-file-invoice"></i> <?= $servico['total_orcamentos'] ?></span>
                                    <span><i class="fas fa-dollar-sign"></i> R$ <?= number_format($servico['preco'], 2, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #636e72; padding: 2rem;">
                        <i class="fas fa-plus-circle" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i><br>
                        Cadastre seus primeiros serviços
                    </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Botões de Ação -->
        <div class="action-buttons">
            <a href="cadastrar-servico.php" class="action-btn">
                <i class="fas fa-plus"></i>
                Cadastrar Novo Serviço
            </a>
            <a href="meus-servicos.php" class="action-btn secondary">
                <i class="fas fa-cogs"></i>
                Gerenciar Serviços
            </a>
            <a href="orcamentos.php" class="action-btn secondary">
                <i class="fas fa-file-invoice"></i>
                Ver Orçamentos
            </a>
            <a href="perfil-prestador.php" class="action-btn secondary">
                <i class="fas fa-user-edit"></i>
                Editar Perfil
            </a>
        </div>
    </div>
    
    <script>
        // Atualizar estatísticas em tempo real (opcional)
        function atualizarEstatisticas() {
            // Implementar AJAX para atualizar estatísticas
        }
        
        // Atualizar a cada 5 minutos
        setInterval(atualizarEstatisticas, 300000);
    </script>
</body>
</html>

