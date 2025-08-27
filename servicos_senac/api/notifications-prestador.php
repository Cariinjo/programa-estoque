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
    // Buscar notificações do prestador
    $stmt = $pdo->prepare("
        SELECT n.*, 
               CASE 
                   WHEN n.tipo = 'orcamento_novo' THEN 'fas fa-file-invoice'
                   WHEN n.tipo = 'orcamento_aceito' THEN 'fas fa-check-circle'
                   WHEN n.tipo = 'avaliacao_nova' THEN 'fas fa-star'
                   WHEN n.tipo = 'mensagem_nova' THEN 'fas fa-comments'
                   WHEN n.tipo = 'servico_visualizado' THEN 'fas fa-eye'
                   ELSE 'fas fa-bell'
               END as icone,
               CASE 
                   WHEN TIMESTAMPDIFF(MINUTE, n.created_at, NOW()) < 60 
                   THEN CONCAT(TIMESTAMPDIFF(MINUTE, n.created_at, NOW()), ' min atrás')
                   WHEN TIMESTAMPDIFF(HOUR, n.created_at, NOW()) < 24 
                   THEN CONCAT(TIMESTAMPDIFF(HOUR, n.created_at, NOW()), ' h atrás')
                   ELSE DATE_FORMAT(n.created_at, '%d/%m %H:%i')
               END as tempo
        FROM notificacoes n
        WHERE n.usuario_id = ?
        ORDER BY n.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notificacoes
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar notificações prestador: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>

