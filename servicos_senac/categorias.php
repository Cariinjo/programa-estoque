<?php
require_once 'includes/config.php';

try {
    // Buscar todas as categorias ativas
    $stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome_categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todas as Categorias - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        
        <div class="category-header" style="text-align: center; margin-bottom: 2rem;">
            <h1>Todas as Categorias</h1>
            <p>Explore todos os serviços disponíveis por categoria</p>
        </div>
        
        <section class="categories-grid">
            <?php if (!empty($categorias)): ?>
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
                        <div class="category-action">
                            <a href="categoria.php?id=<?= $categoria['id_categoria'] ?>" class="btn btn-primary">
                                Ver Serviços
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-categories" style="text-align: center; width: 100%; padding: 2rem;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <h3>Nenhuma categoria encontrada</h3>
                    <p>As categorias serão disponibilizadas em breve.</p>
                    <a href="index.php" class="btn btn-secondary">Voltar para a Página Inicial</a>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>