<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

try {
    // Buscar profissionais
    $stmt = $pdo->query("
        SELECT 
            p.*,
            u.nome,
            u.email,
            u.telefone,
            u.data_cadastro,
            COUNT(s.id_servico) as total_servicos
        FROM profissionais p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN servicos s ON p.id_profissional = s.id_profissional
        GROUP BY p.id_profissional
        ORDER BY p.media_avaliacao DESC, u.data_cadastro DESC
    ");
    $profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar profissionais: " . $e->getMessage());
    $profissionais = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Profissionais - Admin SENAC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> Admin SENAC</h2>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                <li><a href="profissionais.php" class="active"><i class="fas fa-briefcase"></i> Profissionais</a></li>
                <li><a href="servicos.php"><i class="fas fa-cogs"></i> Serviços</a></li>
                <li><a href="categorias.php"><i class="fas fa-tags"></i> Categorias</a></li>
                <li><a href="orcamentos.php"><i class="fas fa-file-invoice"></i> Orçamentos</a></li>
                <li><a href="avaliacoes.php"><i class="fas fa-star"></i> Avaliações</a></li>
                <li><a href="notificacoes.php"><i class="fas fa-bell"></i> Notificações</a></li>
                <li><a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
                <li><a href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </aside>
    
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                <h1>Gerenciar Profissionais</h1>
            </div>
            
            <div class="header-right">
                <div class="admin-user">
                    <span>Olá, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="admin-content">
            <div class="content-header">
                <div class="content-stats">
                    <span>Total: <?= count($profissionais) ?> profissionais</span>
                </div>
            </div>
            
            <!-- Grid de Profissionais -->
            <div class="professionals-grid">
                <?php if (!empty($profissionais)): ?>
                    <?php foreach ($profissionais as $profissional): ?>
                        <div class="professional-card">
                            <div class="professional-header">
                                <div class="professional-avatar">
                                    <?php if (!empty($profissional['foto_perfil_url'])): ?>
                                        <img src="<?= htmlspecialchars($profissional['foto_perfil_url']) ?>" alt="Foto">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="professional-info">
                                    <h3><?= htmlspecialchars($profissional['nome']) ?></h3>
                                    <p class="area"><?= htmlspecialchars($profissional['area_atuacao']) ?></p>
                                    
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $profissional['media_avaliacao'] ? 'active' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span>(<?= $profissional['total_avaliacoes'] ?>)</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="professional-stats">
                                <div class="stat">
                                    <i class="fas fa-cogs"></i>
                                    <span><?= $profissional['total_servicos'] ?> serviços</span>
                                </div>
                                <div class="stat">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d/m/Y', strtotime($profissional['data_cadastro'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="professional-contact">
                                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($profissional['email']) ?></p>
                                <?php if ($profissional['telefone']): ?>
                                    <p><i class="fas fa-phone"></i> <?= htmlspecialchars($profissional['telefone']) ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="professional-actions">
                                <a href="../profissional-perfil.php?id=<?= $profissional["id_profissional"] ?>" 
                                   class="btn btn-outline" target="_blank">
                                    <i class="fas fa-eye"></i> Ver Perfil
                                </a>
                                <a href="editar-profissional.php?id=<?= $profissional["id_profissional"] ?>" 
                                   class="btn btn-secondary">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <button class="btn btn-danger" 
                                        onclick="deleteProfessional(<?= $profissional["id_profissional"] ?>)">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <p>Nenhum profissional cadastrado</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="js/admin.js"></script>
    <script>
        function deleteProfessional(professionalId) {
            if (confirm("Tem certeza que deseja excluir este profissional? Isso removerá todos os serviços e orçamentos associados.")) {
                fetch("../api/delete-professional.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ professional_id: professionalId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Recarrega a página para refletir a mudança
                    } else {
                        alert("Erro: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Erro ao excluir profissional:", error);
                    alert("Erro ao excluir profissional. Verifique o console para mais detalhes.");
                });
            }
        }
    </script>
</body>
</html>

