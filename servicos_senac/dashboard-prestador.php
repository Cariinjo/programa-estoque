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
        LEFT JOIN cidades_senac_mg c ON u.cidade_id = c.id_cidade
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
    
    // Orçamentos recebidos (novos - últimas 24h)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orcamentos 
        WHERE id_profissional = ? AND data_solicitacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['orcamentos_novos'] = $stmt->fetchColumn();
    
    // Orçamentos pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos WHERE id_profissional = ? AND status = 'pendente'");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['orcamentos_pendentes'] = $stmt->fetchColumn();
    
    // Orçamentos aceitos este mês
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orcamentos 
        WHERE id_profissional = ? AND status = 'aceito' 
        AND MONTH(data_resposta) = MONTH(NOW()) AND YEAR(data_resposta) = YEAR(NOW())
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $stats['orcamentos_aceitos_mes'] = $stmt->fetchColumn();
    
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
    
    // Orçamentos recebidos recentemente (últimos 10)
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, u.nome as cliente_nome, u.telefone as cliente_telefone,
               c.nome_cidade as cliente_cidade
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN usuarios u ON o.id_cliente = u.id_usuario
        LEFT JOIN cidades_senac_mg c ON u.cidade_id = c.id_cidade
        WHERE o.id_profissional = ?
        ORDER BY o.data_solicitacao DESC
        LIMIT 10
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $orcamentos_recebidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Serviços com mais orçamentos
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(o.id_orcamento) as total_orcamentos,
               SUM(CASE WHEN o.status = 'pendente' THEN 1 ELSE 0 END) as orcamentos_pendentes
        FROM servicos s
        LEFT JOIN orcamentos o ON s.id_servico = o.id_servico
        WHERE s.id_profissional = ?
        GROUP BY s.id_servico
        HAVING total_orcamentos > 0
        ORDER BY total_orcamentos DESC, s.visualizacoes DESC
        LIMIT 5
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $servicos_demandados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mensagens não lidas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens_chat WHERE id_destinatario = ? AND lida = 0");
    $stmt->execute([$user_id]);
    $stats['mensagens_nao_lidas'] = $stmt->fetchColumn();
    
    // Histórico de orçamentos por status (últimos 30 dias)
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as total
        FROM orcamentos 
        WHERE id_profissional = ? AND data_solicitacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY status
    ");
    $stmt->execute([$prestador['id_profissional']]);
    $historico_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro no dashboard prestador: " . $e->getMessage());
    $stats = array_fill_keys(['total_servicos', 'servicos_ativos', 'orcamentos_novos', 'orcamentos_pendentes', 'orcamentos_aceitos_mes', 'media_avaliacao', 'total_avaliacoes', 'receita_estimada', 'mensagens_nao_lidas'], 0);
    $orcamentos_recebidos = [];
    $servicos_demandados = [];
    $historico_status = [];
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

        

        

        

        

        

        

        

        

        

        

        

        

     </style>
