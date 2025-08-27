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

// Processar ações (ativar/desativar serviço)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $servico_id = (int)$_POST['servico_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'toggle_status') {
            $novo_status = $_POST['novo_status'];
            
            $stmt = $pdo->prepare("
                UPDATE servicos SET status_servico = ? 
                WHERE id_servico = ? AND id_profissional IN (
                    SELECT id_profissional FROM profissionais WHERE id_usuario = ?
                )
            ");
            $stmt->execute([$novo_status, $servico_id, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $success_message = 'Status do serviço atualizado com sucesso!';
            } else {
                $error_message = 'Erro ao atualizar status do serviço';
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
    
    // Buscar serviços do prestador
    $stmt = $pdo->prepare("
        SELECT s.*, c.nome_categoria,
               COUNT(DISTINCT o.id_orcamento) as total_orcamentos,
               COUNT(DISTINCT CASE WHEN o.status = 'pendente' THEN o.id_orcamento END) as orcamentos_pendentes,
               COUNT(DISTINCT CASE WHEN o.status = 'aceito' THEN o.id_orcamento END) as orcamentos_aceitos,
               AVG(a.nota) as media_avaliacoes,
               COUNT(DISTINCT a.id_avaliacao) as total_avaliacoes
        FROM servicos s
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        LEFT JOIN orcamentos o ON s.id_servico = o.id_servico
        LEFT JOIN avaliacoes a ON s.id_servico = a.id_servico
        WHERE s.id_profissional = ?
        GROUP BY s.id_servico
        ORDER BY s.data_criacao DESC
    ");
    $stmt->execute([$prestador_id]);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar serviços: " . $e->getMessage());
    $error_message = 'Erro ao carregar serviços';
    $servicos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Serviços - Prestador</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .servicos-container {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header-content h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .header-content p {
            margin: 0;
            opacity: 0.9;
        }
        
        .header-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-add-service {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-add-service:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
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
        
        .servicos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .servico-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .servico-card:hover {
            transform: translateY(-5px);
        }
        
        .servico-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .servico-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .servico-categoria {
            color: #3498db;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .servico-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inativo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pausado {
            background: #fff3cd;
            color: #856404;
        }
        
        .servico-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .servico-content {
            padding: 1.5rem;
        }
        
        .servico-description {
            color: #7f8c8d;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .servico-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }
        
        .servico-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
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
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
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
            .servicos-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            
            .servico-stats {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header-prestador.php'; ?>
    
    <div class="servicos-container">
        <!-- Header da Página -->
        <div class="page-header">
            <div class="header-content">
                <h1><i class="fas fa-cogs"></i> Meus Serviços</h1>
                <p>Gerencie todos os seus serviços cadastrados</p>
            </div>
            <div class="header-actions">
                <a href="cadastrar-servico.php" class="btn-add-service">
                    <i class="fas fa-plus"></i> Novo Serviço
                </a>
            </div>
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
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                        <option value="pausado">Pausado</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="categoria-filter">Categoria:</label>
                    <select id="categoria-filter">
                        <option value="">Todas</option>
                        <?php
                        $categorias_unicas = array_unique(array_column($servicos, 'nome_categoria'));
                        foreach ($categorias_unicas as $categoria):
                            if ($categoria):
                        ?>
                            <option value="<?= htmlspecialchars($categoria) ?>"><?= htmlspecialchars($categoria) ?></option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Lista de Serviços -->
        <?php if (empty($servicos)): ?>
            <div class="empty-state">
                <i class="fas fa-plus-circle"></i>
                <h3>Nenhum serviço cadastrado</h3>
                <p>Comece cadastrando seus primeiros serviços para receber orçamentos.</p>
                <a href="cadastrar-servico.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Cadastrar Primeiro Serviço
                </a>
            </div>
        <?php else: ?>
            <div class="servicos-grid">
                <?php foreach ($servicos as $servico): ?>
                    <div class="servico-card" data-status="<?= $servico['status_servico'] ?>" data-categoria="<?= htmlspecialchars($servico['nome_categoria']) ?>">
                        <!-- Header do Serviço -->
                        <div class="servico-header">
                            <div class="servico-title"><?= htmlspecialchars($servico['titulo']) ?></div>
                            <div class="servico-categoria"><?= htmlspecialchars($servico['nome_categoria'] ?? 'Sem categoria') ?></div>
                            <div class="servico-status">
                                <span class="status-badge status-<?= $servico['status_servico'] ?>">
                                    <?= ucfirst($servico['status_servico']) ?>
                                </span>
                                <div class="servico-price">R$ <?= number_format($servico['preco'], 2, ',', '.') ?></div>
                            </div>
                        </div>
                        
                        <!-- Conteúdo do Serviço -->
                        <div class="servico-content">
                            <div class="servico-description">
                                <?= htmlspecialchars($servico['descricao']) ?>
                            </div>
                            
                            <!-- Estatísticas -->
                            <div class="servico-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $servico['total_orcamentos'] ?></div>
                                    <div class="stat-label">Orçamentos</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $servico['orcamentos_pendentes'] ?></div>
                                    <div class="stat-label">Pendentes</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $servico['visualizacoes'] ?? 0 ?></div>
                                    <div class="stat-label">Visualizações</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $servico['media_avaliacoes'] ? number_format($servico['media_avaliacoes'], 1) : '0' ?></div>
                                    <div class="stat-label">Avaliação</div>
                                </div>
                            </div>
                            
                            <!-- Ações -->
                            <div class="servico-actions">
                                <a href="editar-servico.php?id=<?= $servico['id_servico'] ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                
                                <?php if ($servico['status_servico'] === 'ativo'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="servico_id" value="<?= $servico['id_servico'] ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="novo_status" value="pausado">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-pause"></i> Pausar
                                        </button>
                                    </form>
                                <?php elseif ($servico['status_servico'] === 'pausado'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="servico_id" value="<?= $servico['id_servico'] ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="novo_status" value="ativo">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-play"></i> Ativar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="servico_id" value="<?= $servico['id_servico'] ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="novo_status" value="ativo">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Ativar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="servico-detalhes.php?id=<?= $servico['id_servico'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Filtros
        document.getElementById('status-filter').addEventListener('change', function() {
            aplicarFiltros();
        });
        
        document.getElementById('categoria-filter').addEventListener('change', function() {
            aplicarFiltros();
        });
        
        function aplicarFiltros() {
            const statusFilter = document.getElementById('status-filter').value;
            const categoriaFilter = document.getElementById('categoria-filter').value;
            const cards = document.querySelectorAll('.servico-card');
            
            cards.forEach(card => {
                let show = true;
                
                // Filtro por status
                if (statusFilter && card.dataset.status !== statusFilter) {
                    show = false;
                }
                
                // Filtro por categoria
                if (categoriaFilter && card.dataset.categoria !== categoriaFilter) {
                    show = false;
                }
                
                card.style.display = show ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>

