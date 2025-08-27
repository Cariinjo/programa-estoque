<?php
require_once 'includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $senha = $_POST['senha'];
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Verificar se é usuário comum
            $stmt = $pdo->prepare("SELECT id_usuario, nome, senha, tipo_usuario FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($senha, $user['senha'])) {
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo_usuario'] ?: 'cliente';
                
                // Verificar se é prestador/profissional
                if ($user['tipo_usuario'] === 'profissional' || $user['tipo_usuario'] === 'prestador') {
                    $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
                    $stmt->execute([$user['id_usuario']]);
                    if ($stmt->fetch()) {
                        $_SESSION['is_professional'] = true;
                        $_SESSION['user_type'] = 'prestador'; // Padronizar como prestador
                        header('Location: dashboard-prestador.php');
                        exit;
                    }
                }
                
                // Se for cliente, vai para dashboard normal
                if ($user['tipo_usuario'] === 'cliente') {
                    header('Location: dashboard.php');
                    exit;
                }
                
                // Fallback para dashboard normal
                header('Location: dashboard.php');
                exit;
            }
            
            // Verificar se é administrador
            $stmt = $pdo->prepare("SELECT id_administrador, nome, senha FROM administradores WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin && password_verify($senha, $admin['senha'])) {
                $_SESSION['user_id'] = $admin['id_administrador'];
                $_SESSION['user_name'] = $admin['nome'];
                $_SESSION['user_type'] = 'admin';
                
                header('Location: admin/dashboard.php');
                exit;
            }
            
            $error = 'Email ou senha incorretos.';
            
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
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
    <title>Login - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            padding: 2rem;
        }
        
        .login-form {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #2d3436;
            margin-bottom: 0.5rem;
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
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #6c5ce7;
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
        
        .login-btn {
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
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .login-links {
            text-align: center;
            margin-top: 2rem;
        }
        
        .login-links a {
            color: #6c5ce7;
            text-decoration: none;
            margin: 0 1rem;
        }
        
        .login-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST">
            <div class="login-header">
                <h1><i class="fas fa-graduation-cap"></i> Serviços SENAC</h1>
                <p>Entre em sua conta</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
            
            <div class="login-links">
                <a href="cadastro.php">Criar conta</a>
                <a href="recuperar-senha.php">Esqueci minha senha</a>
            </div>
            
            <div class="login-links" style="margin-top: 1rem;">
                <a href="index.php">← Voltar ao início</a>
            </div>
        </form>
    </div>
</body>
</html>

