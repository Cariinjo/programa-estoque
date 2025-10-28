<?php
// api/buscar-notificacoes-api.php
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Origin: *'); // RESTRINJA em produção!
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';
require_once '../includes/helpers.php'; // Inclui a função time_elapsed_string

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Use GET.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // 1. Buscar contagem de não lidas
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*)
        FROM notificacoes
        WHERE id_usuario_destino = ? AND lida = 0 -- Usando 0
    ");
    $stmt_count->execute([$userId]);
    $unreadCount = (int)$stmt_count->fetchColumn();

    // 2. Buscar últimas notificações com ícone
    $stmt_list = $pdo->prepare("
        SELECT
            id_notificacao, mensagem, link_acao, data_criacao, lida, tipo_notificacao,
            CASE tipo_notificacao
                WHEN 'orcamento_novo' THEN 'fas fa-file-invoice'
                WHEN 'orcamento_respondido' THEN 'fas fa-reply'
                WHEN 'orcamento_aceito' THEN 'fas fa-check-circle'
                WHEN 'orcamento_recusado' THEN 'fas fa-times-circle'
                WHEN 'avaliacao_nova' THEN 'fas fa-star'
                WHEN 'mensagem_nova' THEN 'fas fa-comments'
                WHEN 'servico_visualizado' THEN 'fas fa-eye'
                ELSE 'fas fa-bell'
            END as icone
        FROM notificacoes
        WHERE id_usuario_destino = ?
        ORDER BY data_criacao DESC
        LIMIT 10
    ");
    $stmt_list->execute([$userId]);
    $notifications_raw = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

    // Adiciona tempo relativo e converte 'lida'
    $notifications_processed = [];
    foreach ($notifications_raw as $notif) {
        $notif['tempo'] = time_elapsed_string($notif['data_criacao']);
        $notif['lida'] = (bool)$notif['lida'];
        $notifications_processed[] = $notif;
    }

    // Retorna os dados
    echo json_encode([
        'success'       => true,
        'unread_count'  => $unreadCount,
        'notifications' => $notifications_processed
    ]);

} catch (PDOException $e) {
    error_log("Erro buscar notificações API (PDO): UserID {$userId} - " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
} catch (Exception $e) {
     error_log("Erro geral buscar notificações API: UserID {$userId} - " . $e->getMessage());
     http_response_code(500);
     echo json_encode(['success' => false, 'message' => 'Erro interno inesperado']);
}
?>