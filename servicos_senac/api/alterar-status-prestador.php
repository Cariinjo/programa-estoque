<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$novo_status = $input['status'] ?? '';

// Validar status
$status_validos = ['disponivel', 'ocupado', 'ausente', 'offline'];
if (!in_array($novo_status, $status_validos)) {
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Atualizar status do prestador
    $stmt = $pdo->prepare("
        UPDATE profissionais 
        SET disponibilidade = ?, ultima_atividade = NOW() 
        WHERE id_usuario = ?
    ");
    $stmt->execute([$novo_status, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        // Registrar log de atividade
        $stmt = $pdo->prepare("
            INSERT INTO logs_atividade (usuario_id, acao, detalhes, data_acao) 
            VALUES (?, 'status_alterado', ?, NOW())
        ");
        $stmt->execute([$user_id, "Status alterado para: $novo_status"]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Status alterado com sucesso',
            'novo_status' => $novo_status
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao alterar status']);
    }
    
} catch (PDOException $e) {
    error_log("Erro ao alterar status prestador: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>

