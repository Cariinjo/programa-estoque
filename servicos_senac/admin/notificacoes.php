<?php
require_once '../includes/config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT n.*, u.nome as usuario_nome
        FROM notificacoes n
        JOIN usuarios u ON n.id_usuario_destino = u.id_usuario
        ORDER BY n.data_criacao DESC
        LIMIT 100
    ");
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $notificacoes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações - Admin SENAC</title>
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
                <li><a href="notificacoes.php" class="active"><i class="fas fa-bell"></i> Notificações</a></li>
                <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                <li><a href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <h1>Notificações</h1>
            </div>
            <div class="header-right">
                <div class="admin-user">
                    <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
            </div>
        </header>
        
        <div class="admin-content">
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Tipo</th>
                            <th>Mensagem</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notificacoes as $notificacao): ?>
                            <tr>
                                <td><?= htmlspecialchars($notificacao['usuario_nome']) ?></td>
                                <td><?= htmlspecialchars($notificacao['tipo_notificacao']) ?></td>
                                <td><?= htmlspecialchars($notificacao['mensagem']) ?></td>
                                <td>
                                    <span class="badge <?= $notificacao['lida'] ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $notificacao['lida'] ? 'Lida' : 'Não lida' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($notificacao['data_criacao'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>

