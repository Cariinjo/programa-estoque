<?php
require_once 'includes/config.php';

// 1. Validar e obter o ID da categoria da URL
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    header("Location: index.php");
    exit;
}

$id_categoria = (int)$_GET['id'];

try {
    // 2. Buscar informações da categoria no banco de dados
    $stmt_cat = $pdo->prepare("SELECT nome_categoria, descricao FROM categorias WHERE id_categoria = ?");
    $stmt_cat->execute([$id_categoria]);
    $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);

    // Se a categoria não for encontrada, redireciona
    if (!$categoria) {
        header("Location: index.php");
        exit;
    }

    // 3. Buscar os serviços pertencentes a essa categoria
    // REMOVA temporariamente a condição AND s.ativo = 1
    $stmt_serv = $pdo->prepare("
        SELECT s.*, u.nome as nome_profissional 
        FROM servicos s
        JOIN profissionais p ON s.id_profissional = p.id_profissional
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE s.id_categoria = ?
        ORDER BY s.media_avaliacao DESC
    ");
    $stmt_serv->execute([$id_categoria]);
    $servicos = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro em categoria.php: " . $e->getMessage());
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços de <?= htmlspecialchars($categoria['nome_categoria']) ?> - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
        
        <div class="category-header" style="text-align: center; margin-bottom: 2rem;">
            <h1><?= htmlspecialchars($categoria['nome_categoria']) ?></h1>
            <p><?= htmlspecialchars($categoria['descricao']) ?></p>
        </div>
        
        <section class="services-grid">
            <?php if (!empty($servicos)): ?>
                <?php foreach ($servicos as $servico): ?>
                    <div class="service-card">
                        <div class="service-content">
                            <h3 class="service-title"><?= htmlspecialchars($servico['titulo']) ?></h3>
                            <p class="service-description"><?= htmlspecialchars(substr($servico['descricao'], 0, 100)) ?>...</p>
                            <div class="service-price">R$ <?= number_format($servico['preco'], 2, ',', '.') ?></div>
                            <p><strong>Por:</strong> <?= htmlspecialchars($servico['nome_profissional']) ?></p>
                            <a href="servico-detalhes.php?id=<?= $servico['id_servico'] ?>" class="btn btn-primary" style="width: 100%; text-align: center; background-color: blue; margin-top: 1rem;">Ver Detalhes</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; width: 100%;">Ainda não há serviços cadastrados nesta categoria.</p>
            <?php endif; ?>
        </section>

    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>