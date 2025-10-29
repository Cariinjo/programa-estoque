<?php
// includes/header.php

// Garante que config.php e helpers.php sejam incluídos (estão no mesmo diretório)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php'; // Certifique-se que helpers.php existe com time_elapsed_string

// Define a URL base do site (IMPORTANTE!)
// Ajuste '/teste/servicos_senac' se a pasta raiz do seu projeto for diferente
$base_url = '/teste/servicos_senac';

$user_logged = isLoggedIn();
$user_name = $user_logged ? ($_SESSION['user_name'] ?? '') : '';
$user_type = $user_logged ? ($_SESSION['user_type'] ?? '') : '';
// Define o caminho web base para uploads
$base_upload_url = $base_url . "/uploads/perfis/";
$default_avatar_web = $base_upload_url . "default.png";
$user_avatar = $default_avatar_web; // Avatar padrão
$user_initials = '';
$unread_count = 0;
$notifications_list = []; // Inicializa como array vazio

if ($user_logged && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // --- Lógica do Avatar ---
    // Caminho FÍSICO no servidor para verificar existência
    $potential_avatar_path_physical = dirname(__DIR__) . "/uploads/perfis/" . $user_id . ".png"; // dirname(__DIR__) sobe um nível
    // Caminho WEB para usar na tag <img>
    $potential_avatar_path_web = $base_upload_url . $user_id . ".png";
    if (file_exists($potential_avatar_path_physical)) {
        $user_avatar = $potential_avatar_path_web;
    }

    // --- Gerar Iniciais ---
    if ($user_name) {
        $names = explode(' ', trim($user_name));
        $user_initials = strtoupper(substr($names[0], 0, 1));
        if (count($names) > 1) {
            $user_initials .= strtoupper(substr($names[count($names) - 1], 0, 1));
        }
    }

    // --- Busca Notificações Diretamente no PHP ---
    try {
        // 1. Contar não lidas
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE id_usuario_destino = ? AND lida = 0");
        $stmt_count->execute([$user_id]);
        $unread_count = (int)$stmt_count->fetchColumn();

        // 2. Buscar as últimas 5 notificações
        $stmt_list = $pdo->prepare("
            SELECT id_notificacao, tipo_notificacao, mensagem, link_acao, lida, data_criacao
            FROM notificacoes
            WHERE id_usuario_destino = ?
            ORDER BY data_criacao DESC
            LIMIT 5
        ");
        $stmt_list->execute([$user_id]);
        $notifications_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Erro ao buscar notificacoes header: " . $e->getMessage());
        $unread_count = 0;
        $notifications_list = [];
    }
    // --- Fim Busca Notificações ---

} // Fim if ($user_logged)

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços SENAC</title>
    <link rel="stylesheet" href="<?= $base_url ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>/css/notifications.css"> <style>
        /* Cole aqui APENAS os estilos CSS específicos do SEU header.php */
        /* Estilos básicos para dropdowns e notificações */
        .user-dropdown, .register-menu, .notifications-panel { display: none; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; position: absolute; background: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1001; margin-top: 5px; }
        .user-dropdown.active, .register-menu.active, .notifications-panel.active { display: block; opacity: 1; visibility: visible; transform: translateY(0); top: 1px; }
        .header-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); z-index: 999; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .header-overlay.active { opacity: 1; visibility: visible; }
        .notification-item.unread { background-color: #f0f0f7; /* Roxo claro */ }
        .notification-item.read { background-color: #fff; opacity: 0.8; }
        .notification-item.unread .message { font-weight: bold; }
        .mark-all-read { text-decoration: none; color: #6c5ce7; font-size: 0.8rem; cursor: pointer; }
        .mark-all-read:hover { text-decoration: underline; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255, 255, 255, 0.3); }
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-initials { color: white; font-weight: bold; font-size: 0.9rem; }
        /* Cole o restante do SEU CSS específico do header aqui */
        .modern-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 4px 20px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; height: 70px; }
        .header-brand .brand-link { display: flex; align-items: center; gap: 12px; text-decoration: none; color: white; }
        .brand-logo { width: 45px; height: 45px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .brand-text { display: flex; flex-direction: column; }
        .brand-name { font-size: 1.4rem; font-weight: 700; line-height: 1; }
        .brand-subtitle { font-size: 0.8rem; opacity: 0.9; font-weight: 400; }
        .header-nav { display: flex; align-items: center; }
        .nav-links { display: flex; gap: 8px; }
        .nav-link { display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; text-decoration: none; color: rgba(255,255,255,0.9); font-weight: 500; transition: all 0.3s ease; position: relative; }
        .nav-link:hover { background: rgba(255,255,255,0.15); color: white; transform: translateY(-2px); }
        .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .header-actions { display: flex; align-items: center; gap: 16px; }
        .user-menu-container { display: flex; align-items: center; gap: 16px; position: relative; } /* position: relative adicionado */
        .notification-btn { position: relative; width: 40px; height: 40px; background: rgba(255,255,255,0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; border: none; padding: 0; }
        .notification-btn:hover { background: rgba(255,255,255,0.25); }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: #ff4757; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .user-menu { display: flex; align-items: center; gap: 12px; padding: 8px 12px; background: rgba(255,255,255,0.15); border-radius: 12px; cursor: pointer; }
        .user-menu:hover { background: rgba(255,255,255,0.25); }
        .user-info { display: flex; flex-direction: column; color: white; }
        .user-name { font-weight: 600; font-size: 0.9rem; line-height: 1; }
        .user-type { font-size: 0.75rem; opacity: 0.8; }
        .dropdown-icon { color: white; font-size: 0.8rem; transition: transform 0.3s ease; margin-left: 5px; }
        .user-menu.active .dropdown-icon { transform: rotate(180deg); }
        .user-dropdown { right: 0; min-width: 220px; padding: 8px; }
        .dropdown-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; text-decoration: none; color: #333; transition: background 0.3s ease; font-size: 0.9rem; }
        .dropdown-item:hover { background: #f8f9fa; }
        .dropdown-item.logout { color: #e74c3c; }
        .dropdown-item.logout:hover { background: #ffeaea; }
        .dropdown-divider { height: 1px; background: #eee; margin: 8px 0; }
        .notifications-panel { right: 0; /* Alinha à direita do container pai */ width: 320px; padding: 0; margin-top: 8px; /* Ajuste para espaçamento */ }
        .notifications-header { padding: 10px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; border-radius: 8px 8px 0 0; }
        .notifications-header h3 { margin: 0; font-size: 1rem; color: #333; }
        .notifications-list { max-height: 300px; overflow-y: auto; list-style: none; padding: 0; margin: 0; }
        .notification-item { display: list-item; padding: 0; border-bottom: 1px solid #f5f5f5; }
        .notification-item a { display: flex; width: 100%; padding: 12px 15px; gap: 12px; text-decoration: none; color: inherit; transition: background-color 0.2s ease; }
        .notification-item:last-child { border-bottom: none; }
        .notification-item a:hover { background-color: #f0f0f7; }
        .notification-icon { width: 35px; height: 35px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.9rem; }
        .notification-content { overflow: hidden; text-overflow: ellipsis; flex-grow: 1; }
        .notification-content p.message { margin: 0 0 4px 0; font-size: 0.85rem; color: #333; line-height: 1.3; }
        .notification-time { font-size: 0.75rem; color: #666; }
        .mobile-menu-btn { display: none; }
         /* Botões Auth */
        .auth-buttons { display: flex; align-items: center; gap: 12px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn-outline { background: transparent; color: white; border: 1px solid rgba(255,255,255,0.4); }
        .btn-outline:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.6); }
        .btn-primary { background: rgba(255,255,255,0.2); color: white; }
        .btn-primary:hover { background: rgba(255,255,255,0.3); }
        .register-dropdown { position: relative; }
        .register-menu { right: 0; min-width: 250px; padding: 8px; margin-top: 5px; }
        .register-option { display: flex; align-items: center; gap: 12px; padding: 10px; border-radius: 6px; text-decoration: none; color: #333; transition: background 0.3s ease; }
        .register-option:hover { background: #f1f1f1; }
        .register-option i { width: 35px; height: 35px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
        .register-option strong { display: block; margin-bottom: 2px; font-size: 0.9rem; }
        .register-option small { color: #666; font-size: 0.75rem; }

        @media (max-width: 768px) { /* Ajustes responsivos */
             .header-nav { display: none; }
             .mobile-menu-btn { display: flex; flex-direction: column; gap: 4px; background: none; border: none; cursor: pointer; padding: 8px;}
             .mobile-menu-btn span { width: 25px; height: 3px; background: white; border-radius: 2px; }
             .header-nav.active { display: flex; position: absolute; top: 70px; left: 0; background: #6a5acd; width: 100%; flex-direction: column; padding: 10px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.1); z-index: 1000; }
             .nav-links { flex-direction: column; width: 100%; }
             .nav-link { justify-content: center; padding: 12px 20px; border-radius: 0; }
             .nav-link.active::after { display: none; }
             .user-info { display: none; }
             .brand-text { display: none; }
             .header-actions { gap: 8px; }
             .auth-buttons .btn span { display: none; }
             .auth-buttons .btn { padding: 8px 10px; }
             .notifications-panel { right: 5px; width: calc(100vw - 10px); max-width: 320px; } /* Ajuste para mobile */
             .user-dropdown { right: 5px; }
        }
    </style>
</head>
<body>

<header class="modern-header">
    <div class="header-container">
        <div class="header-brand">
            <a href="<?= $base_url ?>/index.php" class="brand-link">
                <div class="brand-logo"><i class="fas fa-graduation-cap"></i></div>
                <div class="brand-text">
                    <span class="brand-name">SENAC</span>
                    <span class="brand-subtitle">Minas Gerais</span>
                </div>
            </a>
        </div>

        <nav class="header-nav" id="header-nav">
             <div class="nav-links">
                 <a href="<?= $base_url ?>/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i><span>Início</span></a>
                <a href="<?= $base_url ?>/servicos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'servicos.php' ? 'active' : '' ?>"><i class="fas fa-cogs"></i><span>Serviços</span></a>
                <a href="<?= $base_url ?>/profissionais.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profissionais.php' ? 'active' : '' ?>"><i class="fas fa-users"></i><span>Profissionais</span></a>
                <a href="<?= $base_url ?>/como-funciona.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'como-funciona.php' ? 'active' : '' ?>"><i class="fas fa-question-circle"></i><span>Como Funciona</span></a>
                <a href="<?= $base_url ?>/contato.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'contato.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i><span>Contato</span></a>
            </div>
        </nav>

        <div class="header-actions">
            <?php if ($user_logged): ?>
                <div class="user-menu-container">

                    <div class="notification-btn" id="notification-toggle-btn" title="Notificações">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notification-badge" style="<?= $unread_count > 0 ? '' : 'display: none;' ?>"><?= $unread_count ?></span>
                    </div>
                    <div class="notifications-panel" id="notifications-panel">
                        <div class="notifications-header">
                            <h3>Notificações</h3>
                            <a href="<?= $base_url ?>/marcar-todas-lidas.php" class="mark-all-read" id="mark-all-read-link" style="<?= $unread_count > 0 ? '' : 'display: none;' ?>">
                                Marcar todas como lidas
                            </a>
                        </div>
                        <ul class="notifications-list" id="notifications-list">
                            <?php if (empty($notifications_list)): ?>
                                <li class="no-notifications" style="padding: 20px; text-align: center; color: #888;">Nenhuma notificação.</li>
                            <?php else: ?>
                                <?php foreach ($notifications_list as $notification): ?>
                                    <?php
                                    // Lógica Ícone
                                    $icon = 'fas fa-bell';
                                    if (strpos($notification['tipo_notificacao'], 'orcamento') !== false) $icon = 'fas fa-file-invoice';
                                    if (strpos($notification['tipo_notificacao'], 'mensagem') !== false) $icon = 'fas fa-message'; // Ajustado
                                    if (strpos($notification['tipo_notificacao'], 'avaliacao') !== false) $icon = 'fas fa-star';

                                    // Adiciona parâmetro mark_read ao link
                                    $link = $notification['link_acao'] ?? '#';
                                    // Garante que o link seja relativo à raiz
                                    if ($link !== '#' && substr($link, 0, 1) !== '/' && substr($link, 0, 4) !== 'http') {
                                        $link = $base_url . '/' . ltrim($link, '/');
                                    }
                                    $separator = (strpos($link, '?') !== false) ? '&' : '?';
                                    // Adiciona mark_read apenas se houver um link válido
                                    $link_com_mark_read = ($link !== '#') ? ($link . $separator . 'mark_read=' . $notification['id_notificacao']) : '#';
                                    ?>
                                    <li class="notification-item <?= !$notification['lida'] ? 'unread' : 'read' ?>">
                                        <a href="<?= htmlspecialchars($link_com_mark_read) ?>">
                                            <div class="notification-icon"><i class="<?= $icon ?>"></i></div>
                                            <div class="notification-content">
                                                <p class="message"><?= htmlspecialchars($notification['mensagem']) ?></p>
                                                <span class="time"><?= time_elapsed_string($notification['data_criacao']) ?></span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                         </div>
                    <div class="user-menu" id="user-menu-toggle">
                        <div class="user-avatar">
                            <?php if ($user_avatar && $user_avatar !== $default_avatar_web): ?>
                                <img src="<?= htmlspecialchars($user_avatar) ?>?v=<?= time() // Cache buster ?>" alt="Avatar">
                            <?php else: ?>
                                <span class="avatar-initials"><?= $user_initials ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($user_name) ?></span>
                            <span class="user-type"><?= ucfirst($user_type) ?></span>
                        </div>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </div>
                    <div class="user-dropdown" id="user-dropdown">
                        <?php if ($user_type === 'prestador'): ?>
                             <a href="<?= $base_url ?>/dashboard-prestador.php" class="dropdown-item"><i class="fas fa-briefcase"></i><span>Meu Dashboard</span></a>
                             <a href="<?= $base_url ?>/meus-servicos.php" class="dropdown-item"><i class="fas fa-cogs"></i><span>Meus Serviços</span></a>
                             <a href="<?= $base_url ?>/orcamentos-recebidos.php" class="dropdown-item"><i class="fas fa-inbox"></i><span>Orçamentos Recebidos</span></a>
                         <?php else: // Cliente ?>
                             <a href="<?= $base_url ?>/dashboard.php" class="dropdown-item"><i class="fas fa-user"></i><span>Meu Perfil</span></a>
                             <a href="<?= $base_url ?>/meus-orcamentos.php" class="dropdown-item"><i class="fas fa-file-invoice"></i><span>Meus Orçamentos</span></a>
                         <?php endif; ?>
                         <div class="dropdown-divider"></div>
                         <a href="<?= $base_url ?>/logout.php" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i><span>Sair</span></a>
                    </div>
                    </div>
            <?php else: ?>
                <div class="auth-buttons">
                     <a href="<?= $base_url ?>/login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i><span>Entrar</span></a>
                     <div class="register-dropdown">
                         <button class="btn btn-primary register-btn" id="register-toggle-btn">
                             <i class="fas fa-user-plus"></i><span>Cadastrar</span><i class="fas fa-chevron-down"></i>
                         </button>
                         <div class="register-menu" id="register-menu">
                              <a href="<?= $base_url ?>/cadastro.php?tipo=cliente" class="register-option">... Sou Cliente ...</a>
                              <a href="<?= $base_url ?>/cadastro.php?tipo=prestador" class="register-option">... Sou Prestador ...</a>
                         </div>
                     </div>
                 </div>
            <?php endif; ?>
        </div> <button class="mobile-menu-btn" id="mobile-menu-toggle-btn" aria-label="Abrir menu">
            <span></span><span></span><span></span>
        </button>
    </div> <div class="header-overlay" id="header-overlay"></div>
</header>

<?php if ($user_logged): // Inclui JS só se logado ?>
<script>
// --- LÓGICA PARA ABRIR/FECHAR MENUS DROPDOWN ---
const userMenuToggle = document.getElementById('user-menu-toggle');
const userDropdown = document.getElementById('user-dropdown');
const registerToggleBtn = document.getElementById('register-toggle-btn'); // Pode ser null se logado
const registerMenu = document.getElementById('register-menu'); // Pode ser null se logado
const notificationToggleBtn = document.getElementById('notification-toggle-btn');
const notificationsPanel = document.getElementById('notifications-panel');
const overlay = document.getElementById('header-overlay');
const mobileMenuBtn = document.getElementById('mobile-menu-toggle-btn'); // Botão mobile
const nav = document.getElementById('header-nav'); // Menu de navegação

function closeAllDropdowns() {
    userDropdown?.classList.remove('active');
    registerMenu?.classList.remove('active');
    notificationsPanel?.classList.remove('active');
    overlay?.classList.remove('active');
    nav?.classList.remove('active'); // Fecha menu mobile
}

// Evento para o Menu do Usuário
if (userMenuToggle) {
    userMenuToggle.addEventListener('click', (e) => {
        e.stopPropagation(); // Impede que o clique no botão feche o menu imediatamente
        const isActive = userDropdown.classList.contains('active');
        closeAllDropdowns(); // Fecha outros menus antes de abrir este
        if (!isActive) { // Só abre se estava fechado
            userDropdown.classList.add('active');
            overlay.classList.add('active'); // Mostra o fundo
        }
    });
}

// Evento para o Menu de Registro (se existir)
if (registerToggleBtn) {
    registerToggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isActive = registerMenu.classList.contains('active');
        closeAllDropdowns();
        if (!isActive) {
            registerMenu.classList.add('active');
            overlay.classList.add('active');
        }
    });
}

// Evento para o Painel de Notificações
if (notificationToggleBtn) {
    notificationToggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isActive = notificationsPanel.classList.contains('active');
        closeAllDropdowns();
        if (!isActive) {
            notificationsPanel.classList.add('active');
            overlay.classList.add('active');
        }
    });
}

// Evento para o Menu Mobile
if (mobileMenuBtn && nav) {
    mobileMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isActive = nav.classList.contains('active');
        closeAllDropdowns(); // Fecha outros menus
        if (!isActive) {
            nav.classList.add('active'); // Adicione CSS para mostrar .header-nav.active
            overlay.classList.add('active');
        }
    });
}


// Evento para fechar clicando no Overlay ou ESC
if (overlay) {
    overlay.addEventListener('click', closeAllDropdowns);
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAllDropdowns();
    }
});

// Impede que cliques DENTRO dos painéis dropdown os fechem
userDropdown?.addEventListener('click', (e) => e.stopPropagation());
registerMenu?.addEventListener('click', (e) => e.stopPropagation());
notificationsPanel?.addEventListener('click', (e) => {
    // Permite clique nos links (<a>), mas impede fechar para outros cliques
    if (!e.target.closest('a')) {
         e.stopPropagation();
    }
    // A navegação normal do link (incluindo o mark_read) ocorrerá
});


// --- LÓGICA PARA MARCAR NOTIFICAÇÃO COMO LIDA ESTÁ NO TOPO DAS PÁGINAS DE DESTINO (PHP) ---
// Nenhum JavaScript AJAX é necessário aqui para marcar como lida nesta abordagem

</script>
<?php endif; // Fim do if (isLoggedIn()) para o script ?>

</body>
</html>