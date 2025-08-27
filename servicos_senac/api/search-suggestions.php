<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([]);
    exit;
}

$query = trim($_GET['q']);
$suggestions = [];

try {
    // Buscar em serviços
    $stmt = $pdo->prepare("
        SELECT DISTINCT titulo as text, 'servico' as type 
        FROM servicos 
        WHERE titulo LIKE ? 
        LIMIT 5
    ");
    $stmt->execute(["%$query%"]);
    $serviceSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar em categorias
    $stmt = $pdo->prepare("
        SELECT DISTINCT nome_categoria as text, 'categoria' as type 
        FROM categorias 
        WHERE nome_categoria LIKE ? 
        LIMIT 3
    ");
    $stmt->execute(["%$query%"]);
    $categorySuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar em profissionais
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.nome as text, 'profissional' as type 
        FROM usuarios u 
        JOIN profissionais p ON u.id_usuario = p.id_usuario 
        WHERE u.nome LIKE ? OR p.area_atuacao LIKE ?
        LIMIT 3
    ");
    $stmt->execute(["%$query%", "%$query%"]);
    $professionalSuggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $suggestions = array_merge($serviceSuggestions, $categorySuggestions, $professionalSuggestions);
    
} catch (PDOException $e) {
    error_log("Erro na busca de sugestões: " . $e->getMessage());
    echo json_encode([]);
    exit;
}

echo json_encode($suggestions);
?>

