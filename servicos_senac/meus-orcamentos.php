<?php
// meus-orcamentos.php
require_once 'includes/config.php'; // Verifique o caminho

// *** NOVO BLOCO: MARCAR NOTIFICAÇÃO COMO LIDA ***
if (isset($_GET['mark_read']) && isLoggedIn() && $_SESSION['user_type'] === 'cliente') { // Verifica se logado e é cliente
    $notification_id_to_mark = (int)$_GET['mark_read'];
    $current_user_id = $_SESSION['user_id'];

    if ($notification_id_to_mark > 0) {
        try {
            $stmt_mark_read = $pdo->prepare("
                UPDATE notificacoes
                SET lida = 1
                WHERE id_notificacao = ? AND id_usuario_destino = ? AND lida = 0
            ");
            $stmt_mark_read->execute([$notification_id_to_mark, $current_user_id]);

            // Opcional: Redirecionar para limpar o parâmetro da URL
            if ($stmt_mark_read->rowCount() > 0) {
               $current_url = strtok($_SERVER["REQUEST_URI"], '?');
               $query_params = $_GET;
               unset($query_params['mark_read']);
               $new_query_string = http_build_query($query_params);
               // Garante que a URL base termine com .php antes de adicionar a query string
               if (substr($current_url, -4) !== '.php' && !empty($new_query_string)) {
                  $current_url .= '.php'; // Adiciona se estiver faltando (ajuste se necessário)
               }
               $redirect_url = $current_url . ($new_query_string ? '?' . $new_query_string : '');
               header('Location: ' . $redirect_url);
               exit;
            }

        } catch (PDOException $e) {
            error_log("Erro ao marcar notificacao ID $notification_id_to_mark como lida: " . $e->getMessage());
        }
    }
}
// *** FIM DO BLOCO ***


// --- Restante do seu código PHP ---

// Verifica se o usuário (cliente) está logado (redundante se já verificado acima, mas seguro)
if (!isLoggedIn() || $_SESSION['user_type'] !== 'cliente') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
$error = null;
$success = null;
$orcamentos = [];

// Processar Ações (Aceitar/Recusar Orçamento via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['orcamento_id'])) {
    $orcamentoId = (int)$_POST['orcamento_id'];
    $action = $_POST['action'];

    $pdo->beginTransaction();
    try {
        $novoStatus = '';
        $tipoNotificacao = '';
        $mensagemNotificacaoBase = '';

        if ($action === 'aceitar') {
            $novoStatus = 'aceito';
            $tipoNotificacao = 'orcamento_aceito';
            $mensagemNotificacaoBase = 'foi ACEITO pelo cliente!';
            $success = "Orçamento aceito com sucesso!";
        } elseif ($action === 'recusar') {
            $novoStatus = 'recusado';
            $tipoNotificacao = 'orcamento_recusado';
            $mensagemNotificacaoBase = 'foi RECUSADO pelo cliente.';
            $success = "Orçamento recusado.";
        } else {
            throw new Exception("Ação inválida.");
        }

        // 1. Atualiza o status
        $stmt_update = $pdo->prepare("
            UPDATE orcamentos SET status = ?
            WHERE id_orcamento = ? AND id_cliente = ? AND status = 'respondido'
        ");
        $stmt_update->execute([$novoStatus, $orcamentoId, $userId]);

        if ($stmt_update->rowCount() > 0) {
            // 2. Busca dados para notificação
            $stmt_get_data = $pdo->prepare("
                SELECT prof.id_usuario, s.titulo
                FROM orcamentos o
                JOIN profissionais prof ON o.id_profissional = prof.id_profissional
                JOIN servicos s ON o.id_servico = s.id_servico
                WHERE o.id_orcamento = ?
            ");
            $stmt_get_data->execute([$orcamentoId]);
            $prof_data = $stmt_get_data->fetch(PDO::FETCH_ASSOC);

            if ($prof_data && isset($prof_data['id_usuario'])) {
                $id_usuario_destino_prof = $prof_data['id_usuario'];
                $servico_titulo_notif = $prof_data['titulo'] ?? 'Serviço';
                $mensagem_notif = "Seu orçamento para \"" . htmlspecialchars($servico_titulo_notif) . "\" " . $mensagemNotificacaoBase;
                $link_acao_notif = "orcamentos-recebidos.php?id=" . $orcamentoId; // Link para o prestador

                // 3. Insere a notificação (nomes corretos)
                $stmt_notif = $pdo->prepare("
                    INSERT INTO notificacoes (
                        id_usuario_destino, tipo_notificacao, mensagem, link_acao, data_criacao, lida
                    ) VALUES (?, ?, ?, ?, NOW(), 0)
                ");
                $stmt_notif->execute([
                    $id_usuario_destino_prof, $tipoNotificacao, $mensagem_notif, $link_acao_notif
                ]);

                $pdo->commit();
                header('Location: meus-orcamentos.php?success=' . urlencode($success)); // Redireciona com msg sucesso
                exit;
            } else {
                throw new Exception("Não foi possível encontrar os dados do profissional para notificar.");
            }
        } else {
            throw new Exception("Orçamento não encontrado, não pertence a você ou já foi processado.");
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Erro ao processar ação ($action) orçamento ID $orcamentoId: " . $e->getMessage());
        $error = "Erro ao processar ação: " . $e->getMessage();
    }
}

// Verifica se há mensagem de sucesso na URL (após redirecionamento)
if (isset($_GET['success'])) {
    $success = htmlspecialchars(urldecode($_GET['success'])); // Usa urldecode
}

// --- Busca dos Orçamentos para Exibição ---
try {
    $stmt_orc = $pdo->prepare("
        SELECT o.*,
               s.titulo as servico_titulo, s.descricao as servico_descricao, s.preco as servico_preco_original,
               u.nome as profissional_nome, u.email as profissional_email, u.telefone as profissional_telefone,
               prof.id_profissional,
               prof.area_atuacao, prof.descricao_perfil as profissional_descricao,
               c.nome_categoria as categoria_nome -- Nome CORRETO da coluna categoria
        FROM orcamentos o
        JOIN servicos s ON o.id_servico = s.id_servico
        JOIN profissionais prof ON o.id_profissional = prof.id_profissional
        JOIN usuarios u ON prof.id_usuario = u.id_usuario
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        WHERE o.id_cliente = ?
        ORDER BY o.data_solicitacao DESC
    ");
    $stmt_orc->execute([$userId]);
    $orcamentos = $stmt_orc->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar orçamentos cliente ID {$userId} (PDO): " . $e->getMessage());
    if ($error === null) { // Só define se não houver erro do POST
        $error = "Erro ao carregar seus orçamentos. Tente novamente.";
    }
    $orcamentos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Orçamentos - Serviços SENAC</title>
    <link rel="stylesheet" href="/teste/servicos_senac/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/teste/servicos_senac/css/notifications.css"> <style>
        /* Cole TODO o seu CSS da página meus-orcamentos.php aqui */
        .orcamentos-container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { background: linear-gradient(45deg, #6c5ce7, #a29bfe); color: white; padding: 2rem; border-radius: 15px; margin-bottom: 2rem; text-align: center; }
        .page-header h1 { margin: 0 0 0.5rem 0; font-size: 2rem; }
        .page-header p { margin: 0; opacity: 0.9; }
        .filters { background: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .filter-row { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .filter-group label { font-weight: 500; color: #2d3436; }
        .filter-group select { padding: 0.5rem; border: 1px solid #ddd; border-radius: 5px; font-size: 0.9rem; min-width: 150px; } /* Largura mínima */
        .orcamento-card { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-bottom: 1.5rem; overflow: hidden; transition: transform 0.3s ease; }
        .orcamento-card:hover { transform: translateY(-2px); }
        .orcamento-header { background: #f8f9fa; padding: 1.5rem; border-bottom: 1px solid #eee; }
        .orcamento-title { font-size: 1.2rem; font-weight: bold; color: #2d3436; margin-bottom: 0.5rem; }
        .orcamento-meta { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .orcamento-info { display: flex; flex-direction: column; gap: 0.25rem; }
        .profissional-nome { color: #6c5ce7; font-weight: 500; }
        .categoria-nome { color: #636e72; font-size: 0.9rem; }
        .orcamento-status { padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 500; text-transform: uppercase; }
        .status-pendente { background: #ffeaa7; color: #d63031; }
        .status-respondido { background: #ddd6fe; color: #6c5ce7; }
        .status-aceito { background: #d1f2eb; color: #00b894; }
        .status-concluido { background: #d1f2eb; color: #00b894; }
        .status-recusado { background: #fab1a0; color: #e17055; }
        .orcamento-content { padding: 1.5rem; }
        .orcamento-details { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 1.5rem; }
        .detail-section h4 { color: #2d3436; margin-bottom: 0.5rem; font-size: 1rem; }
        .detail-section p { color: #636e72; line-height: 1.5; margin-bottom: 1rem; white-space: pre-wrap; /* Mantém quebras de linha */}
        .price-section { text-align: right; }
        .price-original { color: #636e72; text-decoration: line-through; font-size: 0.9rem; }
        .price-proposed { color: #6c5ce7; font-size: 1.5rem; font-weight: bold; }
        .orcamento-dates { display: flex; justify-content: space-between; padding: 1rem; background: #f8f9fa; border-radius: 10px; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .date-item { text-align: center; }
        .date-label { color: #636e72; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .date-value { color: #2d3436; font-weight: 500; }
        .orcamento-actions { display: flex; gap: 1rem; justify-content: flex-end; flex-wrap: wrap; margin-top: 1rem; /* Adiciona espaço acima dos botões */ }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 25px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.3s ease; font-size: 0.9rem; }
        .btn-primary { background: linear-gradient(45deg, #6c5ce7, #a29bfe); color: white; }
        .btn-success { background: linear-gradient(45deg, #00b894, #55efc4); color: white; }
        .btn-danger { background: linear-gradient(45deg, #e17055, #fd79a8); color: white; }
        .btn-secondary { background: #636e72; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .empty-state { text-align: center; padding: 3rem; color: #636e72; background: #fff; border-radius: 15px; margin-top: 2rem; }
        .empty-state i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; color: #a29bfe;}
        .empty-state h3 { margin-bottom: 1rem; color: #2d3436; }
        .alert { padding: 1rem; border-radius: 10px; margin: 1rem 0; /* Ajustado margin */ }
        .alert-success { background: #d1f2eb; color: #00b894; border: 1px solid #00b894; }
        .alert-error { background: #fab1a0; color: #e17055; border: 1px solid #e17055; }
        @media (max-width: 768px) {
            .orcamento-details { grid-template-columns: 1fr; }
            .orcamento-meta { flex-direction: column; align-items: flex-start; }
            .orcamento-actions { justify-content: center; }
            .filter-row { flex-direction: column; align-items: stretch; }
            .orcamento-dates { justify-content: center; gap: 1.5rem; } /* Aumenta gap */
            .price-section { text-align: left; margin-top: 1rem; } /* Ajusta seção de preço */
        }
    </style>
</head>
<body class="logged-in"> <?php include 'includes/header.php'; // Inclui o cabeçalho corrigido ?>

    <div class="container"> <div class="orcamentos-container">
            <div class="page-header">
                <h1><i class="fas fa-file-invoice"></i> Meus Orçamentos</h1>
                <p>Gerencie os orçamentos que você solicitou aos profissionais</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
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
                            <option value="concluido">Concluído</option>
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
                    </div>
            </div>

            <div id="orcamento-list-container"> <?php if (empty($orcamentos)): ?>
                    <div class="empty-state" id="empty-state-initial">
                        <i class="fas fa-file-invoice"></i>
                        <h3>Nenhum orçamento encontrado</h3>
                        <p>Você ainda não solicitou ou recebeu orçamentos dos profissionais.</p>
                        <a href="servicos.php" class="btn btn-primary"> <i class="fas fa-search"></i> Buscar Serviços </a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($orcamentos as $orcamento): ?>
                        <div class="orcamento-card" data-status="<?= $orcamento['status'] ?>" data-date="<?= date('Y-m-d', strtotime($orcamento['data_solicitacao'])) // Formato Y-m-d para JS ?>">
                            <div class="orcamento-header">
                                <div class="orcamento-title"><?= htmlspecialchars($orcamento['servico_titulo']) ?></div>
                                <div class="orcamento-meta">
                                    <div class="orcamento-info">
                                        <div class="profissional-nome">
                                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($orcamento['profissional_nome']) ?>
                                        </div>
                                        <div class="categoria-nome">
                                            <i class="fas fa-tag"></i> <?= htmlspecialchars($orcamento['categoria_nome'] ?: 'N/A') ?>
                                        </div>
                                    </div>
                                    <div class="orcamento-status status-<?= $orcamento['status'] ?>">
                                        <?= ucfirst($orcamento['status']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="orcamento-content">
                                <div class="orcamento-details">
                                    <div class="detail-section">
                                        <h4><i class="fas fa-info-circle"></i> Descrição do Serviço</h4>
                                        <p><?= nl2br(htmlspecialchars($orcamento['servico_descricao'])) ?></p>

                                        <?php if (!empty($orcamento['detalhes_solicitacao'])): ?>
                                            <h4><i class="fas fa-comment-dots"></i> Sua Solicitação</h4>
                                            <p><?= nl2br(htmlspecialchars($orcamento['detalhes_solicitacao'])) ?></p>
                                        <?php endif; ?>

                                        <?php if (!empty($orcamento['observacoes'])): ?>
                                            <h4><i class="fas fa-clipboard-list"></i> Observações do Profissional</h4>
                                            <p><?= nl2br(htmlspecialchars($orcamento['observacoes'])) ?></p>
                                        <?php endif; ?>

                                        <h4><i class="fas fa-user-tie"></i> Sobre o Profissional</h4>
                                        <p><?= nl2br(htmlspecialchars($orcamento['profissional_descricao'] ?: 'Descrição não disponível')) ?></p>
                                        <p><strong>Área:</strong> <?= htmlspecialchars($orcamento['area_atuacao']) ?></p>
                                    </div>
                                    <div class="price-section">
                                        <h4><i class="fas fa-money-bill-wave"></i> Valor</h4>
                                        <?php
                                        $preco_exibir = $orcamento['servico_preco_original'];
                                        $preco_original_exibir = null;
                                        if ($orcamento['status'] !== 'pendente' && !empty($orcamento['valor_proposto'])) {
                                            $preco_exibir = $orcamento['valor_proposto'];
                                            if ($orcamento['valor_proposto'] != $orcamento['servico_preco_original']) {
                                                $preco_original_exibir = $orcamento['servico_preco_original'];
                                            }
                                        }
                                        ?>
                                        <?php if ($preco_original_exibir): ?>
                                            <div class="price-original">Original: R$ <?= number_format($preco_original_exibir, 2, ',', '.') ?></div>
                                        <?php endif; ?>
                                        <div class="price-proposed">R$ <?= number_format($preco_exibir, 2, ',', '.') ?></div>
                                    </div>
                                </div>
                                <div class="orcamento-dates">
                                    <div class="date-item">
                                        <div class="date-label">Solicitado em</div>
                                        <div class="date-value"><?= date('d/m/Y H:i', strtotime($orcamento['data_solicitacao'])) ?></div>
                                    </div>
                                    <?php if ($orcamento['data_resposta']): ?>
                                        <div class="date-item">
                                            <div class="date-label">Respondido em</div>
                                            <div class="date-value"><?= date('d/m/Y H:i', strtotime($orcamento['data_resposta'])) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($orcamento['prazo_execucao']): ?>
                                        <div class="date-item">
                                            <div class="date-label">Prazo Proposto</div>
                                            <div class="date-value"><?= date('d/m/Y', strtotime($orcamento['prazo_execucao'])) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="orcamento-actions">
                                    <?php if ($orcamento['status'] === 'respondido'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="orcamento_id" value="<?= $orcamento['id_orcamento'] ?>">
                                            <input type="hidden" name="action" value="aceitar">
                                            <button type="submit" class="btn btn-success" onclick="return confirm('Confirmar aceitação deste orçamento?')">
                                                <i class="fas fa-check"></i> Aceitar Proposta
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="orcamento_id" value="<?= $orcamento['id_orcamento'] ?>">
                                            <input type="hidden" name="action" value="recusar">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Confirmar recusa deste orçamento?')">
                                                <i class="fas fa-times"></i> Recusar Proposta
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($orcamento['status'] === 'aceito'): ?>
                                        <a href="chat.php?orcamento=<?= $orcamento['id_orcamento'] ?>" class="btn btn-primary"> <i class="fas fa-comments"></i> Conversar</a>
                                        <?php if ($orcamento['profissional_telefone']):
                                            $whatsapp_number = preg_replace('/[^0-9]/', '', $orcamento['profissional_telefone']);
                                            if (strlen($whatsapp_number) <= 11) { $whatsapp_number = '55' . $whatsapp_number; }
                                        ?>
                                        <a href="https://wa.me/<?= $whatsapp_number ?>" target="_blank" class="btn btn-success">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($orcamento['status'] !== 'pendente'): ?>
                                        <a href="profissional-perfil.php?id=<?= $orcamento['id_profissional'] ?>" class="btn btn-secondary">
                                            <i class="fas fa-user"></i> Ver Perfil
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="empty-state" id="no-filter-results" style="display: none;">
                     <i class="fas fa-filter"></i>
                     <h3>Nenhum orçamento encontrado</h3>
                     <p>Nenhum orçamento corresponde aos filtros selecionados.</p>
                 </div>
            </div> </div>
    </div>

    <?php // include 'includes/footer.php'; ?>

    <script>
        // Função de Filtro (adaptação da anterior)
        function filterOrcamentos() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const cards = document.querySelectorAll('#orcamento-list-container .orcamento-card');
            const emptyStateInitial = document.getElementById('empty-state-initial');
            const noFilterResults = document.getElementById('no-filter-results');
            let hasVisibleCard = false;

            cards.forEach(card => {
                let show = true;
                const cardStatus = card.dataset.status;
                const cardDateStr = card.dataset.date; // Já está como YYYY-MM-DD

                // Filtro por status
                if (statusFilter && cardStatus !== statusFilter) {
                    show = false;
                }

                // Filtro por data (só aplica se ainda estiver visível)
                if (show && dateFilter && cardDateStr) {
                    try {
                        const cardDate = new Date(cardDateStr + 'T00:00:00'); // Trata como início do dia
                        const now = new Date();
                        now.setHours(0, 0, 0, 0); // Zera hora atual
                        const daysAgo = parseInt(dateFilter);
                        // Calcula a data limite (X dias atrás)
                        const filterDateLimit = new Date(now.getTime() - (daysAgo * 24 * 60 * 60 * 1000));

                        if (cardDate < filterDateLimit) {
                            show = false;
                        }
                    } catch (e) {
                        console.error("Erro ao processar data do card:", cardDateStr, e);
                        show = false; // Esconde se a data for inválida
                    }
                }

                // Mostra ou esconde o card
                card.style.display = show ? 'block' : 'none';
                if (show) {
                    hasVisibleCard = true;
                }
            });

            // Gerencia as mensagens de "nenhum resultado"
            if (emptyStateInitial) emptyStateInitial.style.display = 'none'; // Esconde a inicial sempre que houver cards ou filtro
            if (noFilterResults) noFilterResults.style.display = 'none'; // Esconde a de filtro por padrão

            if (cards.length > 0) { // Se existiam cards originalmente
                if (!hasVisibleCard && (statusFilter || dateFilter)) {
                    // Se nenhum card ficou visível E algum filtro está ativo
                    if (noFilterResults) noFilterResults.style.display = 'block'; // Mostra msg de filtro
                }
            } else {
                 // Se não havia NENHUM card vindo do PHP
                 if(emptyStateInitial) emptyStateInitial.style.display = 'block'; // Mostra a msg inicial
            }
        }

        // Adiciona listeners para aplicar filtros ao mudar seleção
        const statusSelect = document.getElementById('status-filter');
        const dateSelect = document.getElementById('date-filter');
        if (statusSelect) statusSelect.addEventListener('change', filterOrcamentos);
        if (dateSelect) dateSelect.addEventListener('change', filterOrcamentos);

        // Opcional: Chama o filtro ao carregar a página se houver valores pré-selecionados (ex: via URL)
        // document.addEventListener('DOMContentLoaded', filterOrcamentos);

    </script>
     </body>
</html>