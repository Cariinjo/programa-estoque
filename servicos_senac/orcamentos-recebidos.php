<?php
require_once 'includes/config.php';

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Processar ações (responder orçamento)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orcamento_id = (int)$_POST['orcamento_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'responder') {
            $valor_proposto = (float)$_POST['valor_proposto'];
            $observacoes = trim($_POST['observacoes'] ?? '');
            $prazo_execucao = $_POST['prazo_execucao'] ?? '';
            
            if ($valor_proposto <= 0) {
                throw new Exception('Valor proposto deve ser maior que zero');
            }
            
            $stmt = $pdo->prepare("
                UPDATE orcamentos SET 
                    valor_proposto = ?, observacoes = ?, prazo_execucao = ?, 
                    status = 'respondido', data_resposta = NOW()
                WHERE id_orcamento = ? AND id_profissional IN (
                    SELECT id_profissional FROM profissionais WHERE id_usuario = ?
                )
            ");
            $stmt->execute([$valor_proposto, $observacoes, $prazo_execucao, $orcamento_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Criar notificação para o cliente
                $stmt = $pdo->prepare("
                    INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, created_at)
                    SELECT o.id_cliente, 'orcamento_respondido', 
                           'Orçamento Respondido',
                           CONCAT('Você recebeu uma resposta para o orçamento do serviço \"', s.titulo, '\"'),
                           NOW()
                    FROM orcamentos o
                    JOIN servicos s ON o.id_servico = s.id_servico
                    WHERE o.id_orcamento = ?
                ");
                $stmt->execute([$orcamento_id]);
                
                $success_message = 'Orçamento respondido com sucesso!';
            } else {
                $error_message = 'Erro ao responder orçamento';
            }
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

try {
    // Buscar ID do profissional
    $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $prestador_id = $stmt->fetchColumn();
    
    if (!$prestador_id) {
        header('Location: login.php');
        exit;
    }
    
    // Buscar orçamentos recebidos
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, s.descricao as servico_descricao, s.preco,
               u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
               u.endereco_completo as cliente_endereco, c.nome_cidade as cliente_cidade,
               cat.nome_categoria
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN usuarios u ON o.id_cliente = u.id_usuario
        LEFT JOIN cidades_senac_mg c ON u.cidade_id = c.id_cidade
        LEFT JOIN categorias cat ON s.id_categoria = cat.id_categoria
        WHERE o.id_profissional = ?
        ORDER BY o.data_solicitacao DESC
    ");
    $stmt->execute([$prestador_id]);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar orçamentos recebidos: " . $e->getMessage());
    $error_message = 'Erro ao carregar orçamentos';
    $orcamentos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamentos Recebidos - Prestador</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .orcamentos-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
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
            color: #2c3e50;
        }
        
        .filter-group select {
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .btn-confirm-filter {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .btn-confirm-filter:hover {
            background: #2980b9;
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
        
        .orcamento-card.novo {
            border-left: 5px solid #e74c3c;
        }
        
        .orcamento-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .orcamento-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .orcamento-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .cliente-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .cliente-nome {
            color: #3498db;
            font-weight: 500;
        }
        
        .cliente-cidade {
            color: #7f8c8d;
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
            background: #fff3cd;
            color: #856404;
        }
        
        .status-respondido {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-aceito {
            background: #d4edda;
            color: #155724;
        }
        
        .status-recusado {
            background: #f8d7da;
            color: #721c24;
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
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .detail-section p {
            color: #7f8c8d;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .cliente-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
        }
        
        .cliente-contact {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        .price-section {
            text-align: right;
        }
        
        .price-original {
            color: #7f8c8d;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .price-proposed {
            color: #27ae60;
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 0.5rem;
        }
        
        .response-form {
            background: #e3f2fd;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .orcamento-details {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header-prestador.php'; ?>
    
    <div class="orcamentos-container">
        <!-- Header da Página -->
        <div class="page-header">
            <h1><i class="fas fa-inbox"></i> Orçamentos Recebidos</h1>
            <p>Gerencie as solicitações de orçamento dos seus serviços</p>
        </div>
        
        <!-- Alertas -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
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
                
                <button class="btn-confirm-filter" onclick="aplicarFiltros()">
                    <i class="fas fa-filter"></i> Confirmar Filtro
                </button>
            </div>
        </div>
        
        <!-- Lista de Orçamentos -->
        <?php if (empty($orcamentos)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Nenhum orçamento recebido</h3>
                <p>Quando clientes solicitarem orçamentos para seus serviços, eles aparecerão aqui.</p>
                <a href="meus-servicos.php" class="btn btn-primary">
                    <i class="fas fa-cogs"></i> Ver Meus Serviços
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orcamentos as $orcamento): ?>
                <?php 
                $is_novo = (strtotime($orcamento['data_solicitacao']) > strtotime('-24 hours'));
                ?>
                <div class="orcamento-card <?= $is_novo ? 'novo' : '' ?>" data-status="<?= $orcamento['status'] ?>" data-date="<?= $orcamento['data_solicitacao'] ?>">
                    <!-- Header do Orçamento -->
                    <div class="orcamento-header">
                        <div class="orcamento-title">
                            <?= htmlspecialchars($orcamento['servico_titulo']) ?>
                            <?php if ($is_novo): ?>
                                <span style="color: #e74c3c; font-size: 0.8rem; margin-left: 0.5rem;">NOVO</span>
                            <?php endif; ?>
                        </div>
                        <div class="orcamento-meta">
                            <div class="cliente-info">
                                <div class="cliente-nome">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($orcamento['cliente_nome']) ?>
                                </div>
                                <div class="cliente-cidade">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($orcamento['cliente_cidade'] ?? 'Cidade não informada') ?>
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
                                
                                <?php if ($orcamento['detalhes_solicitacao']): ?>
                                    <h4><i class="fas fa-comment"></i> Detalhes da Solicitação</h4>
                                    <p><?= htmlspecialchars($orcamento['detalhes_solicitacao']) ?></p>
                                <?php endif; ?>
                                
                                <p><strong>Solicitado em:</strong> <?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></p>
                            </div>
                            
                            <div class="cliente-section">
                                <h4><i class="fas fa-user-circle"></i> Informações do Cliente</h4>
                                <div class="cliente-contact">
                                    <?php if ($orcamento['cliente_telefone']): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?= htmlspecialchars($orcamento['cliente_telefone']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?= htmlspecialchars($orcamento['cliente_email']) ?></span>
                                    </div>
                                    <?php if ($orcamento['cliente_endereco']): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($orcamento['cliente_endereco']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="price-section" style="margin-top: 1rem;">
                                    <h4><i class="fas fa-money-bill-wave"></i> Valor</h4>
                                    <div class="price-original">R$ <?= number_format($orcamento['preco'], 2, ',', '.') ?></div>
                                    <?php if ($orcamento['valor_proposto']): ?>
                                        <div class="price-proposed">Proposto: R$ <?= number_format($orcamento['valor_proposto'], 2, ',', '.') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Formulário de Resposta (apenas para orçamentos pendentes) -->
                        <?php if ($orcamento['status'] === 'pendente'): ?>
                            <div class="response-form">
                                <h4><i class="fas fa-reply"></i> Responder Orçamento</h4>
                                <form method="POST">
                                    <input type="hidden" name="orcamento_id" value="<?= $orcamento['id_orcamento'] ?>">
                                    <input type="hidden" name="action" value="responder">
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="valor_proposto_<?= $orcamento['id_orcamento'] ?>">Valor Proposto (R$) *</label>
                                            <input type="number" 
                                                   id="valor_proposto_<?= $orcamento['id_orcamento'] ?>" 
                                                   name="valor_proposto" 
                                                   step="0.01" 
                                                   min="0.01" 
                                                   value="<?= $orcamento['preco'] ?>" 
                                                   required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="prazo_execucao_<?= $orcamento['id_orcamento'] ?>">Prazo de Execução</label>
                                            <input type="date" 
                                                   id="prazo_execucao_<?= $orcamento['id_orcamento'] ?>" 
                                                   name="prazo_execucao" 
                                                   min="<?= date('Y-m-d') ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group form-group-full">
                                        <label for="observacoes_<?= $orcamento['id_orcamento'] ?>">Observações</label>
                                        <textarea id="observacoes_<?= $orcamento['id_orcamento'] ?>" 
                                                  name="observacoes" 
                                                  rows="3" 
                                                  placeholder="Adicione detalhes sobre o serviço, condições, etc."></textarea>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Enviar Resposta
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Ações para orçamentos aceitos -->
                        <?php if ($orcamento['status'] === 'aceito'): ?>
                            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                                <a href="chat.php?cliente=<?= $orcamento['id_cliente'] ?>" class="btn btn-primary">
                                    <i class="fas fa-comments"></i> Chat
                                </a>
                                <?php if ($orcamento['cliente_telefone']): ?>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $orcamento['cliente_telefone']) ?>" 
                                       target="_blank" class="btn btn-success">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function aplicarFiltros() {
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
        
        // Aplicar filtros automaticamente quando mudarem
        document.getElementById('status-filter').addEventListener('change', aplicarFiltros);
        document.getElementById('date-filter').addEventListener('change', aplicarFiltros);
    </script>
</body>
</html>

