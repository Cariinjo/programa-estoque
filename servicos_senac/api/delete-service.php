<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $serviceId = isset($input['service_id']) ? (int)$input['service_id'] : 0;

    if ($serviceId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID do serviço inválido.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Excluir orçamentos associados a este serviço
        $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id_servico = ?");
        $stmt->execute([$serviceId]);

        // Excluir o serviço
        $stmt = $pdo->prepare("DELETE FROM servicos WHERE id_servico = ?");
        $stmt->execute([$serviceId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Serviço e orçamentos associados excluídos com sucesso.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao excluir serviço: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir serviço.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>

