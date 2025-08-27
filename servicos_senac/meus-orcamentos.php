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
    
    // Buscar orçamentos recebidos pelo cliente
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, s.descricao as servico_descricao, s.preco,
               u.nome as profissional_nome, u.email as profissional_email, u.telefone as profissional_telefone,
               prof.area_atuacao, prof.descricao_perfil as profissional_descricao,
               c.nome as categoria_nome
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN profissionais prof ON o.id_profissional = prof.id_profissional
        JOIN usuarios u ON prof.id_usuario = u.id_usuario
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        WHERE o.id_cliente = ?
        ORDER BY o.data_solicitacao DESC
    ");
    $stmt->execute([$userId]);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar orçamentos: " . $e->getMessage());
    $error = "Erro ao carregar orçamentos.";
}

// Processar ações (aceitar/recusar orçamento)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orcamentoId = $_POST['orcamento_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'aceitar') {
            $stmt = $pdo->prepare("UPDATE orcamentos SET status = 'aceito' WHERE id_orcamento = ? AND id_cliente = ?");
            $stmt->execute([$orcamentoId, $userId]);
            
            // Criar notificação para o profissional
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes (id_usuario_destino, tipo, mensagem, data_criacao)
                SELECT prof.id_usuario, 'orcamento_aceito', 
                       CONCAT('Seu orçamento para \"', s.titulo, '\" foi aceito!'),
                       NOW()
                FROM orcamentos o
                JOIN servicos s ON o.id_servico = s.id_servico
                JOIN profissionais prof ON o.id_profissional = prof.id_profissional
                WHERE o.id_orcamento = ?
            ");
            $stmt->execute([$orcamentoId]);
            
            $success = "Orçamento aceito com sucesso!";
            
        } elseif ($action === 'recusar') {
            $stmt = $pdo->prepare("UPDATE orcamentos SET status = 'recusado' WHERE id_orcamento = ? AND id_cliente = ?");
            $stmt->execute([$orcamentoId, $userId]);
            
            // Criar notificação para o profissional
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes (id_usuario_destino, tipo, mensagem, data_criacao)
                SELECT prof.id_usuario, 'orcamento_recusado', 
                       CONCAT('Seu orçamento para \"', s.titulo, '\" foi recusado.'),
                       NOW()
                FROM orcamentos o
                JOIN servicos s ON o.id_servico = s.id_servico
                JOIN profissionais prof ON o.id_profissional = prof.id_profissional
                WHERE o.id_orcamento = ?
            ");
            $stmt->execute([$orcamentoId]);
            
            $success = "Orçamento recusado.";
        }
        
        // Recarregar a página para mostrar as mudanças
        header('Location: meus-orcamentos.php');
        exit;
        
    } catch (PDOException $e) {
        error_log("Erro ao processar ação do orçamento: " . $e->getMessage());
        $error = "Erro ao processar ação.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Orçamentos - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .orcamentos-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .page-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .page-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 500;
            color: #2d3436;
        }
        
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .orcamento-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .orcamento-card:hover {
            transform: translateY(-2px);
        }
        
        .orcamento-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .orcamento-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        
        .orcamento-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .orcamento-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .profissional-nome {
            color: #6c5ce7;
            font-weight: 500;
        }
        
        .categoria-nome {
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .orcamento-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-pendente {
            background: #ffeaa7;
            color: #d63031;
        }
        
        .status-respondido {
            background: #ddd6fe;
            color: #6c5ce7;
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
        
        .orcamento-content {
            padding: 1.5rem;
        }
        
        .orcamento-details {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-section h4 {
            color: #2d3436;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .detail-section p {
            color: #636e72;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .price-section {
            text-align: right;
        }
        
        .price-original {
            color: #636e72;
            text-decoration: line-through;
            font-size: 0.9rem;
        }
        
        .price-proposed {
            color: #6c5ce7;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .orcamento-dates {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .date-item {
            text-align: center;
        }
        
        .date-label {
            color: #636e72;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .date-value {
            color: #2d3436;
            font-weight: 500;
        }
        
        .orcamento-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(45deg, #00b894, #55efc4);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #e17055, #fd79a8);
            color: white;
        }
        
        .btn-secondary {
            background: #636e72;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #636e72;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: #2d3436;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1f2eb;
            color: #00b894;
            border: 1px solid #00b894;
        }
        
        .alert-error {
            background: #fab1a0;
            color: #e17055;
            border: 1px solid #e17055;
        }
        
        @media (max-width: 768px) {
            .orcamento-details {
                grid-template-columns: 1fr;
            }
            
            .orcamento-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .orcamento-actions {
                justify-content: center;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="orcamentos-container">
            <!-- Header da Página -->
            <div class="page-header">
                <h1><i class="fas fa-file-invoice"></i> Orçamentos Recebidos</h1>
                <p>Gerencie os orçamentos que você recebeu dos profissionais</p>
            </div>
            
            <!-- Alertas -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Filtros -->
            <div class="filters">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status-filter">Status:</label>
                        <select id="status-filter">
                            <option value="">Todos</option>
                            <option value="pendente">Pendente</option>
                            <option value="respondido">Respondido</option>
                            <option value="aceito">Aceito</option>
                            <option value="concluido">Concluído</option>
                            <option value="recusado">Recusado</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="date-filter">Período:</label>
                        <select id="date-filter">
                            <option value="">Todos</option>
                            <option value="7">Últimos 7 dias</option>
                            <option value="30">Últimos 30 dias</option>
                            <option value="90">Últimos 3 meses</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Orçamentos -->
            <?php if (empty($orcamentos)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice"></i>
                    <h3>Nenhum orçamento encontrado</h3>
                    <p>Você ainda não recebeu nenhum orçamento dos profissionais.</p>
                    <a href="servicos.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Buscar Serviços
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orcamentos as $orcamento): ?>
                    <div class="orcamento-card" data-status="<?= $orcamento['status'] ?>" data-date="<?= $orcamento['data_solicitacao'] ?>">
                        <!-- Header do Orçamento -->
                        <div class="orcamento-header">
                            <div class="orcamento-title"><?= htmlspecialchars($orcamento['servico_titulo']) ?></div>
                            <div class="orcamento-meta">
                                <div class="orcamento-info">
                                    <div class="profissional-nome">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($orcamento['profissional_nome']) ?>
                                    </div>
                                    <div class="categoria-nome">
                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($orcamento['categoria_nome'] ?: 'Categoria não definida') ?>
                                    </div>
                                </div>
                                <div class="orcamento-status status-<?= $orcamento['status'] ?>">
                                    <?= ucfirst($orcamento['status']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Conteúdo do Orçamento -->
                        <div class="orcamento-content">
                            <!-- Detalhes -->
                            <div class="orcamento-details">
                                <div class="detail-section">
                                    <h4><i class="fas fa-info-circle"></i> Descrição do Serviço</h4>
                                    <p><?= htmlspecialchars($orcamento['servico_descricao']) ?></p>
                                    
                                    <?php if ($orcamento['observacoes']): ?>
                                        <h4><i class="fas fa-comment"></i> Observações do Profissional</h4>
                                        <p><?= htmlspecialchars($orcamento['observacoes']) ?></p>
                                    <?php endif; ?>
                                    
                                    <h4><i class="fas fa-user-tie"></i> Sobre o Profissional</h4>
                                    <p><?= htmlspecialchars($orcamento['profissional_descricao'] ?: 'Descrição não disponível') ?></p>
                                    <p><strong>Área de Atuação:</strong> <?= htmlspecialchars($orcamento['area_atuacao']) ?></p>
                                </div>
                                
                                <div class="price-section">
                                    <h4><i class="fas fa-money-bill-wave"></i> Valor</h4>
                                    <?php if ($orcamento['valor_proposto'] && $orcamento['valor_proposto'] != $orcamento['preco']): ?>
                                        <div class="price-original">Preço original: R$ <?= number_format($orcamento['preco'], 2, ',', '.') ?></div>
                                        <div class="price-proposed">R$ <?= number_format($orcamento['valor_proposto'], 2, ',', '.') ?></div>
                                    <?php else: ?>
                                        <div class="price-proposed">R$ <?= number_format($orcamento['preco'], 2, ',', '.') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Datas -->
                            <div class="orcamento-dates">
                                <div class="date-item">
                                    <div class="date-label">Solicitado em</div>
                                    <div class="date-value"><?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></div>
                                </div>
                                
                                <?php if ($orcamento['data_resposta']): ?>
                                    <div class="date-item">
                                        <div class="date-label">Respondido em</div>
                                        <div class="date-value"><?= date('d/m/Y H:i', strtotime($orcamento['data_resposta'])) ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($orcamento['prazo_execucao']): ?>
                                    <div class="date-item">
                                        <div class="date-label">Prazo de Execução</div>
                                        <div class="date-value"><?= date('d/m/Y', strtotime($orcamento['prazo_execucao'])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Ações -->
                            <div class="orcamento-actions">
                                <?php if ($orcamento['status'] === 'respondido'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="orcamento_id" value="<?= $orcamento['id_orcamento'] ?>">
                                        <input type="hidden" name="action" value="aceitar">
                                        <button type="submit" class="btn btn-success" onclick="return confirm('Tem certeza que deseja aceitar este orçamento?')">
                                            <i class="fas fa-check"></i> Aceitar
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="orcamento_id" value="<?= $orcamento['id_orcamento'] ?>">
                                        <input type="hidden" name="action" value="recusar">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja recusar este orçamento?')">
                                            <i class="fas fa-times"></i> Recusar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($orcamento['status'] === 'aceito'): ?>
                                    <a href="chat.php?id=<?= $orcamento['id_orcamento'] ?>" class="btn btn-primary">
                                        <i class="fas fa-comments"></i> Conversar
                                    </a>
                                    
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $orcamento['profissional_telefone']) ?>" target="_blank" class="btn btn-success">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                <?php endif; ?>
                                
                                <a href="profissional-perfil.php?id=<?= $orcamento['id_profissional'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-user"></i> Ver Perfil
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        // Filtros
        document.getElementById('status-filter').addEventListener('change', function() {
            filterOrcamentos();
        });
        
        document.getElementById('date-filter').addEventListener('change', function() {
            filterOrcamentos();
        });
        
        function filterOrcamentos() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const cards = document.querySelectorAll('.orcamento-card');
            
            cards.forEach(card => {
                let show = true;
                
                // Filtro por status
                if (statusFilter && card.dataset.status !== statusFilter) {
                    show = false;
                }
                
                // Filtro por data
                if (dateFilter) {
                    const cardDate = new Date(card.dataset.date);
                    const now = new Date();
                    const daysAgo = parseInt(dateFilter);
                    const filterDate = new Date(now.getTime() - (daysAgo * 24 * 60 * 60 * 1000));
                    
                    if (cardDate < filterDate) {
                        show = false;
                    }
                }
                
                card.style.display = show ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>

