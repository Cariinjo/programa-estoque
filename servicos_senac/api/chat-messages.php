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

if (!isset($_GET['chat_id'])) {
    echo json_encode(['error' => 'ID do chat não fornecido']);
    exit;
}

$chatId = (int)$_GET['chat_id'];
$userId = $_SESSION['user_id'];

try {
    // Verificar se o usuário tem acesso a este chat (através do orçamento)
    $stmt = $pdo->prepare("
        SELECT id_orcamento 
        FROM orcamentos 
        WHERE id_orcamento = ? AND (id_cliente = ? OR id_profissional IN (
            SELECT id_profissional FROM profissionais WHERE id_usuario = ?
        ))
    ");
    $stmt->execute([$chatId, $userId, $userId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    
    // Buscar mensagens do chat
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u_remetente.nome as nome_remetente,
               u_destinatario.nome as nome_destinatario,
               CASE WHEN m.id_remetente = ? THEN 1 ELSE 0 END as is_own
        FROM mensagens_chat m
        JOIN usuarios u_remetente ON m.id_remetente = u_remetente.id_usuario
        JOIN usuarios u_destinatario ON m.id_destinatario = u_destinatario.id_usuario
        WHERE m.id_orcamento = ?
        ORDER BY m.data_envio ASC
    ");
        $stmt->execute([$userId, $chatId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensagens como lidas
    $stmt = $pdo->prepare("
        UPDATE mensagens_chat 
        SET lida = TRUE 
        WHERE id_orcamento = ? AND id_destinatario = ? AND lida = FALSE
    ");
    $stmt->execute([$chatId, $userId]);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao carregar mensagens: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>

