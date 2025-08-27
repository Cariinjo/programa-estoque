<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id'])) {
    echo json_encode(['error' => 'ID da notificação não fornecido']);
    exit;
}

$notificationId = (int)$input['notification_id'];
$userId = $_SESSION['user_id'];

try {
    // Marcar notificação como lida (apenas se pertencer ao usuário)
    $stmt = $pdo->prepare("
        UPDATE notificacoes 
        SET lida = TRUE 
        WHERE id_notificacao = ? AND id_usuario_destino = ?
    ");
    $stmt->execute([$notificationId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificação marcada como lida'
        ]);
    } else {
        echo json_encode(['error' => 'Notificação não encontrada']);
    }
    
} catch (PDOException $e) {
    error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>

