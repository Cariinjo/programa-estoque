<?php
require_once 'includes/config.php';

// Parâmetros de busca e filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$cidade = isset($_GET['cidade']) ? (int)$_GET['cidade'] : 0;
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

try {
    // Buscar categorias para o filtro
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome_categoria");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construir query de busca
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(s.titulo LIKE ? OR s.descricao LIKE ? OR c.nome_categoria LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if ($category > 0) {
        $whereConditions[] = "s.id_categoria = ?";
        $params[] = $category;
    }
    
    if ($minPrice > 0) {
        $whereConditions[] = "s.preco >= ?";
        $params[] = $minPrice;
    }
    
    if ($maxPrice > 0) {
        $whereConditions[] = "s.preco <= ?";
        $params[] = $maxPrice;
    }
    
    if ($cidade > 0) {
        $whereConditions[] = "s.cidade_id = ?";
        $params[] = $cidade;
    }
    
    if ($rating > 0) {
        $whereConditions[] = "s.media_avaliacao >= ?";
        $params[] = $rating;
    }
    
    if ($cidade > 0) {
        $whereConditions[] = "s.cidade_id = ?";
        $params[] = $cidade;
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    // Definir ordenação
    $orderBy = "ORDER BY ";
    switch ($sortBy) {
        case 'price_asc':
            $orderBy .= "s.preco ASC";
            break;
        case 'price_desc':
            $orderBy .= "s.preco DESC";
            break;
        case 'rating':
            $orderBy .= "s.media_avaliacao DESC, s.total_avaliacoes DESC";
            break;
        case 'newest':
            $orderBy .= "s.data_criacao DESC";
            break;
        default:
            $orderBy .= "s.media_avaliacao DESC, s.total_avaliacoes DESC";
    }
    
    // Contar total de resultados
    $countQuery = "
        SELECT COUNT(*) as total
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        $whereClause
    ";
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalResults / $perPage);
    
    // Buscar serviços
    $query = "
        SELECT s.*, c.nome_categoria, u.nome as profissional_nome, p.area_atuacao
        FROM servicos s
        JOIN categorias c ON s.id_categoria = c.id_categoria
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        $whereClause
        $orderBy
        LIMIT $perPage OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erro na busca de serviços: " . $e->getMessage());
    $services = [];
    $categories = [];
    $totalResults = 0;
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .services-container {
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .filter-select, .filter-input {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .filter-select:focus, .filter-input:focus {
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
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .service-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .service-image {
            height: 200px;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
        }
        
        .service-category {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(255,255,255,0.9);
            color: #6c5ce7;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .service-content {
            padding: 1.5rem;
        }
        
        .service-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2d3436;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .service-description {
            color: #636e72;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .service-professional {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .professional-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .service-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .stars {
            color: #ffd700;
        }
        
        .rating-text {
            color: #636e72;
            font-size: 0.9rem;
        }
        
        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .service-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #6c5ce7;
        }
        
        .service-button {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }
        
        .service-button:hover {
            transform: translateY(-2px);
            color: white;
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
            
            .services-grid {
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
        <div class="services-container">
            <!-- Header da Página -->
            <div class="page-header">
                <h1><i class="fas fa-cogs"></i> Serviços Disponíveis</h1>
                <p>Encontre o serviço perfeito para suas necessidades. Profissionais qualificados do SENAC prontos para atendê-lo.</p>
            </div>
            
            <!-- Filtro por Cidade -->
            <?php include 'includes/filtro-cidade.php'; ?>
            
            <!-- Filtros de Busca -->
            <form class="search-filters" method="GET">
                <div class="search-row">
                    <input type="text" name="search" class="search-input" placeholder="Buscar serviços..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                
                <div class="filters-row">
                    <div class="filter-group">
                        <label class="filter-label">Categoria</label>
                        <select name="category" class="filter-select">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= $category == $cat['id_categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome_categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Preço Mínimo</label>
                        <input type="number" name="min_price" class="filter-input" placeholder="R$ 0,00" value="<?= $minPrice > 0 ? $minPrice : '' ?>" step="0.01">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Preço Máximo</label>
                        <input type="number" name="max_price" class="filter-input" placeholder="R$ 0,00" value="<?= $maxPrice > 0 ? $maxPrice : '' ?>" step="0.01">
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
                
                <!-- Manter filtro de cidade -->
                <?php if ($cidade > 0): ?>
                    <input type="hidden" name="cidade" value="<?= $cidade ?>">
                <?php endif; ?>
            </form>
            
            <!-- Cabeçalho dos Resultados -->
            <div class="results-header">
                <div class="results-count">
                    <?= $totalResults ?> serviço<?= $totalResults != 1 ? 's' : '' ?> encontrado<?= $totalResults != 1 ? 's' : '' ?>
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
                        <option value="relevance" <?= $sortBy == 'relevance' ? 'selected' : '' ?>>Mais relevantes</option>
                        <option value="rating" <?= $sortBy == 'rating' ? 'selected' : '' ?>>Melhor avaliados</option>
                        <option value="price_asc" <?= $sortBy == 'price_asc' ? 'selected' : '' ?>>Menor preço</option>
                        <option value="price_desc" <?= $sortBy == 'price_desc' ? 'selected' : '' ?>>Maior preço</option>
                        <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Mais recentes</option>
                    </select>
                </form>
            </div>
            
            <!-- Grid de Serviços -->
            <?php if (empty($services)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Nenhum serviço encontrado</h3>
                    <p>Tente ajustar os filtros de busca ou explore outras categorias.</p>
                </div>
            <?php else: ?>
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-image">
                                <span class="service-category"><?= htmlspecialchars($service['nome_categoria']) ?></span>
                                <i class="fas fa-cogs"></i>
                            </div>
                            
                            <div class="service-content">
                                <h3 class="service-title"><?= htmlspecialchars($service['titulo']) ?></h3>
                                <p class="service-description"><?= htmlspecialchars($service['descricao']) ?></p>
                                
                                <div class="service-professional">
                                    <div class="professional-avatar">
                                        <?= strtoupper(substr($service['profissional_nome'], 0, 1)) ?>
                                    </div>
                                    <span>Por: <?= htmlspecialchars($service['profissional_nome']) ?></span>
                                </div>
                                
                                <div class="service-rating">
                                    <div class="stars">
                                        <?php
                                        $rating = $service['media_avaliacao'];
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
                                    <span class="rating-text">
                                        <?= number_format($rating, 1) ?> (<?= $service['total_avaliacoes'] ?> avaliações)
                                    </span>
                                </div>
                                
                                <div class="service-footer">
                                    <div class="service-price">R$ <?= number_format($service['preco'], 2, ',', '.') ?></div>
                                    <a href="servico-detalhes.php?id=<?= $service['id_servico'] ?>" class="service-button">
                                        <i class="fas fa-eye"></i> Ver Detalhes
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

