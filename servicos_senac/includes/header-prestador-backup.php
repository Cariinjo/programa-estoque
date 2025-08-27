<?php
// Verificar se o usuário está logado e obter informações
$user_logged = isLoggedIn();
$user_name = $user_logged ? $_SESSION['user_name'] : '';
$user_type = $user_logged ? $_SESSION['user_type'] : '';
$user_avatar = $user_logged ? (isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : '') : '';
$user_id = $user_logged ? $_SESSION['user_id'] : 0;

// Gerar iniciais do nome para avatar
$user_initials = '';
if ($user_name) {
    $names = explode(' ', $user_name);
    $user_initials = strtoupper(substr($names[0], 0, 1));
    if (count($names) > 1) {
        $user_initials .= strtoupper(substr($names[count($names) - 1], 0, 1));
    }
}

// Buscar dados dinâmicos do prestador
$orcamentos_pendentes = 0;
$servicos_ativos = 0;
$avaliacoes_novas = 0;
$mensagens_nao_lidas = 0;

if ($user_logged && $user_type === 'prestador') {
    try {
        // Contar orçamentos pendentes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orcamentos WHERE prestador_id = ? AND status = 'pendente'");
        $stmt->execute([$user_id]);
        $orcamentos_pendentes = $stmt->fetchColumn();
        
        // Contar serviços ativos
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM servicos WHERE prestador_id = ? AND status = 'ativo'");
        $stmt->execute([$user_id]);
        $servicos_ativos = $stmt->fetchColumn();
        
        // Contar avaliações novas (últimos 7 dias)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM avaliacoes WHERE prestador_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute([$user_id]);
        $avaliacoes_novas = $stmt->fetchColumn();
        
        // Contar mensagens não lidas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = 0");
        $stmt->execute([$user_id]);
        $mensagens_nao_lidas = $stmt->fetchColumn();
        
    } catch (Exception $e) {
        // Em caso de erro, manter valores padrão
    }
}
?>

<header class="modern-header prestador-header">
    <div class="header-container">
        <!-- Logo e Brand -->
        <div class="header-brand">
            <a href="dashboard-prestador.php" class="brand-link">
                <div class="brand-logo prestador-logo">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">SENAC</span>
                    <span class="brand-subtitle">Prestador</span>
                </div>
            </a>
        </div>

        <!-- Navegação Principal do Prestador -->
        <nav class="header-nav" id="header-nav">
            <div class="nav-links">
                <a href="dashboard-prestador.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard-prestador.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orcamentos-recebidos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orcamentos-recebidos.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Orçamentos</span>
                    <?php if ($orcamentos_pendentes > 0): ?>
                        <span class="nav-badge"><?= $orcamentos_pendentes ?></span>
                    <?php endif; ?>
                </a>
                <a href="meus-servicos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'meus-servicos.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i>
                    <span>Meus Serviços</span>
                    <span class="nav-count"><?= $servicos_ativos ?></span>
                </a>
                <a href="historico-servicos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'historico-servicos.php' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    <span>Histórico</span>
                </a>
                <a href="chat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i>
                    <span>Chat</span>
                    <?php if ($mensagens_nao_lidas > 0): ?>
                        <span class="nav-badge"><?= $mensagens_nao_lidas ?></span>
                    <?php endif; ?>
                </a>
                <a href="suporte.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'suporte.php' ? 'active' : '' ?>">
                    <i class="fas fa-life-ring"></i>
                    <span>Suporte</span>
                </a>
            </div>
        </nav>

        <!-- Ações do Usuário -->
        <div class="header-actions">
            <?php if ($user_logged && $user_type === 'prestador'): ?>
                <!-- Status do Prestador -->
                <div class="prestador-status" onclick="toggleStatusMenu()">
                    <div class="status-indicator online"></div>
                    <span class="status-text">Disponível</span>
                    <i class="fas fa-chevron-down"></i>
                </div>

                <!-- Menu de Status -->
                <div class="status-menu" id="status-menu">
                    <div class="status-option" data-status="disponivel">
                        <div class="status-dot online"></div>
                        <span>Disponível</span>
                    </div>
                    <div class="status-option" data-status="ocupado">
                        <div class="status-dot busy"></div>
                        <span>Ocupado</span>
                    </div>
                    <div class="status-option" data-status="ausente">
                        <div class="status-dot away"></div>
                        <span>Ausente</span>
                    </div>
                    <div class="status-option" data-status="offline">
                        <div class="status-dot offline"></div>
                        <span>Offline</span>
                    </div>
                </div>

                <!-- Usuário Logado -->
                <div class="user-menu-container">
                    <!-- Notificações -->
                    <div class="notification-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if ($avaliacoes_novas > 0): ?>
                            <span class="notification-badge"><?= $avaliacoes_novas ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Menu do Usuário -->
                    <div class="user-menu" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            <?php if ($user_avatar): ?>
                                <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Avatar">
                            <?php else: ?>
                                <span class="avatar-initials"><?= $user_initials ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                            <span class="user-type">Prestador de Serviços</span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </div>

                    <!-- Dropdown do Usuário -->
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="dashboard-prestador.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Meu Dashboard</span>
                        </a>
                        <a href="perfil-prestador.php" class="dropdown-item">
                            <i class="fas fa-user-tie"></i>
                            <span>Meu Perfil</span>
                        </a>
                        <a href="cadastrar-servico.php" class="dropdown-item">
                            <i class="fas fa-plus-circle"></i>
                            <span>Novo Serviço</span>
                        </a>
                        <a href="financeiro.php" class="dropdown-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Financeiro</span>
                        </a>
                        <a href="avaliacoes.php" class="dropdown-item">
                            <i class="fas fa-star"></i>
                            <span>Avaliações</span>
                            <?php if ($avaliacoes_novas > 0): ?>
                                <span class="dropdown-badge"><?= $avaliacoes_novas ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="configuracoes-prestador.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </a>
                        <a href="ajuda-prestador.php" class="dropdown-item">
                            <i class="fas fa-question-circle"></i>
                            <span>Ajuda</span>
                        </a>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Sair</span>
                        </a>
                    </div>
                </div>

                <!-- Painel de Notificações -->
                <div class="notifications-panel" id="notifications-panel">
                    <div class="notifications-header">
                        <h3>Notificações</h3>
                        <button class="mark-all-read" onclick="marcarTodasLidas()">Marcar todas como lidas</button>
                    </div>
                    <div class="notifications-list" id="notifications-list">
                        <!-- Notificações serão carregadas dinamicamente -->
                    </div>
                    <div class="notifications-footer">
                        <a href="notificacoes-prestador.php">Ver todas as notificações</a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Redirecionamento se não for prestador -->
                <script>
                    if (<?= json_encode($user_logged) ?> && '<?= $user_type ?>' !== 'prestador') {
                        window.location.href = 'dashboard.php';
                    } else if (!<?= json_encode($user_logged) ?>) {
                        window.location.href = 'login.php';
                    }
                </script>
            <?php endif; ?>
        </div>

        <!-- Botão Mobile Menu -->
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <!-- Overlay para fechar menus -->
    <div class="header-overlay" id="header-overlay" onclick="closeAllMenus()"></div>
