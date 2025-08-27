<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Buscar ID do profissional
    $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $prestador_id = $stmt->fetchColumn();
    
    if (!$prestador_id) {
        echo json_encode(['success' => false, 'message' => 'Prestador não encontrado']);
        exit;
    }
    
    $counters = [];
    
    // Orçamentos pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos WHERE id_profissional = ? AND status = 'pendente'");
    $stmt->execute([$prestador_id]);
    $counters['orcamentos_pendentes'] = $stmt->fetchColumn();
    
    // Mensagens não lidas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = 0");
    $stmt->execute([$user_id]);
    $counters['mensagens_nao_lidas'] = $stmt->fetchColumn();
    
    // Serviços ativos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE id_profissional = ? AND status_servico = 'ativo'");
    $stmt->execute([$prestador_id]);
    $counters['servicos_ativos'] = $stmt->fetchColumn();
    
    // Avaliações novas (últimos 7 dias)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM avaliacoes 
        WHERE prestador_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$prestador_id]);
    $counters['avaliacoes_novas'] = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'orcamentos_pendentes' => $counters['orcamentos_pendentes'],
        'mensagens_nao_lidas' => $counters['mensagens_nao_lidas'],
        'servicos_ativos' => $counters['servicos_ativos'],
        'avaliacoes_novas' => $counters['avaliacoes_novas']
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar contadores prestador: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>

