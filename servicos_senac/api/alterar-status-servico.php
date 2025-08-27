<?php
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Verificar se é prestador
if ($user_type !== 'prestador' && $user_type !== 'profissional') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas prestadores podem alterar status de serviços']);
    exit;
}

// Obter dados do POST (JSON)
$input = json_decode(file_get_contents('php://input'), true);

$id_servico = (int)($input['id_servico'] ?? 0);
$novo_status = trim($input['status'] ?? '');

// Validações
if ($id_servico <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do serviço é obrigatório']);
    exit;
}

if (empty($novo_status)) {
    echo json_encode(['success' => false, 'message' => 'Status é obrigatório']);
    exit;
}

// Validar status permitidos
$status_permitidos = ['ativo', 'fechado', 'pausado', 'inativo'];
if (!in_array($novo_status, $status_permitidos)) {
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit;
}

try {
    // Verificar se o serviço pertence ao prestador logado
    $stmt = $pdo->prepare("
        SELECT s.id_servico, s.titulo, s.status, p.id_profissional
        FROM servicos s
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        WHERE s.id_servico = ? AND p.id_usuario = ?
    ");
    $stmt->execute([$id_servico, $user_id]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$servico) {
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado ou você não tem permissão para alterá-lo']);
        exit;
    }
    
    // Verificar se o status realmente mudou
    if ($servico['status'] === $novo_status) {
        echo json_encode(['success' => false, 'message' => 'O serviço já possui este status']);
        exit;
    }
    
    // Atualizar status do serviço
    $stmt = $pdo->prepare("
        UPDATE servicos 
        SET status = ?, data_atualizacao = NOW() 
        WHERE id_servico = ?
    ");
    $stmt->execute([$novo_status, $id_servico]);
    
    // Registrar log da alteração
    $stmt = $pdo->prepare("
        INSERT INTO logs_servicos (id_servico, id_usuario, acao, status_anterior, status_novo, data_acao)
        VALUES (?, ?, 'alteracao_status', ?, ?, NOW())
    ");
    $stmt->execute([$id_servico, $user_id, $servico['status'], $novo_status]);
    
    // Criar notificação para o prestador
    $mensagem_notificacao = '';
    switch ($novo_status) {
        case 'ativo':
            $mensagem_notificacao = "Serviço '{$servico['titulo']}' foi reativado com sucesso";
            break;
        case 'fechado':
            $mensagem_notificacao = "Serviço '{$servico['titulo']}' foi fechado";
            break;
        case 'pausado':
            $mensagem_notificacao = "Serviço '{$servico['titulo']}' foi pausado";
            break;
        case 'inativo':
            $mensagem_notificacao = "Serviço '{$servico['titulo']}' foi desativado";
            break;
    }
    
    if ($mensagem_notificacao) {
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, created_at)
            VALUES (?, 'status_servico', 'Status do Serviço Alterado', ?, NOW())
        ");
        $stmt->execute([$user_id, $mensagem_notificacao]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Status do serviço alterado com sucesso',
        'servico' => [
            'id' => $id_servico,
            'titulo' => $servico['titulo'],
            'status_anterior' => $servico['status'],
            'status_novo' => $novo_status
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao alterar status do serviço: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'debug' => $e->getMessage() // Remover em produção
    ]);
} catch (Exception $e) {
    error_log("Erro geral ao alterar status do serviço: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro inesperado',
        'debug' => $e->getMessage() // Remover em produção
    ]);
}
?>

