<?php
// Verificar se o usuário está logado e obter informações
$user_logged = isLoggedIn();
$user_name = $user_logged ? $_SESSION['user_name'] : '';
$user_type = $user_logged ? $_SESSION['user_type'] : '';
$user_avatar = $user_logged ? (isset($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : '') : '';

// Gerar iniciais do nome para avatar
$user_initials = '';
if ($user_name) {
    $names = explode(' ', $user_name);
    $user_initials = strtoupper(substr($names[0], 0, 1));
    if (count($names) > 1) {
        $user_initials .= strtoupper(substr($names[count($names) - 1], 0, 1));
    }
}
?>

<header class="modern-header">
    <div class="header-container">
        <!-- Logo e Brand -->
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

        <!-- Navegação Principal -->
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

        <!-- Ações do Usuário -->
        <div class="header-actions">
            <?php if ($user_logged): ?>
                <!-- Usuário Logado -->
                <div class="user-menu-container">
                    <!-- Notificações -->
                    <div class="notification-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
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

                    <!-- Dropdown do Usuário -->
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
                        <?php else: ?>
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

                <!-- Painel de Notificações -->
                <div class="notifications-panel" id="notifications-panel">
                    <div class="notifications-header">
                        <h3>Notificações</h3>
                        <button class="mark-all-read">Marcar todas como lidas</button>
                    </div>
                    <div class="notifications-list">
                        <div class="notification-item unread">
                            <div class="notification-icon">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="notification-content">
                                <p>Novo orçamento recebido</p>
                                <span class="notification-time">2 min atrás</span>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="notification-content">
                                <p>Nova avaliação recebida</p>
                                <span class="notification-time">1 hora atrás</span>
                            </div>
                        </div>
                        <div class="notification-item">
                            <div class="notification-icon">
                                <i class="fas fa-message"></i>
                            </div>
                            <div class="notification-content">
                                <p>Nova mensagem no chat</p>
                                <span class="notification-time">3 horas atrás</span>
                            </div>
                        </div>
                    </div>
                    <div class="notifications-footer">
                        <a href="notificacoes.php">Ver todas as notificações</a>
                    </div>
                </div>

            <?php else: ?>
                <!-- Usuário Não Logado -->
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

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    overflow: hidden;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

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
    right: 60px;
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
        display: none;
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
    
    dropdown.classList.toggle('active');
    userMenu.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Fechar outros menus
    document.getElementById('register-menu')?.classList.remove('active');
    document.getElementById('notifications-panel')?.classList.remove('active');
}

function toggleRegisterMenu() {
    const menu = document.getElementById('register-menu');
    const overlay = document.getElementById('header-overlay');
    
    menu.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Fechar outros menus
    document.getElementById('user-dropdown')?.classList.remove('active');
    document.querySelector('.user-menu')?.classList.remove('active');
    document.getElementById('notifications-panel')?.classList.remove('active');
}

function toggleNotifications() {
    const panel = document.getElementById('notifications-panel');
    const overlay = document.getElementById('header-overlay');
    
    panel.classList.toggle('active');
    overlay.classList.toggle('active');
    
    // Fechar outros menus
    document.getElementById('user-dropdown')?.classList.remove('active');
    document.querySelector('.user-menu')?.classList.remove('active');
    document.getElementById('register-menu')?.classList.remove('active');
}

function toggleMobileMenu() {
    // Implementar menu mobile
    console.log('Mobile menu clicked');
}

function closeAllMenus() {
    document.getElementById('user-dropdown')?.classList.remove('active');
    document.querySelector('.user-menu')?.classList.remove('active');
    document.getElementById('register-menu')?.classList.remove('active');
    document.getElementById('notifications-panel')?.classList.remove('active');
    document.getElementById('header-overlay')?.classList.remove('active');
}

// Fechar menus ao clicar fora
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu-container') && 
        !e.target.closest('.register-dropdown')) {
        closeAllMenus();
    }
});

// Fechar menus ao pressionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllMenus();
    }
});
</script>

