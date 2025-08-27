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

// Obter dados do POST (JSON ou form-data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$destinatario_id = (int)($input['destinatario_id'] ?? 0);
$mensagem = trim($input['mensagem'] ?? '');

// Validações
if ($destinatario_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do destinatário é obrigatório']);
    exit;
}

if (empty($mensagem)) {
    echo json_encode(['success' => false, 'message' => 'Mensagem não pode estar vazia']);
    exit;
}

if ($destinatario_id == $user_id) {
    echo json_encode(['success' => false, 'message' => 'Não é possível enviar mensagem para si mesmo']);
    exit;
}

try {
    // Verificar se o destinatário existe
    $stmt = $pdo->prepare("SELECT id_usuario, nome FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$destinatario_id]);
    $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$destinatario) {
        echo json_encode(['success' => false, 'message' => 'Destinatário não encontrado']);
        exit;
    }
    
    // Verificar se existe uma tabela de mensagens_chat, se não, criar
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'mensagens_chat'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        // Criar tabela de mensagens_chat
        $pdo->exec("
            CREATE TABLE mensagens_chat (
                id_mensagem INT AUTO_INCREMENT PRIMARY KEY,
                id_remetente INT NOT NULL,
                id_destinatario INT NOT NULL,
                mensagem TEXT NOT NULL,
                data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                lida BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (id_remetente) REFERENCES usuarios(id_usuario),
                FOREIGN KEY (id_destinatario) REFERENCES usuarios(id_usuario)
            )
        ");
    }
    
    // Inserir mensagem
    $stmt = $pdo->prepare("
        INSERT INTO mensagens_chat (id_remetente, id_destinatario, mensagem, data_envio, lida)
        VALUES (?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([$user_id, $destinatario_id, $mensagem]);
    
    $mensagem_id = $pdo->lastInsertId();
    
    // Criar notificação para o destinatário
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, created_at)
        VALUES (?, 'mensagem_nova', 'Nova Mensagem', ?, NOW())
    ");
    
    $stmt_user = $pdo->prepare("SELECT nome FROM usuarios WHERE id_usuario = ?");
    $stmt_user->execute([$user_id]);
    $remetente_nome = $stmt_user->fetchColumn();
    
    $notificacao_mensagem = "Você recebeu uma nova mensagem de {$remetente_nome}";
    $stmt->execute([$destinatario_id, $notificacao_mensagem]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mensagem enviada com sucesso',
        'mensagem_id' => $mensagem_id,
        'destinatario' => $destinatario['nome']
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao enviar mensagem: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'debug' => $e->getMessage() // Remover em produção
    ]);
} catch (Exception $e) {
    error_log("Erro geral ao enviar mensagem: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro inesperado',
        'debug' => $e->getMessage() // Remover em produção
    ]);
}
?>

