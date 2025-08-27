<?php
require_once 'includes/config.php';

if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$servico_id = isset($_GET['servico_id']) ? (int)$_GET['servico_id'] : 0;

$cliente_nome = '';
$servico_titulo = '';
$servico_preco = 0;

try {
    // Buscar ID do profissional
    $stmt = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $prestador_id = $stmt->fetchColumn();

    if (!$prestador_id) {
        header('Location: login.php');
        exit;
    }

    // Se cliente_id e servico_id forem fornecidos, pré-preencher formulário
    if ($cliente_id > 0 && $servico_id > 0) {
        $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id_usuario = ? AND tipo_usuario = 'cliente'");
        $stmt->execute([$cliente_id]);
        $cliente_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente_data) {
            $cliente_nome = $cliente_data['nome'];
        }

        $stmt = $pdo->prepare("SELECT titulo, preco FROM servicos WHERE id_servico = ? AND id_profissional = ?");
        $stmt->execute([$servico_id, $prestador_id]);
        $servico_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($servico_data) {
            $servico_titulo = $servico_data['titulo'];
            $servico_preco = $servico_data['preco'];
        }
    }

    // Processar envio do formulário
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $target_cliente_id = (int)$_POST['cliente_id'];
        $target_servico_id = (int)$_POST['servico_id'];
        $valor_proposto = (float)$_POST['valor_proposto'];
        $observacoes = trim($_POST['observacoes'] ?? '');
        $prazo_execucao = $_POST['prazo_execucao'] ?? '';

        if ($valor_proposto <= 0) {
            throw new Exception('Valor proposto deve ser maior que zero.');
        }

        // Verificar se o serviço pertence ao profissional logado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE id_servico = ? AND id_profissional = ?");
        $stmt->execute([$target_servico_id, $prestador_id]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception('Serviço inválido ou não pertence a você.');
        }

        // Inserir o novo orçamento
        $stmt = $pdo->prepare("
            INSERT INTO orcamentos (id_cliente, id_profissional, id_servico, valor_proposto, observacoes, prazo_execucao, status, data_solicitacao)
            VALUES (?, ?, ?, ?, ?, ?, 'respondido', NOW())
        ");
        $stmt->execute([$target_cliente_id, $prestador_id, $target_servico_id, $valor_proposto, $observacoes, $prazo_execucao]);

        $orcamento_id = $pdo->lastInsertId();

        // Criar notificação para o cliente
        $stmt = $pdo->prepare("
            INSERT INTO notificacoes (id_usuario_destino, tipo_notificacao, mensagem, link_acao)
            VALUES (?, 'novo_orcamento', ?, ?)
        ");
        $notificationMessage = "Você recebeu um novo orçamento para o serviço '" . $servico_titulo . "'.";
        $linkAcao = "meus-orcamentos.php";
        $stmt->execute([$target_cliente_id, $notificationMessage, $linkAcao]);

        $success_message = 'Orçamento enviado com sucesso!';

    } else {
        // Se não for POST, buscar lista de clientes e serviços para os dropdowns
        $stmt = $pdo->prepare("SELECT id_usuario, nome FROM usuarios WHERE tipo_usuario = 'cliente' ORDER BY nome");
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT id_servico, titulo, preco FROM servicos WHERE id_profissional = ? ORDER BY titulo");
        $stmt->execute([$prestador_id]);
        $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Erro ao criar orçamento: " . $e->getMessage());
    $error_message = 'Erro interno do servidor ao criar orçamento.';
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Orçamento - Serviços SENAC</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group-full {
            grid-column: 1 / -1;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary {
            background: linear-gradient(45deg, #6c5ce7, #a29bfe);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d1f2eb;
            color: #00b894;
            border: 1px solid #00b894;
        }
        .alert-error {
            background: #fab1a0;
            color: #e17055;
            border: 1px solid #e17055;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/header-prestador.php'; ?>

    <div class="container">
        <div class="form-container">
            <h1><i class="fas fa-file-invoice-dollar"></i> Criar Novo Orçamento</h1>
            <p>Preencha os detalhes para enviar um orçamento a um cliente.</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="cliente_id">Cliente *</label>
                        <?php if ($cliente_id > 0 && $cliente_nome): ?>
                            <input type="text" value="<?= htmlspecialchars($cliente_nome) ?>" disabled>
                            <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                        <?php else: ?>
                            <select id="cliente_id" name="cliente_id" required>
                                <option value="">Selecione um cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?= $cliente['id_usuario'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="servico_id">Serviço *</label>
                        <?php if ($servico_id > 0 && $servico_titulo): ?>
                            <input type="text" value="<?= htmlspecialchars($servico_titulo) ?> (R$ <?= number_format($servico_preco, 2, ',', '.') ?>)" disabled>
                            <input type="hidden" name="servico_id" value="<?= $servico_id ?>">
                        <?php else: ?>
                            <select id="servico_id" name="servico_id" required>
                                <option value="">Selecione um serviço</option>
                                <?php foreach ($servicos as $servico): ?>
                                    <option value="<?= $servico['id_servico'] ?>"><?= htmlspecialchars($servico['titulo']) ?> (R$ <?= number_format($servico['preco'], 2, ',', '.') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="valor_proposto">Valor Proposto (R$) *</label>
                        <input type="number" id="valor_proposto" name="valor_proposto" step="0.01" min="0.01" value="<?= $servico_preco > 0 ? $servico_preco : '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="prazo_execucao">Prazo de Execução (Data)</label>
                        <input type="date" id="prazo_execucao" name="prazo_execucao" min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="form-group form-group-full">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="5" placeholder="Detalhes adicionais sobre o orçamento, condições, etc."></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar Orçamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

