<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$serviceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$servico = null;
$categorias = [];
$message = '';
$messageType = '';

try {
    // Buscar categorias para o dropdown
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nome_categoria");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($serviceId > 0) {
        // Buscar dados do serviço
        $stmt = $pdo->prepare("
            SELECT s.*, u.nome as nome_profissional
            FROM servicos s
            JOIN profissionais p ON s.id_profissional = p.id_profissional
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE s.id_servico = ?
        ");
        $stmt->execute([$serviceId]);
        $servico = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$servico) {
            $message = 'Serviço não encontrado.';
            $messageType = 'error';
        }
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar dados para edição de serviço: " . $e->getMessage());
    $message = 'Erro ao carregar dados do serviço ou categorias.';
    $messageType = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $serviceId > 0) {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $id_categoria = (int)$_POST['id_categoria'];
    $preco = (float)$_POST['preco'];
    $status_servico = trim($_POST['status_servico']);

    if (empty($titulo) || empty($descricao) || $id_categoria <= 0 || $preco < 0) {
        $message = 'Por favor, preencha todos os campos obrigatórios.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE servicos SET titulo = ?, descricao = ?, id_categoria = ?, preco = ?, status_servico = ?
                WHERE id_servico = ?
            ");
            $stmt->execute([$titulo, $descricao, $id_categoria, $preco, $status_servico, $serviceId]);

            $message = 'Serviço atualizado com sucesso!';
            $messageType = 'success';
            // Recarregar dados do serviço após a atualização
            header('Location: editar-servico.php?id=' . $serviceId . '&msg=' . urlencode($message) . '&type=' . $messageType);
            exit;

        } catch (PDOException $e) {
            error_log("Erro ao atualizar serviço: " . $e->getMessage());
            $message = 'Erro interno do servidor ao atualizar serviço.';
            $messageType = 'error';
        }
    }
}

// Exibir mensagens de sucesso/erro após redirecionamento
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['msg']);
    $messageType = htmlspecialchars($_GET['type']);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Serviço - Admin SENAC</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn-submit {
            background-color: #3498db;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #2980b9;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
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
                <li><a href="profissionais.php"><i class="fas fa-briefcase"></i> Profissionais</a></li>
                <li><a href="servicos.php" class="active"><i class="fas fa-cogs"></i> Serviços</a></li>
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
                <h1>Editar Serviço</h1>
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
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($servico): ?>
                <form action="editar-servico.php?id=<?= $serviceId ?>" method="POST">
                    <div class="form-group">
                        <label for="titulo">Título do Serviço:</label>
                        <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($servico['titulo']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao">Descrição:</label>
                        <textarea id="descricao" name="descricao" required><?= htmlspecialchars($servico['descricao']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="id_categoria">Categoria:</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= $servico['id_categoria'] == $cat['id_categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome_categoria']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="preco">Preço (R$):</label>
                        <input type="number" id="preco" name="preco" value="<?= htmlspecialchars($servico['preco']) ?>" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="status_servico">Status do Serviço:</label>
                        <select id="status_servico" name="status_servico" required>
                            <option value="ativo" <?= $servico['status_servico'] == 'ativo' ? 'selected' : '' ?>>Ativo</option>
                            <option value="inativo" <?= $servico['status_servico'] == 'inativo' ? 'selected' : '' ?>>Inativo</option>
                            <option value="pausado" <?= $servico['status_servico'] == 'pausado' ? 'selected' : '' ?>>Pausado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Profissional:</label>
                        <p><?= htmlspecialchars($servico['nome_profissional']) ?></p>
                    </div>

                    <button type="submit" class="btn-submit">Atualizar Serviço</button>
                </form>
            <?php else: ?>
                <p>Serviço não encontrado ou erro ao carregar dados.</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/admin.js"></script>
</body>
</html>


