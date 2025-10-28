<?php
// marcar-todas-lidas.php
require_once 'includes/config.php'; // Ajuste o caminho

// Verifica se o usuário está logado
if (!isLoggedIn()) {
    header('Location: login.php'); // Ajuste o nome da página de login
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Atualiza TODAS as notificações NÃO LIDAS (lida = 0) para LIDAS (lida = 1)
    $stmt = $pdo->prepare("
        UPDATE notificacoes
        SET lida = 1 -- Marca como lida
        WHERE id_usuario_destino = ? AND lida = 0 -- Apenas as não lidas
    ");
    $stmt->execute([$userId]);

    // Opcional: Mensagem de sucesso via sessão flash
    // if (session_status() === PHP_SESSION_NONE) { session_start(); }
    // $_SESSION['flash_success'] = "Notificações marcadas como lidas.";

} catch (PDOException $e) {
    error_log("Erro marcar todas como lidas UserID {$userId}: " . $e->getMessage());
    // Opcional: Mensagem de erro via sessão flash
    // if (session_status() === PHP_SESSION_NONE) { session_start(); }
    // $_SESSION['flash_error'] = "Erro ao marcar notificações.";
}

// Redireciona de volta para a página anterior ou para index.php
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php'; // Ajuste o fallback
header('Location: ' . $referer);
exit;
?>