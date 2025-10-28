<?php
// api/marcar-uma-lida-api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Origin: *'); // RESTRINJA em produção!
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php'; // Ajuste o caminho

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Use POST.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id']) || !is_numeric($input['notification_id']) || (int)$input['notification_id'] <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da notificação inválido ou não fornecido']);
    exit;
}

$notificationId = (int)$input['notification_id'];
$userId = $_SESSION['user_id'];

try {
    // Marcar notificação como lida (lida = 1)
    $stmt = $pdo->prepare("
        UPDATE notificacoes
        SET lida = 1 -- Usando 1
        WHERE id_notificacao = ? AND id_usuario_destino = ? AND lida = 0 -- Só atualiza se não estiver lida
    ");
    $stmt->execute([$notificationId, $userId]);

    // Retorna sucesso mesmo que já estivesse lida (idempotente)
    echo json_encode([
        'success' => true,
        'message' => 'Notificação processada'
    ]);

} catch (PDOException $e) {
    error_log("Erro marcar notificação $notificationId como lida (PDO): UserID {$userId} - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>