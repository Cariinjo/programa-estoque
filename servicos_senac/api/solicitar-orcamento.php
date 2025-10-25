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

$user_id = $_SESSION['user_id']; // ID do cliente logado

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
    // 1. Verificar se o serviço existe e pegar dados do prestador
    $stmt = $pdo->prepare("
        SELECT s.*, p.id_profissional, p.id_usuario as id_usuario_prestador, u.nome as prestador_nome
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
    
    $id_profissional_servico = $servico['id_profissional'];
    $id_usuario_prestador = $servico['id_usuario_prestador']; // ID do usuário que receberá a notificação

    // 2. Verificar se já existe um orçamento pendente
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
    
    // 3. Inserir novo orçamento
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
        $user_id, // id_cliente
        $id_profissional_servico, // id_profissional
        $detalhes_solicitacao,
        $prazo_desejado,
        $orcamento_maximo
    ]);
    
    $orcamento_id = $pdo->lastInsertId();
    
    // 4. Criar notificação para o prestador (BLOCO CORRIGIDO)
    if ($id_usuario_prestador) {
        
        // Definir a mensagem e o link
        $mensagem = "Você recebeu uma nova solicitação de orçamento para o serviço \"{$servico['titulo']}\".";
        $link_acao = "orcamentos.php?id=" . $orcamento_id;

        // Inserir na tabela 'notificacoes' com os nomes corretos
        $stmt_notif = $pdo->prepare("
            INSERT INTO notificacoes (
                id_usuario_destino, 
                tipo_notificacao, 
                mensagem, 
                link_acao, 
                data_criacao
            ) VALUES (?, 'orcamento_novo', ?, ?, NOW())
        ");
        
        $stmt_notif->execute([
            $id_usuario_prestador,
            $mensagem,
            $link_acao
        ]);
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
    error_log("Erro ao solicitar orçamento (PDO): " . $e->getMessage());
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