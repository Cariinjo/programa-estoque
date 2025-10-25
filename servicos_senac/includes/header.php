<?php
// --- BLOCO PHP CORRIGIDO (com base no seu resumo) ---

// --- NOVO: Inclui o config para ter acesso ao $pdo e às funções de sessão
// (Ajuste o caminho se o seu header.php não estiver na raiz do site)
require_once 'includes/config.php'; 

$user_logged = isLoggedIn();
$user_name = $user_logged ? $_SESSION['user_name'] : '';
$user_type = $user_logged ? $_SESSION['user_type'] : '';
$user_avatar = ''; // Inicia a variável

// --- NOVO: Variáveis para Notificações ---
$unread_count = 0;
$notifications_list = [];

// Lógica do Avatar (Seguindo o Resumo)
if ($user_logged && !empty($_SESSION['user_id'])) {
    
    // 1. Pegar o user_id da sessão
    $user_id = $_SESSION['user_id'];
    
    // 2. Construir o caminho do avatar
    $potential_avatar_path = "uploads/perfis/" . htmlspecialchars($user_id) . ".png";
    
    // 3. Verificar se o arquivo realmente existe
    if (file_exists($potential_avatar_path)) {
        $user_avatar = $potential_avatar_path;
    } else {
        // Se o arquivo não existe, usa o default
        $user_avatar = 'uploads/perfis/default.png';
    }

    // --- NOVO: Lógica para buscar notificações ---
    try {
        // 1. Contar notificações não lidas
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE id_usuario_destino = ? AND lida = 0");
        $stmt_count->execute([$user_id]);
        $unread_count = $stmt_count->fetchColumn();
        
        // 2. Buscar as 5 últimas notificações
        $stmt_list = $pdo->prepare("
            SELECT tipo_notificacao, mensagem, link_acao, lida, data_criacao 
            FROM notificacoes 
            WHERE id_usuario_destino = ? 
            ORDER BY data_criacao DESC 
            LIMIT 5
        ");
        $stmt_list->execute([$user_id]);
        $notifications_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        // Em caso de erro de DB, não quebra a página
        error_log("Erro ao buscar notificacoes: " . $e->getMessage());
    }
    // --- FIM DA LÓGICA DE NOTIFICAÇÕES ---

} else {
    // 4. Usuário não logado, usa o default
    $user_avatar = 'uploads/perfis/default.png';
}

// Gerar iniciais do nome para avatar
$user_initials = '';
if ($user_name) {
    $names = explode(' ', $user_name);
    $user_initials = strtoupper(substr($names[0], 0, 1));
    if (count($names) > 1) {
        $user_initials .= strtoupper(substr($names[count($names) - 1], 0, 1));
    }
}

// --- NOVO: Função para formatar o tempo ---
function time_ago($datetime) {
    if (!$datetime) {
        return 'agora mesmo';
    }
    try {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        $dias = $diff->d;
        $horas = $diff->h;
        $minutos = $diff->i;
        
        if ($dias > 0) return $dias . ' dia' . ($dias > 1 ? 's' : '') . ' atrás';
        if ($horas > 0) return $horas . ' hora' . ($horas > 1 ? 's' : '') . ' atrás';
        if ($minutos > 0) return $minutos . ' min' . ($minutos > 1 ? 's' : '') . ' atrás';
        return 'agora mesmo';

    } catch (Exception $e) {
        return 'agora mesmo';
    }
}
// --- FIM DO BLOCO PHP CORRIGIDO ---
?>

<header class="modern-header">
    <div class="header-container">
        <div class="header-brand">
            <a href="index.php" class="brand-link">
                <div class="brand-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">SENAC</span>
                    <span class="brand-subtitle">Minas Gerais</span>
                </div>
            </a>
        </div>

        <nav class="header-nav" id="header-nav">
            <div class="nav-links">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Início</span>
                </a>
                <a href="servicos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'servicos.php' ? 'active' : '' ?>">
                    <i class="fas fa-cogs"></i>
                    <span>Serviços</span>
                </a>
                <a href="profissionais.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profissionais.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Profissionais</span>
                </a>
                <a href="como-funciona.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'como-funciona.php' ? 'active' : '' ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>Como Funciona</span>
                </a>
                <a href="contato.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contato.php' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Contato</span>
                </a>
            </div>
        </nav>

        <div class="header-actions">
            <?php if ($user_logged): ?>
                <div class="user-menu-container">
                    
                    <div class="notification-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge" id="notification-badge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-menu" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            <?php 
                            // Esta lógica usa a variável $user_avatar corrigida.
                            // Prioriza a imagem; se não houver, usa iniciais.
                            ?>
                            <?php if ($user_avatar && $user_avatar !== 'uploads/perfis/default.png'): ?>
                                <img src="<?= htmlspecialchars($user_avatar) ?>?v=<?= time() ?>" alt="Avatar"> <?php else: ?>
                                <span class="avatar-initials"><?= $user_initials ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                            <span class="user-type">
                                <?php
                                switch ($user_type) {
                                    case 'prestador':
                                        echo 'Prestador';
                                        break;
                                    case 'admin':
                                        echo 'Administrador';
                                        break;
                                    default:
                                        echo 'Cliente';
                                }
                                ?>
                            </span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </div>

                    <div class="user-dropdown" id="user-dropdown">
                        <?php if ($user_type === 'admin'): ?>
                            <a href="admin/dashboard.php" class="dropdown-item">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Painel Admin</span>
                            </a>
                        <?php elseif ($user_type === 'prestador'): ?>
                            <a href="dashboard-prestador.php" class="dropdown-item">
                                <i class="fas fa-briefcase"></i>
                                <span>Meu Dashboard</span>
                            </a>
                            <a href="meus-servicos.php" class="dropdown-item">
                                <i class="fas fa-cogs"></i>
                                <span>Meus Serviços</span>
                            </a>
                            <a href="orcamentos.php" class="dropdown-item">
                                <i class="fas fa-file-invoice"></i>
                                <span>Orçamentos</span>
                            </a>
                        <?php else: // Cliente ?>
                            <a href="dashboard.php" class="dropdown-item">
                                <i class="fas fa-user"></i>
                                <span>Meu Perfil</span>
                            </a>
                            <a href="meus-pedidos.php" class="dropdown-item">
                                <i class="fas fa-shopping-bag"></i>
                                <span>Meus Pedidos</span>
                            </a>
                        <?php endif; ?>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="configuracoes.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Configurações</span>
                        </a>
                        <a href="ajuda.php" class="dropdown-item">
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

                <div class="notifications-panel" id="notifications-panel">
                    <div class="notifications-header">
                        <h3>Notificações</h3>
                        <button class="mark-all-read" id="mark-all-read-btn">Marcar todas como lidas</button>
                    </div>
                    
                    <div class="notifications-list" id="notifications-list">
                        
                        <?php if (empty($notifications_list)): ?>
                            <div class="notification-item" style="justify-content: center; color: #777;">
                                Nenhuma notificação encontrada.
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications_list as $notification): ?>
                                <?php
                                // Lógica para definir o ícone com base no 'tipo_notificacao'
                                $icon = 'fas fa-bell'; // Padrão
                                if ($notification['tipo_notificacao'] == 'orcamento_novo') {
                                    $icon = 'fas fa-file-invoice';
                                } elseif ($notification['tipo_notificacao'] == 'nova_avaliacao') {
                                    $icon = 'fas fa-star';
                                } elseif ($notification['tipo_notificacao'] == 'nova_mensagem') {
                                    $icon = 'fas fa-message';
                                }
                                ?>

                                <a href="<?= htmlspecialchars($notification['link_acao'] ?? '#') ?>" 
                                   class="notification-item <?= !$notification['lida'] ? 'unread' : '' ?>">
                                    
                                    <div class="notification-icon">
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p><?= htmlspecialchars($notification['mensagem']) ?></p> 
                                        <span class="notification-time"><?= time_ago($notification['data_criacao']) ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>
                </div>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Entrar</span>
                    </a>
                    <div class="register-dropdown">
                        <button class="btn btn-primary register-btn" onclick="toggleRegisterMenu()">
                            <i class="fas fa-user-plus"></i>
                            <span>Cadastrar</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="register-menu" id="register-menu">
                            <a href="cadastro.php?tipo=cliente" class="register-option">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Sou Cliente</strong>
                                    <small>Quero contratar serviços</small>
                                </div>
                            </a>
                            <a href="cadastro.php?tipo=prestador" class="register-option">
                                <i class="fas fa-briefcase"></i>
                                <div>
                                    <strong>Sou Prestador</strong>
                                    <small>Quero oferecer serviços</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <div class="header-overlay" id="header-overlay" onclick="closeAllMenus()"></div>
