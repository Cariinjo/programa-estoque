<?php
require_once '../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin SENAC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> Admin SENAC</h2>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                <li><a href="profissionais.php"><i class="fas fa-briefcase"></i> Profissionais</a></li>
                <li><a href="servicos.php"><i class="fas fa-cogs"></i> Serviços</a></li>
                <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                <li><a href="orcamentos.php"><i class="fas fa-file-invoice"></i> Orçamentos</a></li>
                <li><a href="avaliacoes.php"><i class="fas fa-star"></i> Avaliações</a></li>
                <li><a href="notificacoes.php"><i class="fas fa-bell"></i> Notificações</a></li>
                <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                <li><a href="configuracoes.php" class="active"><i class="fas fa-cog"></i> Configurações</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <h1>Configurações do Sistema</h1>
            </div>
            <div class="header-right">
                <div class="admin-user">
                    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
            </div>
        </header>
        
        <div class="admin-content">
            <div class="config-sections">
                <div class="config-card">
                    <div class="config-header">
                        <h3><i class="fas fa-globe"></i> Configurações Gerais</h3>
                    </div>
                    <div class="config-body">
                        <div class="form-group">
                            <label>Nome do Site</label>
                            <input type="text" value="Serviços SENAC" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Email de Contato</label>
                            <input type="email" value="contato@servicossenac.com" class="form-control">
                        </div>
                        <button class="btn btn-primary">Salvar Configurações</button>
                    </div>
                </div>
                
                <div class="config-card">
                    <div class="config-header">
                        <h3><i class="fas fa-shield-alt"></i> Segurança</h3>
                    </div>
                    <div class="config-body">
                        <div class="form-group">
                            <label>Alterar Senha do Administrador</label>
                            <input type="password" placeholder="Nova senha" class="form-control">
                        </div>
                        <div class="form-group">
                            <input type="password" placeholder="Confirmar senha" class="form-control">
                        </div>
                        <button class="btn btn-primary">Alterar Senha</button>
                    </div>
                </div>
                
                <div class="config-card">
                    <div class="config-header">
                        <h3><i class="fas fa-database"></i> Manutenção</h3>
                    </div>
                    <div class="config-body">
                        <p>Ferramentas de manutenção do sistema</p>
                        <div class="maintenance-actions">
                            <button class="btn btn-outline" onclick="alert('Funcionalidade será implementada')">
                                <i class="fas fa-broom"></i> Limpar Cache
                            </button>
                            <button class="btn btn-outline" onclick="alert('Funcionalidade será implementada')">
                                <i class="fas fa-download"></i> Backup do Banco
                            </button>
                            <button class="btn btn-outline" onclick="alert('Funcionalidade será implementada')">
                                <i class="fas fa-chart-bar"></i> Logs do Sistema
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

