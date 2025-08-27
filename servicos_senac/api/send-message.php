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

if (!isset($input['chat_id']) || !isset($input['message'])) {
    echo json_encode(['error' => 'Dados incompletos']);
    exit;
}

$chatId = (int)$input['chat_id'];
$message = trim($input['message']);
$userId = $_SESSION['user_id'];

if (empty($message)) {
    echo json_encode(['error' => 'Mensagem não pode estar vazia']);
    exit;
}

try {
    // Verificar se o usuário tem acesso a este chat e obter o destinatário
    $stmt = $pdo->prepare("
        SELECT o.id_cliente, o.id_profissional, p.id_usuario as profissional_user_id
        FROM orcamentos o
        JOIN profissionais p ON o.id_profissional = p.id_profissional
        WHERE o.id_orcamento = ? AND (o.id_cliente = ? OR p.id_usuario = ?)
    ");
    $stmt->execute([$chatId, $userId, $userId]);
    $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orcamento) {
        echo json_encode(['error' => 'Acesso negado']);
        exit;
    }
    
    // Determinar o destinatário
    $destinatarioId = ($userId == $orcamento['id_cliente']) 
        ? $orcamento['profissional_user_id'] 
        : $orcamento['id_cliente'];
    
    $pdo->beginTransaction();
    
    // Inserir mensagem
    $stmt = $pdo->prepare("
        INSERT INTO mensagens_chat (id_remetente, id_destinatario, id_orcamento, mensagem) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $destinatarioId, $chatId, $message]);
    $messageId = $pdo->lastInsertId();
    
    // Criar notificação para o destinatário
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes (id_usuario_destino, tipo_notificacao, mensagem, link_acao) 
        VALUES (?, 'nova_mensagem', ?, ?)
    ");
    $notificationMessage = "Você recebeu uma nova mensagem no chat.";
    $linkAcao = "chat.php?id=" . $chatId;
    $stmt->execute([$destinatarioId, $notificationMessage, $linkAcao]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'message' => 'Mensagem enviada com sucesso'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao enviar mensagem: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>

