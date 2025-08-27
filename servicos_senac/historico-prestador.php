<?php
require_once 'includes/config.php';

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Lógica para buscar histórico de serviços e orçamentos do prestador
try {
    // Buscar ID do profissional
    $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $prestador_id = $stmt->fetchColumn();

    if (!$prestador_id) {
        // Se não encontrar o ID do profissional, não há histórico para mostrar
        $historico_orcamentos = [];
        $servicos_concluidos = [];
    } else {
        // Buscar histórico de orçamentos
        $stmt = $pdo->prepare("
            SELECT o.*, s.titulo as servico_titulo, u.nome as cliente_nome
            FROM orcamentos o
            JOIN servicos s ON o.id_servico = s.id_servico
            JOIN usuarios u ON o.id_cliente = u.id_usuario
            WHERE o.id_profissional = ?
            ORDER BY o.data_solicitacao DESC
        ");
        $stmt->execute([$prestador_id]);
        $historico_orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar histórico de serviços concluídos (se houver uma tabela para isso)
        // Por enquanto, vamos considerar orçamentos com status 'concluido' como serviços concluídos
        $stmt = $pdo->prepare("
            SELECT o.*, s.titulo as servico_titulo, u.nome as cliente_nome
            FROM orcamentos o
            JOIN servicos s ON o.id_servico = s.id_servico
            JOIN usuarios u ON o.id_cliente = u.id_usuario
            WHERE o.id_profissional = ? AND o.status = 'concluido'
            ORDER BY o.data_resposta DESC
        ");
        $stmt->execute([$prestador_id]);
        $servicos_concluidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar histórico do prestador: " . $e->getMessage());
    $historico_orcamentos = [];
    $servicos_concluidos = [];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico - Prestador</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .history-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .history-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #f0f0f0;
            margin-bottom: 2rem;
        }
        .history-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .history-header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 2rem;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 1rem;
            background: #fdfdfd;
        }
        .history-item-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2d3436;
            font-size: 1.1rem;
        }
        .history-item-info p {
            margin: 0;
            color: #636e72;
            font-size: 0.9rem;
        }
        .history-item-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-aceito { background: #d4edda; color: #155724; }
        .status-recusado { background: #f8d7da; color: #721c24; }
        .status-concluido { background: #d1ecf1; color: #0c5460; }
        .empty-state {
            text-align: center;
            color: #636e72;
            padding: 3rem 2rem;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        .empty-state h3 {
            margin: 0 0 0.5rem 0;
            color: #2d3436;
        }
        .empty-state p {
            margin: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header-prestador.php'; ?>

    <div class="history-container">
        <div class="history-card">
            <div class="history-header">
                <h1><i class="fas fa-history"></i> Histórico de Orçamentos</h1>
            </div>

            <?php if (!empty($historico_orcamentos)): ?>
                <?php foreach ($historico_orcamentos as $orcamento): ?>
                    <div class="history-item">
                        <div class="history-item-info">
                            <h4><?= htmlspecialchars($orcamento['servico_titulo']) ?></h4>
                            <p>Cliente: <?= htmlspecialchars($orcamento['cliente_nome']) ?></p>
                            <p>Data: <?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></p>
                            <?php if ($orcamento['valor_proposto']): ?>
                                <p>Valor Proposto: R$ <?= number_format($orcamento['valor_proposto'], 2, ',', '.') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="history-item-status status-<?= $orcamento['status'] ?>">
                            <?= ucfirst($orcamento['status']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Nenhum orçamento no histórico</h3>
                    <p>Seus orçamentos aceitos ou recusados aparecerão aqui.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="history-card">
            <div class="history-header">
                <h1><i class="fas fa-check-circle"></i> Serviços Concluídos</h1>
            </div>

            <?php if (!empty($servicos_concluidos)): ?>
                <?php foreach ($servicos_concluidos as $servico): ?>
                    <div class="history-item">
                        <div class="history-item-info">
                            <h4><?= htmlspecialchars($servico['servico_titulo']) ?></h4>
                            <p>Cliente: <?= htmlspecialchars($servico['cliente_nome']) ?></p>
                            <p>Data de Conclusão: <?= date('d/m/Y H:i', strtotime($servico['data_resposta'])) ?></p>
                            <?php if ($servico['valor_proposto']): ?>
                                <p>Valor Final: R$ <?= number_format($servico['valor_proposto'], 2, ',', '.') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="history-item-status status-concluido">
                            Concluído
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>Nenhum serviço concluído ainda</h3>
                    <p>Serviços que você aceitou e marcou como concluídos aparecerão aqui.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; // Assumindo que você tem um footer ?>
</body>
</html>

