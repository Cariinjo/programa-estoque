<?php
// ajuda-profissional.php
require_once 'includes/config.php'; // Ajuste o caminho se necessário
require_once 'includes/helpers.php'; // Para funções auxiliares, se usadas no header

// Verifica se o usuário está logado (para exibir o header correto)
// Não restringe o acesso apenas a clientes ou prestadores, qualquer um logado pode ver
if (!isLoggedIn()) {
    // Redireciona para login SE a página de ajuda for apenas para logados
    // Se for pública, remova este bloco ou apenas não busque dados do usuário
    // header('Location: login.php');
    // exit;
}

$userId = $_SESSION['user_id'] ?? null; // Pega o ID se logado, senão null
$userType = $_SESSION['user_type'] ?? null;
$error = null;
$success = null;
$orcamentos = []; // Define como vazio para evitar erros no HTML se o header tentar usar

// --- NENHUMA LÓGICA DE BUSCA DE DADOS ESPECÍFICA PARA ESTA PÁGINA ---
// --- NENHUM PROCESSAMENTO DE FORMULÁRIO (POST) ---

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuda para Profissionais - Serviços SENAC</title>
    <link rel="stylesheet" href="/teste/servicos_senac/css/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos da página meus-orcamentos.php que queremos REUTILIZAR */
        .ajuda-container { /* Renomeado de .orcamentos-container */
            max-width: 900px; /* Um pouco mais estreito talvez? */
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header { /* Reutiliza o estilo do cabeçalho roxo */
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); /* Tema Prestador */
            /* Ou Mantenha o roxo: background: linear-gradient(45deg, #6c5ce7, #a29bfe); */
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }

        .page-header p {
            margin: 0;
            opacity: 0.9;
        }

        .faq-section { /* Container para os itens de FAQ */
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 1.5rem 2rem; /* Mais padding */
        }

        .faq-item { /* Cada pergunta/resposta */
            border-bottom: 1px solid #eee;
            padding: 1.5rem 0;
        }
        .faq-item:last-child {
            border-bottom: none;
            padding-bottom: 0.5rem; /* Menos padding no último */
        }
         .faq-item:first-child {
            padding-top: 0.5rem; /* Menos padding no primeiro */
        }

        .faq-question { /* Estilo da pergunta */
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3436;
            margin-bottom: 0.75rem;
            cursor: pointer; /* Indica que pode ser clicável (se adicionar JS para expandir) */
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
         .faq-question i { /* Ícone antes da pergunta */
            color: #6c5ce7; /* Cor roxa */
             transition: transform 0.3s ease;
        }
        /* Efeito opcional se usar JS para expandir/recolher */
         /* .faq-item.active .faq-question i { transform: rotate(90deg); } */

        .faq-answer { /* Estilo da resposta */
            color: #636e72;
            line-height: 1.6;
            font-size: 0.95rem;
            padding-left: 2rem; /* Indenta a resposta em relação ao ícone */
            /* display: none; */ /* Opcional: Esconder por padrão se usar JS */
        }
         /* Efeito opcional se usar JS para expandir/recolher */
         /* .faq-item.active .faq-answer { display: block; } */

        .faq-answer p {
            margin-bottom: 0.75rem;
        }
         .faq-answer ul {
            margin-left: 20px;
            margin-bottom: 0.75rem;
        }
         .faq-answer li {
            margin-bottom: 0.5rem;
         }

        .contact-support { /* Seção extra no final */
             margin-top: 2rem;
             text-align: center;
             padding: 1.5rem;
             background-color: #f8f9fa;
             border-radius: 10px;
        }
        .contact-support h4 {
            margin-bottom: 0.5rem;
            color: #2d3436;
        }
         .contact-support p {
             color: #636e72;
             margin-bottom: 1rem;
         }
        .btn-contact { /* Reutiliza a classe btn se já definida */
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-contact:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Responsividade básica */
        @media (max-width: 768px) {
            .faq-section { padding: 1rem 1.5rem; }
            .faq-question { font-size: 1rem; }
            .faq-answer { padding-left: 1.5rem; font-size: 0.9rem;}
        }

    </style>
</head>
<body class="logged-in"> <?php include 'includes/header.php'; // Inclui o cabeçalho padrão ?>

    <div class="container"> <div class="ajuda-container">
            <div class="page-header">
                <h1><i class="fas fa-question-circle"></i> Ajuda para Profissionais</h1>
                <p>Encontre respostas para as dúvidas mais comuns sobre como oferecer seus serviços na plataforma.</p>
            </div>

            <div class="faq-section">

                <div class="faq-item">
                    <h3 class="faq-question"><i class="fas fa-user-plus"></i> Como me cadastro como profissional?</h3>
                    <div class="faq-answer">
                        <p>Para se cadastrar como profissional, siga estes passos:</p>
                        <ul>
                            <li>Clique no botão "Cadastrar" no canto superior direito.</li>
                            <li>Escolha a opção "Sou Profissional".</li>
                            <li>Preencha seus dados pessoais (Nome, Email, Senha, Telefone, Cidade).</li>
                            <li>Preencha os dados profissionais (CPF, Categoria Principal, Descrição do Perfil).</li>
                            <li>Leia e aceite os termos (se houver).</li>
                            <li>Clique em "Criar Conta".</li>
                        </ul>
                        <p>Após o cadastro, você poderá acessar seu dashboard e configurar seus serviços.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-question"><i class="fas fa-id-card"></i> Como completar e editar meu perfil?</h3>
                    <div class="faq-answer">
                        <p>Manter seu perfil atualizado é importante para atrair clientes. Para editar:</p>
                        <ul>
                            <li>Faça login na sua conta de prestador.</li>
                            <li>Acesse o "Meu Dashboard" (geralmente clicando no seu nome/avatar no topo).</li>
                            <li>Procure pela opção "Editar Perfil" ou similar.</li>
                            <li>Você poderá atualizar sua foto, descrição, anos de experiência, telefone, WhatsApp, endereço (se aplicável), cidade e status de disponibilidade.</li>
                            <li>Não se esqueça de salvar as alterações.</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-question"><i class="fas fa-cogs"></i> Como cadastro e gerencio meus serviços?</h3>
                    <div class="faq-answer">
                        <p>Para adicionar ou editar os serviços que você oferece:</p>
                        <ul>
                            <li>Acesse o "Meu Dashboard".</li>
                            <li>Clique na opção "Meus Serviços".</li>
                            <li>Para adicionar um novo, clique em "Cadastrar Novo Serviço" (ou similar). Preencha o título, descrição detalhada, categoria, preço (fixo ou negociável) e prazo estimado de execução.</li>
                            <li>Para editar um serviço existente, encontre-o na lista e clique no botão de edição (geralmente um ícone de lápis).</li>
                            <li>Você também pode pausar ou excluir serviços que não oferece mais.</li>
                        </ul>
                    </div>
                </div>

                 <div class="faq-item">
                    <h3 class="faq-question"><i class="fas fa-inbox"></i> Como funcionam os orçamentos recebidos?</h3>
                    <div class="faq-answer">
                        <p>Quando um cliente se interessa por um de seus serviços, ele pode solicitar um orçamento detalhando suas necessidades.</p>
                        <ul>
                            <li>Você receberá uma notificação sobre a nova solicitação.</li>
                            <li>Acesse a seção "Orçamentos Recebidos" no seu dashboard.</li>
                            <li>Leia os detalhes da solicitação do cliente.</li>
                            <li>Responda ao orçamento propondo um valor (pode ser o valor original do serviço ou outro), um prazo de execução e adicionando observações (detalhes, materiais, etc.).</li>
                            <li>Clique em "Enviar Resposta". O cliente será notificado.</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <h3 class="faq-question"><i class="fas fa-check-circle"></i> O cliente aceitou meu orçamento. E agora?</h3>
                    <div class="faq-answer">
                        <p>Parabéns! Quando um cliente aceita seu orçamento:</p>
                        <ul>
                            <li>Você receberá uma notificação.</li>
                            <li>O status do orçamento mudará para "Aceito".</li>
                            <li>Recomendamos entrar em contato com o cliente para combinar os detalhes finais, como data de início, forma de pagamento (se não for via plataforma), e alinhar expectativas.</li>
                            <li>Você pode usar o chat da plataforma (se disponível) ou os contatos do cliente (Telefone/WhatsApp) que ficam visíveis após o aceite.</li>
                            <li>Após concluir o serviço, lembre-se de marcar o status apropriado (se a plataforma tiver essa função) e peça ao cliente para avaliar seu trabalho.</li>
                        </ul>
                    </div>
                </div>

                 <div class="faq-item">
                    <h3 class="faq-question"><i class="fas fa-star"></i> Como funcionam as avaliações?</h3>
                    <div class="faq-answer">
                        <p>As avaliações são importantes para construir sua reputação na plataforma.</p>
                        <ul>
                            <li>Após a conclusão de um serviço originado de um orçamento aceito, o cliente pode deixar uma avaliação (nota e comentário).</li>
                            <li>Boas avaliações aumentam sua visibilidade e a confiança de futuros clientes.</li>
                            <li>Preze pela qualidade do serviço e boa comunicação para receber feedbacks positivos.</li>
                            <li>As avaliações geralmente ficam visíveis no seu perfil público.</li>
                        </ul>
                    </div>
                </div>

                </div> <div class="contact-support">
                <h4>Ainda com dúvidas?</h4>
                <p>Se não encontrou a resposta que procurava, entre em contato conosco.</p>
                <a href="contato.php" class="btn btn-contact">
                    <i class="fas fa-envelope"></i> Fale Conosco
                </a>
            </div>

        </div> </div> <?php // include 'includes/footer.php'; // Inclua seu rodapé se houver ?>

    <script>
        // // Opcional: Adiciona funcionalidade de clique para expandir/recolher respostas
        // document.querySelectorAll('.faq-question').forEach(question => {
        //     question.addEventListener('click', () => {
        //         const item = question.closest('.faq-item');
        //         item.classList.toggle('active'); // Adiciona/remove a classe 'active'
        //     });
        // });

         // Script para menus do header (copiado do header.php, se necessário)
        const userMenuToggle = document.getElementById('user-menu-toggle');
        const userDropdown = document.getElementById('user-dropdown');
        const notificationToggleBtn = document.getElementById('notification-toggle-btn');
        const notificationsPanel = document.getElementById('notifications-panel');
        const overlay = document.getElementById('header-overlay');
        const mobileMenuBtn = document.getElementById('mobile-menu-toggle-btn');
        const nav = document.getElementById('header-nav');

        function closeAllDropdowns() {
            userDropdown?.classList.remove('active');
            notificationsPanel?.classList.remove('active');
            overlay?.classList.remove('active');
            nav?.classList.remove('active');
        }
        if (userMenuToggle) { userMenuToggle.addEventListener('click', (e) => { e.stopPropagation(); const isActive = userDropdown.classList.contains('active'); closeAllDropdowns(); if (!isActive) { userDropdown.classList.add('active'); overlay.classList.add('active'); } }); }
        if (notificationToggleBtn) { notificationToggleBtn.addEventListener('click', (e) => { e.stopPropagation(); const isActive = notificationsPanel.classList.contains('active'); closeAllDropdowns(); if (!isActive) { notificationsPanel.classList.add('active'); overlay.classList.add('active'); } }); }
        if (mobileMenuBtn && nav) { mobileMenuBtn.addEventListener('click', (e) => { e.stopPropagation(); const isActive = nav.classList.contains('active'); closeAllDropdowns(); if (!isActive) { nav.classList.add('active'); overlay.classList.add('active'); } }); }
        if (overlay) { overlay.addEventListener('click', closeAllDropdowns); }
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeAllDropdowns(); } });
        userDropdown?.addEventListener('click', (e) => e.stopPropagation());
        notificationsPanel?.addEventListener('click', (e) => { if (!e.target.closest('a')) { e.stopPropagation(); } });

    </script>
</body>
</html>