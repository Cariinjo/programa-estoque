<?php
require_once '../includes/config.php'; // Ajuste o caminho se necessário

// Defina o tipo de conteúdo da resposta como JSON
header('Content-Type: application/json');
// Permita requisições de qualquer origem (Ajuste em produção se necessário)
header('Access-Control-Allow-Origin: *');
// Permita apenas o método POST
header('Access-Control-Allow-Methods: POST');
// Permita o header Content-Type (importante para POST com JSON)
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

// Verificar se o método é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$user_id = $_SESSION['user_id']; // ID do cliente logado

// Obter dados do POST (JSON ou form-data)
// Tenta ler o corpo da requisição como JSON primeiro
$input = json_decode(file_get_contents('php://input'), true);
// Se não for JSON, tenta pegar de $_POST (form-data)
if (!$input) {
    $input = $_POST;
}

// Pegar e limpar os dados de entrada
$id_servico = (int)($input['id_servico'] ?? 0);
$detalhes_solicitacao = trim($input['detalhes_solicitacao'] ?? $input['descricao'] ?? ''); // Compatibilidade com nome 'descricao'
$prazo_desejado_str = $input['prazo_desejado'] ?? null; // Data como string (ex: "20/10/2025")
$orcamento_maximo_str = $input['orcamento_maximo'] ?? null; // Orçamento como string, pode ser vazio

// *** INÍCIO DA CORREÇÃO DA DATA E ORÇAMENTO MÁXIMO ***
$prazo_desejado_sql = null; // Valor padrão para o banco de dados
$orcamento_maximo_sql = null; // Valor padrão para o banco de dados

// Tenta converter a data se ela não for nula ou vazia
if (!empty($prazo_desejado_str)) {
    // Cria um objeto DateTime a partir do formato DD/MM/AAAA
    $dateObj = DateTime::createFromFormat('d/m/Y', $prazo_desejado_str);

    // Verifica se a conversão foi bem-sucedida E se a data original era válida nesse formato
    // (createFromFormat pode ser leniente, então verificamos se formatar de volta dá a mesma string)
    if ($dateObj !== false && $dateObj->format('d/m/Y') === $prazo_desejado_str) {
         // Formata para o padrão do MySQL (AAAA-MM-DD)
        $prazo_desejado_sql = $dateObj->format('Y-m-d');
    } else {
        // Se a data for inválida, retorna erro ao usuário
        echo json_encode(['success' => false, 'message' => 'Formato de data inválido para Prazo Desejado. Use DD/MM/AAAA.']);
        exit;
    }
}

// Tenta converter o orçamento máximo se ele não for nulo ou vazio
if (!empty($orcamento_maximo_str)) {
    // Remove caracteres não numéricos exceto ponto e vírgula
    $orcamento_limpo = preg_replace('/[^\d,\.]/', '', $orcamento_maximo_str);
    // Substitui vírgula por ponto para conversão em float
    $orcamento_limpo = str_replace(',', '.', $orcamento_limpo);
    $orcamento_float = filter_var($orcamento_limpo, FILTER_VALIDATE_FLOAT);

    if ($orcamento_float !== false && $orcamento_float >= 0) {
        $orcamento_maximo_sql = $orcamento_float;
    } else {
        // Se o valor for inválido (não numérico ou negativo), retorna erro
        echo json_encode(['success' => false, 'message' => 'Valor inválido para Orçamento Máximo.']);
        exit;
    }
}
// *** FIM DA CORREÇÃO ***


