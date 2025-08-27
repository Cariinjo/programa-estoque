<?php
// Verificar se é prestador logado
if (!isLoggedIn() || $_SESSION['user_type'] !== 'prestador') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Buscar dados do prestador
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome, u.foto_perfil, u.status_usuario
        FROM profissionais p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE u.id_usuario = ?
    ");
    $stmt->execute([$user_id]);
    $prestador = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prestador) {
        header('Location: login.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Erro ao buscar dados do prestador: " . $e->getMessage());
}
?>

<header class="prestador-header">
    <div class="header-container">
        <!-- Logo e Branding -->
        <div class="header-brand">
            <a href="dashboard-prestador.php" class="brand-link">
                <div class="brand-logo">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="brand-text">
                    <span class="brand-name">SENAC</span>
                    <span class="brand-subtitle">Prestador</span>
                </div>
            </a>
        </div>

        <!-- Navegação Principal -->
        <nav class="header-nav">
            <div class="nav-items">
                <a href="orcamentos-recebidos.php" class="nav-item" data-tooltip="Gerenciar orçamentos recebidos">
                    <i class="fas fa-inbox"></i>
                    <span class="nav-text">Orçamentos</span>
                    <span class="nav-badge" id="orcamentos-count">0</span>
                </a>
                
                <a href="meus-servicos.php" class="nav-item" data-tooltip="Gerenciar meus serviços">
                    <i class="fas fa-cogs"></i>
                    <span class="nav-text">Meus Serviços</span>
                </a>
                
                <a href="historico-servicos.php" class="nav-item" data-tooltip="Histórico de serviços realizados">
                    <i class="fas fa-history"></i>
                    <span class="nav-text">Histórico</span>
                </a>
                
                <a href="suporte.php" class="nav-item" data-tooltip="Central de suporte">
                    <i class="fas fa-headset"></i>
                    <span class="nav-text">Suporte</span>
                </a>
            </div>
        </nav>

        <!-- Área do Usuário -->
        <div class="header-user">
            <!-- Status do Prestador -->
            <div class="status-selector">
                <button class="status-btn" id="statusBtn" data-tooltip="Alterar status de disponibilidade">
                    <i class="fas fa-circle status-indicator" id="statusIndicator"></i>
                    <span class="status-text" id="statusText">Disponível</span>
                    <i class="fas fa-chevron-down status-arrow"></i>
                </button>
                <div class="status-dropdown" id="statusDropdown">
                    <div class="status-option" data-status="disponivel">
                        <i class="fas fa-circle text-success"></i>
                        <span>Disponível</span>
                    </div>
                    <div class="status-option" data-status="ocupado">
                        <i class="fas fa-circle text-warning"></i>
                        <span>Ocupado</span>
                    </div>
                    <div class="status-option" data-status="indisponivel">
                        <i class="fas fa-circle text-danger"></i>
                        <span>Indisponível</span>
                    </div>
                </div>
            </div>

            <!-- Notificações -->
            <div class="notifications">
                <button class="notification-btn" id="notificationBtn" data-tooltip="Notificações">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notification-count">0</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h4>Notificações</h4>
                        <button class="mark-all-read" id="markAllRead">Marcar todas como lidas</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Nenhuma notificação</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menu do Usuário -->
            <div class="user-menu">
                <button class="user-btn" id="userBtn" data-tooltip="Menu do usuário">
                    <div class="user-avatar">
                        <?php if (!empty($prestador['foto_perfil']) && file_exists($prestador['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($prestador['foto_perfil']) ?>" alt="Avatar">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                        <span class="user-role">Prestador</span>
                    </div>
                    <i class="fas fa-chevron-down user-arrow"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="editar-perfil-prestador.php" class="dropdown-item">
                        <i class="fas fa-user-edit"></i>
                        <span>Editar Perfil</span>
                    </a>
                    <a href="configuracoes.php" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Sair</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Menu Mobile -->
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <!-- Menu Mobile Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay">
        <div class="mobile-menu-content">
            <div class="mobile-user-info">
                <div class="mobile-avatar">
                    <?php if (!empty($prestador['foto_perfil']) && file_exists($prestador['foto_perfil'])): ?>
                        <img src="<?= htmlspecialchars($prestador['foto_perfil']) ?>" alt="Avatar">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="mobile-user-details">
                    <span class="mobile-user-name"><?= htmlspecialchars($user_name) ?></span>
                    <span class="mobile-user-role">Prestador de Serviços</span>
                </div>
            </div>
            
            <nav class="mobile-nav">
                <a href="orcamentos-recebidos.php" class="mobile-nav-item">
                    <i class="fas fa-inbox"></i>
                    <span>Orçamentos Recebidos</span>
                    <span class="mobile-badge" id="mobile-orcamentos-count">0</span>
                </a>
                <a href="meus-servicos.php" class="mobile-nav-item">
                    <i class="fas fa-cogs"></i>
                    <span>Meus Serviços</span>
                </a>
                <a href="historico-servicos.php" class="mobile-nav-item">
                    <i class="fas fa-history"></i>
                    <span>Histórico</span>
                </a>
                <a href="suporte.php" class="mobile-nav-item">
                    <i class="fas fa-headset"></i>
                    <span>Suporte</span>
                </a>
                <div class="mobile-nav-divider"></div>
                <a href="editar-perfil-prestador.php" class="mobile-nav-item">
                    <i class="fas fa-user-edit"></i>
                    <span>Editar Perfil</span>
                </a>
                <a href="configuracoes.php" class="mobile-nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
                <a href="logout.php" class="mobile-nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </nav>
        </div>
    </div>
</header>

<style>
/* Reset e Base */
.prestador-header * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Header Principal */
.prestador-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.header-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 70px;
}

/* Brand */
.header-brand {
    flex-shrink: 0;
}

.brand-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: white;
    transition: all 0.3s ease;
}

.brand-link:hover {
    transform: translateY(-1px);
}

.brand-logo {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
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
    opacity: 0.8;
    font-weight: 400;
}

/* Navegação */
.header-nav {
    flex: 1;
    display: flex;
    justify-content: center;
    max-width: 600px;
    margin: 0 2rem;
}

.nav-items {
    display: flex;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem;
    border-radius: 16px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.nav-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: white;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 500;
    font-size: 0.9rem;
    white-space: nowrap;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.nav-item.active {
    background: rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.nav-item i {
    font-size: 1rem;
    width: 18px;
    text-align: center;
}

.nav-text {
    display: block;
}

.nav-badge {
    background: #ff4757;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1;
}

/* Área do Usuário */
.header-user {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

/* Status Selector */
.status-selector {
    position: relative;
}

.status-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    backdrop-filter: blur(10px);
}

.status-btn:hover {
    background: rgba(255, 255, 255, 0.15);
}

.status-indicator {
    font-size: 0.6rem;
}

.status-arrow {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.status-btn.active .status-arrow {
    transform: rotate(180deg);
}

.status-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    padding: 0.5rem;
    min-width: 150px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
}

.status-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.status-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
    color: #333;
    font-size: 0.9rem;
}

.status-option:hover {
    background: #f8f9fa;
}

.text-success { color: #28a745; }
.text-warning { color: #ffc107; }
.text-danger { color: #dc3545; }

/* Notificações */
.notifications {
    position: relative;
}

.notification-btn {
    position: relative;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
}

.notification-btn:hover {
    background: rgba(255, 255, 255, 0.15);
}

.notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ff4757;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.4rem;
    border-radius: 8px;
    min-width: 18px;
    text-align: center;
    line-height: 1;
}

.notification-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    width: 320px;
    max-height: 400px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
    overflow: hidden;
}

.notification-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.notification-header {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h4 {
    color: #333;
    font-size: 1rem;
    margin: 0;
}

.mark-all-read {
    background: none;
    border: none;
    color: #667eea;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    transition: background 0.2s ease;
}

.mark-all-read:hover {
    background: #f8f9fa;
}

.notification-list {
    max-height: 300px;
    overflow-y: auto;
}

.notification-empty {
    padding: 2rem;
    text-align: center;
    color: #999;
}

.notification-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Menu do Usuário */
.user-menu {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.user-btn:hover {
    background: rgba(255, 255, 255, 0.15);
}

.user-avatar {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
    line-height: 1.2;
}

.user-role {
    font-size: 0.75rem;
    opacity: 0.8;
}

.user-arrow {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.user-btn.active .user-arrow {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    padding: 0.5rem;
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1001;
}

.user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    transition: background 0.2s ease;
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: #f8f9fa;
}

.dropdown-item.logout {
    color: #dc3545;
}

.dropdown-item.logout:hover {
    background: #fff5f5;
}

.dropdown-divider {
    height: 1px;
    background: #eee;
    margin: 0.5rem 0;
}

/* Menu Mobile */
.mobile-menu-btn {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0.75rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mobile-menu-btn span {
    width: 20px;
    height: 2px;
    background: white;
    border-radius: 1px;
    transition: all 0.3s ease;
}

.mobile-menu-btn.active span:nth-child(1) {
    transform: rotate(45deg) translate(6px, 6px);
}

.mobile-menu-btn.active span:nth-child(2) {
    opacity: 0;
}

.mobile-menu-btn.active span:nth-child(3) {
    transform: rotate(-45deg) translate(6px, -6px);
}

.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1002;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-menu-overlay.show {
    opacity: 1;
    visibility: visible;
}

.mobile-menu-content {
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: white;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    overflow-y: auto;
}

.mobile-menu-overlay.show .mobile-menu-content {
    transform: translateX(0);
}

.mobile-user-info {
    padding: 2rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.mobile-avatar {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.mobile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mobile-user-details {
    display: flex;
    flex-direction: column;
}

.mobile-user-name {
    font-weight: 600;
    font-size: 1.1rem;
    line-height: 1.2;
}

.mobile-user-role {
    font-size: 0.85rem;
    opacity: 0.8;
}

.mobile-nav {
    padding: 1rem 0;
}

.mobile-nav-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    color: #333;
    text-decoration: none;
    transition: background 0.2s ease;
    position: relative;
}

.mobile-nav-item:hover {
    background: #f8f9fa;
}

.mobile-nav-item.logout {
    color: #dc3545;
}

.mobile-nav-item i {
    width: 20px;
    text-align: center;
}

.mobile-badge {
    position: absolute;
    right: 1.5rem;
    background: #ff4757;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

.mobile-nav-divider {
    height: 1px;
    background: #eee;
    margin: 0.5rem 1.5rem;
}

/* Tooltips */
[data-tooltip] {
    position: relative;
}

[data-tooltip]:hover::before {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 0.5rem);
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    white-space: nowrap;
    z-index: 1003;
    opacity: 0;
    animation: tooltipFadeIn 0.3s ease forwards;
}

@keyframes tooltipFadeIn {
    to {
        opacity: 1;
    }
}

/* Responsividade */
@media (max-width: 1024px) {
    .nav-text {
        display: none;
    }
    
    .nav-item {
        padding: 0.75rem;
    }
    
    .user-info {
        display: none;
    }
}

@media (max-width: 768px) {
    .header-container {
        padding: 0 1rem;
    }
    
    .header-nav,
    .status-selector,
    .notifications,
    .user-menu {
        display: none;
    }
    
    .mobile-menu-btn {
        display: flex;
    }
    
    .brand-text {
        display: none;
    }
}

@media (max-width: 480px) {
    .header-container {
        height: 60px;
        padding: 0 0.75rem;
    }
    
    .brand-logo {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    
    .mobile-menu-content {
        width: 280px;
    }
}

/* Animações */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.nav-item,
.status-btn,
.notification-btn,
.user-btn {
    animation: slideIn 0.3s ease;
}

/* Estados de carregamento */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos
    const statusBtn = document.getElementById('statusBtn');
    const statusDropdown = document.getElementById('statusDropdown');
    const statusIndicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    const userBtn = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
    
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    
    // Status do prestador
    if (statusBtn && statusDropdown) {
        statusBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            statusDropdown.classList.toggle('show');
            statusBtn.classList.toggle('active');
            
            // Fechar outros dropdowns
            notificationDropdown?.classList.remove('show');
            userDropdown?.classList.remove('show');
            notificationBtn?.classList.remove('active');
            userBtn?.classList.remove('active');
        });
        
        // Opções de status
        document.querySelectorAll('.status-option').forEach(option => {
            option.addEventListener('click', function() {
                const status = this.dataset.status;
                const statusName = this.querySelector('span').textContent;
                const statusColor = this.querySelector('i').className;
                
                // Atualizar UI
                statusText.textContent = statusName;
                statusIndicator.className = statusColor;
                
                // Fechar dropdown
                statusDropdown.classList.remove('show');
                statusBtn.classList.remove('active');
                
                // Enviar para API
                alterarStatus(status);
            });
        });
    }
    
    // Notificações
    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            notificationBtn.classList.toggle('active');
            
            // Fechar outros dropdowns
            statusDropdown?.classList.remove('show');
            userDropdown?.classList.remove('show');
            statusBtn?.classList.remove('active');
            userBtn?.classList.remove('active');
            
            // Carregar notificações
            if (notificationDropdown.classList.contains('show')) {
                carregarNotificacoes();
            }
        });
    }
    
    // Menu do usuário
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
            userBtn.classList.toggle('active');
            
            // Fechar outros dropdowns
            statusDropdown?.classList.remove('show');
            notificationDropdown?.classList.remove('show');
            statusBtn?.classList.remove('active');
            notificationBtn?.classList.remove('active');
        });
    }
    
    // Menu mobile
    if (mobileMenuBtn && mobileMenuOverlay) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenuOverlay.classList.toggle('show');
            mobileMenuBtn.classList.toggle('active');
        });
        
        mobileMenuOverlay.addEventListener('click', function(e) {
            if (e.target === mobileMenuOverlay) {
                mobileMenuOverlay.classList.remove('show');
                mobileMenuBtn.classList.remove('active');
            }
        });
    }
    
    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function() {
        statusDropdown?.classList.remove('show');
        notificationDropdown?.classList.remove('show');
        userDropdown?.classList.remove('show');
        statusBtn?.classList.remove('active');
        notificationBtn?.classList.remove('active');
        userBtn?.classList.remove('active');
    });
    
    // Marcar página ativa
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-item, .mobile-nav-item').forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
        }
    });
    
    // Carregar contadores iniciais
    carregarContadores();
    
    // Atualizar contadores periodicamente
    setInterval(carregarContadores, 30000); // A cada 30 segundos
});

