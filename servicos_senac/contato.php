<?php
require_once 'includes/config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    
    // Validação
    if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } else {
        try {
            // Salvar mensagem no banco de dados
            $stmt = $pdo->prepare("
                INSERT INTO contatos (nome, email, telefone, assunto, mensagem, data_envio) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nome, $email, $telefone, $assunto, $mensagem]);
            
            $success = true;
            
            // Limpar campos após sucesso
            $nome = $email = $telefone = $assunto = $mensagem = '';
            
        } catch (PDOException $e) {
            error_log("Erro ao salvar contato: " . $e->getMessage());
            $error = 'Erro interno. Tente novamente mais tarde.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .contact-container {
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
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-bottom: 4rem;
        }
        
        .contact-form-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-title {
            font-size: 1.8rem;
            color: #2d3436;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-subtitle {
            color: #636e72;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #6c5ce7;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-button {
            width: 100%;
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .form-button:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: #d1f2eb;
            color: #00b894;
            border: 1px solid #00b894;
        }
        
        .alert-error {
            background: #fab1a0;
            color: #e17055;
            border: 1px solid #e17055;
        }
        
        .contact-info-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .info-title {
            font-size: 1.8rem;
            color: #2d3436;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .contact-details h4 {
            color: #2d3436;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .contact-details p {
            color: #636e72;
            margin: 0;
            line-height: 1.5;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .social-link {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .social-link:hover {
            transform: translateY(-3px);
            color: white;
        }
        
        .map-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .map-title {
            font-size: 1.8rem;
            color: #2d3436;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .map-description {
            color: #636e72;
            margin-bottom: 2rem;
        }
        
        .map-placeholder {
            width: 100%;
            height: 300px;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .hours-section {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        
        .hours-title {
            font-size: 1.8rem;
            color: #2d3436;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .hours-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .hours-item {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 15px;
        }
        
        .hours-item h4 {
            color: #2d3436;
            margin-bottom: 1rem;
        }
        
        .hours-item p {
            color: #636e72;
            margin: 0.25rem 0;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .contact-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .contact-form-section,
            .contact-info-section {
                padding: 2rem;
            }
            
            .hours-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="contact-container">
            <!-- Header da Página -->
            <div class="page-header">
                <h1><i class="fas fa-envelope"></i> Entre em Contato</h1>
                <p>Estamos aqui para ajudar! Entre em contato conosco através dos canais abaixo ou envie uma mensagem diretamente.</p>
            </div>
            
            <!-- Conteúdo Principal -->
            <div class="contact-content">
                <!-- Formulário de Contato -->
                <div class="contact-form-section">
                    <h2 class="form-title">
                        <i class="fas fa-paper-plane"></i>
                        Envie uma Mensagem
                    </h2>
                    <p class="form-subtitle">
                        Preencha o formulário abaixo e nossa equipe entrará em contato com você em até 24 horas.
                    </p>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Mensagem enviada com sucesso! Entraremos em contato em breve.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="nome">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" class="form-input" required value="<?= htmlspecialchars($nome ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-input" required value="<?= htmlspecialchars($email ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" class="form-input" value="<?= htmlspecialchars($telefone ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="assunto">Assunto *</label>
                            <select id="assunto" name="assunto" class="form-select" required>
                                <option value="">Selecione um assunto</option>
                                <option value="Dúvidas Gerais" <?= ($assunto ?? '') === 'Dúvidas Gerais' ? 'selected' : '' ?>>Dúvidas Gerais</option>
                                <option value="Suporte Técnico" <?= ($assunto ?? '') === 'Suporte Técnico' ? 'selected' : '' ?>>Suporte Técnico</option>
                                <option value="Cadastro de Profissional" <?= ($assunto ?? '') === 'Cadastro de Profissional' ? 'selected' : '' ?>>Cadastro de Profissional</option>
                                <option value="Problemas com Serviços" <?= ($assunto ?? '') === 'Problemas com Serviços' ? 'selected' : '' ?>>Problemas com Serviços</option>
                                <option value="Sugestões" <?= ($assunto ?? '') === 'Sugestões' ? 'selected' : '' ?>>Sugestões</option>
                                <option value="Parcerias" <?= ($assunto ?? '') === 'Parcerias' ? 'selected' : '' ?>>Parcerias</option>
                                <option value="Outros" <?= ($assunto ?? '') === 'Outros' ? 'selected' : '' ?>>Outros</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="mensagem">Mensagem *</label>
                            <textarea id="mensagem" name="mensagem" class="form-textarea" required placeholder="Descreva sua dúvida ou solicitação..."><?= htmlspecialchars($mensagem ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="form-button">
                            <i class="fas fa-paper-plane"></i>
                            Enviar Mensagem
                        </button>
                    </form>
                </div>
                
                <!-- Informações de Contato -->
                <div class="contact-info-section">
                    <h2 class="info-title">
                        <i class="fas fa-info-circle"></i>
                        Informações de Contato
                    </h2>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Endereço</h4>
                            <p>SENAC SÃO JOAO DEL REI, 123<br>Centro<br>CEP: 36300-000</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Telefone</h4>
                            <p>(32) 98431-4926<br>(32) 98765-4321</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email</h4>
                            <p>contato@servicossenac.com.br<br>suporte@servicossenac.com.br</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Website</h4>
                            <p>www.servicossenac.com.br</p>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Mapa -->
            <div class="map-section">
                <h2 class="map-title">
                    <i class="fas fa-map"></i>
                    Nossa Localização
                </h2>
                <p class="map-description">
                    Visite nossa sede e conheça nossa equipe pessoalmente.
                </p>
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt"></i>
                    Mapa Interativo em Breve
                </div>
            </div>
            
            <!-- Horários de Funcionamento -->
            <div class="hours-section">
                <h2 class="hours-title">
                    <i class="fas fa-clock"></i>
                    Horários de Atendimento
                </h2>
                
                <div class="hours-grid">
                    <div class="hours-item">
                        <h4>Atendimento Online</h4>
                        <p><strong>Segunda a Sexta:</strong> 8h às 18h</p>
                        <p><strong>Sábado:</strong> 9h às 14h</p>
                        <p><strong>Domingo:</strong> Fechado</p>
                    </div>
                    
                    <div class="hours-item">
                        <h4>Suporte Técnico</h4>
                        <p><strong>Segunda a Sexta:</strong> 9h às 17h</p>
                        <p><strong>Sábado:</strong> 10h às 13h</p>
                        <p><strong>Domingo:</strong> Fechado</p>
                    </div>
                    
                    <div class="hours-item">
                        <h4>Atendimento Presencial</h4>
                        <p><strong>Segunda a Sexta:</strong> 9h às 17h</p>
                        <p><strong>Sábado:</strong> Agendamento</p>
                        <p><strong>Domingo:</strong> Fechado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/main.js"></script>
    <script>
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                if (value.length < 14) {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                }
                e.target.value = value;
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const nome = document.getElementById('nome').value.trim();
            const email = document.getElementById('email').value.trim();
            const assunto = document.getElementById('assunto').value;
            const mensagem = document.getElementById('mensagem').value.trim();
            
            if (!nome || !email || !assunto || !mensagem) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }
            
            if (!email.includes('@') || !email.includes('.')) {
                e.preventDefault();
                alert('Por favor, insira um email válido.');
                return;
            }
            
            if (mensagem.length < 10) {
                e.preventDefault();
                alert('A mensagem deve ter pelo menos 10 caracteres.');
                return;
            }
        });
    </script>
</body>
</html>