</header>

<style>
/* HEADER ESPECÍFICO DO PRESTADOR */
.prestador-header {
    background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
}

.prestador-logo {
    background: rgba(52, 152, 219, 0.3);
}

.brand-subtitle {
    color: rgba(255,255,255,0.9);
}

/* BADGES E CONTADORES */
.nav-badge {
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-left: 4px;
}

.nav-count {
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: bold;
    margin-left: 4px;
}

.dropdown-badge {
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 0.6rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-left: auto;
}

/* STATUS DO PRESTADOR */
.prestador-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    font-size: 0.9rem;
    position: relative;
}

.prestador-status:hover {
    background: rgba(255,255,255,0.25);
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid white;
}

.status-indicator.online { background: #2ecc71; }
.status-indicator.busy { background: #f39c12; }
.status-indicator.away { background: #e67e22; }
.status-indicator.offline { background: #95a5a6; }

.status-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    padding: 8px;
    min-width: 150px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    margin-top: 8px;
}

.status-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.status-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s ease;
    color: #333;
    font-size: 0.9rem;
}

.status-option:hover {
    background: #f8f9fa;
}

.status-option.active {
    background: #e3f2fd;
    color: #1976d2;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-dot.online { background: #2ecc71; }
.status-dot.busy { background: #f39c12; }
.status-dot.away { background: #e67e22; }
.status-dot.offline { background: #95a5a6; }

/* RESPONSIVO */
@media (max-width: 768px) {
    .prestador-status .status-text {
        display: none;
    }
    
    .nav-count {
        display: none;
    }
}
</style>

<script>
// JAVASCRIPT ESPECÍFICO DO PRESTADOR
function toggleStatusMenu() {
    const menu = document.getElementById('status-menu');
    const overlay = document.getElementById('header-overlay');
    
    menu.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Fechar outros menus
    closeOtherMenus(['status-menu']);
}

function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    const userMenu = document.querySelector('.user-menu');
    const overlay = document.getElementById('header-overlay');
    
    dropdown.classList.toggle('active');
    userMenu.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Fechar outros menus
    closeOtherMenus(['user-dropdown']);
}

function toggleNotifications() {
    const panel = document.getElementById('notifications-panel');
    const overlay = document.getElementById('header-overlay');
    
    panel.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Carregar notificações
    carregarNotificacoes();
    
    // Fechar outros menus
    closeOtherMenus(['notifications-panel']);
}

function closeOtherMenus(except = []) {
    const menus = ['user-dropdown', 'status-menu', 'notifications-panel'];
    
    menus.forEach(menuId => {
        if (!except.includes(menuId)) {
            document.getElementById(menuId)?.classList.remove('active');
        }
    });
    
    if (!except.includes('user-menu')) {
        document.querySelector('.user-menu')?.classList.remove('active');
    }
}

function closeAllMenus() {
    closeOtherMenus();
    document.getElementById('header-overlay')?.classList.remove('active');
}

// Alterar status do prestador
document.addEventListener('DOMContentLoaded', function() {
    const statusOptions = document.querySelectorAll('.status-option');
    
    statusOptions.forEach(option => {
        option.addEventListener('click', function() {
            const status = this.dataset.status;
            alterarStatusPrestador(status);
        });
    });
});

function alterarStatusPrestador(novoStatus) {
    fetch('api/alterar-status-prestador.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: novoStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar interface
            atualizarStatusInterface(novoStatus);
            closeAllMenus();
        } else {
            alert('Erro ao alterar status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao alterar status');
    });
}

function atualizarStatusInterface(status) {
    const indicator = document.querySelector('.status-indicator');
    const statusText = document.querySelector('.status-text');
    
    // Remover classes antigas
    indicator.className = 'status-indicator';
    
    // Adicionar nova classe e texto
    switch(status) {
        case 'disponivel':
            indicator.classList.add('online');
            statusText.textContent = 'Disponível';
            break;
        case 'ocupado':
            indicator.classList.add('busy');
            statusText.textContent = 'Ocupado';
            break;
        case 'ausente':
            indicator.classList.add('away');
            statusText.textContent = 'Ausente';
            break;
        case 'offline':
            indicator.classList.add('offline');
            statusText.textContent = 'Offline';
            break;
    }
    
    // Atualizar opção ativa no menu
    document.querySelectorAll('.status-option').forEach(opt => {
        opt.classList.remove('active');
        if (opt.dataset.status === status) {
            opt.classList.add('active');
        }
    });
}

// Carregar notificações dinamicamente
function carregarNotificacoes() {
    fetch('api/notifications-prestador.php')
    .then(response => response.json())
    .then(data => {
        const lista = document.getElementById('notifications-list');
        
        if (data.success && data.notifications.length > 0) {
            lista.innerHTML = data.notifications.map(notif => `
                <div class="notification-item ${notif.lida ? '' : 'unread'}" data-id="${notif.id}">
                    <div class="notification-icon">
                        <i class="${notif.icone}"></i>
                    </div>
                    <div class="notification-content">
                        <p>${notif.titulo}</p>
                        <span class="notification-time">${notif.tempo}</span>
                    </div>
                </div>
            `).join('');
        } else {
            lista.innerHTML = '<div class="no-notifications">Nenhuma notificação</div>';
        }
    })
    .catch(error => {
        console.error('Erro ao carregar notificações:', error);
    });
}

function marcarTodasLidas() {
    fetch('api/mark-all-notifications-read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar interface
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Remover badge
            const badge = document.querySelector('.notification-badge');
            if (badge) badge.remove();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}

// Fechar menus ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu-container') && 
        !e.target.closest('.prestador-status')) {
        closeAllMenus();
    }
});

// Fechar menus ao pressionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllMenus();
    }
});