// Validações dos campos obrigatórios
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
    $stmt_servico = $pdo->prepare("
        SELECT s.id_servico, s.titulo, p.id_profissional, p.id_usuario as id_usuario_prestador, u.nome as prestador_nome
        FROM servicos s
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE s.id_servico = ? 
        -- AND s.status_servico = 'ativo' -- Removido temporariamente caso a coluna não exista
    ");
    $stmt_servico->execute([$id_servico]);
    $servico = $stmt_servico->fetch(PDO::FETCH_ASSOC);
    
    if (!$servico) {
        // Se status_servico existir, descomente a linha acima e use esta mensagem:
        // echo json_encode(['success' => false, 'message' => 'Serviço não encontrado ou não está ativo']);
        echo json_encode(['success' => false, 'message' => 'Serviço não encontrado']);
        exit;
    }
    
    $id_profissional_servico = $servico['id_profissional'];
    $id_usuario_prestador = $servico['id_usuario_prestador']; // ID do usuário que receberá a notificação

    // 2. Verificar se já existe um orçamento pendente ou respondido para este cliente e serviço
    $stmt_check = $pdo->prepare("
        SELECT id_orcamento FROM orcamentos 
        WHERE id_servico = ? AND id_cliente = ? AND status IN ('pendente', 'respondido')
    ");
    $stmt_check->execute([$id_servico, $user_id]);
    
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Você já possui um orçamento em andamento para este serviço']);
        exit;
    }
    
    // Inicia a transação
    $pdo->beginTransaction();
    
    // 3. Inserir novo orçamento na tabela 'orcamentos'
    $stmt_insert = $pdo->prepare("
        INSERT INTO orcamentos (
            id_servico, 
            id_cliente, 
            id_profissional, 
            detalhes_solicitacao,
            prazo_desejado,         -- Coluna no DB
            orcamento_maximo,       -- Coluna no DB
            status,
            data_solicitacao
        ) VALUES (?, ?, ?, ?, ?, ?, 'pendente', NOW())
    ");
    
    // Executa a inserção com os valores corretos e formatados
    $stmt_insert->execute([
        $id_servico,
        $user_id,                       // id_cliente (usuário logado)
        $id_profissional_servico,       // id_profissional (dono do serviço)
        $detalhes_solicitacao,
        $prazo_desejado_sql,            // Data formatada para YYYY-MM-DD ou NULL
        $orcamento_maximo_sql           // Valor float ou NULL
    ]);
    
    // Pega o ID do orçamento que acabou de ser inserido
    $orcamento_id = $pdo->lastInsertId();
    
    // 4. Criar notificação para o prestador (se ele foi encontrado)
    if ($id_usuario_prestador) {
        
        // Mensagem da notificação
        $mensagem = "Você recebeu uma nova solicitação de orçamento para o serviço \"{$servico['titulo']}\".";
        // Link para onde o prestador será direcionado ao clicar na notificação
        $link_acao = "orcamentos.php?id=" . $orcamento_id; // Ajuste o nome do arquivo se necessário

        // Inserir na tabela 'notificacoes'
        $stmt_notif = $pdo->prepare("
            INSERT INTO notificacoes (
                id_usuario_destino, 
                tipo_notificacao, 
                mensagem, 
                link_acao, 
                data_criacao,
                lida -- Adicionado campo lida com valor padrão 0
            ) VALUES (?, 'orcamento_novo', ?, ?, NOW(), 0) -- Assumindo que lida começa como 0 (não lida)
        ");
        
        // Executa a inserção da notificação
        $stmt_notif->execute([
            $id_usuario_prestador, // Para quem é a notificação
            $mensagem,             // O texto da notificação
            $link_acao             // O link de destino
        ]);
    } else {
        // Log ou tratamento caso o id_usuario_prestador não seja encontrado (improvável se o serviço existe)
        error_log("Aviso: Prestador (id_usuario) não encontrado para notificação do orçamento ID: " . $orcamento_id);
    }
    
    // Se tudo deu certo até aqui, confirma as operações no banco de dados
    $pdo->commit();
    
    // Retorna sucesso para o cliente via JSON
    echo json_encode([
        'success' => true, 
        'message' => 'Orçamento solicitado com sucesso! O prestador ' . htmlspecialchars($servico['prestador_nome']) . ' será notificado.',
        'orcamento_id' => $orcamento_id
        // Removido 'prestador' daqui pois já está na mensagem
    ]);
    
} catch (PDOException $e) {
    // Se qualquer operação de banco de dados falhar, desfaz tudo (rollback)
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Loga o erro detalhado no servidor (importante para depuração)
    error_log("Erro ao solicitar orçamento (PDO): " . $e->getMessage());
    // Retorna uma mensagem genérica para o usuário
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor ao processar a solicitação. Tente novamente.',
        'debug' => $e->getMessage() // Mantenha isso apenas em ambiente de desenvolvimento
    ]);
} catch (Exception $e) {
    // Captura outros erros gerais (como validações ou lógicas)
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Garante rollback mesmo para exceções não-PDO
    }
    // Loga o erro geral
    error_log("Erro geral ao solicitar orçamento: " . $e->getMessage());
    // Retorna uma mensagem genérica
    echo json_encode([
        'success' => false, 
        'message' => 'Ocorreu um erro inesperado. Tente novamente.',
        'debug' => $e->getMessage() // Mantenha isso apenas em ambiente de desenvolvimento
    ]);
}
?>