// Funções
function alterarStatus(status) {
    fetch('api/alterar-status-prestador.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Erro ao alterar status:', data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}

function carregarContadores() {
    fetch('api/prestador-counters.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Atualizar badges
            const orcamentosCount = document.getElementById('orcamentos-count');
            const mobileOrcamentosCount = document.getElementById('mobile-orcamentos-count');
            const notificationCount = document.getElementById('notification-count');
            
            if (orcamentosCount) {
                orcamentosCount.textContent = data.orcamentos_pendentes || 0;
                orcamentosCount.style.display = data.orcamentos_pendentes > 0 ? 'block' : 'none';
            }
            
            if (mobileOrcamentosCount) {
                mobileOrcamentosCount.textContent = data.orcamentos_pendentes || 0;
                mobileOrcamentosCount.style.display = data.orcamentos_pendentes > 0 ? 'block' : 'none';
            }
            
            if (notificationCount) {
                notificationCount.textContent = data.notificacoes_nao_lidas || 0;
                notificationCount.style.display = data.notificacoes_nao_lidas > 0 ? 'block' : 'none';
            }
        }
    })
    .catch(error => {
        console.error('Erro ao carregar contadores:', error);
    });
}

function carregarNotificacoes() {
    fetch('api/notifications-prestador.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationList = document.getElementById('notificationList');
            if (notificationList) {
                if (data.notificacoes.length === 0) {
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Nenhuma notificação</p>
                        </div>
                    `;
                } else {
                    notificationList.innerHTML = data.notificacoes.map(notif => `
                        <div class="notification-item ${notif.lida ? '' : 'unread'}">
                            <div class="notification-content">
                                <h5>${notif.titulo}</h5>
                                <p>${notif.mensagem}</p>
                                <span class="notification-time">${notif.tempo_relativo}</span>
                            </div>
                        </div>
                    `).join('');
                }
            }
        }
    })
    .catch(error => {
        console.error('Erro ao carregar notificações:', error);
    });
}
</script>

