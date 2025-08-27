<?php require_once 'includes/config.php'; ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Como Funciona - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .how-it-works-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 4rem;
            padding: 3rem 0;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            border-radius: 20px;
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .section {
            margin-bottom: 4rem;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            color: #2d3436;
            margin-bottom: 3rem;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .step-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
            position: relative;
        }
        
        .step-card:hover {
            transform: translateY(-10px);
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 1.5rem auto;
        }
        
        .step-icon {
            font-size: 3rem;
            color: #6c5ce7;
            margin-bottom: 1.5rem;
        }
        
        .step-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 1rem;
        }
        
        .step-description {
            color: #636e72;
            line-height: 1.6;
        }
        
        .user-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 3rem;
            margin-bottom: 4rem;
        }
        
        .user-type-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .user-type-header {
            padding: 2rem;
            text-align: center;
            color: white;
        }
        
        .client-header {
            background: linear-gradient(45deg, #00b894, #55efc4);
        }
        
        .professional-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
        }
        
        .user-type-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .user-type-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .user-type-subtitle {
            opacity: 0.9;
        }
        
        .user-type-content {
            padding: 2rem;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #2d3436;
        }
        
        .feature-list i {
            color: #6c5ce7;
            width: 20px;
        }
        
        .cta-section {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 4rem 2rem;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .cta-title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .cta-description {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta-button {
            padding: 1rem 2rem;
            border: 2px solid white;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .cta-button:hover {
            background: white;
            color: #6c5ce7;
        }
        
        .faq-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .faq-item {
            border-bottom: 1px solid #eee;
            padding: 1.5rem 0;
        }
        
        .faq-item:last-child {
            border-bottom: none;
        }
        
        .faq-question {
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .faq-question i {
            color: #6c5ce7;
        }
        
        .faq-answer {
            color: #636e72;
            line-height: 1.6;
            padding-left: 2rem;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .benefit-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .benefit-icon {
            font-size: 2.5rem;
            color: #6c5ce7;
            margin-bottom: 1rem;
        }
        
        .benefit-title {
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        
        .benefit-description {
            color: #636e72;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
            }
            
            .user-types {
                grid-template-columns: 1fr;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="how-it-works-container">
            <!-- Header da Página -->
            <div class="page-header">
                <h1><i class="fas fa-question-circle"></i> Como Funciona</h1>
                <p>Descubra como nossa plataforma conecta talentos do SENAC com clientes que buscam serviços de qualidade.</p>
            </div>
            
            <!-- Processo Geral -->
            <div class="section">
                <h2 class="section-title">Processo Simples em 3 Passos</h2>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="step-title">Busque Serviços</h3>
                        <p class="step-description">
                            Navegue por nossa ampla gama de serviços ou use nossa busca avançada para encontrar exatamente o que precisa. Filtre por categoria, preço e avaliações.
                        </p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3 class="step-title">Entre em Contato</h3>
                        <p class="step-description">
                            Converse diretamente com o profissional através do nosso sistema de chat integrado. Solicite orçamentos personalizados e tire todas suas dúvidas.
                        </p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="step-title">Contrate e Avalie</h3>
                        <p class="step-description">
                            Após a conclusão do serviço, avalie o profissional para ajudar outros usuários. Sua experiência contribui para a qualidade da plataforma.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Para Diferentes Tipos de Usuário -->
            <div class="section">
                <h2 class="section-title">Para Cada Tipo de Usuário</h2>
                <div class="user-types">
                    <!-- Para Clientes -->
                    <div class="user-type-card">
                        <div class="user-type-header client-header">
                            <div class="user-type-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3 class="user-type-title">Para Clientes</h3>
                            <p class="user-type-subtitle">Encontre o profissional ideal</p>
                        </div>
                        <div class="user-type-content">
                            <ul class="feature-list">
                                <li><i class="fas fa-check"></i> Busca avançada por categoria e localização</li>
                                <li><i class="fas fa-check"></i> Visualização de perfis detalhados</li>
                                <li><i class="fas fa-check"></i> Sistema de avaliações confiável</li>
                                <li><i class="fas fa-check"></i> Chat direto com profissionais</li>
                                <li><i class="fas fa-check"></i> Orçamentos personalizados</li>
                                <li><i class="fas fa-check"></i> Histórico de serviços contratados</li>
                                <li><i class="fas fa-check"></i> Notificações em tempo real</li>
                                <li><i class="fas fa-check"></i> Suporte ao cliente dedicado</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Para Profissionais -->
                    <div class="user-type-card">
                        <div class="user-type-header professional-header">
                            <div class="user-type-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h3 class="user-type-title">Para Profissionais</h3>
                            <p class="user-type-subtitle">Divulgue seus talentos</p>
                        </div>
                        <div class="user-type-content">
                            <ul class="feature-list">
                                <li><i class="fas fa-check"></i> Perfil profissional completo</li>
                                <li><i class="fas fa-check"></i> Cadastro ilimitado de serviços</li>
                                <li><i class="fas fa-check"></i> Dashboard com estatísticas</li>
                                <li><i class="fas fa-check"></i> Gerenciamento de orçamentos</li>
                                <li><i class="fas fa-check"></i> Sistema de chat integrado</li>
                                <li><i class="fas fa-check"></i> Controle de avaliações</li>
                                <li><i class="fas fa-check"></i> Relatórios de desempenho</li>
                                <li><i class="fas fa-check"></i> Visibilidade na plataforma</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Benefícios -->
            <div class="section">
                <h2 class="section-title">Por Que Escolher Nossa Plataforma?</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="benefit-title">Segurança</h4>
                        <p class="benefit-description">Todos os profissionais são verificados e formados pelo SENAC, garantindo qualidade e confiabilidade.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4 class="benefit-title">Qualidade</h4>
                        <p class="benefit-description">Sistema de avaliações transparente que garante a qualidade dos serviços prestados.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="benefit-title">Agilidade</h4>
                        <p class="benefit-description">Encontre e contrate profissionais rapidamente através de nossa plataforma otimizada.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h4 class="benefit-title">Comunicação</h4>
                        <p class="benefit-description">Chat integrado para comunicação direta e eficiente entre clientes e profissionais.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="benefit-title">Crescimento</h4>
                        <p class="benefit-description">Plataforma que cresce com você, oferecendo ferramentas para expandir seus negócios.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 class="benefit-title">Suporte</h4>
                        <p class="benefit-description">Equipe de suporte dedicada para ajudar em todas as etapas do processo.</p>
                    </div>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div class="cta-section">
                <h2 class="cta-title">Pronto para Começar?</h2>
                <p class="cta-description">
                    Junte-se à nossa comunidade de profissionais qualificados e clientes satisfeitos. 
                    Cadastre-se agora e descubra as oportunidades que esperam por você.
                </p>
                <div class="cta-buttons">
                    <a href="cadastro.php?type=client" class="cta-button">
                        <i class="fas fa-user-plus"></i> Sou Cliente
                    </a>
                    <a href="cadastro.php?type=professional" class="cta-button">
                        <i class="fas fa-briefcase"></i> Sou Profissional
                    </a>
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="section">
                <h2 class="section-title">Perguntas Frequentes</h2>
                <div class="faq-section">
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-question-circle"></i>
                            Como posso ter certeza da qualidade dos profissionais?
                        </div>
                        <div class="faq-answer">
                            Todos os profissionais da nossa plataforma são formados pelo SENAC e passam por um processo de verificação. Além disso, contamos com um sistema de avaliações transparente onde clientes anteriores compartilham suas experiências.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-question-circle"></i>
                            Como funciona o sistema de orçamentos?
                        </div>
                        <div class="faq-answer">
                            Após encontrar um serviço de interesse, você pode solicitar um orçamento personalizado. O profissional analisará suas necessidades e enviará uma proposta detalhada. Você pode aceitar, recusar ou negociar os termos.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-question-circle"></i>
                            Existe alguma taxa para usar a plataforma?
                        </div>
                        <div class="faq-answer">
                            O cadastro e a busca por serviços são completamente gratuitos para clientes. Para profissionais, oferecemos planos flexíveis que se adequam às suas necessidades de negócio.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-question-circle"></i>
                            Como posso entrar em contato com um profissional?
                        </div>
                        <div class="faq-answer">
                            Nossa plataforma oferece um sistema de chat integrado que permite comunicação direta e segura entre clientes e profissionais. Todas as conversas ficam registradas para sua segurança.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-question-circle"></i>
                            O que acontece se eu não ficar satisfeito com o serviço?
                        </div>
                        <div class="faq-answer">
                            Temos uma equipe de suporte dedicada para resolver qualquer problema. Além disso, incentivamos a comunicação aberta entre clientes e profissionais para garantir que as expectativas sejam atendidas.
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-question-circle"></i>
                            Como posso me tornar um profissional verificado?
                        </div>
                        <div class="faq-answer">
                            Para se tornar um profissional verificado, você precisa ser formado pelo SENAC e completar nosso processo de cadastro, incluindo a verificação de documentos e qualificações. Nossa equipe analisará sua candidatura em até 48 horas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/main.js"></script>
    <script>
        // Adicionar interatividade às perguntas FAQ
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', function() {
                const answer = this.nextElementSibling;
                const icon = this.querySelector('i');
                
                if (answer.style.display === 'none' || answer.style.display === '') {
                    answer.style.display = 'block';
                    icon.className = 'fas fa-minus-circle';
                } else {
                    answer.style.display = 'none';
                    icon.className = 'fas fa-question-circle';
                }
            });
        });
        
        // Inicializar FAQ com respostas ocultas
        document.querySelectorAll('.faq-answer').forEach(answer => {
            answer.style.display = 'none';
        });
    </script>
</body>
</html>

