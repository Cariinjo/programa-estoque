<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$chatId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$chatId) {
    header('Location: dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Verificar acesso ao chat e obter informações do orçamento
    $stmt = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, s.preco,
               u_cliente.nome as cliente_nome,
               u_profissional.nome as profissional_nome,
               p.area_atuacao
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN usuarios u_cliente ON o.id_cliente = u_cliente.id_usuario
        JOIN profissionais prof ON o.id_profissional = prof.id_profissional
        JOIN usuarios u_profissional ON prof.id_usuario = u_profissional.id_usuario
        WHERE o.id_orcamento = ? AND (o.id_cliente = ? OR prof.id_usuario = ?)
    ");
    $stmt->execute([$chatId, $userId, $userId]);
    $orcamento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orcamento) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Determinar se o usuário é cliente ou profissional e o ID do outro usuário
    $isCliente = ($userId == $orcamento["id_cliente"]);
    $otherUserId = $isCliente ? $orcamento["profissional_user_id"] : $orcamento["id_cliente"];
    $otherUserName = $isCliente ? $orcamento["profissional_nome"] : $orcamento["cliente_nome"];
    
} catch (PDOException $e) {
    error_log("Erro ao carregar chat: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?= htmlspecialchars($otherUserName) ?> - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chat-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            height: 80vh;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-info h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.3rem;
        }
        
        .chat-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .chat-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #00b894;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
        }
        
        .message.own {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .message-content {
            max-width: 70%;
            background: white;
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .message.own .message-content {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
        }
        
        .message-text {
            margin: 0;
            line-height: 1.4;
        }
        
        .message-time {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 0.5rem;
        }
        
        .chat-input {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #eee;
        }
        
        .message-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .message-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 25px;
            resize: none;
            min-height: 50px;
            max-height: 100px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .message-input:focus {
            border-color: #6c5ce7;
        }
        
        .send-button {
            width: 50px;
            height: 50px;
            border: none;
            border-radius: 50%;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }
        
        .send-button:hover {
            transform: scale(1.1);
        }
        
        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .order-info {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .order-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2d3436;
        }
        
        .order-info p {
            margin: 0;
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .status-pendente {
            background: #ffeaa7;
            color: #d63031;
        }
        
        .status-aceito {
            background: #d1f2eb;
            color: #00b894;
        }
        
        .status-concluido {
            background: #d1f2eb;
            color: #00b894;
        }
        
        .status-recusado {
            background: #fab1a0;
            color: #e17055;
        }
        
        .typing-indicator {
            display: none;
            padding: 1rem;
            color: #636e72;
            font-style: italic;
        }
        
        .back-button {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.3s ease;
        }
        
        .back-button:hover {
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                margin: 1rem;
                height: calc(100vh - 2rem);
            }
            
            .chat-header {
                padding: 1rem;
            }
            
            .chat-info h2 {
                font-size: 1.1rem;
            }
            
            .message-content {
                max-width: 85%;
            }
            
            .chat-input {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="chat-container" data-chat-id="<?= $chatId ?>">
            <!-- Header do Chat -->
            <div class="chat-header">
                <div class="chat-info">
                    <h2><i class="fas fa-comments"></i> Chat com <?= htmlspecialchars($otherUserName) ?></h2>
                    <p><?= htmlspecialchars($orcamento['servico_titulo']) ?> - R$ <?= number_format($orcamento['preco'], 2, ',', '.') ?></p>
                </div>
                
                <div class="chat-actions">
                    <a href="dashboard.php" class="back-button">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
            
            <!-- Informações do Orçamento -->
            <div class="order-info">
                <h4>Informações do Orçamento</h4>
                <p><strong>Serviço:</strong> <?= htmlspecialchars($orcamento['servico_titulo']) ?></p>
                <p><strong>Valor:</strong> R$ <?= number_format($orcamento['valor_proposto'] ?: $orcamento['preco'], 2, ',', '.') ?></p>
                <p><strong>Data da Solicitação:</strong> <?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></p>
                <span class="order-status status-<?= $orcamento['status'] ?>">
                    <?= ucfirst($orcamento['status']) ?>
                </span>
            </div>
            
            <!-- Mensagens -->
            <div class="chat-messages" id="chatMessages">
                <!-- Mensagens serão carregadas via JavaScript -->
            </div>
            
            <!-- Indicador de digitação -->
            <div class="typing-indicator" id="typingIndicator">
                <i class="fas fa-ellipsis-h"></i> Digitando...
            </div>
            
            <!-- Input de Mensagem -->
            <div class="chat-input">
                <form class="message-form" id="messageForm">
                    <textarea 
                        class="message-input" 
                        id="messageInput" 
                        placeholder="Digite sua mensagem..."
                        rows="1"
                    ></textarea>
                    <button type="submit" class="send-button" id="sendButton">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        // Configuração específica do chat
        document.addEventListener('DOMContentLoaded', function() {
            const chatContainer = document.querySelector('.chat-container');
            const messagesContainer = document.getElementById('chatMessages');
            const messageForm = document.getElementById('messageForm');
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const typingIndicator = document.getElementById('typingIndicator');
            
            const chatId = chatContainer.dataset.chatId;
            let lastMessageId = 0;
            let isTyping = false;
            let typingTimeout;
            
            // Carregar mensagens iniciais
            loadMessages();
            
            // Atualizar mensagens a cada 3 segundos
            setInterval(loadMessages, 3000);
            
            // Envio de mensagem
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });
            
            // Auto-resize do textarea
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
                
                // Indicador de digitação
                if (!isTyping) {
                    isTyping = true;
                    // Aqui você pode enviar uma notificação de que o usuário está digitando
                }
                
                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    isTyping = false;
                    // Parar indicador de digitação
                }, 1000);
            });
            
            // Enviar com Enter (Shift+Enter para nova linha)
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            function loadMessages() {
                const currentUserId = <?= $userId ?>;
                fetch(`api/chat-messages.php?chat_id=${chatId}&current_user_id=${currentUserId}`)                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayMessages(data.messages);
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar mensagens:', error);
                    });
            }
            
            function displayMessages(messages) {
                const currentUserId = <?= $userId ?>;
                
                messagesContainer.innerHTML = '';
                
                messages.forEach(message => {
                    const messageElement = document.createElement('div');
                    messageElement.className = `message ${message.is_own ? 'own' : 'other'}`;
                    
                    const avatarLetter = message.nome_remetente.charAt(0).toUpperCase();
                    
                    messageElement.innerHTML = `
                        <div class="message-avatar">${avatarLetter}</div>
                        <div class="message-content">
                            <p class="message-text">${escapeHtml(message.mensagem)}</p>
                            <div class="message-time">${formatDateTime(message.data_envio)}</div>
                        </div>
                    `;
                    
                    messagesContainer.appendChild(messageElement);
                    
                    if (message.id_mensagem > lastMessageId) {
                        lastMessageId = message.id_mensagem;
                    }
                });
                
                // Scroll para a última mensagem
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            function sendMessage() {
                const message = messageInput.value.trim();
                
                if (!message) return;
                
                sendButton.disabled = true;
                
                fetch('api/send-message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        chat_id: chatId,
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        messageInput.style.height = 'auto';
                        loadMessages();
                    } else {
                        alert('Erro ao enviar mensagem: ' + (data.error || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao enviar mensagem.');
                })
                .finally(() => {
                    sendButton.disabled = false;
                    messageInput.focus();
                });
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            function formatDateTime(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diffTime = Math.abs(now - date);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays === 1) {
                    return 'Hoje às ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                } else if (diffDays === 2) {
                    return 'Ontem às ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                } else {
                    return date.toLocaleDateString('pt-BR') + ' às ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
                }
            }
        });
    </script>
</body>
</html>

