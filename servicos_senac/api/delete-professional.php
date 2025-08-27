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
    $professionalId = isset($input['professional_id']) ? (int)$input['professional_id'] : 0;

    if ($professionalId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID do profissional inválido.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Obter o id_usuario associado ao profissional
        $stmt = $pdo->prepare("SELECT id_usuario FROM profissionais WHERE id_profissional = ?");
        $stmt->execute([$professionalId]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Profissional não encontrado.']);
            exit;
        }

        // Excluir serviços associados ao profissional
        $stmt = $pdo->prepare("DELETE FROM servicos WHERE id_profissional = ?");
        $stmt->execute([$professionalId]);

        // Excluir orçamentos associados aos serviços do profissional
        // (Isso pode ser mais complexo dependendo da estrutura de orçamentos, aqui assumimos que orcamentos estão ligados a servicos)
        // Ou, se orcamentos estão diretamente ligados a id_profissional:
        $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id_profissional = ?");
        $stmt->execute([$professionalId]);

        // Excluir o profissional
        $stmt = $pdo->prepare("DELETE FROM profissionais WHERE id_profissional = ?");
        $stmt->execute([$professionalId]);

        // Excluir o usuário associado
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$userId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Profissional e dados associados excluídos com sucesso.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao excluir profissional: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir profissional.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>

