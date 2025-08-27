<?php
require_once 'includes/config.php';

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$cliente_id = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
$orcamento_id = isset($_GET['orcamento']) ? (int)$_GET['orcamento'] : 0;

if (!$cliente_id && !$orcamento_id) {
    header('Location: dashboard-prestador.php');
    exit;
}

try {
    // Buscar ID do profissional
    $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $prestador_id = $stmt->fetchColumn();
    
    if (!$prestador_id) {
        header('Location: login.php');
        exit;
    }
    
    // Se foi passado o ID do orçamento, buscar informações completas
    if ($orcamento_id) {
        $stmt = $pdo->prepare("
            SELECT o.*, s.titulo as servico_titulo, s.descricao as servico_descricao,
                   u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
                   u.endereco_completo as cliente_endereco
            FROM orcamentos o
            JOIN servicos s ON o.id_servico = s.id_servico
            JOIN usuarios u ON o.id_cliente = u.id_usuario
            WHERE o.id_orcamento = ? AND o.id_profissional = ?
        ");
        $stmt->execute([$orcamento_id, $prestador_id]);
        $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orcamento) {
            header('Location: orcamentos-recebidos.php');
            exit;
        }
        
        $cliente_id = $orcamento['id_cliente'];
        $cliente_nome = $orcamento['cliente_nome'];
        
    } else {
        // Buscar informações do cliente
        $stmt = $pdo->prepare("SELECT nome, email, telefone FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$cliente_id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cliente) {
            header('Location: dashboard-prestador.php');
            exit;
        }
        
        $cliente_nome = $cliente['nome'];
    }
    
    // Buscar mensagens do chat
    $stmt = $pdo->prepare("
        SELECT m.*, u.nome as remetente_nome
        FROM mensagens_chat m
        JOIN usuarios u ON m.id_remetente = u.id_usuario
        WHERE (m.id_remetente = ? AND m.id_destinatario = ?) 
           OR (m.id_remetente = ? AND m.id_destinatario = ?)
        ORDER BY m.data_envio ASC
    ");
    $stmt->execute([$user_id, $cliente_id, $cliente_id, $user_id]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Marcar mensagens como lidas
    $stmt = $pdo->prepare("
        UPDATE mensagens_chat SET lida = 1 
        WHERE id_remetente = ? AND id_destinatario = ? AND lida = 0
    ");
    $stmt->execute([$cliente_id, $user_id]);
    
} catch (PDOException $e) {
    error_log("Erro ao carregar chat: " . $e->getMessage());
    header('Location: dashboard-prestador.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com <?= htmlspecialchars($cliente_nome) ?> - Prestador</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chat-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .chat-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        
        .chat-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-action:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .chat-body {
            background: white;
            flex: 1;
            display: flex;
            flex-direction: column;
            border-left: 1px solid #e9ecef;
            border-right: 1px solid #e9ecef;
        }
        
        .messages-container {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .message.own {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .message.own .message-avatar {
            background: #2c3e50;
        }
        
        .message-content {
            max-width: 70%;
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .message.own .message-content {
            background: #3498db;
            color: white;
        }
        
        .message-text {
            margin: 0 0 0.5rem 0;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        .message-form {
            padding: 1rem;
            background: white;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 15px 15px;
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        
        .message-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            resize: none;
            min-height: 45px;
            max-height: 120px;
            font-family: inherit;
        }
        
        .message-input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .send-button {
            background: #3498db;
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .send-button:hover {
            background: #2980b9;
        }
        
        .send-button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .orcamento-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
        }
        
        .orcamento-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }
        
        .orcamento-info p {
            margin: 0.25rem 0;
            color: #5a6c7d;
            font-size: 0.9rem;
        }
        
        .empty-chat {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        
        .empty-chat i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                height: calc(100vh - 150px);
                margin: 1rem auto;
            }
            
            .chat-header {
                flex-direction: column;
                text-align: center;
            }
            
            .message-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header-prestador.php'; ?>
    
    <div class="chat-container">
        <!-- Header do Chat -->
        <div class="chat-header">
            <div class="chat-info">
                <h1><i class="fas fa-comments"></i> Chat com <?= htmlspecialchars($cliente_nome) ?></h1>
                <?php if (isset($orcamento)): ?>
                    <p>Orçamento: <?= htmlspecialchars($orcamento['servico_titulo']) ?></p>
                <?php else: ?>
                    <p>Conversa direta com cliente</p>
                <?php endif; ?>
            </div>
            <div class="chat-actions">
                <?php if (isset($orcamento) && !empty($orcamento['cliente_telefone'])): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $orcamento['cliente_telefone']) ?>" 
                       target="_blank" class="btn-action">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                <?php endif; ?>
                <a href="orcamentos-recebidos.php" class="btn-action">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        
        <!-- Corpo do Chat -->
        <div class="chat-body">
            <!-- Informações do Orçamento -->
            <?php if (isset($orcamento)): ?>
                <div class="orcamento-info">
                    <h4><?= htmlspecialchars($orcamento['servico_titulo']) ?></h4>
                    <p><strong>Status:</strong> <?= ucfirst($orcamento['status']) ?></p>
                    <?php if ($orcamento['valor_proposto']): ?>
                        <p><strong>Valor Proposto:</strong> R$ <?= number_format($orcamento['valor_proposto'], 2, ',', '.') ?></p>
                    <?php endif; ?>
                    <?php if ($orcamento['detalhes_solicitacao']): ?>
                        <p><strong>Detalhes:</strong> <?= htmlspecialchars($orcamento['detalhes_solicitacao']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Container de Mensagens -->
            <div class="messages-container" id="messagesContainer">
                <?php if (empty($mensagens)): ?>
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <h3>Nenhuma mensagem ainda</h3>
                        <p>Inicie a conversa enviando uma mensagem</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($mensagens as $mensagem): ?>
                        <div class="message <?= $mensagem['id_remetente'] == $user_id ? 'own' : '' ?>">
                            <div class="message-avatar">
                                <?= strtoupper(substr($mensagem['remetente_nome'], 0, 1)) ?>
                            </div>
                            <div class="message-content">
                                <p class="message-text"><?= nl2br(htmlspecialchars($mensagem['mensagem'])) ?></p>
                                <div class="message-time">
                                    <?= date('d/m/Y H:i', strtotime($mensagem['data_envio'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Formulário de Mensagem -->
            <form class="message-form" id="messageForm">
                <div class="input-group">
                    <textarea class="message-input" 
                              id="messageInput" 
                              placeholder="Digite sua mensagem..." 
                              rows="1"></textarea>
                    <button type="submit" class="send-button" id="sendButton">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const messagesContainer = document.getElementById('messagesContainer');
        
        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        
        // Enviar mensagem
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const mensagem = messageInput.value.trim();
            if (!mensagem) return;
            
            sendButton.disabled = true;
            
            fetch('api/enviar-mensagem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    destinatario_id: <?= $cliente_id ?>,
                    mensagem: mensagem
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    messageInput.style.height = 'auto';
                    carregarMensagens();
                } else {
                    alert('Erro ao enviar mensagem: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar mensagem');
            })
            .finally(() => {
                sendButton.disabled = false;
                messageInput.focus();
            });
        });
        
        // Carregar mensagens
        function carregarMensagens() {
            fetch(`api/carregar-mensagens.php?cliente_id=<?= $cliente_id ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    atualizarMensagens(data.mensagens);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar mensagens:', error);
            });
        }
        
        // Atualizar interface de mensagens
        function atualizarMensagens(mensagens) {
            if (mensagens.length === 0) {
                messagesContainer.innerHTML = `
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <h3>Nenhuma mensagem ainda</h3>
                        <p>Inicie a conversa enviando uma mensagem</p>
                    </div>
                `;
                return;
            }
            
            messagesContainer.innerHTML = '';
            
            mensagens.forEach(mensagem => {
                const isOwn = mensagem.id_remetente == <?= $user_id ?>;
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${isOwn ? 'own' : ''}`;
                
                messageDiv.innerHTML = `
                    <div class="message-avatar">
                        ${mensagem.remetente_nome.charAt(0).toUpperCase()}
                    </div>
                    <div class="message-content">
                        <p class="message-text">${mensagem.mensagem.replace(/\n/g, '<br>')}</p>
                        <div class="message-time">
                            ${new Date(mensagem.data_envio).toLocaleString('pt-BR')}
                        </div>
                    </div>
                `;
                
                messagesContainer.appendChild(messageDiv);
            });
            
            // Scroll para a última mensagem
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Atualizar mensagens periodicamente
        setInterval(carregarMensagens, 5000);
        
        // Scroll inicial para o final
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Focus no input
        messageInput.focus();
        
        // Enter para enviar (Shift+Enter para nova linha)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                messageForm.dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>

