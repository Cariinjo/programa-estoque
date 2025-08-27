<?php
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$outro_usuario_id = (int)($_GET['cliente_id'] ?? $_GET['prestador_id'] ?? 0);

if ($outro_usuario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do outro usuário é obrigatório']);
    exit;
}

try {
    // Verificar se a tabela existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'mensagens_chat'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => true, 'mensagens' => []]);
        exit;
    }
    
    // Buscar mensagens entre os dois usuários
    $stmt = $pdo->prepare("
        SELECT m.*, u.nome as remetente_nome
        FROM mensagens_chat m
        JOIN usuarios u ON m.id_remetente = u.id_usuario
        WHERE (m.id_remetente = ? AND m.id_destinatario = ?) 
           OR (m.id_remetente = ? AND m.id_destinatario = ?)
        ORDER BY m.data_envio ASC
    ");
    $stmt->execute([$user_id, $outro_usuario_id, $outro_usuario_id, $user_id]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensagens como lidas
    $stmt = $pdo->prepare("
        UPDATE mensagens_chat SET lida = 1 
        WHERE id_remetente = ? AND id_destinatario = ? AND lida = 0
    ");
    $stmt->execute([$outro_usuario_id, $user_id]);
    
    echo json_encode([
        'success' => true,
        'mensagens' => $mensagens,
        'total' => count($mensagens)
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao carregar mensagens: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'debug' => $e->getMessage() // Remover em produção
    ]);
} catch (Exception $e) {
    error_log("Erro geral ao carregar mensagens: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro inesperado',
        'debug' => $e->getMessage() // Remover em produção
    ]);
}
?>