</head>
<body>
    <?php include 'includes/header-prestador.php'; ?>
    
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
                        <p>Gerencie seus orçamentos e serviços</p>
                        <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($prestador['nome_cidade'] ?? 'Cidade não informada') ?>, <?= htmlspecialchars($prestador['uf'] ?? 'MG') ?></p>
                    </div>
                </div>
                
                <div class="quick-stats">
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $stats['orcamentos_novos'] ?></div>
                        <div class="quick-stat-label">Novos Orçamentos</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $stats['orcamentos_pendentes'] ?></div>
                        <div class="quick-stat-label">Pendentes</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $stats['orcamentos_aceitos_mes'] ?></div>
                        <div class="quick-stat-label">Aceitos Este Mês</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $stats['mensagens_nao_lidas'] ?></div>
                        <div class="quick-stat-label">Mensagens</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number">R$ <?= number_format($stats['receita_estimada'], 0, ',', '.') ?></div>
                        <div class="quick-stat-label">Receita Total</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Estatísticas Detalhadas -->
        <div class="stats-grid">
            <div class="stat-card highlight">
                <div class="stat-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="stat-number"><?= $stats['orcamentos_pendentes'] ?></div>
                <div class="stat-label">Orçamentos Pendentes</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['orcamentos_aceitos_mes'] ?></div>
                <div class="stat-label">Aceitos Este Mês</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-number"><?= $stats['servicos_ativos'] ?></div>
                <div class="stat-label">Serviços Ativos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?= $stats['media_avaliacao'] ?></div>
                <div class="stat-label">Avaliação Média</div>
            </div>
        </div>
        
        <!-- Grid Principal -->
        <div class="dashboard-grid">
            <!-- Orçamentos Recebidos -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-inbox"></i>
                        Orçamentos Recebidos
                    </h2>
                    <a href="orcamentos-recebidos.php" class="card-action">Ver todos</a>
                </div>
                
                <?php if (!empty($orcamentos_recebidos)): ?>
                    <?php foreach ($orcamentos_recebidos as $orcamento): ?>
                        <?php 
                        $is_novo = (strtotime($orcamento['data_solicitacao']) > strtotime('-24 hours'));
                        ?>
                        <div class="orcamento-item <?= $is_novo ? 'novo' : '' ?>">
                            <div class="orcamento-info">
                                <h4><?= htmlspecialchars($orcamento['servico_titulo']) ?></h4>
                                <p><i class="fas fa-user"></i> <?= htmlspecialchars($orcamento['cliente_nome']) ?></p>
                                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($orcamento['cliente_cidade'] ?? 'Não informado') ?></p>
                                <div class="orcamento-meta">
                                    <span><i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></span>
                                    <?php if ($orcamento['cliente_telefone']): ?>
                                        <span><i class="fas fa-phone"></i> <?= htmlspecialchars($orcamento['cliente_telefone']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="orcamento-actions">
                                <div class="orcamento-status status-<?= $orcamento['status'] ?>">
                                    <?= ucfirst($orcamento['status']) ?>
                                </div>
                                <?php if ($orcamento['valor_proposto']): ?>
                                    <div class="orcamento-valor">
                                        R$ <?= number_format($orcamento['valor_proposto'], 2, ',', '.') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Nenhum orçamento ainda</h3>
                        <p>Quando clientes solicitarem orçamentos para seus serviços, eles aparecerão aqui.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Serviços Mais Demandados -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-chart-bar"></i>
                        Serviços Demandados
                    </h2>
                    <a href="meus-servicos.php" class="card-action">Ver todos</a>
                </div>
                
                <?php if (!empty($servicos_demandados)): ?>
                    <?php foreach ($servicos_demandados as $servico): ?>
                        <div class="servico-demandado">
                            <div class="servico-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="servico-info">
                                <h4><?= htmlspecialchars($servico['titulo']) ?></h4>
                                <div class="servico-meta">
                                    <span><i class="fas fa-file-invoice"></i> <?= $servico['total_orcamentos'] ?> orçamentos</span>
                                    <span><i class="fas fa-eye"></i> <?= $servico['visualizacoes'] ?? 0 ?> views</span>
                                </div>
                                <div class="servico-badges">
                                    <span class="badge badge-primary"><?= $servico['total_orcamentos'] ?> total</span>
                                    <?php if ($servico['orcamentos_pendentes'] > 0): ?>
                                        <span class="badge badge-warning"><?= $servico['orcamentos_pendentes'] ?> pendentes</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Cadastre seus serviços</h3>
                        <p>Adicione serviços para começar a receber orçamentos.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Botões de Ação -->
        <div class="action-buttons">
            <a href="criar-orcamento.php" class="action-btn">
                <i class="fas fa-plus-circle"></i>
                Criar Orçamento
            </a>
            <a href="orcamentos-recebidos.php" class="action-btn">
                <i class="fas fa-inbox"></i>
                Ver Todos os Orçamentos
            </a>
            <a href="cadastrar-servico.php" class="action-btn secondary">
                <i class="fas fa-plus"></i>
                Cadastrar Novo Serviço
            </a>
            <a href="meus-servicos.php" class="action-btn secondary">
                <i class="fas fa-cogs"></i>
                Gerenciar Serviços
            </a>
            <a href="chat.php" class="action-btn secondary">
                <i class="fas fa-comments"></i>
                Chat com Clientes
            </a>
        </div>
    </div>
    
    <script>
        // Atualizar estatísticas em tempo real
        function atualizarEstatisticas() {
            fetch('api/prestador-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar números na interface
                    document.querySelector('.quick-stat:nth-child(1) .quick-stat-number').textContent = data.orcamentos_novos;
                    document.querySelector('.quick-stat:nth-child(2) .quick-stat-number').textContent = data.orcamentos_pendentes;
                    document.querySelector('.quick-stat:nth-child(3) .quick-stat-number').textContent = data.orcamentos_aceitos_mes;
                    document.querySelector('.quick-stat:nth-child(4) .quick-stat-number').textContent = data.mensagens_nao_lidas;
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar estatísticas:', error);
            });
        }
        
        // Atualizar a cada 2 minutos
        setInterval(atualizarEstatisticas, 120000);
        
        // Marcar orçamentos como visualizados quando o usuário interage
        document.querySelectorAll('.orcamento-item').forEach(item => {
            item.addEventListener('click', function() {
                // Implementar marcação como visualizado
            });
        });
    </script>
</body>
</html>

