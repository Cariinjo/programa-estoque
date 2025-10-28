<?php
require_once 'includes/config.php'; // Verifique se o caminho está correto

// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // ID do usuário (prestador) logado
$success_message = '';
$error_message = '';

// Processar ações (responder orçamento) via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orcamento_id = (int)$_POST['orcamento_id'];
    $action = $_POST['action'];

    // Garante que a operação ocorra dentro de uma transação se necessário
    $pdo->beginTransaction(); // Inicia a transação aqui

    try {
        if ($action === 'responder') {
            // Pega os dados do formulário de resposta
            $valor_proposto = filter_input(INPUT_POST, 'valor_proposto', FILTER_VALIDATE_FLOAT);
            $observacoes = trim($_POST['observacoes'] ?? '');
            // Pega a data e verifica se é válida no formato YYYY-MM-DD
            $prazo_execucao_str = $_POST['prazo_execucao'] ?? '';
            $prazo_execucao_sql = null;
            if (!empty($prazo_execucao_str)) {
                $dateObj = DateTime::createFromFormat('Y-m-d', $prazo_execucao_str);
                if ($dateObj !== false && $dateObj->format('Y-m-d') === $prazo_execucao_str) {
                    $prazo_execucao_sql = $prazo_execucao_str;
                } else {
                    throw new Exception('Formato inválido para Prazo de Execução.');
                }
            }

            // Validação do valor proposto
            if ($valor_proposto === false || $valor_proposto <= 0) {
                throw new Exception('Valor proposto inválido ou menor/igual a zero');
            }

            // Atualiza o orçamento no banco de dados
            $stmt_update = $pdo->prepare("
                UPDATE orcamentos SET
                    valor_proposto = ?,
                    observacoes = ?,
                    prazo_execucao = ?, -- Nome correto da coluna no BD orcamentos
                    status = 'respondido',
                    data_resposta = NOW() -- Nome correto da coluna no BD orcamentos
                WHERE id_orcamento = ? AND id_profissional IN (
                    SELECT id_profissional FROM profissionais WHERE id_usuario = ? -- Verifica se pertence ao prestador logado
                )
            ");
            // Executa a atualização
            $stmt_update->execute([
                $valor_proposto,
                $observacoes,
                $prazo_execucao_sql, // Usa a data formatada ou null
                $orcamento_id,
                $user_id // ID do prestador logado
            ]);

            // Verifica se alguma linha foi realmente atualizada
            if ($stmt_update->rowCount() > 0) {
                // Se atualizou, busca os dados necessários para a notificação
                $stmt_get_data = $pdo->prepare("
                    SELECT o.id_cliente, s.titulo
                    FROM orcamentos o
                    JOIN servicos s ON o.id_servico = s.id_servico
                    WHERE o.id_orcamento = ?
                ");
                $stmt_get_data->execute([$orcamento_id]);
                $notif_data = $stmt_get_data->fetch(PDO::FETCH_ASSOC);

                // Verifica se encontrou os dados para notificar
                if ($notif_data && isset($notif_data['id_cliente'])) {
                    $id_cliente_destino = $notif_data['id_cliente'];
                    $servico_titulo_notif = $notif_data['titulo'] ?? 'Serviço'; // Usa 'Serviço' se o título for nulo

                    // Define os detalhes da notificação
                    $mensagem_notif = "Você recebeu uma resposta para o orçamento do serviço \"" . htmlspecialchars($servico_titulo_notif) . "\"";
                    // Link para o cliente ver o orçamento respondido (ajuste o nome da página se necessário)
                    $link_acao_notif = "meus-orcamentos.php?id=" . $orcamento_id;

                    // Cria a notificação para o cliente usando os nomes CORRETOS das colunas
                    $stmt_notif_cliente = $pdo->prepare("
                        INSERT INTO notificacoes (
                            id_usuario_destino,
                            tipo_notificacao,
                            mensagem,
                            link_acao,
                            data_criacao,
                            lida
                        ) VALUES (?, 'orcamento_respondido', ?, ?, NOW(), 0) -- Define lida como 0 (não lida)
                    ");
                    $stmt_notif_cliente->execute([
                        $id_cliente_destino,   // O ID do cliente que receberá
                        $mensagem_notif,      // A mensagem
                        $link_acao_notif      // O link
                    ]);
                } else {
                     // Log (opcional): Informa se não conseguiu encontrar o cliente para notificar
                     error_log("Aviso: Dados do cliente ou serviço não encontrados para notificação do orçamento respondido ID: " . $orcamento_id);
                }

                $success_message = 'Orçamento respondido com sucesso!';
                $pdo->commit(); // Confirma a transação se tudo deu certo
            } else {
                // Se rowCount for 0, o orçamento não foi encontrado ou não pertence ao prestador
                throw new Exception('Erro ao atualizar o orçamento. Verifique se o orçamento existe e pertence a você.');
            }
        } // Fim do if ($action === 'responder')

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack(); // Desfaz a transação em caso de erro
        }
        // Loga o erro detalhado no servidor para depuração
        error_log("Erro ao processar ação do orçamento: " . $e->getMessage());
        // Define a mensagem de erro a ser exibida
        $error_message = 'Ocorreu um erro ao processar a ação: ' . $e->getMessage(); // Mantenha $e->getMessage() para depuração
    }
} // Fim do POST check

// --- Busca dos Orçamentos para Exibição ---
try {
    // Buscar ID do profissional associado ao usuário logado
    $stmt_prof = $pdo->prepare("SELECT id_profissional FROM profissionais WHERE id_usuario = ?");
    $stmt_prof->execute([$user_id]);
    $prestador_id = $stmt_prof->fetchColumn();

    // Se não encontrar um profissional associado, redireciona ou mostra erro
    if (!$prestador_id) {
        // Talvez redirecionar para uma página de erro ou perfil incompleto
        throw new Exception("Perfil de profissional não encontrado para este usuário.");
        // header('Location: login.php'); // Ou redirecionar para login/dashboard
        // exit;
    }

    // Buscar todos os orçamentos recebidos por este profissional
    $stmt_orc = $pdo->prepare("
        SELECT o.*, s.titulo as servico_titulo, s.descricao as servico_descricao, s.preco as servico_preco_original, -- Renomeado para evitar conflito
               u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone,
               u.endereco_completo as cliente_endereco, c.nome_cidade as cliente_cidade,
               cat.nome_categoria
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN usuarios u ON o.id_cliente = u.id_usuario -- Junta com usuarios pelo id_cliente do orçamento
        LEFT JOIN cidades_senac_mg c ON u.cidade_id = c.id_cidade -- Junta com cidades pelo cidade_id do usuário cliente
        LEFT JOIN categorias cat ON s.id_categoria = cat.id_categoria -- Junta com categorias pelo id_categoria do serviço
        WHERE o.id_profissional = ? -- Filtra pelo ID do profissional logado
        ORDER BY o.data_solicitacao DESC -- Ordena pelos mais recentes primeiro
    ");
    $stmt_orc->execute([$prestador_id]);
    $orcamentos = $stmt_orc->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar orçamentos recebidos (PDO): " . $e->getMessage());
    $error_message = 'Erro ao carregar orçamentos. Tente recarregar a página.';
    $orcamentos = []; // Garante que a variável exista como array vazio
} catch (Exception $e) { // Captura a exceção do profissional não encontrado
    error_log("Erro ao buscar dados do profissional: " . $e->getMessage());
    $error_message = $e->getMessage();
    $orcamentos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orçamentos Recebidos - Prestador</title>
    <link rel="stylesheet" href="css/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copie todo o seu CSS aqui */
        .orcamentos-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; padding: 2rem; border-radius: 20px; margin-bottom: 2rem; text-align: center; }
        .page-header h1 { margin: 0 0 0.5rem 0; font-size: 2rem; }
        .page-header p { margin: 0; opacity: 0.9; }
        .filters { background: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .filter-row { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .filter-group label { font-weight: 500; color: #2c3e50; }
        .filter-group select { padding: 0.5rem; border: 2px solid #e9ecef; border-radius: 8px; font-size: 0.9rem; }
        .btn-confirm-filter { background: #3498db; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background 0.3s ease; }
        .btn-confirm-filter:hover { background: #2980b9; }
        .orcamento-card { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 1.5rem; overflow: hidden; transition: transform 0.3s ease; }
        .orcamento-card:hover { transform: translateY(-2px); }
        .orcamento-card.novo { border-left: 5px solid #e74c3c; }
        .orcamento-header { background: #f8f9fa; padding: 1.5rem; border-bottom: 1px solid #eee; }
        .orcamento-title { font-size: 1.2rem; font-weight: bold; color: #2c3e50; margin-bottom: 0.5rem; }
        .orcamento-meta { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .cliente-info { display: flex; flex-direction: column; gap: 0.25rem; }
        .cliente-nome { color: #3498db; font-weight: 500; }
        .cliente-cidade { color: #7f8c8d; font-size: 0.9rem; }
        .orcamento-status { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 500; text-transform: uppercase; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-respondido { background: #d1ecf1; color: #0c5460; }
        .status-aceito { background: #d4edda; color: #155724; }
        .status-recusado { background: #f8d7da; color: #721c24; }
        .orcamento-content { padding: 1.5rem; }
        .orcamento-details { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 1.5rem; }
        .detail-section h4 { color: #2c3e50; margin-bottom: 0.5rem; font-size: 1rem; }
        .detail-section p { color: #7f8c8d; line-height: 1.5; margin-bottom: 1rem; }
        .cliente-section { background: #f8f9fa; padding: 1rem; border-radius: 10px; }
        .cliente-contact { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
        .contact-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: #2c3e50; }
        .price-section { text-align: right; }
        .price-original { color: #7f8c8d; font-size: 1.2rem; font-weight: bold; }
        .price-proposed { color: #27ae60; font-size: 1.5rem; font-weight: bold; margin-top: 0.5rem; }
        .response-form { background: #e3f2fd; padding: 1.5rem; border-radius: 10px; margin-top: 1rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .form-group label { font-weight: 500; color: #2c3e50; }
        .form-group input, .form-group textarea { padding: 0.75rem; border: 2px solid #e9ecef; border-radius: 8px; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3498db; }
        .form-group-full { grid-column: 1 / -1; }
        .form-actions { display: flex; gap: 1rem; justify-content: flex-end; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        .empty-state { text-align: center; padding: 3rem; color: #7f8c8d; }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state h3 { margin-bottom: 1rem; color: #2c3e50; }
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        @media (max-width: 768px) {
            .orcamento-details { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .filter-row { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header-prestador.php'; // Verifique se o caminho está correto ?>

    <div class="orcamentos-container">
        <div class="page-header">
            <h1><i class="fas fa-inbox"></i> Orçamentos Recebidos</h1>
            <p>Gerencie as solicitações de orçamento dos seus serviços</p>
        </div>

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

        <div class="filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="respondido">Respondido</option>
                        <option value="aceito">Aceito</option>
                        <option value="recusado">Recusado</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date-filter">Período:</label>
                    <select id="date-filter">
                        <option value="">Todos</option>
                        <option value="7">Últimos 7 dias</option>
                        <option value="30">Últimos 30 dias</option>
                        <option value="90">Últimos 3 meses</option>
                    </select>
                </div>

                <button class="btn-confirm-filter" onclick="aplicarFiltros()">
                    <i class="fas fa-filter"></i> Confirmar Filtro
                </button>
            </div>
        </div>

        <?php if (empty($orcamentos)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Nenhum orçamento recebido</h3>
                <p>Quando clientes solicitarem orçamentos para seus serviços, eles aparecerão aqui.</p>
                <a href="meus-servicos.php" class="btn btn-primary">
                    <i class="fas fa-cogs"></i> Ver Meus Serviços
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orcamentos as $orcamento): ?>
                <?php
                // Verifica se o orçamento foi solicitado nas últimas 24 horas
                $is_novo = (strtotime($orcamento['data_solicitacao']) > strtotime('-24 hours'));
                // Pega o preço original do serviço para exibir (renomeado para servico_preco_original na query)
                $preco_original_servico = $orcamento['servico_preco_original'] ?? $orcamento['preco'] ?? 0;
                ?>
                <div class="orcamento-card <?= $is_novo ? 'novo' : '' ?>" data-status="<?= $orcamento['status'] ?>" data-date="<?= $orcamento['data_solicitacao'] ?>">
                    <div class="orcamento-header">
                        <div class="orcamento-title">
                            <?= htmlspecialchars($orcamento['servico_titulo']) ?>
                            <?php if ($is_novo): ?>
                                <span style="color: #e74c3c; font-size: 0.8rem; margin-left: 0.5rem;">NOVO</span>
                            <?php endif; ?>
                        </div>
                        <div class="orcamento-meta">
                            <div class="cliente-info">
                                <div class="cliente-nome">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($orcamento['cliente_nome']) ?>
                                </div>
                                <div class="cliente-cidade">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($orcamento['cliente_cidade'] ?? 'Cidade não informada') ?>
                                </div>
                            </div>
                            <div class="orcamento-status status-<?= $orcamento['status'] ?>">
                                <?= ucfirst($orcamento['status']) // Exibe status com primeira letra maiúscula ?>
                            </div>
                        </div>
                    </div>

                    <div class="orcamento-content">
                        <div class="orcamento-details">
                            <div class="detail-section">
                                <h4><i class="fas fa-info-circle"></i> Descrição do Serviço</h4>
                                <p><?= nl2br(htmlspecialchars($orcamento['servico_descricao'])) // nl2br para quebras de linha ?></p>

                                <?php if (!empty($orcamento['detalhes_solicitacao'])): ?>
                                    <h4><i class="fas fa-comment"></i> Detalhes da Solicitação do Cliente</h4>
                                    <p><?= nl2br(htmlspecialchars($orcamento['detalhes_solicitacao'])) ?></p>
                                <?php endif; ?>

                                <p><strong>Solicitado em:</strong> <?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></p>

                                <?php if ($orcamento['prazo_desejado']): ?>
                                   <p><strong>Prazo Desejado pelo Cliente:</strong> <?= date('d/m/Y', strtotime($orcamento['prazo_desejado'])) ?></p>
                                <?php endif; ?>

                                <?php if ($orcamento['orcamento_maximo']): ?>
                                   <p><strong>Orçamento Máximo Informado:</strong> R$ <?= number_format($orcamento['orcamento_maximo'], 2, ',', '.') ?></p>
                                <?php endif; ?>

                                <?php if ($orcamento['status'] !== 'pendente' && !empty($orcamento['observacoes'])): ?>
                                    <h4><i class="fas fa-clipboard-list"></i> Suas Observações</h4>
                                    <p><?= nl2br(htmlspecialchars($orcamento['observacoes'])) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="cliente-section">
                                <h4><i class="fas fa-user-circle"></i> Informações do Cliente</h4>
                                <div class="cliente-contact">
                                    <?php if ($orcamento['cliente_telefone']): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?= htmlspecialchars($orcamento['cliente_telefone']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="contact-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?= htmlspecialchars($orcamento['cliente_email']) ?></span>
                                    </div>
                                    <?php if ($orcamento['cliente_endereco']): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= htmlspecialchars($orcamento['cliente_endereco']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="price-section" style="margin-top: 1rem;">
                                    <h4><i class="fas fa-money-bill-wave"></i> Valor</h4>
                                    <div class="price-original">Original: R$ <?= number_format($preco_original_servico, 2, ',', '.') ?></div>
                                    <?php if ($orcamento['valor_proposto'] && $orcamento['status'] !== 'pendente'): ?>
                                        <div class="price-proposed">Proposto: R$ <?= number_format($orcamento['valor_proposto'], 2, ',', '.') ?></div>
                                    <?php endif; ?>
                                     <?php if ($orcamento['prazo_execucao'] && $orcamento['status'] !== 'pendente'): ?>
                                       <p style="font-size: 0.9em; color: #555; margin-top: 5px;"><strong>Prazo Proposto:</strong> <?= date('d/m/Y', strtotime($orcamento['prazo_execucao'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($orcamento['status'] === 'pendente'): ?>
                            <div class="response-form">
                                <h4><i class="fas fa-reply"></i> Responder Orçamento</h4>
                                <form method="POST">
                                    <input type="hidden" name="orcamento_id" value="<?= $orcamento['id_orcamento'] ?>">
                                    <input type="hidden" name="action" value="responder">

                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="valor_proposto_<?= $orcamento['id_orcamento'] ?>">Valor Proposto (R$) *</label>
                                            <input type="number"
                                                   id="valor_proposto_<?= $orcamento['id_orcamento'] ?>"
                                                   name="valor_proposto"
                                                   step="0.01"
                                                   min="0.01"
                                                   value="<?= htmlspecialchars($preco_original_servico) // Preenche com o valor original como sugestão ?>"
                                                   required>
                                        </div>

                                        <div class="form-group">
                                            <label for="prazo_execucao_<?= $orcamento['id_orcamento'] ?>">Prazo de Execução Proposto</label>
                                            <input type="date"
                                                   id="prazo_execucao_<?= $orcamento['id_orcamento'] ?>"
                                                   name="prazo_execucao"
                                                   min="<?= date('Y-m-d') // Data mínima é hoje ?>">
                                        </div>
                                    </div>

                                    <div class="form-group form-group-full">
                                        <label for="observacoes_<?= $orcamento['id_orcamento'] ?>">Observações</label>
                                        <textarea id="observacoes_<?= $orcamento['id_orcamento'] ?>"
                                                  name="observacoes"
                                                  rows="3"
                                                  placeholder="Adicione detalhes sobre o serviço, condições, materiais inclusos/exclusos, etc."></textarea>
                                    </div>

                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Enviar Resposta
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($orcamento['status'] === 'aceito'): ?>
                            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                                <a href="chat.php?cliente=<?= $orcamento['id_cliente'] ?>" class="btn btn-primary">
                                    <i class="fas fa-comments"></i> Iniciar Chat
                                </a>
                                <?php if ($orcamento['cliente_telefone']):
                                    // Limpa o número de telefone para usar no link do WhatsApp
                                    $whatsapp_number = preg_replace('/[^0-9]/', '', $orcamento['cliente_telefone']);
                                    // Adiciona o código do país (55 para Brasil) se não estiver presente
                                    if (strlen($whatsapp_number) <= 11) { // Considera DDD + 8 ou 9 dígitos
                                        $whatsapp_number = '55' . $whatsapp_number;
                                    }
                                ?>
                                    <a href="https://wa.me/<?= $whatsapp_number ?>"
                                       target="_blank" class="btn btn-success">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                         <?php if ($orcamento['status'] === 'respondido'): ?>
                            <div style="text-align: right; margin-top: 1rem; color: #555; font-style: italic;">
                                <i class="fas fa-clock"></i> Aguardando resposta do cliente...
                            </div>
                        <?php endif; ?>
                         <?php if ($orcamento['status'] === 'recusado'): ?>
                            <div style="text-align: right; margin-top: 1rem; color: #721c24; font-style: italic;">
                                <i class="fas fa-times-circle"></i> O cliente recusou este orçamento.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function aplicarFiltros() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const cards = document.querySelectorAll('.orcamento-card');
            let hasVisibleCard = false;

            cards.forEach(card => {
                let show = true;

                // Filtro por status
                if (statusFilter && card.dataset.status !== statusFilter) {
                    show = false;
                }

                // Filtro por data
                if (dateFilter) {
                    try {
                        const cardDate = new Date(card.dataset.date.split(' ')[0]); // Pega só a parte da data
                        const now = new Date();
                        now.setHours(0, 0, 0, 0); // Zera hora para comparar só dias
                        const daysAgo = parseInt(dateFilter);
                        const filterDate = new Date(now.getTime() - (daysAgo * 24 * 60 * 60 * 1000));

                        if (cardDate < filterDate) {
                            show = false;
                        }
                    } catch (e) {
                        console.error("Erro ao processar data do card:", card.dataset.date, e);
                        show = false; // Esconde se a data for inválida
                    }
                }

                card.style.display = show ? 'block' : 'none';
                if (show) {
                    hasVisibleCard = true;
                }
            });

             // Mostra/Esconde mensagem de "Nenhum resultado" (opcional)
            const emptyState = document.querySelector('.empty-state');
            if (emptyState) { // Verifica se existe antes de manipular
                 if (!hasVisibleCard && cards.length > 0) { // Se houver cards, mas nenhum visível
                    // Poderia mostrar uma mensagem "Nenhum orçamento encontrado com esses filtros"
                 } else if (cards.length === 0) {
                     emptyState.style.display = 'block'; // Mostra o estado vazio original
                 }
                 // Se hasVisibleCard for true, não faz nada (mantém estado vazio escondido se houver cards)
            }
        }

        // Aplicar filtros automaticamente quando mudarem (ou ao clicar no botão, como está agora)
        // document.getElementById('status-filter').addEventListener('change', aplicarFiltros);
        // document.getElementById('date-filter').addEventListener('change', aplicarFiltros);

        // Inicializa os filtros se houver parâmetros na URL (opcional)
        // const urlParams = new URLSearchParams(window.location.search);
        // const initialStatus = urlParams.get('status');
        // if (initialStatus) {
        //    document.getElementById('status-filter').value = initialStatus;
        //    aplicarFiltros();
        // }
    </script>
</body>
</html>