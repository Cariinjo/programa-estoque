<?php
require_once 'includes/config.php';

$error = '';
$success = '';

// Buscar categorias para o select
try {
    $stmt = $pdo->query("SELECT id_categoria, nome_categoria FROM categorias ORDER BY nome_categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
    $categorias = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome']);
    $email = sanitize($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $telefone = sanitize($_POST['telefone']);
    $endereco = sanitize($_POST['endereco']);
    $tipo_usuario = $_POST['tipo_usuario'];
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($tipo_usuario)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        try {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                $pdo->beginTransaction();
                
                // Inserir usuário
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, senha, telefone, endereco, tipo_usuario) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $email, $senhaHash, $telefone, $endereco, $tipo_usuario]);
                $userId = $pdo->lastInsertId();
                
                // Se for profissional, inserir na tabela profissionais
                if ($tipo_usuario === 'prestador') {
                    $cpf = sanitize($_POST['cpf']);
                    $id_categoria = $_POST['id_categoria'];
                    $descricao_perfil = sanitize($_POST['descricao_perfil']);
                    
                    if (empty($cpf) || empty($id_categoria)) {
                        throw new Exception('CPF e categoria são obrigatórios para profissionais.');
                    }
                    
                    // Buscar nome da categoria
                    $stmt = $pdo->prepare("SELECT nome_categoria FROM categorias WHERE id_categoria = ?");
                    $stmt->execute([$id_categoria]);
                    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
                    $area_atuacao = $categoria ? $categoria['nome_categoria'] : 'Não especificado';
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO profissionais (id_usuario, cpf, area_atuacao, descricao_perfil) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $cpf, $area_atuacao, $descricao_perfil]);
                }
                
                $pdo->commit();
                $success = 'Cadastro realizado com sucesso! Você pode fazer login agora.';
                
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erro no cadastro: " . $e->getMessage());
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erro no cadastro: " . $e->getMessage());
            $error = 'Erro interno do servidor.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            padding: 2rem;
        }
        
        .register-form {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            color: #2d3436;
            margin-bottom: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 10px;
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
        
        .user-type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .user-type-option {
            padding: 1.5rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-type-option:hover {
            border-color: #6c5ce7;
        }
        
        .user-type-option.active {
            border-color: #6c5ce7;
            background: #f8f9ff;
        }
        
        .user-type-option input[type="radio"] {
            display: none;
        }
        
        .professional-fields {
            display: none;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .professional-fields.active {
            display: block;
        }
        
        .error-message {
            background: #ff7675;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .success-message {
            background: #00b894;
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .register-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
        }
        
        .register-links {
            text-align: center;
            margin-top: 2rem;
        }
        
        .register-links a {
            color: #6c5ce7;
            text-decoration: none;
            margin: 0 1rem;
        }
        
        .register-links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .user-type-selector {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <form class="register-form" method="POST">
            <div class="register-header">
                <h1><i class="fas fa-graduation-cap"></i> Serviços SENAC</h1>
                <p>Crie sua conta</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="user-type-selector">
                <label class="user-type-option <?= (!isset($_POST['tipo_usuario']) || $_POST['tipo_usuario'] === 'cliente') ? 'active' : '' ?>">
                    <input type="radio" name="tipo_usuario" value="cliente" <?= (!isset($_POST['tipo_usuario']) || $_POST['tipo_usuario'] === 'cliente') ? 'checked' : '' ?>>
                    <i class="fas fa-user" style="font-size: 2rem; color: #6c5ce7; margin-bottom: 0.5rem;"></i>
                    <h3>Cliente</h3>
                    <p>Quero contratar serviços</p>
                </label>
                
                <label class="user-type-option <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'prestador') ? 'active' : '' ?>">
                    <input type="radio" name="tipo_usuario" value="prestador" <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'prestador') ? 'checked' : '' ?>>
                    <i class="fas fa-briefcase" style="font-size: 2rem; color: #6c5ce7; margin-bottom: 0.5rem;"></i>
                    <h3>Profissional</h3>
                    <p>Quero oferecer serviços</p>
                </label>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Completo *</label>
                    <input type="text" id="nome" name="nome" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="senha">Senha *</label>
                    <input type="password" id="senha" name="senha" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha *</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" class="phone-mask" value="<?= isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" value="<?= isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : '' ?>">
                </div>
            </div>
            
            <div class="professional-fields <?= (isset($_POST['tipo_usuario']) && $_POST['tipo_usuario'] === 'prestador') ? 'active' : '' ?>">
                <h3 style="margin-bottom: 1rem; color: #2d3436;">Informações Profissionais</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cpf">CPF *</label>
                        <input type="text" id="cpf" name="cpf" class="cpf-mask" value="<?= isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_categoria">Categoria *</label>
                        <select id="id_categoria" name="id_categoria">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id_categoria'] ?>" 
                                        <?= (isset($_POST['id_categoria']) && $_POST['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nome_categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descricao_perfil">Descrição do Perfil</label>
                    <textarea id="descricao_perfil" name="descricao_perfil" placeholder="Conte um pouco sobre sua experiência e especialidades..."><?= isset($_POST['descricao_perfil']) ? htmlspecialchars($_POST['descricao_perfil']) : '' ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="register-btn">
                <i class="fas fa-user-plus"></i> Criar Conta
            </button>
            
            <div class="register-links">
                <a href="login.php">Já tenho uma conta</a>
            </div>
            
            <div class="register-links" style="margin-top: 1rem;">
                <a href="index.php">← Voltar ao início</a>
            </div>
        </form>
    </div>
    
    <script>
        // Alternar entre tipos de usuário
        const userTypeOptions = document.querySelectorAll('.user-type-option');
        const professionalFields = document.querySelector('.professional-fields');
        
        userTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                userTypeOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
                
                const radioInput = this.querySelector('input[type="radio"]');
                radioInput.checked = true;
                
                if (radioInput.value === 'prestador') {
                    professionalFields.classList.add('active');
                    // Tornar campos obrigatórios
                    document.getElementById('cpf').required = true;
                    document.getElementById('id_categoria').required = true;
                } else {
                    professionalFields.classList.remove('active');
                    // Remover obrigatoriedade
                    document.getElementById('cpf').required = false;
                    document.getElementById('id_categoria').required = false;
                }
            });
        });
        
        // Validação de senha
        const senha = document.getElementById('senha');
        const confirmarSenha = document.getElementById('confirmar_senha');
        
        function validatePassword() {
            if (senha.value !== confirmarSenha.value) {
                confirmarSenha.setCustomValidity('As senhas não coincidem');
            } else {
                confirmarSenha.setCustomValidity('');
            }
        }
        
        senha.addEventListener('input', validatePassword);
        confirmarSenha.addEventListener('input', validatePassword);
    </script>
    
    <script src="js/main.js"></script>
</body>
</html>