</header>

<style>
/* ... (TODO O SEU CSS VEM AQUI - Nenhuma alteração necessária) ... */

/* HEADER MODERNO */
.modern-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    height: 70px;
}

/* BRAND */
.header-brand .brand-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: white;
    transition: transform 0.3s ease;
}

.header-brand .brand-link:hover {
    transform: scale(1.05);
}

.brand-logo {
    width: 45px;
    height: 45px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1;
}

.brand-subtitle {
    font-size: 0.8rem;
    opacity: 0.9;
    font-weight: 400;
}

/* NAVEGAÇÃO */
.header-nav {
    display: flex;
    align-items: center;
}

.nav-links {
    display: flex;
    gap: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    text-decoration: none;
    color: rgba(255,255,255,0.9);
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    background: rgba(255,255,255,0.15);
    color: white;
    transform: translateY(-2px);
}

.nav-link.active {
    background: rgba(255,255,255,0.2);
    color: white;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 3px;
    background: white;
    border-radius: 2px;
}

/* AÇÕES DO USUÁRIO */
.header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* BOTÕES DE AUTENTICAÇÃO */
.auth-buttons {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-outline {
    background: transparent;
    color: white;
    border: 2px solid rgba(255,255,255,0.3);
}

.btn-outline:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.5);
}