// Atualizar contadores periodicamente
setInterval(function() {
    fetch('api/prestador-counters.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar badges
            const orcamentosBadge = document.querySelector('.nav-link[href="orcamentos-recebidos.php"] .nav-badge');
            const chatBadge = document.querySelector('.nav-link[href="chat.php"] .nav-badge');
            const servicosCount = document.querySelector('.nav-link[href="meus-servicos.php"] .nav-count');
            
            if (data.orcamentos_pendentes > 0) {
                if (orcamentosBadge) {
                    orcamentosBadge.textContent = data.orcamentos_pendentes;
                } else {
                    // Criar badge se não existir
                    const link = document.querySelector('.nav-link[href="orcamentos-recebidos.php"]');
                    const badge = document.createElement('span');
                    badge.className = 'nav-badge';
                    badge.textContent = data.orcamentos_pendentes;
                    link.appendChild(badge);
                }
            } else if (orcamentosBadge) {
                orcamentosBadge.remove();
            }
            
            if (data.mensagens_nao_lidas > 0) {
                if (chatBadge) {
                    chatBadge.textContent = data.mensagens_nao_lidas;
                } else {
                    const link = document.querySelector('.nav-link[href="chat.php"]');
                    const badge = document.createElement('span');
                    badge.className = 'nav-badge';
                    badge.textContent = data.mensagens_nao_lidas;
                    link.appendChild(badge);
                }
            } else if (chatBadge) {
                chatBadge.remove();
            }
            
            if (servicosCount) {
                servicosCount.textContent = data.servicos_ativos;
            }
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar contadores:', error);
    });
}, 30000); // Atualizar a cada 30 segundos
</script>

