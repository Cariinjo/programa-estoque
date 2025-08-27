<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Buscar notificações não lidas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notificacoes 
        WHERE id_usuario_destino = ? AND lida = FALSE
    ");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    // Buscar últimas notificações
    $stmt = $pdo->prepare("
        SELECT * 
        FROM notificacoes 
        WHERE id_usuario_destino = ? 
        ORDER BY data_criacao DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount,
        'notifications' => $notifications
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar notificações: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>