.btn-primary {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid transparent;
}

.btn-primary:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

/* DROPDOWN DE CADASTRO */
.register-dropdown {
    position: relative;
}

.register-btn {
    background: rgba(255,255,255,0.2) !important;
}

.register-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    padding: 8px;
    min-width: 250px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    margin-top: 8px; /* --- NOVO (ou ajuste) --- Adiciona espaço */
}

.register-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.register-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: background 0.3s ease;
}

.register-option:hover {
    background: #f8f9fa;
}

.register-option i {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.register-option strong {
    display: block;
    margin-bottom: 2px;
}

.register-option small {
    color: #666;
    font-size: 0.8rem;
}

/* MENU DO USUÁRIO */
.user-menu-container {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
}

.notification-btn {
    position: relative;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.notification-btn:hover {
    background: rgba(255,255,255,0.25);
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4757;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.user-menu:hover {
    background: rgba(255,255,255,0.25);
}

/* --- ESTILO DO AVATAR CORRIGIDO (conforme seu resumo) --- */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%; /* <-- CORREÇÃO: 50% para ser redondo */
    overflow: hidden;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255, 255, 255, 0.3); /* --- NOVO (Opcional): Borda bonita */
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover; /* <-- CORREÇÃO: como pedido no resumo */
}
/* --- FIM DA CORREÇÃO DO AVATAR --- */

.avatar-initials {
    color: white;
    font-weight: bold;
    font-size: 0.9rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    color: white;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
    line-height: 1;
}

.user-type {
    font-size: 0.75rem;
    opacity: 0.8;
}

.dropdown-icon {
    color: white;
    font-size: 0.8rem;
    transition: transform 0.3s ease;
}

.user-menu.active .dropdown-icon {
    transform: rotate(180deg);
}

/* DROPDOWN DO USUÁRIO */
.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    padding: 8px;
    min-width: 220px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    margin-top: 8px;
}

.user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: background 0.3s ease;
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

.dropdown-item.logout {
    color: #e74c3c;
}

.dropdown-item.logout:hover {
    background: #ffeaea;
}

.dropdown-divider {
    height: 1px;
    background: #eee;
    margin: 8px 0;
}

/* PAINEL DE NOTIFICAÇÕES */
.notifications-panel {
    position: absolute;
    top: 100%;
    right: 60px; /* Ajustado para dar espaço ao menu do usuário */
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    width: 320px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    margin-top: 8px;
}

.notifications-panel.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.notifications-header {
    padding: 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notifications-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: #333;
}

.mark-all-read {
    background: none;
    border: none;
    color: #667eea;
    font-size: 0.8rem;
    cursor: pointer;
}
/* --- NOVO: Estilo do botão desabilitado --- */
.mark-all-read:disabled {
    color: #999;
    cursor: default;
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.3s ease;
    text-decoration: none; /* --- NOVO: Remove sublinhado do link --- */
}

/* --- NOVO: Garante que o item não tenha :hover estranho se for só texto --- */
.notification-item:not(a) {
    cursor: default;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #f0f7ff;
}

.notification-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-content p {
    margin: 0 0 4px 0;
    font-size: 0.9rem;
    color: #333;
    line-height: 1.3; /* --- NOVO (Opcional): Melhora leitura --- */
}

.notification-content {
     /* --- NOVO: Evita que texto vaze --- */
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: normal;
}


.notification-time {
    font-size: 0.8rem;
    color: #666;
}

.notifications-footer {
    padding: 12px 16px;
    text-align: center;
    border-top: 1px solid #eee;
}

.notifications-footer a {
    color: #667eea;
    text-decoration: none;
    font-size: 0.9rem;
}

/* MOBILE MENU */
.mobile-menu-btn {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
}

.mobile-menu-btn span {
    width: 25px;
    height: 3px;
    background: white;
    border-radius: 2px;
    transition: all 0.3s ease;
}

/* OVERLAY */
.header-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.3);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 999;
}

