<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'servicos_senac');
define('DB_USER', 'senac');
define('DB_PASS', 'senac123');

// Configurações gerais
define('SITE_URL', 'http://localhost');
define('SITE_NAME', 'Serviços SENAC');

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Função para sanitizar dados
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Função para verificar se o usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectToDashboard() {
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'profissional') {
        header('Location: dashboard-profissional.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

// Iniciar sessão
session_start();
?>

