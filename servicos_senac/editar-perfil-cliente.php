<?php
require_once 'includes/config.php';

// Verificar se é cliente logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'cliente') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $endereco_completo = trim($_POST['endereco_completo'] ?? '');
        $cep = trim($_POST['cep'] ?? '');
        $cidade_id = (int)($_POST['cidade_id'] ?? 0);
        $data_nascimento = $_POST['data_nascimento'] ?? '';
        
        // Validações básicas
        if (empty($nome) || empty($email)) {
            throw new Exception('Nome e email são obrigatórios');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Verificar se email já existe (exceto o próprio usuário)
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn()) {
            throw new Exception('Este email já está sendo usado por outro usuário');
        }
        
        $pdo->beginTransaction();
        
        // Atualizar dados do usuário
        $stmt = $pdo->prepare("
            UPDATE usuarios SET 
                nome = ?, email = ?, telefone = ?, whatsapp = ?, 
                endereco_completo = ?, cep = ?, cidade_id = ?, data_nascimento = ?
            WHERE id_usuario = ?
        ");
        $stmt->execute([$nome, $email, $telefone, $whatsapp, $endereco_completo, $cep, $cidade_id, $data_nascimento, $user_id]);
        
        // Processar upload de foto se enviada
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/perfis/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'cliente_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_path)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?");
                    $stmt->execute([$upload_path, $user_id]);
                }
            }
        }
        
        $pdo->commit();
        
        // Atualizar sessão
        $_SESSION['user_name'] = $nome;
        
        $success_message = 'Perfil atualizado com sucesso!';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

try {
    // Buscar dados atuais do cliente
    $stmt = $pdo->prepare("
        SELECT u.*, c.nome_cidade, c.uf
        FROM usuarios u
        LEFT JOIN cidades_senac_mg c ON u.cidade_id = c.id_cidade
        WHERE u.id_usuario = ?
    ");
    $stmt->execute([$user_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        header('Location: login.php');
        exit;
    }
    
    // Buscar cidades
    $stmt = $pdo->prepare("SELECT id_cidade, nome_cidade, uf FROM cidades_senac_mg ORDER BY nome_cidade");
    $stmt->execute();
    $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do cliente: " . $e->getMessage());
    $error_message = 'Erro ao carregar dados do perfil';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Cliente</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #f0f0f0;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .profile-header h1 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .profile-header p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .photo-upload {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .current-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #e9ecef;
        }
        
        .current-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .current-photo i {
            font-size: 2rem;
            color: #adb5bd;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .file-input-label:hover {
            background: #5a67d8;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f8f9fa;
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .info-card h3 {
            margin: 0 0 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .photo-upload {
                flex-direction: column;
                text-align: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="edit-profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <h1><i class="fas fa-user-edit"></i> Editar Perfil</h1>
                <p>Mantenha suas informações sempre atualizadas</p>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Dica</h3>
                <p>Mantenha seu perfil completo para receber melhores recomendações de serviços e facilitar o contato dos prestadores.</p>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <!-- Foto de Perfil -->
                <div class="form-group form-group-full">
                    <label>Foto de Perfil</label>
                    <div class="photo-upload">
                        <div class="current-photo">
                            <?php if (!empty($cliente['foto_perfil']) && file_exists($cliente['foto_perfil'])): ?>
                                <img src="<?= htmlspecialchars($cliente['foto_perfil']) ?>" alt="Foto atual">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div class="file-input-wrapper">
                            <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                            <label for="foto_perfil" class="file-input-label">
                                <i class="fas fa-camera"></i> Alterar Foto
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Dados Pessoais -->
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="tel" id="telefone" name="telefone" value="<?= htmlspecialchars($cliente['telefone']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="whatsapp">WhatsApp</label>
                        <input type="tel" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($cliente['whatsapp']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento</label>
                        <input type="date" id="data_nascimento" name="data_nascimento" value="<?= htmlspecialchars($cliente['data_nascimento']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="cidade_id">Cidade</label>
                        <select id="cidade_id" name="cidade_id">
                            <option value="">Selecione uma cidade</option>
                            <?php foreach ($cidades as $cidade): ?>
                                <option value="<?= $cidade['id_cidade'] ?>" <?= $cidade['id_cidade'] == $cliente['cidade_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cidade['nome_cidade']) ?> - <?= htmlspecialchars($cidade['uf']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Endereço -->
                <div class="form-grid">
                    <div class="form-group">
                        <label for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" value="<?= htmlspecialchars($cliente['cep']) ?>" maxlength="9">
                    </div>
                    
                    <div class="form-group">
                        <label for="endereco_completo">Endereço Completo</label>
                        <input type="text" id="endereco_completo" name="endereco_completo" value="<?= htmlspecialchars($cliente['endereco_completo']) ?>" placeholder="Rua, número, bairro">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Máscara para CEP
        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            e.target.value = value;
        });
        
        // Buscar endereço por CEP
        document.getElementById('cep').addEventListener('blur', function(e) {
            const cep = e.target.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        const endereco = `${data.logradouro}, ${data.bairro}`;
                        document.getElementById('endereco_completo').value = endereco;
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                });
            }
        });
        
        // Preview da foto
        document.getElementById('foto_perfil').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.current-photo img');
                    if (img) {
                        img.src = e.target.result;
                    } else {
                        document.querySelector('.current-photo').innerHTML = `<img src="${e.target.result}" alt="Nova foto">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Máscara para telefone
        function mascaraTelefone(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            } else {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            }
            input.value = value;
        }
        
        document.getElementById('telefone').addEventListener('input', function() {
            mascaraTelefone(this);
        });
        
        document.getElementById('whatsapp').addEventListener('input', function() {
            mascaraTelefone(this);
        });
    </script>
</body>
</html>

