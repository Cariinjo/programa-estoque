<?php
require_once 'includes/config.php';

// Verifica se o usuário está logado para exibir conteúdo personalizado
$user_logged = isLoggedIn();
$user_name = $user_logged ? $_SESSION['user_name'] : '';
$user_type = $user_logged ? $_SESSION['user_type'] : '';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuda e Suporte - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .help-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .help-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .help-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .help-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .help-section h2 {
            color: #2d3436;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #6c5ce7;
        }
        
        .faq-item {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1.5rem;
        }
        
        .faq-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .faq-question {
            font-weight: bold;
            color: #6c5ce7;
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-question i {
            transition: transform 0.3s ease;
        }
        
        .faq-question.active i {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            color: #636e72;
            font-size: 0.95rem;
            display: none;
            margin-top: 0.5rem;
        }
        
        .contact-info p {
            margin-bottom: 0.5rem;
            color: #2d3436;
        }
        
        .contact-info a {
            color: #6c5ce7;
            text-decoration: none;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .help-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="help-container">
            <div class="help-header">
                <h1><i class="fas fa-question-circle"></i> Ajuda e Suporte</h1>
                <p>Encontre respostas para suas dúvidas ou entre em contato conosco.</p>
            </div>
            
            <div class="help-section">
                <h2>Perguntas Frequentes (FAQ)</h2>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Como faço para me cadastrar como profissional?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Para se cadastrar como profissional, clique em "Cadastrar" no menu superior e selecione "Sou Prestador". Preencha o formulário com seus dados e informações sobre seus serviços.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Como solicito um orçamento?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Após encontrar o serviço ou profissional desejado, clique no botão "Solicitar Orçamento" na página de detalhes. Preencha as informações necessárias e aguarde o contato do profissional.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Como funciona o sistema de chat?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Nosso sistema de chat permite que clientes e profissionais se comuniquem em tempo real após a solicitação de um orçamento ou contratação de um serviço. Você pode acessá-lo através da página de detalhes do orçamento ou do seu dashboard.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Posso editar meu perfil após o cadastro?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Sim, você pode editar suas informações de perfil a qualquer momento. Basta acessar seu dashboard e clicar em "Editar Perfil".
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        Como faço para avaliar um serviço?
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Após a conclusão de um serviço, você receberá uma notificação ou poderá encontrar a opção de avaliação no histórico de seus pedidos. Sua avaliação é muito importante para a comunidade!
                    </div>
                </div>
            </div>
            
            <div class="help-section">
                <h2>Ainda Precisa de Ajuda?</h2>
                <div class="contact-info">
                    <p>Se você não encontrou a resposta para sua pergunta, entre em contato com nossa equipe de suporte:</p>
                    <p><i class="fas fa-envelope"></i> Email: <a href="mailto:suporte@servicossenac.com">suporte@servicossenac.com</a></p>
                    <p><i class="fas fa-phone"></i> Telefone: (XX) XXXX-XXXX (Horário comercial)</p>
                    <p>Ou utilize nosso formulário de contato: <a href="contato.php">Página de Contato</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script>
        function toggleAnswer(element) {
            const answer = element.nextElementSibling;
            element.classList.toggle('active');
            if (answer.style.display === 'block') {
                answer.style.display = 'none';
            } else {
                answer.style.display = 'block';
            }
        }
    </script>
</body>
</html>