.header-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* RESPONSIVO */
@media (max-width: 768px) {
    .header-container {
        padding: 0 16px;
        height: 60px;
    }
    
    .header-nav {
        display: none; /* --- NOVO: Adicionar lógica JS para 'toggleMobileMenu' para mostrar isso --- */
    }
    
    .mobile-menu-btn {
        display: flex;
    }
    
    .brand-text {
        display: none;
    }
    
    .user-info {
        display: none;
    }
    
    .auth-buttons .btn span {
        display: none;
    }

    .auth-buttons .btn {
        padding: 10px 12px; /* --- NOVO: Ajuste para mobile --- */
    }
    
    .notifications-panel {
        right: 16px;
        width: calc(100vw - 32px);
        max-width: 320px;
    }
}

@media (max-width: 480px) {
    .header-container {
        padding: 0 12px;
    }
    
    .header-actions {
        gap: 8px;
    }
}
</style>

<script>
// JAVASCRIPT PARA INTERAÇÕES DO HEADER
function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    const userMenu = document.querySelector('.user-menu');
    const overlay = document.getElementById('header-overlay');
    
    // Fecha outros menus abertos
    document.getElementById('register-menu')?.classList.remove('active');
    document.getElementById('notifications-panel')?.classList.remove('active');

    // Abre/fecha o menu do usuário
    dropdown.classList.toggle('active');
    userMenu.classList.toggle('active');
    overlay.classList.toggle('active');
}

function toggleRegisterMenu() {
    const menu = document.getElementById('register-menu');
    const overlay = document.getElementById('header-overlay');
    
    // Fecha outros menus abertos
    document.getElementById('user-dropdown')?.classList.remove('active');
    document.querySelector('.user-menu')?.classList.remove('active');
    document.getElementById('notifications-panel')?.classList.remove('active');

    // Abre/fecha o menu de registro
    menu.classList.toggle('active');
    overlay.classList.toggle('active');
}

function toggleNotifications() {
    const panel = document.getElementById('notifications-panel');
    const overlay = document.getElementById('header-overlay');
    
    // Fecha outros menus abertos
    document.getElementById('user-dropdown')?.classList.remove('active');
    document.querySelector('.user-menu')?.classList.remove('active');
    document.getElementById('register-menu')?.classList.remove('active');

    // Abre/fecha o painel de notificações
    panel.classList.toggle('active');
    overlay.classList.toggle('active');
}

function toggleMobileMenu() {
    // --- NOVO: Implementação básica do menu mobile ---
    const nav = document.getElementById('header-nav');
    const overlay = document.getElementById('header-overlay');
    nav.classList.toggle('active'); // Você precisará de CSS para .header-nav.active
    overlay.classList.toggle('active');
    console.log('Mobile menu clicked');
}

function closeAllMenus() {
    document.getElementById('user-dropdown')?.classList.remove('active');
    document.querySelector('.user-menu')?.classList.remove('active');
    document.getElementById('register-menu')?.classList.remove('active');
    document.getElementById('notifications-panel')?.classList.remove('active');
    document.getElementById('header-overlay')?.classList.remove('active');
    
    // --- NOVO: Fecha menu mobile também ---
    document.getElementById('header-nav')?.classList.remove('active');
}

// Fechar menus ao clicar fora
document.addEventListener('click', function(e) {
    // --- NOVO: Lógica de clique fora melhorada ---
    if (e.target.id === 'header-overlay') {
        closeAllMenus();
    }
});

// Fechar menus ao pressionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllMenus();
    }
});

// --- NOVO: LÓGICA AJAX PARA NOTIFICAÇÕES ---
document.addEventListener('DOMContentLoaded', function() {
    
    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            
            // 1. Chamar o arquivo PHP em segundo plano
            // Ajuste o caminho se necessário
            fetch('ajax_mark_all_read.php', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro de rede ou servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // 2. Atualizar a interface (UI)
                    console.log('Notificações marcadas como lidas.');
                    
                    // Esconder o contador
                    const badge = document.getElementById('notification-badge');
                    if (badge) {
                        badge.style.display = 'none';
                    }
                    
                    // Remover o fundo azul de todos os itens
                    const listItems = document.querySelectorAll('#notifications-list .notification-item.unread');
                    listItems.forEach(item => {
                        item.classList.remove('unread');
                    });
                    
                    // Desabilitar o botão
                    markAllReadBtn.textContent = 'Todas lidas';
                    markAllReadBtn.disabled = true;

                } else {
                    console.error('Falha ao marcar como lidas:', data.message);
                }
            })
            .catch(error => {
                console.error('Erro na requisição AJAX:', error);
            });
        });
    }
});
</script>