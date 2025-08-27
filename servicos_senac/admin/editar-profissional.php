<?php
require_once '../includes/config.php';

// Verificar se é administrador
if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$professionalId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$profissional = null;
$message = '';
$messageType = '';

if ($professionalId > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome, u.email, u.telefone, u.whatsapp, u.endereco_completo, u.cep, u.tipo_usuario
            FROM profissionais p
            JOIN usuarios u ON p.id_usuario = u.id_usuario
            WHERE p.id_profissional = ?
        ");
        $stmt->execute([$professionalId]);
        $profissional = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profissional) {
            $message = 'Profissional não encontrado.';
            $messageType = 'error';
        }

    } catch (PDOException $e) {
        error_log("Erro ao buscar profissional para edição: " . $e->getMessage());
        $message = 'Erro ao carregar dados do profissional.';
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $professionalId > 0) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $whatsapp = trim($_POST['whatsapp']);
    $endereco_completo = trim($_POST['endereco_completo']);
    $cep = trim($_POST['cep']);
    $area_atuacao = trim($_POST['area_atuacao']);
    $descricao = trim($_POST['descricao']);
    $experiencia_anos = (int)$_POST['experiencia_anos'];
    $preco_minimo = (float)$_POST['preco_minimo'];
    $disponibilidade = trim($_POST['disponibilidade']);

    if (empty($nome) || empty($email) || empty($area_atuacao) || empty($descricao)) {
        $message = 'Por favor, preencha todos os campos obrigatórios.';
        $messageType = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            // Atualizar tabela usuarios
            $stmt = $pdo->prepare("
                UPDATE usuarios SET nome = ?, email = ?, telefone = ?, whatsapp = ?, endereco_completo = ?, cep = ?
                WHERE id_usuario = ?
            ");
            $stmt->execute([$nome, $email, $telefone, $whatsapp, $endereco_completo, $cep, $profissional['id_usuario']]);

            // Atualizar tabela profissionais
            $stmt = $pdo->prepare("
                UPDATE profissionais SET area_atuacao = ?, descricao = ?, experiencia_anos = ?, preco_minimo = ?, disponibilidade = ?
                WHERE id_profissional = ?
            ");
            $stmt->execute([$area_atuacao, $descricao, $experiencia_anos, $preco_minimo, $disponibilidade, $professionalId]);

            $pdo->commit();
            $message = 'Profissional atualizado com sucesso!';
            $messageType = 'success';
            // Recarregar dados do profissional após a atualização
            header('Location: editar-profissional.php?id=' . $professionalId . '&msg=' . urlencode($message) . '&type=' . $messageType);
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erro ao atualizar profissional: " . $e->getMessage());
            $message = 'Erro interno do servidor ao atualizar profissional.';
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
    <title>Editar Profissional - Admin SENAC</title>
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
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group textarea {
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
                <h1>Editar Profissional</h1>
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

            <?php if ($profissional): ?>
                <form action="editar-profissional.php?id=<?= $professionalId ?>" method="POST">
                    <h3>Dados do Usuário</h3>
                    <div class="form-group">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($profissional['nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($profissional['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="telefone">Telefone:</label>
                        <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($profissional['telefone']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="whatsapp">WhatsApp:</label>
                        <input type="text" id="whatsapp" name="whatsapp" value="<?= htmlspecialchars($profissional['whatsapp']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="endereco_completo">Endereço Completo:</label>
                        <input type="text" id="endereco_completo" name="endereco_completo" value="<?= htmlspecialchars($profissional['endereco_completo']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="cep">CEP:</label>
                        <input type="text" id="cep" name="cep" value="<?= htmlspecialchars($profissional['cep']) ?>">
                    </div>

                    <h3>Dados do Profissional</h3>
                    <div class="form-group">
                        <label for="area_atuacao">Área de Atuação:</label>
                        <input type="text" id="area_atuacao" name="area_atuacao" value="<?= htmlspecialchars($profissional['area_atuacao']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao">Descrição:</label>
                        <textarea id="descricao" name="descricao" required><?= htmlspecialchars($profissional['descricao']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="experiencia_anos">Anos de Experiência:</label>
                        <input type="number" id="experiencia_anos" name="experiencia_anos" value="<?= htmlspecialchars($profissional['experiencia_anos']) ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="preco_minimo">Preço Mínimo (R$):</label>
                        <input type="number" id="preco_minimo" name="preco_minimo" value="<?= htmlspecialchars($profissional['preco_minimo']) ?>" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label for="disponibilidade">Disponibilidade:</label>
                        <input type="text" id="disponibilidade" name="disponibilidade" value="<?= htmlspecialchars($profissional['disponibilidade']) ?>">
                    </div>

                    <button type="submit" class="btn-submit">Atualizar Profissional</button>
                </form>
            <?php else: ?>
                <p>Profissional não encontrado ou erro ao carregar dados.</p>
            <?php endif; ?>
        </div>
    </main>

    <script src="js/admin.js"></script>
</body>
</html>


