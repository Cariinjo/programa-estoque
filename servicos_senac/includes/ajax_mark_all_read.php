<?php
// Inclui o config para acesso ao $pdo e à sessão
require_once 'includes/config.php'; 

header('Content-Type: application/json');

// Verificação de segurança
if (!isLoggedIn()) {
    http_response_code(403); // Proibido
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Atualiza o banco de dados usando os nomes de coluna corretos
    $sql = "UPDATE notificacoes SET lida = 1 WHERE id_usuario_destino = ? AND lida = 0";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$user_id]);

    if ($success) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Falha ao atualizar o banco de dados.']);
    }

} catch (PDOException $e) {
    http_response_code(500); // Erro interno do servidor
    error_log("Erro no AJAX de notificações: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro de banco de dados.']);
}
?>