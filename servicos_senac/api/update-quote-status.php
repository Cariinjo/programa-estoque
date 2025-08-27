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

if (!isset($input['quote_id']) || !isset($input['status'])) {
    echo json_encode(['error' => 'Dados incompletos']);
    exit;
}

$quoteId = (int)$input['quote_id'];
$status = $input['status'];
$userId = $_SESSION['user_id'];

// Validar status
$validStatuses = ['aceito', 'recusado', 'concluido'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['error' => 'Status inválido']);
    exit;
}

try {
    // Verificar se o orçamento pertence ao profissional logado
    $stmt = $pdo->prepare("
        SELECT o.*, p.id_usuario as profissional_user_id, o.id_cliente
        FROM orcamentos o
        JOIN profissionais p ON o.id_profissional = p.id_profissional
        WHERE o.id_orcamento = ? AND p.id_usuario = ?
    ");
    $stmt->execute([$quoteId, $userId]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quote) {
        echo json_encode(['error' => 'Orçamento não encontrado ou acesso negado']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Atualizar status do orçamento
    $stmt = $pdo->prepare("UPDATE orcamentos SET status = ? WHERE id_orcamento = ?");
    $stmt->execute([$status, $quoteId]);
    
    // Criar notificação para o cliente
    $notificationMessages = [
        'aceito' => 'Seu orçamento foi aceito! O profissional está pronto para iniciar o trabalho.',
        'recusado' => 'Seu orçamento foi recusado pelo profissional.',
        'concluido' => 'Seu serviço foi concluído! Não se esqueça de avaliar o profissional.'
    ];
    
    $notificationMessage = $notificationMessages[$status];
    $linkAcao = $status === 'aceito' ? "chat.php?id=" . $quoteId : "dashboard.php";
    
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes (id_usuario_destino, tipo_notificacao, mensagem, link_acao) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$quote['id_cliente'], 'orcamento_' . $status, $notificationMessage, $linkAcao]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status do orçamento atualizado com sucesso'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao atualizar status do orçamento: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>

