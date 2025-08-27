<?php
require_once '../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Usuário não está logado']);
    exit;
}

// Verificar se é cliente
if ($_SESSION['user_type'] !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'Apenas clientes podem solicitar orçamentos']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Obter dados do POST (JSON ou form-data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id_servico = (int)($input['id_servico'] ?? 0);
$detalhes_solicitacao = trim($input['detalhes_solicitacao'] ?? $input['descricao'] ?? '');
$prazo_desejado = $input['prazo_desejado'] ?? null;
$orcamento_maximo = $input['orcamento_maximo'] ?? null;

// Validações
if ($id_servico <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do serviço é obrigatório']);
    exit;
}

if (empty($detalhes_solicitacao)) {
    echo json_encode(['success' => false, 'message' => 'Descrição das necessidades é obrigatória']);
    exit;
}

try {
    // Verificar se o serviço existe e está ativo
    $stmt = $pdo->prepare("
        SELECT s.*, p.id_profissional, u.nome as prestador_nome
        FROM servicos s
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE s.id_servico = ? AND s.status_servico = 'ativo'
    ");
    $stmt->execute([$id_servico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$servico) {
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado ou não está ativo']);
        exit;
    }
    
    // Verificar se já existe um orçamento pendente para este serviço e cliente
    $stmt = $pdo->prepare("
        SELECT id_orcamento FROM orcamentos 
        WHERE id_servico = ? AND id_cliente = ? AND status IN ('pendente', 'respondido')
    ");
    $stmt->execute([$id_servico, $user_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Você já possui um orçamento em andamento para este serviço']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Inserir novo orçamento
    $stmt = $pdo->prepare("
        INSERT INTO orcamentos (
            id_servico, 
            id_cliente, 
            id_profissional, 
            detalhes_solicitacao,
            prazo_desejado,
            orcamento_maximo,
            status,
            data_solicitacao
        ) VALUES (?, ?, ?, ?, ?, ?, 'pendente', NOW())
    ");
    
    $stmt->execute([
        $id_servico,
        $user_id,
        $servico['id_profissional'],
        $detalhes_solicitacao,
        $prazo_desejado,
        $orcamento_maximo
    ]);
    
    $orcamento_id = $pdo->lastInsertId();
    
    // Criar notificação para o prestador
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, created_at)
        VALUES (?, 'orcamento_novo', 'Nova Solicitação de Orçamento', ?, NOW())
    ");
    
    $mensagem = "Você recebeu uma nova solicitação de orçamento para o serviço \"{$servico['titulo']}\"";
    $stmt->execute([
        $servico['id_profissional'], // Usar o ID do usuário prestador, não do profissional
        $mensagem
    ]);
    
    // Buscar o ID do usuário prestador
    $stmt = $pdo->prepare("SELECT id_usuario FROM profissionais WHERE id_profissional = ?");
    $stmt->execute([$servico['id_profissional']]);
    $prestador_user_id = $stmt->fetchColumn();
    
    if ($prestador_user_id) {
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, created_at)
            VALUES (?, 'orcamento_novo', 'Nova Solicitação de Orçamento', ?, NOW())
        ");
        $stmt->execute([$prestador_user_id, $mensagem]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Orçamento solicitado com sucesso! O prestador será notificado.',
        'orcamento_id' => $orcamento_id,
        'prestador' => $servico['prestador_nome']
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao solicitar orçamento: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor. Tente novamente.',
        'debug' => $e->getMessage() // Remover em produção
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro geral ao solicitar orçamento: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro inesperado. Tente novamente.',
        'debug' => $e->getMessage() // Remover em produção
    ]);
}
?>

