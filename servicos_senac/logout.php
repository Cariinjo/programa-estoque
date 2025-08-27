<?php
require_once 'includes/config.php';

// Verificar se há uma sessão ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão se existir
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Limpar qualquer cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirecionar para página inicial
header('Location: index.php');
exit;
?>

