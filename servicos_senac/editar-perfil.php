<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

$success = '';
$error = '';

try {
    // Buscar informações do usuário
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: logout.php');
        exit;
    }
    
    // Se for profissional, buscar dados específicos
    $professional = null;
    if ($userType === 'profissional') {
        $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id_usuario = ?");
        $stmt->execute([$userId]);
        $professional = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Buscar cidades para o select
    $stmt = $pdo->prepare("SELECT * FROM cidades_senac_mg ORDER BY nome_cidade");
    $stmt->execute();
    $cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar categorias para profissionais
    $categories = [];
    if ($userType === 'profissional') {
        $stmt = $pdo->prepare("SELECT * FROM categorias ORDER BY nome");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Processar formulário
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $telefone = trim($_POST['telefone']);
        $whatsapp = trim($_POST['whatsapp']);
        $endereco_completo = trim($_POST['endereco_completo']);
        $cep = trim($_POST['cep']);
        $cidade_id = $_POST['cidade_id'];
        
        // Validações básicas
        if (empty($nome) || empty($email)) {
            $error = "Nome e email são obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email inválido.";
        } else {
            // Verificar se email já existe (exceto o próprio usuário)
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $error = "Este email já está sendo usado por outro usuário.";
            } else {
                // Atualizar dados do usuário
                $stmt = $pdo->prepare("
                    UPDATE usuarios SET 
                        nome = ?, email = ?, telefone = ?, whatsapp = ?, 
                        endereco_completo = ?, cep = ?, cidade_id = ?
                    WHERE id_usuario = ?
                ");
                $stmt->execute([
                    $nome, $email, $telefone, $whatsapp, 
                    $endereco_completo, $cep, $cidade_id, $userId
                ]);
                
                // Se for profissional, atualizar dados específicos
                if ($userType === 'profissional' && $professional) {
                    $area_atuacao = trim($_POST['area_atuacao']);
                    $descricao_perfil = trim($_POST['descricao_perfil']);
                    $endereco_comercial = trim($_POST['endereco_comercial']);
                    $disponibilidade = $_POST['disponibilidade'];
                    $aceita_orcamento = isset($_POST['aceita_orcamento']) ? 1 : 0;
                    $atende_presencial = isset($_POST['atende_presencial']) ? 1 : 0;
                    $atende_online = isset($_POST['atende_online']) ? 1 : 0;
                    
                    $stmt = $pdo->prepare("
                        UPDATE profissionais SET 
                            area_atuacao = ?, descricao_perfil = ?, endereco_comercial = ?,
                            disponibilidade = ?, aceita_orcamento = ?, atende_presencial = ?, 
                            atende_online = ?, cidade_id = ?
                        WHERE id_usuario = ?
                    ");
                    $stmt->execute([
                        $area_atuacao, $descricao_perfil, $endereco_comercial,
                        $disponibilidade, $aceita_orcamento, $atende_presencial,
                        $atende_online, $cidade_id, $userId
                    ]);
                }
                
                // Atualizar dados na sessão
                $_SESSION['user_name'] = $nome;
                $_SESSION['user_email'] = $email;
                
                $success = "Perfil atualizado com sucesso!";
                
                // Recarregar dados atualizados
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userType === 'profissional') {
                    $stmt = $pdo->prepare("SELECT * FROM profissionais WHERE id_usuario = ?");
                    $stmt->execute([$userId]);
                    $professional = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Erro ao editar perfil: " . $e->getMessage());
    $error = "Erro ao atualizar perfil. Tente novamente.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .profile-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .profile-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: #2d3436;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #6c5ce7;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d3436;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6c5ce7;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
        
        .btn-primary {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #636e72;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 1rem;
        }
        
        .btn-secondary:hover {
            background: #2d3436;
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .profile-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h1><i class="fas fa-user-edit"></i> Editar Perfil</h1>
                <p>Mantenha suas informações sempre atualizadas.</p>
            </div>
            
            <div class="profile-form">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <!-- Dados Pessoais -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Dados Pessoais</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome Completo *</label>
                                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($user['telefone']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="whatsapp">WhatsApp</label>
                                <input type="tel" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($user['whatsapp']) ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Endereço -->
                    <div class="form-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Endereço</h3>
                        
                        <div class="form-group">
                            <label for="endereco_completo">Endereço Completo</label>
                            <textarea id="endereco_completo" name="endereco_completo" placeholder="Rua, número, bairro..."><?= htmlspecialchars($user['endereco_completo']) ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cep">CEP</label>
                                <input type="text" id="cep" name="cep" value="<?= htmlspecialchars($user['cep']) ?>" placeholder="00000-000">
                            </div>
                            <div class="form-group">
                                <label for="cidade_id">Cidade</label>
                                <select id="cidade_id" name="cidade_id">
                                    <option value="">Selecione uma cidade</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= $city['id_cidade'] ?>" <?= $user['cidade_id'] == $city['id_cidade'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($city['nome_cidade']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dados Profissionais (apenas para profissionais) -->
                    <?php if ($userType === 'profissional' && $professional): ?>
                        <div class="form-section">
                            <h3><i class="fas fa-briefcase"></i> Dados Profissionais</h3>
                            
                            <div class="form-group">
                                <label for="area_atuacao">Área de Atuação</label>
                                <input type="text" id="area_atuacao" name="area_atuacao" value="<?= htmlspecialchars($professional['area_atuacao']) ?>" placeholder="Ex: Design Gráfico, Desenvolvimento Web...">
                            </div>
                            
                            <div class="form-group">
                                <label for="descricao_perfil">Descrição do Perfil</label>
                                <textarea id="descricao_perfil" name="descricao_perfil" placeholder="Conte um pouco sobre sua experiência e especialidades..."><?= htmlspecialchars($professional['descricao_perfil']) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="endereco_comercial">Endereço Comercial</label>
                                <textarea id="endereco_comercial" name="endereco_comercial" placeholder="Endereço onde atende clientes (se diferente do pessoal)"><?= htmlspecialchars($professional['endereco_comercial']) ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="disponibilidade">Disponibilidade</label>
                                <select id="disponibilidade" name="disponibilidade">
                                    <option value="disponivel" <?= $professional['disponibilidade'] === 'disponivel' ? 'selected' : '' ?>>Disponível</option>
                                    <option value="ocupado" <?= $professional['disponibilidade'] === 'ocupado' ? 'selected' : '' ?>>Ocupado</option>
                                    <option value="indisponivel" <?= $professional['disponibilidade'] === 'indisponivel' ? 'selected' : '' ?>>Indisponível</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Preferências de Atendimento</label>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="aceita_orcamento" name="aceita_orcamento" <?= $professional['aceita_orcamento'] ? 'checked' : '' ?>>
                                    <label for="aceita_orcamento">Aceita orçamentos</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="atende_presencial" name="atende_presencial" <?= $professional['atende_presencial'] ? 'checked' : '' ?>>
                                    <label for="atende_presencial">Atende presencialmente</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="atende_online" name="atende_online" <?= $professional['atende_online'] ? 'checked' : '' ?>>
                                    <label for="atende_online">Atende online</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="dashboard.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn-secondary">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
    <script>
        // Máscara para CEP
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
        
        // Máscara para telefone
        function phoneMask(input) {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length <= 11) {
                    if (value.length <= 10) {
                        value = value.replace(/(\d{2})(\d{4})(\d)/, '($1) $2-$3');
                    } else {
                        value = value.replace(/(\d{2})(\d{5})(\d)/, '($1) $2-$3');
                    }
                    e.target.value = value;
                }
            });
        }
        
        phoneMask(document.getElementById('telefone'));
        phoneMask(document.getElementById('whatsapp'));
    </script>
</body>
</html>

