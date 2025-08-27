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
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID do usuário inválido.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Verificar se o usuário é um profissional e, se sim, excluir dados relacionados
        $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $professionalId = $stmt->fetchColumn();

        if ($professionalId) {
            // Excluir serviços associados ao profissional
            $stmt = $pdo->prepare("DELETE FROM servicos WHERE id_profissional = ?");
            $stmt->execute([$professionalId]);

            // Excluir orçamentos associados ao profissional
            $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id_profissional = ?");
            $stmt->execute([$professionalId]);

            // Excluir o registro do profissional
            $stmt = $pdo->prepare("DELETE FROM profissionais WHERE id_profissional = ?");
            $stmt->execute([$professionalId]);
        }

        // Excluir orçamentos onde o usuário é o cliente
        $stmt = $pdo->prepare("DELETE FROM orcamentos WHERE id_cliente = ?");
        $stmt->execute([$userId]);

        // Excluir mensagens de chat onde o usuário é remetente ou destinatário
        $stmt = $pdo->prepare("DELETE FROM mensagens_chat WHERE id_remetente = ? OR id_destinatario = ?");
        $stmt->execute([$userId, $userId]);

        // Excluir notificações do usuário
        $stmt = $pdo->prepare("DELETE FROM notificacoes WHERE id_usuario_destino = ?");
        $stmt->execute([$userId]);

        // Finalmente, excluir o usuário
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$userId]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Usuário e dados associados excluídos com sucesso.']);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao excluir usuário: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor ao excluir usuário.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>

