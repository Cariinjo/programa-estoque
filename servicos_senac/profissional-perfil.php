<?php
require_once 'includes/config.php';

// Verificar se foi passado um ID válido
$id_profissional = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_profissional <= 0) {
    header('Location: profissionais.php');
    exit;
}

try {
    // Buscar dados do profissional
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.nome,
            u.email,
            u.telefone,
            u.endereco,
            u.data_cadastro
        FROM profissionais p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE p.id_profissional = ?
    ");
    $stmt->execute([$id_profissional]);
    $profissional = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profissional) {
        header('Location: profissionais.php');
        exit;
    }
    
    // Buscar serviços do profissional
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.nome_categoria
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        WHERE s.id_profissional = ?
        ORDER BY s.data_criacao DESC
    ");
    $stmt->execute([$id_profissional]);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar avaliações do profissional
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.nome as nome_cliente,
            s.titulo as titulo_servico
        FROM avaliacoes a
        JOIN usuarios u ON a.id_usuario = u.id_usuario
        JOIN servicos s ON a.id_servico = s.id_servico
        WHERE s.id_profissional = ?
        ORDER BY a.data_avaliacao DESC
        LIMIT 10
    ");
    $stmt->execute([$id_profissional]);
    $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar profissional: " . $e->getMessage());
    header('Location: profissionais.php');
    exit;
}

$page_title = "Perfil de " . htmlspecialchars($profissional['nome']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Serviços SENAC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <!-- Perfil do Profissional -->
            <div class="profile-header">
                <div class="profile-info">
                    <div class="profile-avatar">
                        <?php if (!empty($profissional['foto_perfil_url'])): ?>
                            <img src="<?= htmlspecialchars($profissional['foto_perfil_url']) ?>" alt="Foto de <?= htmlspecialchars($profissional['nome']) ?>">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-details">
                        <h1 class="profile-name"><?= htmlspecialchars($profissional['nome']) ?></h1>
                        <p class="profile-area"><?= htmlspecialchars($profissional['area_atuacao']) ?></p>
                        
                        <div class="profile-rating">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $profissional['media_avaliacao'] ? 'active' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">
                                <?= number_format($profissional['media_avaliacao'], 1) ?> 
                                (<?= $profissional['total_avaliacoes'] ?> avaliações)
                            </span>
                        </div>
                        
                        <div class="profile-contact">
                            <?php if (!empty($profissional['telefone'])): ?>
                                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($profissional['telefone']) ?></p>
                            <?php endif; ?>
                            <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($profissional['email']) ?></p>
                            <?php if (!empty($profissional['endereco'])): ?>
                                <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($profissional['endereco']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="profile-actions">
                            <?php if (isLoggedIn() && $_SESSION['user_type'] === 'cliente'): ?>
                                <button class="btn btn-primary" onclick="iniciarChat(<?= $profissional['id_profissional'] ?>)">
                                    <i class="fas fa-comments"></i> Conversar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descrição do Profissional -->
            <?php if (!empty($profissional['descricao_perfil'])): ?>
                <div class="profile-section">
                    <h2><i class="fas fa-user-circle"></i> Sobre o Profissional</h2>
                    <div class="profile-description">
                        <?= nl2br(htmlspecialchars($profissional['descricao_perfil'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Serviços do Profissional -->
            <div class="profile-section">
                <h2><i class="fas fa-cogs"></i> Serviços Oferecidos</h2>
                <?php if (!empty($servicos)): ?>
                    <div class="services-grid">
                        <?php foreach ($servicos as $servico): ?>
                            <div class="service-card">
                                <div class="service-header">
                                    <h3 class="service-title">
                                        <a href="servico-detalhes.php?id=<?= $servico['id_servico'] ?>">
                                            <?= htmlspecialchars($servico['titulo']) ?>
                                        </a>
                                    </h3>
                                    <span class="service-category"><?= htmlspecialchars($servico['nome_categoria']) ?></span>
                                </div>
                                
                                <p class="service-description">
                                    <?= htmlspecialchars(substr($servico['descricao'], 0, 150)) ?>
                                    <?= strlen($servico['descricao']) > 150 ? '...' : '' ?>
                                </p>
                                
                                <div class="service-footer">
                                    <div class="service-price">
                                        <strong>R$ <?= number_format($servico['preco'], 2, ',', '.') ?></strong>
                                    </div>
                                    
                                    <div class="service-rating">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $servico['media_avaliacao'] ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span>(<?= $servico['total_avaliacoes'] ?>)</span>
                                    </div>
                                </div>
                                
                                <div class="service-actions">
                                    <a href="servico-detalhes.php?id=<?= $servico['id_servico'] ?>" class="btn btn-outline">
                                        Ver Detalhes
                                    </a>
                                    <?php if (isLoggedIn() && $_SESSION['user_type'] === 'cliente'): ?>
                                        <button class="btn btn-primary" onclick="solicitarOrcamento(<?= $servico['id_servico'] ?>)">
                                            Solicitar Orçamento
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <p>Este profissional ainda não cadastrou nenhum serviço.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Avaliações -->
            <?php if (!empty($avaliacoes)): ?>
                <div class="profile-section">
                    <h2><i class="fas fa-star"></i> Avaliações dos Clientes</h2>
                    <div class="reviews-list">
                        <?php foreach ($avaliacoes as $avaliacao): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <strong><?= htmlspecialchars($avaliacao['nome_cliente']) ?></strong>
                                        <span class="review-service">- <?= htmlspecialchars($avaliacao['titulo_servico']) ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $avaliacao['nota'] ? 'active' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($avaliacao['comentario'])): ?>
                                    <p class="review-comment"><?= htmlspecialchars($avaliacao['comentario']) ?></p>
                                <?php endif; ?>
                                
                                <div class="review-date">
                                    <?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script>
        function iniciarChat(idProfissional) {
            window.location.href = `chat.php?profissional=${idProfissional}`;
        }
        
        function solicitarOrcamento(idServico) {
            // Implementar modal de solicitação de orçamento
            alert('Funcionalidade de orçamento será implementada em breve!');
        }
    </script>
</body>
</html>

