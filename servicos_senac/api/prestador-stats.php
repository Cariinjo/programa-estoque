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
    
    $stats = [];
    
    // Orçamentos novos (últimas 24h)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orcamentos 
        WHERE id_profissional = ? AND data_solicitacao >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$prestador_id]);
    $stats['orcamentos_novos'] = $stmt->fetchColumn();
    
    // Orçamentos pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos WHERE id_profissional = ? AND status = 'pendente'");
    $stmt->execute([$prestador_id]);
    $stats['orcamentos_pendentes'] = $stmt->fetchColumn();
    
    // Orçamentos aceitos este mês
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orcamentos 
        WHERE id_profissional = ? AND status = 'aceito' 
        AND MONTH(data_resposta) = MONTH(NOW()) AND YEAR(data_resposta) = YEAR(NOW())
    ");
    $stmt->execute([$prestador_id]);
    $stats['orcamentos_aceitos_mes'] = $stmt->fetchColumn();
    
    // Mensagens não lidas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = 0");
    $stmt->execute([$user_id]);
    $stats['mensagens_nao_lidas'] = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'orcamentos_novos' => $stats['orcamentos_novos'],
        'orcamentos_pendentes' => $stats['orcamentos_pendentes'],
        'orcamentos_aceitos_mes' => $stats['orcamentos_aceitos_mes'],
        'mensagens_nao_lidas' => $stats['mensagens_nao_lidas']
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas prestador: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>

