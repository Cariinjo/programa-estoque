<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_type = $_SESSION['user_type'];

// Redirecionar baseado no tipo de usuário
if ($user_type === 'prestador') {
    header('Location: orcamentos-recebidos.php');
    exit;
} elseif ($user_type === 'cliente') {
    header('Location: meus-orcamentos.php');
    exit;
} else {
    header('Location: dashboard.php');
    exit;
}
?>

