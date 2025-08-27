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

// Lógica para processar configurações (ex: mudança de senha, preferências de notificação)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        // Validar senhas
        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $error = "Todos os campos de senha são obrigatórios.";
        } elseif ($new_password !== $confirm_new_password) {
            $error = "A nova senha e a confirmação não coincidem.";
        } elseif (strlen($new_password) < 6) {
            $error = "A nova senha deve ter pelo menos 6 caracteres.";
        } else {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($current_password, $user['senha'])) {
                // Atualizar senha
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
                $stmt->execute([$hashed_password, $userId]);
                $success = "Senha alterada com sucesso!";
            } else {
                $error = "Senha atual incorreta.";
            }
        }
    }
    // Adicionar outras configurações aqui (ex: preferências de notificação)
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .settings-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .settings-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .settings-form {
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
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2d3436;
            font-weight: 500;
        }
        
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #6c5ce7;
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
        
        @media (max-width: 768px) {
            .settings-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="settings-container">
            <div class="settings-header">
                <h1><i class="fas fa-cog"></i> Configurações</h1>
                <p>Gerencie suas preferências e segurança da conta.</p>
            </div>
            
            <div class="settings-form">
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
                    <!-- Alterar Senha -->
                    <div class="form-section">
                        <h3><i class="fas fa-lock"></i> Alterar Senha</h3>
                        
                        <div class="form-group">
                            <label for="current_password">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nova Senha</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_new_password">Confirmar Nova Senha</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-primary">
                            <i class="fas fa-save"></i> Salvar Nova Senha
                        </button>
                    </div>
                    
                    <!-- Outras Configurações (ex: Notificações) -->
                    <!--
                    <div class="form-section">
                        <h3><i class="fas fa-bell"></i> Preferências de Notificação</h3>
                        <div class="form-group">
                            <input type="checkbox" id="email_notifications" name="email_notifications" checked>
                            <label for="email_notifications">Receber notificações por e-mail</label>
                        </div>
                        <button type="submit" name="save_notification_prefs" class="btn-primary">
                            <i class="fas fa-save"></i> Salvar Preferências
                        </button>
                    </div>
                    -->
                </form>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="js/main.js"></script>
</body>
</html>


