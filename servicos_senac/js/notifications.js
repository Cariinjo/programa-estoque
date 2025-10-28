document.addEventListener('DOMContentLoaded', () => {
    // Seleciona os elementos do DOM uma vez
    const notificationsContainer = document.getElementById('notifications-container');
    const badge = document.getElementById('notification-badge');
    const list = document.getElementById('notifications-list');
    const markAllLink = document.getElementById('mark-all-read-link');
    const notificationsButton = document.getElementById('notifications-button'); // Botão para abrir/fechar
    const notificationsContent = document.getElementById('notifications-content'); // O dropdown em si

    // Define a URL base da API (ajuste se necessário)
    const apiBaseUrl = 'api/'; // Ex: 'http://localhost/seuprojeto/api/'

    // Verifica se todos os elementos essenciais existem
    if (!notificationsContainer || !badge || !list || !markAllLink || !notificationsButton || !notificationsContent) {
        console.error("Um ou mais elementos essenciais das notificações não foram encontrados no DOM.");
        return; // Impede a execução se elementos cruciais faltarem
    }

    // --- Lógica para Abrir/Fechar o Dropdown ---
    notificationsButton.addEventListener('click', (event) => {
        event.stopPropagation(); // Impede que o clique feche imediatamente
        notificationsContainer.classList.toggle('active'); // Adiciona/remove a classe para mostrar/esconder via CSS
    });

    // Fecha o dropdown se clicar fora dele
    document.addEventListener('click', (event) => {
        if (!notificationsContainer.contains(event.target)) {
            notificationsContainer.classList.remove('active');
        }
    });
    // Impede que cliques dentro do dropdown o fechem
    notificationsContent.addEventListener('click', (event) => {
        event.stopPropagation();
    });
    // --- Fim da Lógica Abrir/Fechar ---


    // --- Lógica para Buscar e Exibir Notificações ---
    async function fetchNotifications() {
        try {
            // Chama a API para buscar notificações
            const response = await fetch(`${apiBaseUrl}buscar-notificacoes-api.php`);
            if (!response.ok) {
                // Se a resposta não for OK (ex: 404, 500), lança um erro
                throw new Error(`Erro HTTP: ${response.status} ${response.statusText}`);
            }
            const data = await response.json();

            // Verifica se a resposta da API indica sucesso
            if (data.success) {
                updateNotificationsUI(data.unread_count, data.notifications);
            } else {
                // Se success for false, mostra a mensagem de erro da API
                console.error('Erro retornado pela API ao buscar notificações:', data.message);
                list.innerHTML = `<li class="no-notifications">${data.message || 'Erro ao carregar.'}</li>`;
                badge.style.display = 'none';
                markAllLink.style.display = 'none';
            }
        } catch (error) {
            // Captura erros de rede ou JSON inválido
            console.error('Falha na requisição fetch para buscar notificações:', error);
            list.innerHTML = '<li class="no-notifications">Erro de conexão.</li>';
            badge.style.display = 'none';
            markAllLink.style.display = 'none';
        }
    }

    // Função para atualizar a interface (contador e lista)
    function updateNotificationsUI(unreadCount, notifications) {
        // Atualiza o contador (badge)
        if (unreadCount > 0) {
            badge.textContent = unreadCount;
            badge.style.display = 'inline-block'; // Mostra o badge
            markAllLink.style.display = 'inline'; // Mostra link "Marcar todas"
        } else {
            badge.textContent = '0';
            badge.style.display = 'none'; // Esconde o badge
            markAllLink.style.display = 'none'; // Esconde link "Marcar todas"
        }

        // Limpa a lista de notificações atual
        list.innerHTML = '';

        // Preenche a lista com as novas notificações ou mostra mensagem
        if (!notifications || notifications.length === 0) {
            list.innerHTML = '<li class="no-notifications">Nenhuma notificação nova.</li>';
        } else {
            notifications.forEach(notif => {
                const listItem = document.createElement('li');
                // Adiciona classe 'read' ou 'unread'
                listItem.className = `notification-item ${notif.lida ? 'read' : 'unread'}`;
                // Cria o link, incluindo o data-id para identificação
                listItem.innerHTML = `
                    <a href="${notif.link_acao || '#'}" data-id="${notif.id_notificacao}" title="${notif.mensagem}">
                        ${notif.icone ? `<i class="${notif.icone}" style="margin-right: 8px; opacity: 0.7; width: 15px;"></i>` : '<i class="fas fa-bell" style="margin-right: 8px; opacity: 0.7; width: 15px;"></i>'}
                        <span class="message">${notif.mensagem}</span>
                        <span class="time">${notif.tempo}</span>
                    </a>
                `;
                list.appendChild(listItem);
            });
        }
    }
    // --- Fim da Lógica Buscar e Exibir ---


    // --- Lógica para Marcar Uma Notificação como Lida ---
    async function markNotificationAsRead(notificationId, linkElement) {
        // Pega o URL original antes de fazer a chamada API
        const originalHref = linkElement.href;

        try {
            // Chama a API para marcar como lida (método POST)
            const response = await fetch(`${apiBaseUrl}marcar-uma-lida-api.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', // Informa que estamos enviando JSON
                },
                // Envia o ID da notificação no corpo da requisição
                body: JSON.stringify({ notification_id: notificationId })
            });

            // Verifica se a resposta da API foi bem-sucedida (status 2xx)
            if (!response.ok) {
                // Se não foi OK, tenta ler a mensagem de erro do JSON
                let errorData = { message: `Erro HTTP: ${response.status}`};
                try {
                    errorData = await response.json();
                } catch(e) { /* Ignora erro ao parsear JSON de erro */ }
                throw new Error(errorData.message || `Erro HTTP: ${response.status}`);
            }

            const data = await response.json();

            // Verifica se a API retornou sucesso
            if (data.success) {
                console.log(`Notificação ${notificationId} marcada como lida (ou já estava).`);
                // Não precisa atualizar a UI aqui, pois vamos redirecionar
            } else {
                // Se a API retornou success: false
                console.error('Erro retornado pela API ao marcar como lida:', data.message);
            }

        } catch (error) {
            // Captura erros de rede ou da API
            console.error('Falha ao enviar requisição para marcar como lida:', error);
            // Continua para o redirecionamento mesmo se falhar, para não prender o usuário
        } finally {
             // SEMPRE redireciona para o link original após a tentativa
             window.location.href = originalHref;
        }
    }

    // Adiciona o listener de clique à lista UL (delegação de evento)
    list.addEventListener('click', (event) => {
        // Encontra o elemento <a> mais próximo que foi clicado e que tenha 'data-id'
        const linkElement = event.target.closest('a[data-id]');

        // Se encontrou um link válido
        if (linkElement) {
            event.preventDefault(); // Impede o navegador de seguir o link imediatamente

            const notificationId = parseInt(linkElement.dataset.id, 10); // Pega o ID do atributo data-id
            const isUnread = linkElement.closest('.notification-item')?.classList.contains('unread'); // Verifica se está como não lida

            // Se a notificação está marcada como 'unread' e tem um ID válido...
            if (isUnread && !isNaN(notificationId) && notificationId > 0) {
                // ...chama a função para marcar como lida via API (que depois redirecionará)
                markNotificationAsRead(notificationId, linkElement);
            } else {
                // ...senão (já está lida ou ID inválido), apenas redireciona
                window.location.href = linkElement.href;
            }
        }
    });
    // --- Fim da Lógica Marcar Uma Lida ---


    // Busca inicial de notificações quando a página carrega
    fetchNotifications();

    // Opcional: Atualizar notificações a cada X segundos
    // setInterval(fetchNotifications, 60000); // Ex: Recarrega a cada 1 minuto

}); // Fim do DOMContentLoaded