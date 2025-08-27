<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../includes/config.php';

if (!isLoggedIn()) {
    echo json_encode(["success" => false, "error" => "Usuário não autenticado."]);
    exit;
}

$userId = $_SESSION["user_id"];
$userType = $_SESSION["user_type"];

$data = json_decode(file_get_contents("php://input"), true);

$orcamentoId = isset($data["orcamento_id"]) ? (int)$data["orcamento_id"] : 0;
$newStatus = isset($data["new_status"]) ? trim($data["new_status"]) : '';

if ($orcamentoId <= 0 || empty($newStatus)) {
    echo json_encode(["success" => false, "error" => "Dados inválidos."]);
    exit;
}

// Validar o novo status
$allowedStatuses = ['aceito', 'recusado', 'em_andamento', 'concluido', 'cancelado'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(["success" => false, "error" => "Status inválido."]);
    exit;
}

try {
    // Buscar informações do orçamento e verificar permissões
    $stmt = $pdo->prepare("
        SELECT o.id_cliente, p.id_usuario as profissional_user_id, o.status as current_status, s.titulo as servico_titulo
        FROM orcamentos o
        JOIN profissionais p ON o.id_profissional = p.id_profissional
        JOIN servicos s ON o.id_servico = s.id_servico
        WHERE o.id_orcamento = ?
    ");
    $stmt->execute([$orcamentoId]);
    $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orcamento) {
        echo json_encode(["success" => false, "error" => "Orçamento não encontrado."]);
        exit;
    }

    $isClient = ($userId == $orcamento['id_cliente']);
    $isProfessional = ($userId == $orcamento['profissional_user_id']);

    // Lógica de permissão e transição de status
    $canUpdate = false;
    $notificationMessage = '';
    $notificationType = '';
    $targetUserId = 0;

    switch ($newStatus) {
        case 'aceito':
            if ($isClient && $orcamento['current_status'] === 'pendente') {
                $canUpdate = true;
                $notificationMessage = "Seu orçamento para '" . htmlspecialchars($orcamento['servico_titulo']) . "' foi ACEITO pelo cliente.";
                $notificationType = 'orcamento_aceito';
                $targetUserId = $orcamento['profissional_user_id'];
            }
            break;
        case 'recusado':
            if (($isClient || $isProfessional) && $orcamento['current_status'] === 'pendente') {
                $canUpdate = true;
                $notificationMessage = ($isClient ? "Você recusou" : "O profissional recusou") . " o orçamento para '" . htmlspecialchars($orcamento['servico_titulo']) . "'.";
                $notificationType = 'orcamento_recusado';
                $targetUserId = $isClient ? $orcamento['profissional_user_id'] : $orcamento['id_cliente'];
            }
            break;
        case 'em_andamento':
            if ($isProfessional && $orcamento['current_status'] === 'aceito') {
                $canUpdate = true;
                $notificationMessage = "O serviço '" . htmlspecialchars($orcamento['servico_titulo']) . "' está AGORA EM ANDAMENTO.";
                $notificationType = 'servico_andamento';
                $targetUserId = $orcamento['id_cliente'];
            }
            break;
        case 'concluido':
            if ($isProfessional && $orcamento['current_status'] === 'em_andamento') {
                $canUpdate = true;
                $notificationMessage = "O serviço '" . htmlspecialchars($orcamento['servico_titulo']) . "' foi CONCLUÍDO pelo profissional. Por favor, avalie o serviço.";
                $notificationType = 'servico_concluido';
                $targetUserId = $orcamento['id_cliente'];
            }
            break;
        case 'cancelado':
            if (($isClient || $isProfessional) && ($orcamento['current_status'] === 'pendente' || $orcamento['current_status'] === 'aceito' || $orcamento['current_status'] === 'em_andamento')) {
                $canUpdate = true;
                $notificationMessage = ($isClient ? "O cliente cancelou" : "O profissional cancelou") . " o serviço '" . htmlspecialchars($orcamento['servico_titulo']) . "'.";
                $notificationType = 'servico_cancelado';
                $targetUserId = $isClient ? $orcamento['profissional_user_id'] : $orcamento['id_cliente'];
            }
            break;
    }

    if (!$canUpdate) {
        echo json_encode(["success" => false, "error" => "Você não tem permissão para alterar o status para '" . $newStatus . "' ou a transição não é permitida."]);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE orcamentos SET status = ? WHERE id_orcamento = ?");
    $stmt->execute([$newStatus, $orcamentoId]);

    // Inserir notificação
    if ($targetUserId > 0 && !empty($notificationMessage)) {
        $stmt = $pdo->prepare("INSERT INTO notificacoes (id_usuario_destino, tipo, mensagem, lida, data_criacao) VALUES (?, ?, ?, FALSE, NOW())");
        $stmt->execute([$targetUserId, $notificationType, $notificationMessage]);
    }

    $pdo->commit();

    echo json_encode(["success" => true, "message" => "Status do orçamento atualizado para '" . $newStatus . "'."]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao atualizar status do orçamento: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Erro interno do servidor ao atualizar status."]);
}
?>

