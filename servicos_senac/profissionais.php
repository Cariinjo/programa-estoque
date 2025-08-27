<?php
require_once 'includes/config.php';

// Parâmetros de busca e filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$area = isset($_GET['area']) ? trim($_GET['area']) : '';
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'rating';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

try {
    // Buscar áreas de atuação para o filtro
    $stmt = $pdo->query("SELECT DISTINCT area_atuacao FROM profissionais ORDER BY area_atuacao");
    $areas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Construir query de busca
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(u.nome LIKE ? OR p.area_atuacao LIKE ? OR p.descricao_perfil LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($area)) {
        $whereConditions[] = "p.area_atuacao = ?";
        $params[] = $area;
    }
    
    if ($rating > 0) {
        $whereConditions[] = "p.media_avaliacao >= ?";
        $params[] = $rating;
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Definir ordenação
    $orderBy = "ORDER BY ";
    switch ($sortBy) {
        case 'name':
            $orderBy .= "u.nome ASC";
            break;
        case 'rating':
            $orderBy .= "p.media_avaliacao DESC, p.total_avaliacoes DESC";
            break;
        case 'newest':
            $orderBy .= "u.data_cadastro DESC";
            break;
        case 'services':
            $orderBy .= "total_servicos DESC";
            break;
        default:
            $orderBy .= "p.media_avaliacao DESC, p.total_avaliacoes DESC";
    }
    
    // Contar total de resultados
    $countQuery = "
        SELECT COUNT(*) as total
        FROM profissionais p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalResults / $perPage);
    
    // Buscar profissionais
    $query = "
        SELECT p.*, u.nome, u.data_cadastro,
               COUNT(s.id_servico) as total_servicos
        FROM profissionais p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        LEFT JOIN servicos s ON p.id_profissional = s.id_profissional
        $whereClause
        GROUP BY p.id_profissional
        $orderBy
        LIMIT $perPage OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $professionals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada profissional, buscar alguns serviços
    foreach ($professionals as &$professional) {
        $stmt = $pdo->prepare("
            SELECT titulo, preco 
            FROM servicos 
            WHERE id_profissional = ? 
            ORDER BY media_avaliacao DESC 
            LIMIT 3
        ");
        $stmt->execute([$professional['id_profissional']]);
        $professional['servicos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Erro na busca de profissionais: " . $e->getMessage());
    $professionals = [];
    $areas = [];
    $totalResults = 0;
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profissionais - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .professionals-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #2d3436;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.1rem;
            color: #636e72;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-filters {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .search-input:focus {
            border-color: #6c5ce7;
        }
        
        .search-button {
            padding: 1rem 2rem;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        
        .search-button:hover {
            transform: translateY(-2px);
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-label {
            font-weight: 500;
            color: #2d3436;
        }
        
        .filter-select {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .filter-select:focus {
            border-color: #6c5ce7;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .results-count {
            color: #636e72;
        }
        
        .sort-select {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            outline: none;
            background: white;
        }
        
        .professionals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .professional-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .professional-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .professional-header {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .professional-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .professional-name {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .professional-area {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .professional-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .stars {
            color: #ffd700;
        }
        
        .professional-content {
            padding: 1.5rem;
        }
        
        .professional-description {
            color: #636e72;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .professional-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #6c5ce7;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #636e72;
        }
        
        .professional-services {
            margin-bottom: 1.5rem;
        }
        
        .services-title {
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }
        
        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }
        
        .service-item:last-child {
            border-bottom: none;
        }
        
        .service-name {
            color: #2d3436;
            flex: 1;
        }
        
        .service-price {
            color: #6c5ce7;
            font-weight: 500;
        }
        
        .professional-footer {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #636e72;
            border: 2px solid #ddd;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }
        
        .pagination a, .pagination span {
            padding: 0.75rem 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #636e72;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            border-color: #6c5ce7;
            color: #6c5ce7;
        }
        
        .pagination .current {
            background: #6c5ce7;
            color: white;
            border-color: #6c5ce7;
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: #636e72;
        }
        
        .no-results i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .no-results h3 {
            margin-bottom: 1rem;
            color: #2d3436;
        }
        
        @media (max-width: 768px) {
            .search-row {
                flex-direction: column;
            }
            
            .filters-row {
                grid-template-columns: 1fr;
            }
            
            .results-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .professionals-grid {
                grid-template-columns: 1fr;
            }
            
            .professional-stats {
                grid-template-columns: 1fr;
            }
            
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="professionals-container">
            <!-- Header da Página -->
            <div class="page-header">
                <h1><i class="fas fa-users"></i> Profissionais Qualificados</h1>
                <p>Conheça nossos profissionais formados pelo SENAC. Talentos especializados prontos para realizar seus projetos.</p>
            </div>
            
            <!-- Filtros de Busca -->
            <form class="search-filters" method="GET">
                <div class="search-row">
                    <input type="text" name="search" class="search-input" placeholder="Buscar profissionais..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Área de Atuação</label>
                        <select name="area" class="filter-select">
                            <option value="">Todas as áreas</option>
                            <?php foreach ($areas as $areaOption): ?>
                                <option value="<?= htmlspecialchars($areaOption) ?>" <?= $area == $areaOption ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($areaOption) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Avaliação Mínima</label>
                        <select name="rating" class="filter-select">
                            <option value="">Qualquer avaliação</option>
                            <option value="5" <?= $rating == 5 ? 'selected' : '' ?>>5 estrelas</option>
                            <option value="4" <?= $rating == 4 ? 'selected' : '' ?>>4+ estrelas</option>
                            <option value="3" <?= $rating == 3 ? 'selected' : '' ?>>3+ estrelas</option>
                            <option value="2" <?= $rating == 2 ? 'selected' : '' ?>>2+ estrelas</option>
                            <option value="1" <?= $rating == 1 ? 'selected' : '' ?>>1+ estrelas</option>
                        </select>
                    </div>
                </div>
            </form>
            
            <!-- Cabeçalho dos Resultados -->
            <div class="results-header">
                <div class="results-count">
                    <?= $totalResults ?> profissional<?= $totalResults != 1 ? 'is' : '' ?> encontrado<?= $totalResults != 1 ? 's' : '' ?>
                    <?php if (!empty($search)): ?>
                        para "<?= htmlspecialchars($search) ?>"
                    <?php endif; ?>
                </div>
                
                <form method="GET" style="display: inline;">
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if ($key !== 'sort'): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="rating" <?= $sortBy == 'rating' ? 'selected' : '' ?>>Melhor avaliados</option>
                        <option value="name" <?= $sortBy == 'name' ? 'selected' : '' ?>>Nome A-Z</option>
                        <option value="services" <?= $sortBy == 'services' ? 'selected' : '' ?>>Mais serviços</option>
                        <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Mais recentes</option>
                    </select>
                </form>
            </div>
            
            <!-- Grid de Profissionais -->
            <?php if (empty($professionals)): ?>
                <div class="no-results">
                    <i class="fas fa-user-slash"></i>
                    <h3>Nenhum profissional encontrado</h3>
                    <p>Tente ajustar os filtros de busca ou explore outras áreas de atuação.</p>
                </div>
            <?php else: ?>
                <div class="professionals-grid">
                    <?php foreach ($professionals as $professional): ?>
                        <div class="professional-card">
                            <div class="professional-header">
                                <div class="professional-avatar">
                                    <?= strtoupper(substr($professional['nome'], 0, 1)) ?>
                                </div>
                                <div class="professional-name"><?= htmlspecialchars($professional['nome']) ?></div>
                                <div class="professional-area"><?= htmlspecialchars($professional['area_atuacao']) ?></div>
                                
                                <div class="professional-rating">
                                    <div class="stars">
                                        <?php
                                        $rating = $professional['media_avaliacao'];
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
                                    <span><?= number_format($rating, 1) ?> (<?= $professional['total_avaliacoes'] ?> avaliações)</span>
                                </div>
                            </div>
                            
                            <div class="professional-content">
                                <?php if (!empty($professional['descricao_perfil'])): ?>
                                    <p class="professional-description"><?= htmlspecialchars($professional['descricao_perfil']) ?></p>
                                <?php endif; ?>
                                
                                <div class="professional-stats">
                                    <div class="stat-item">
                                        <div class="stat-number"><?= $professional['total_servicos'] ?></div>
                                        <div class="stat-label">Serviços</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?= $professional['total_avaliacoes'] ?></div>
                                        <div class="stat-label">Avaliações</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-number"><?= date('Y') - date('Y', strtotime($professional['data_cadastro'])) ?>+</div>
                                        <div class="stat-label">Anos</div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($professional['servicos'])): ?>
                                    <div class="professional-services">
                                        <div class="services-title">Principais Serviços:</div>
                                        <?php foreach ($professional['servicos'] as $service): ?>
                                            <div class="service-item">
                                                <span class="service-name"><?= htmlspecialchars($service['titulo']) ?></span>
                                                <span class="service-price">R$ <?= number_format($service['preco'], 2, ',', '.') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="professional-footer">
                                    <a href="profissional-perfil.php?id=<?= $professional['id_profissional'] ?>" class="btn btn-primary">
                                        <i class="fas fa-user"></i> Ver Perfil
                                    </a>
                                    <a href="servicos.php?professional=<?= $professional['id_profissional'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-cogs"></i> Serviços
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                Próxima <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="js/main.js"></script>
</body>
</html>

