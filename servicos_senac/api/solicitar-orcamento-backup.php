<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['user_type'] !== 'cliente') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$userId = $_SESSION['user_id'];
$idServico = $_POST['id_servico'] ?? 0;
$descricao = $_POST['descricao'] ?? '';
$prazo = $_POST['prazo'] ?? null;
$orcamentoMax = $_POST['orcamento_max'] ?? null;

// Validações
if (empty($idServico) || empty($descricao)) {
    echo json_encode(['success' => false, 'message' => 'Dados obrigatórios não informados']);
    exit;
}

try {
    // Verificar se o serviço existe
    $stmt = $pdo->prepare("SELECT id_profissional, preco FROM servicos WHERE id_servico = ?");
    $stmt->execute([$idServico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$servico) {
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado']);
        exit;
    }
    
    // Verificar se já existe um orçamento pendente para este serviço e cliente
    $stmt = $pdo->prepare("
        SELECT id_orcamento FROM orcamentos 
        WHERE id_servico = ? AND id_cliente = ? AND status = 'pendente'
    ");
    $stmt->execute([$idServico, $userId]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Você já possui um orçamento pendente para este serviço']);
        exit;
    }
    
    // Inserir novo orçamento
    $stmt = $pdo->prepare("
        INSERT INTO orcamentos (
            id_servico, 
            id_cliente, 
            id_profissional, 
            descricao_necessidades,
            prazo_desejado,
            orcamento_maximo,
            valor_proposto,
            status,
            data_solicitacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', NOW())
    ");
    
    $stmt->execute([
        $idServico,
        $userId,
        $servico['id_profissional'],
        $descricao,
        $prazo ?: null,
        $orcamentoMax ?: null,
        $servico['preco'] // Valor inicial baseado no preço do serviço
    ]);
    
    $orcamentoId = $pdo->lastInsertId();
    
    // Criar notificação para o profissional
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes (id_usuario_destino, tipo, mensagem, data_criacao)
        SELECT prof.id_usuario, 'novo_orcamento', 
               CONCAT('Você recebeu uma nova solicitação de orçamento para \"', s.titulo, '\"'),
               NOW()
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN profissionais prof ON o.id_profissional = prof.id_profissional
        WHERE o.id_orcamento = ?
    ");
    $stmt->execute([$orcamentoId]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Orçamento solicitado com sucesso',
        'orcamento_id' => $orcamentoId
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao solicitar orçamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>

