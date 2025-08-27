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

if (!isset($input['service_id']) || !isset($input['rating'])) {
    echo json_encode(['error' => 'Dados incompletos']);
    exit;
}

$serviceId = (int)$input['service_id'];
$rating = (int)$input['rating'];
$comment = isset($input['comment']) ? trim($input['comment']) : '';
$userId = $_SESSION['user_id'];

// Validar rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'Avaliação deve ser entre 1 e 5']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verificar se o usuário já avaliou este serviço
    $stmt = $pdo->prepare("
        SELECT id_avaliacao 
        FROM avaliacoes 
        WHERE id_servico = ? AND id_usuario = ?
    ");
    $stmt->execute([$serviceId, $userId]);
    $existingRating = $stmt->fetch();
    
    if ($existingRating) {
        // Atualizar avaliação existente
        $stmt = $pdo->prepare("
            UPDATE avaliacoes 
            SET nota = ?, comentario = ?, data_avaliacao = CURRENT_TIMESTAMP 
            WHERE id_avaliacao = ?
        ");
        $stmt->execute([$rating, $comment, $existingRating['id_avaliacao']]);
    } else {
        // Inserir nova avaliação
        $stmt = $pdo->prepare("
            INSERT INTO avaliacoes (id_servico, id_usuario, nota, comentario) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$serviceId, $userId, $rating, $comment]);
    }
    
    // Atualizar média de avaliação do serviço
    $stmt = $pdo->prepare("
        UPDATE servicos 
        SET media_avaliacao = (
            SELECT AVG(nota) FROM avaliacoes WHERE id_servico = ?
        ),
        total_avaliacoes = (
            SELECT COUNT(*) FROM avaliacoes WHERE id_servico = ?
        )
        WHERE id_servico = ?
    ");
    $stmt->execute([$serviceId, $serviceId, $serviceId]);
    
    // Atualizar média de avaliação do profissional
    $stmt = $pdo->prepare("
        UPDATE profissionais p
        SET media_avaliacao = (
            SELECT AVG(s.media_avaliacao) 
            FROM servicos s 
            WHERE s.id_profissional = p.id_profissional
        ),
        total_avaliacoes = (
            SELECT SUM(s.total_avaliacoes) 
            FROM servicos s 
            WHERE s.id_profissional = p.id_profissional
        )
        WHERE p.id_profissional = (
            SELECT id_profissional FROM servicos WHERE id_servico = ?
        )
    ");
    $stmt->execute([$serviceId]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Avaliação enviada com sucesso']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao enviar avaliação: " . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>

