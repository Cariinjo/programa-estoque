<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Esta página é para clientes verem seus pedidos/serviços contratados
if ($userType !== 'cliente') {
    header('Location: dashboard.php'); // Redireciona para o dashboard apropriado
    exit;
}

try {
    // Buscar serviços contratados (orçamentos aceitos ou concluídos)
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, s.preco, 
               u.nome as profissional_nome, p.area_atuacao
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN profissionais p ON o.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE o.id_cliente = ? AND (o.status = 'aceito' OR o.status = 'concluido')
        ORDER BY o.data_solicitacao DESC
    ");
    $stmt->execute([$userId]);
    $myOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao carregar meus pedidos: " . $e->getMessage());
    $error = "Erro ao carregar seus pedidos.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .orders-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .orders-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .orders-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .order-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .order-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item:hover {
            background-color: #f8f9fa;
        }
        
        .order-header-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .order-title-item {
            font-weight: bold;
            color: #2d3436;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }
        
        .order-professional-item {
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-aceito {
            background: #d1f2eb;
            color: #00b894;
        }
        
        .status-concluido {
            background: #d1f2eb;
            color: #00b894;
        }
        
        .order-details-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-price-item {
            font-weight: bold;
            color: #6c5ce7;
            font-size: 1rem;
        }
        
        .order-date-item {
            color: #636e72;
            font-size: 0.85rem;
        }
        
        .order-actions-item {
            margin-top: 1rem;
            text-align: right;
        }
        
        .action-button-small {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.3s ease;
            font-size: 0.9rem;
        }
        
        .action-button-small:hover {
            transform: translateY(-2px);
            color: white;
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
        
        @media (max-width: 768px) {
            .orders-header h1 {
                font-size: 1.5rem;
            }
            .order-header-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-status {
                margin-top: 0.5rem;
            }
            .order-details-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .order-price-item {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="orders-container">
            <div class="orders-header">
                <h1><i class="fas fa-shopping-bag"></i> Meus Pedidos</h1>
                <p>Acompanhe os serviços que você contratou.</p>
            </div>
            
            <div class="order-list">
                <?php if (empty($myOrders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>Você ainda não tem nenhum pedido ativo ou concluído.</p>
                        <a href="servicos.php" class="action-button-small" style="display: inline-flex; margin-top: 1rem;">
                            <i class="fas fa-search"></i> Buscar Serviços
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($myOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-header-item">
                                <div>
                                    <div class="order-title-item"><?= htmlspecialchars($order['servico_titulo']) ?></div>
                                    <div class="order-professional-item">Prestador: <?= htmlspecialchars($order['profissional_nome']) ?> - <?= htmlspecialchars($order['area_atuacao']) ?></div>
                                </div>
                                <span class="order-status status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                            <div class="order-details-item">
                                <span class="order-price-item">R$ <?= number_format($order['valor_proposto'] ?: $order['preco'], 2, ',', '.') ?></span>
                                <span class="order-date-item">Contratado em: <?= date('d/m/Y', strtotime($order['data_solicitacao'])) ?></span>
                            </div>
                            <?php if ($order['status'] === 'aceito'): ?>
                                <div class="order-actions-item">
                                    <a href="chat.php?id=<?= $order['id_orcamento'] ?>" class="action-button-small">
                                        <i class="fas fa-comments"></i> Conversar
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>


