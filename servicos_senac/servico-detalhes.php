<?php
require_once 'includes/config.php';

// Verificar se foi passado um ID válido
$id_servico = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_servico <= 0) {
    header('Location: servicos.php');
    exit;
}

try {
    // Buscar dados do serviço
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.nome_categoria,
            p.id_profissional,
            p.area_atuacao,
            p.descricao_perfil,
            p.media_avaliacao as profissional_rating,
            p.total_avaliacoes as profissional_reviews,
            p.foto_perfil_url,
            u.nome as nome_profissional,
            u.telefone,
            u.endereco
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE s.id_servico = ?
    ");
    $stmt->execute([$id_servico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$servico) {
        header('Location: servicos.php');
        exit;
    }
    
    // Buscar avaliações do serviço
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            u.nome as nome_cliente
        FROM avaliacoes a
        JOIN usuarios u ON a.id_usuario = u.id_usuario
        WHERE a.id_servico = ?
        ORDER BY a.data_avaliacao DESC
    ");
    $stmt->execute([$id_servico]);
    $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar outros serviços do mesmo profissional
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.nome_categoria
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        WHERE s.id_profissional = ? AND s.id_servico != ?
        ORDER BY s.media_avaliacao DESC
        LIMIT 3
    ");
    $stmt->execute([$servico['id_profissional'], $id_servico]);
    $outros_servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar serviços similares (mesma categoria)
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            c.nome_categoria,
            u.nome as nome_profissional
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE s.id_categoria = ? AND s.id_servico != ?
        ORDER BY s.media_avaliacao DESC
        LIMIT 4
    ");
    $stmt->execute([$servico['id_categoria'], $id_servico]);
    $servicos_similares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar serviço: " . $e->getMessage());
    header('Location: servicos.php');
    exit;
}

$page_title = htmlspecialchars($servico['titulo']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Serviços SENAC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .service-detail-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .service-status-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-ativo {
            color: #28a745;
        }
        
        .status-fechado {
            color: #dc3545;
        }
        
        .status-pausado {
            color: #ffc107;
        }
        
        .status-inativo {
            color: #6c757d;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
            border: 1px solid #ffc107;
        }
        
        .btn-warning:hover {
            background: #e0a800;
            border-color: #d39e00;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
            border-color: #bd2130;
        }
        
        .breadcrumb {
            max-width: 1200px;
            margin: 1rem auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: #6c5ce7;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .breadcrumb a:hover {
            color: #5a4fcf;
        }
        
        .breadcrumb .separator {
            color: #ddd;
        }
        
        .breadcrumb .current {
            color: #2d3436;
            font-weight: 500;
        }
        
        .service-main {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .service-header {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            padding: 2rem;
        }
        
        .service-title {
            font-size: 2rem;
            font-weight: bold;
            margin: 0 0 1rem 0;
            line-height: 1.2;
        }
        
        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .service-category {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }
        
        .service-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stars {
            display: flex;
            gap: 0.2rem;
        }
        
        .stars i {
            color: #ffd700;
            font-size: 1.1rem;
        }
        
        .stars i:not(.active) {
            color: rgba(255,255,255,0.3);
        }
        
        .rating-text {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .service-description,
        .service-delivery {
            padding: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .service-description h2,
        .service-delivery h3 {
            color: #2d3436;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .description-content {
            color: #636e72;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .service-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .price-card,
        .professional-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .price-card:hover,
        .professional-card:hover {
            transform: translateY(-5px);
        }
        
        .price-header {
            background: linear-gradient(135deg, #00b894 0%, #55efc4 100%);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .price-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0;
        }
        
        .price-actions {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: blue !important;
            box-shadow: 0 5px 20px rgba(108, 92, 231, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 92, 231, 0.4);
            color: blue;
            
        }
        
        .btn-outline {
            background: transparent;
            color: #6c5ce7 !important;
            border: 2px solid #6c5ce7;
        }
        
        .btn-outline:hover {
            background: #6c5ce7;
            color: #000000ff;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00b894 0%, #55efc4 100%);
            color: white;
            box-shadow: 0 5px 20px rgba(0, 184, 148, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 184, 148, 0.4);
            color: white;
        }
        
        .btn-whatsapp {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            box-shadow: 0 5px 20px rgba(37, 211, 102, 0.3);
        }
        
        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
            color: white;
        }
        
        .btn-full {
            width: 100%;
        }
        
        .professional-header {
            padding: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .professional-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .professional-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        
        .professional-info {
            flex: 1;
        }
        
        .professional-name {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
        }
        
        .professional-name a {
            color: #2d3436;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .professional-name a:hover {
            color: #6c5ce7;
        }
        
        .professional-area {
            color: #636e72;
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .professional-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #636e72;
        }
        
        .professional-actions {
            padding: 0 1.5rem 1.5rem 1.5rem;
        }
        
        .service-section {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 1rem;
        }
        
        .service-section h2 {
            color: #2d3436;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
        }
        
        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .review-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .review-card:hover {
            transform: translateY(-2px);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .reviewer-info strong {
            color: #2d3436;
        }
        
        .review-rating .stars i {
            color: #ffd700;
        }
        
        .review-rating .stars i:not(.active) {
            color: #ddd;
        }
        
        .review-comment {
            color: #636e72;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .review-date {
            color: #b2bec3;
            font-size: 0.8rem;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
        }
        
        .service-card .service-header {
            background: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .service-card .service-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .service-card .service-title a {
            color: #2d3436;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .service-card .service-title a:hover {
            color: #6c5ce7;
        }
        
        .service-card .service-category {
            background: #f8f9fa;
            color: #6c5ce7;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
        }
        
        .service-card .service-description {
            color: #636e72;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .service-price {
            color: #00b894;
            font-size: 1.1rem;
        }
        
        .service-professional {
            color: #b2bec3;
            font-size: 0.8rem;
        }
        
        .contact-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2d3436;
        }
        
        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #636e72;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: #2d3436;
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
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6c5ce7;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        @media (max-width: 768px) {
            .service-detail-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .service-title {
                font-size: 1.5rem;
            }
            
            .service-meta {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .professional-header {
                flex-direction: column;
                text-align: center;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .breadcrumb {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Início</a>
                <span class="separator">/</span>
                <a href="servicos.php">Serviços</a>
                <span class="separator">/</span>
                <a href="servicos.php?categoria=<?= urlencode($servico['nome_categoria']) ?>"><?= htmlspecialchars($servico['nome_categoria']) ?></a>
                <span class="separator">/</span>
                <span class="current"><?= htmlspecialchars($servico['titulo']) ?></span>
            </nav>

            <div class="service-detail-container">
                <!-- Informações do Serviço -->
                <div class="service-main">
                    <div class="service-header">
                        <h1 class="service-title"><?= htmlspecialchars($servico['titulo']) ?></h1>
                        <div class="service-meta">
                            <span class="service-category">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($servico['nome_categoria']) ?>
                            </span>
                            <div class="service-rating">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $servico['media_avaliacao'] ? 'active' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text">
                                    <?= number_format($servico['media_avaliacao'], 1) ?> 
                                    (<?= $servico['total_avaliacoes'] ?> avaliações)
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="service-description">
                        <h2><i class="fas fa-info-circle"></i> Descrição do Serviço</h2>
                        <div class="description-content">
                            <?= nl2br(htmlspecialchars($servico['descricao'])) ?>
                        </div>
                    </div>

                    <?php if (!empty($servico['tempo_entrega'])): ?>
                        <div class="service-delivery">
                            <h3><i class="fas fa-clock"></i> Tempo de Entrega</h3>
                            <p><?= htmlspecialchars($servico['tempo_entrega']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar com informações do profissional e ações -->
                <div class="service-sidebar">
                    <div class="price-card">
                        <div class="price-header">
                            <div class="price-value">
                                R$ <?= number_format($servico['preco'], 2, ',', '.') ?>
                            </div>
                        </div>
                        
                        <?php if (isLoggedIn() && $_SESSION['user_type'] === 'cliente'): ?>
                            <div class="price-actions">
                                <button class="btn btn-primary btn-full" onclick="solicitarOrcamento(<?= $servico['id_servico'] ?>)">
                                    <i class="fas fa-shopping-cart"></i> Contratar Serviço
                                </button>
                                <button class="btn btn-outline btn-full" onclick="iniciarChat(<?= $servico['id_profissional'] ?>)">
                                    <i class="fas fa-comments">Chat com Profissional</i> 
                                </button>
                                <div class="contact-actions">
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $servico['telefone']) ?>" target="_blank" class="btn btn-whatsapp" style="flex: 1;">
                                        <i class="fab fa-whatsapp"> WhatsApp</i>
                                    </a>
                                    <button class="btn btn-success" onclick="ligarProfissional('<?= htmlspecialchars($servico['telefone']) ?>')" style="flex: 1;">
                                        <i class="fas fa-phone"></i> Ligar
                                    </button>
                                </div>
                            </div>
                        <?php elseif (isLoggedIn() && $_SESSION['user_type'] === 'prestador'): ?>
                            <?php
                            // Verificar se este serviço pertence ao prestador logado
                            $stmt = $pdo->prepare("
                                SELECT p.id_profissional 
                                FROM profissionais p 
                                WHERE p.id_usuario = ?
                            ");
                            $stmt->execute([$_SESSION['user_id']]);
                            $prestador_logado = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($prestador_logado && $prestador_logado['id_profissional'] == $servico['id_profissional']):
                            ?>
                                <div class="price-actions">
                                    <button class="btn btn-warning btn-full" onclick="editarServico(<?= $servico['id_servico'] ?>)">
                                        <i class="fas fa-edit"></i> Editar Serviço
                                    </button>
                                    
                                    <?php if ($servico['status'] === 'ativo'): ?>
                                        <button class="btn btn-danger btn-full" onclick="fecharServico(<?= $servico['id_servico'] ?>)">
                                            <i class="fas fa-times-circle"></i> Fechar Serviço
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-full" onclick="reabrirServico(<?= $servico['id_servico'] ?>)">
                                            <i class="fas fa-check-circle"></i> Reabrir Serviço
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-outline btn-full" onclick="verEstatisticas(<?= $servico['id_servico'] ?>)">
                                        <i class="fas fa-chart-bar"></i> Ver Estatísticas
                                    </button>
                                </div>
                                
                                <div class="service-status-info">
                                    <div class="status-badge status-<?= strtolower($servico['status']) ?>">
                                        <i class="fas fa-circle"></i>
                                        Status: <?= ucfirst($servico['status']) ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="price-actions">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        Este serviço pertence a outro prestador.
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="price-actions">
                                <a href="login.php" class="btn btn-primary btn-full">
                                    <i class="fas fa-sign-in-alt"></i> Faça login para contratar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Informações do Profissional -->
                    <div class="professional-card">
                        <div class="professional-header">
                            <div class="professional-avatar">
                                <?php if (!empty($servico['foto_perfil_url'])): ?>
                                    <img src="<?= htmlspecialchars($servico['foto_perfil_url']) ?>" alt="Foto de <?= htmlspecialchars($servico['nome_profissional']) ?>">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="professional-info">
                                <h3 class="professional-name">
                                    <a href="profissional-perfil.php?id=<?= $servico['id_profissional'] ?>">
                                        <?= htmlspecialchars($servico['nome_profissional']) ?>
                                    </a>
                                </h3>
                                <p class="professional-area"><?= htmlspecialchars($servico['area_atuacao']) ?></p>
                                <div class="professional-rating">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $servico['profissional_rating'] ? 'active' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span>(<?= $servico['profissional_reviews'] ?> avaliações)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="professional-actions">
                            <a href="profissional-perfil.php?id=<?= $servico['id_profissional'] ?>" class="btn btn-outline btn-full">
                                <i class="fas fa-user"></i> Ver Perfil Completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Avaliações do Serviço -->
            <?php if (!empty($avaliacoes)): ?>
                <div class="service-section">
                    <h2><i class="fas fa-star"></i> Avaliações do Serviço</h2>
                    <div class="reviews-list">
                        <?php foreach ($avaliacoes as $avaliacao): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <strong><?= htmlspecialchars($avaliacao['nome_cliente']) ?></strong>
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

            <!-- Outros serviços do profissional -->
            <?php if (!empty($outros_servicos)): ?>
                <div class="service-section">
                    <h2><i class="fas fa-briefcase"></i> Outros Serviços do Profissional</h2>
                    <div class="services-grid">
                        <?php foreach ($outros_servicos as $outro_servico): ?>
                            <div class="service-card">
                                <div class="service-header">
                                    <h3 class="service-title">
                                        <a href="servico-detalhes.php?id=<?= $outro_servico['id_servico'] ?>">
                                            <?= htmlspecialchars($outro_servico['titulo']) ?>
                                        </a>
                                    </h3>
                                    <span class="service-category"><?= htmlspecialchars($outro_servico['nome_categoria']) ?></span>
                                </div>
                                
                                <p class="service-description">
                                    <?= htmlspecialchars(substr($outro_servico['descricao'], 0, 100)) ?>...
                                </p>
                                
                                <div class="service-footer">
                                    <div class="service-price">
                                        <strong>R$ <?= number_format($outro_servico['preco'], 2, ',', '.') ?></strong>
                                    </div>
                                    
                                    <div class="service-rating">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $outro_servico['media_avaliacao'] ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span>(<?= $outro_servico['total_avaliacoes'] ?>)</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Serviços similares -->
            <?php if (!empty($servicos_similares)): ?>
                <div class="service-section">
                    <h2><i class="fas fa-tags"></i> Serviços Similares</h2>
                    <div class="services-grid">
                        <?php foreach ($servicos_similares as $similar): ?>
                            <div class="service-card">
                                <div class="service-header">
                                    <h3 class="service-title">
                                        <a href="servico-detalhes.php?id=<?= $similar['id_servico'] ?>">
                                            <?= htmlspecialchars($similar['titulo']) ?>
                                        </a>
                                    </h3>
                                    <span class="service-category"><?= htmlspecialchars($similar['nome_categoria']) ?></span>
                                </div>
                                
                                <p class="service-description">
                                    <?= htmlspecialchars(substr($similar['descricao'], 0, 100)) ?>...
                                </p>
                                
                                <div class="service-footer">
                                    <div class="service-price">
                                        <strong>R$ <?= number_format($similar['preco'], 2, ',', '.') ?></strong>
                                    </div>
                                    
                                    <div class="service-rating">
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $similar['media_avaliacao'] ? 'active' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span>(<?= $similar['total_avaliacoes'] ?>)</span>
                                    </div>
                                </div>
                                
                                <div class="service-professional">
                                    <small>por <?= htmlspecialchars($similar['nome_profissional']) ?></small>
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
    
    <!-- Modal de Solicitação de Orçamento -->
    <div id="orcamentoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-invoice"></i> Solicitar Orçamento</h3>
                <button class="close" onclick="fecharModal()">&times;</button>
            </div>
            <form id="orcamentoForm" method="POST" action="api/solicitar-orcamento.php">
                <input type="hidden" name="id_servico" id="servicoId">
                
                <div class="form-group">
                    <label for="descricao">Descreva suas necessidades específicas:</label>
                    <textarea name="descricao" id="descricao" placeholder="Detalhe o que você precisa, quando precisa e qualquer informação adicional relevante..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="prazo">Prazo desejado:</label>
                    <input type="date" name="prazo" id="prazo" min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label for="orcamento_max">Orçamento máximo (opcional):</label>
                    <input type="number" name="orcamento_max" id="orcamento_max" step="0.01" placeholder="R$ 0,00">
                </div>
                
                <div class="price-actions">
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitação
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function iniciarChat(idProfissional) {
            window.location.href = `chat.php?profissional=${idProfissional}`;
        }
        
        function solicitarOrcamento(idServico) {
            document.getElementById('servicoId').value = idServico;
            document.getElementById('orcamentoModal').style.display = 'block';
        }
        
        function fecharModal() {
            document.getElementById('orcamentoModal').style.display = 'none';
        }
        
        function ligarProfissional(telefone) {
            if (telefone) {
                window.location.href = `tel:${telefone}`;
            } else {
                alert('Telefone não disponível');
            }
        }
        
        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('orcamentoModal');
            if (event.target === modal) {
                fecharModal();
            }
        }
        
        // Processar formulário de orçamento
        document.getElementById('orcamentoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('api/solicitar-orcamento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Solicitação enviada com sucesso! O profissional entrará em contato em breve.');
                    fecharModal();
                    // Redirecionar para página de orçamentos
                    window.location.href = 'meus-orcamentos.php';
                } else {
                    alert('Erro ao enviar solicitação: ' + (data.message || 'Tente novamente'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao enviar solicitação. Tente novamente.');
            });
        });
        
        // Funções para prestadores
        function editarServico(idServico) {
            window.location.href = `editar-servico.php?id=${idServico}`;
        }
        
        function fecharServico(idServico) {
            if (confirm('Tem certeza que deseja fechar este serviço? Ele não ficará mais visível para novos clientes.')) {
                fetch('api/alterar-status-servico.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id_servico: idServico,
                        status: 'fechado'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Serviço fechado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao fechar serviço: ' + (data.message || 'Tente novamente'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao fechar serviço. Tente novamente.');
                });
            }
        }
        
        function reabrirServico(idServico) {
            if (confirm('Tem certeza que deseja reabrir este serviço? Ele ficará visível para novos clientes novamente.')) {
                fetch('api/alterar-status-servico.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id_servico: idServico,
                        status: 'ativo'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Serviço reaberto com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao reabrir serviço: ' + (data.message || 'Tente novamente'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao reabrir serviço. Tente novamente.');
                });
            }
        }
        
        function verEstatisticas(idServico) {
            window.location.href = `estatisticas-servico.php?id=${idServico}`;
        }
    </script>
</body>
</html>

