<?php
require_once 'includes/config.php';

// Buscar categorias
$stmt = $pdo->query("SELECT * FROM categorias LIMIT 4");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar serviços em destaque
$stmt = $pdo->query("
    SELECT s.*, p.area_atuacao, u.nome as nome_profissional, c.nome_categoria
    FROM servicos s 
    JOIN profissionais p ON s.id_profissional = p.id_profissional
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    JOIN categorias c ON s.id_categoria = c.id_categoria
    ORDER BY s.media_avaliacao DESC, s.total_avaliacoes DESC
    LIMIT 3
");
$servicos_destaque = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar prestadores bem avaliados
$stmt = $pdo->query("
    SELECT p.*, u.nome, u.email
    FROM profissionais p 
    JOIN usuarios u ON p.id_usuario = u.id_usuario
    ORDER BY p.media_avaliacao DESC, p.total_avaliacoes DESC
    LIMIT 4
");
$prestadores_destaque = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços SENAC - Conectando Talentos e Oportunidades</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Conectando Talentos e Oportunidades</h1>
            <p>Encontre serviços de qualidade oferecidos por alunos formados do SENAC ou divulgue seus talentos e conquiste novos clientes.</p>
            
            <div class="search-container">
                <form action="servicos.php" method="GET">
                    <input type="text" name="busca" class="search-box" placeholder="O que você está procurando?">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Categorias Populares -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Categorias Populares</h2>
            
            <div class="categories-grid">
                <?php foreach ($categorias as $categoria): ?>
                <div class="category-card" onclick="location.href='categoria.php?id=<?= $categoria['id_categoria'] ?>'">
                    <div class="category-icon">
                        <?php
                        $icons = [
                            'Saúde e Bem-Estar' => 'fas fa-heartbeat',
                            'Desenvolvimento' => 'fas fa-code',
                            'Marketing Digital' => 'fas fa-bullhorn',
                            'Beleza e Estética' => 'fas fa-cut',
                            'Design Gráfico' => 'fas fa-palette',
                            'Fotografia e Vídeo' => 'fas fa-camera',
                            'Aulas Particulares' => 'fas fa-chalkboard-teacher',
                            'Consultoria' => 'fas fa-handshake'
                        ];
                        echo '<i class="' . ($icons[$categoria['nome_categoria']] ?? 'fas fa-star') . '"></i>';
                        ?>
                    </div>
                    <h3><?= htmlspecialchars($categoria['nome_categoria']) ?></h3>
                    <p><?= htmlspecialchars($categoria['descricao']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <a href="categorias.php" class="view-all-btn">Ver Todas as Categorias</a>
        </div>
    </section>

    <!-- Serviços em Destaque -->
    <section class="section">
        <div class="container">
            <h2 class="section-title">Serviços em Destaque</h2>
            
            <div class="services-grid">
                <?php foreach ($servicos_destaque as $servico): ?>
                <div class="service-card">
                    <div class="service-image">
                        <!-- Placeholder para imagem do serviço -->
                    </div>
                    <div class="service-content">
                        <h3 class="service-title"><?= htmlspecialchars($servico['titulo']) ?></h3>
                        <p class="service-description"><?= htmlspecialchars(substr($servico['descricao'], 0, 100)) ?>...</p>
                        <div class="service-price">R$ <?= number_format($servico['preco'], 2, ',', '.') ?></div>
                        <div class="service-rating">
                            <div class="stars">
                                <?php
                                $rating = $servico['media_avaliacao'];
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="rating-text">(<?= $servico['total_avaliacoes'] ?> avaliações)</span>
                        </div>
                        <p><strong>Por:</strong> <?= htmlspecialchars($servico['nome_profissional']) ?></p>
                        <a href="servico-detalhes.php?id=<?= $servico['id_servico'] ?>" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 1rem;">Ver Detalhes</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <a href="servicos.php" class="view-all-btn">Ver Todos os Serviços</a>
        </div>
    </section>

    <!-- Prestadores Bem Avaliados -->
    <section class="section" style="background: #f8f9fa;">
        <div class="container">
            <h2 class="section-title">Prestadores Bem Avaliados</h2>
            
            <div class="providers-grid">
                <?php foreach ($prestadores_destaque as $prestador): ?>
                <div class="provider-card">
                    <div class="provider-avatar">
                        <?= strtoupper(substr($prestador['nome'], 0, 1)) ?>
                    </div>
                    <h3 class="provider-name"><?= htmlspecialchars($prestador['nome']) ?></h3>
                    <p class="provider-specialty"><?= htmlspecialchars($prestador['area_atuacao']) ?></p>
                    <div class="service-rating">
                        <div class="stars">
                            <?php
                            $rating = $prestador['media_avaliacao'];
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif ($i - 0.5 <= $rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <span class="rating-text">(<?= $prestador['total_avaliacoes'] ?> avaliações)</span>
                    </div>
                    <a href="profissional-perfil.php?id=<?= $prestador['id_profissional'] ?>" class="btn btn-primary" style="margin-top: 1rem;">Ver Perfil</a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <a href="profissionais.php" class="view-all-btn">Ver Todos os Profissionais</a>
        </div>
    </section>

    <!-- Como Funciona -->
    <section class="section how-it-works">
        <div class="container">
            <h2 class="section-title">Como Funciona</h2>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Busque Serviços</h3>
                    <p class="step-description">Procure pelo serviço que você precisa usando nossa busca avançada ou navegando pelas categorias disponíveis.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Entre em Contato</h3>
                    <p class="step-description">Converse diretamente com o profissional através do nosso sistema de mensagens e solicite um orçamento personalizado.</p>
                </div>
                
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Contrate e Avalie</h3>
                    <p class="step-description">Após a conclusão do serviço, avalie o profissional para ajudar outros usuários a fazerem a melhor escolha.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Serviços SENAC</h3>
                    <p>Conectando talentos e oportunidades. Uma plataforma que une profissionais qualificados do SENAC com clientes que buscam serviços de qualidade.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Links Úteis</h3>
                    <ul>
                        <li><a href="sobre.php">Sobre Nós</a></li>
                        <li><a href="como-funciona.php">Como Funciona</a></li>
                        <li><a href="termos.php">Termos de Uso</a></li>
                        <li><a href="privacidade.php">Política de Privacidade</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Categorias</h3>
                    <ul>
                        <li><a href="categoria.php?id=1">Saúde e Bem-Estar</a></li>
                        <li><a href="categoria.php?id=2">Desenvolvimento</a></li>
                        <li><a href="categoria.php?id=3">Marketing Digital</a></li>
                        <li><a href="categoria.php?id=4">Design Gráfico</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contato</h3>
                    <ul>
                        <li><i class="fas fa-envelope"></i> contato@servicossenac.com</li>
                        <li><i class="fas fa-phone"></i> (11) 1234-5678</li>
                        <li><i class="fas fa-map-marker-alt"></i> São Paulo, SP</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 Serviços SENAC. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>

