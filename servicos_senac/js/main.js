// JavaScript principal para o sistema de serviços SENAC

document.addEventListener('DOMContentLoaded', function() {
    // Animações de entrada
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);

    // Observar elementos para animação
    const elementsToAnimate = document.querySelectorAll('.category-card, .service-card, .provider-card, .step-card');
    elementsToAnimate.forEach(el => observer.observe(el));

    // Busca dinâmica
    const searchBox = document.querySelector('.search-box');
    if (searchBox) {
        searchBox.addEventListener('input', debounce(function(e) {
            const query = e.target.value;
            if (query.length >= 3) {
                searchSuggestions(query);
            }
        }, 300));
    }

    // Menu mobile
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }

    // Smooth scroll para links internos
    const internalLinks = document.querySelectorAll('a[href^="#"]');
    internalLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Filtros de categoria
    const categoryFilters = document.querySelectorAll('.category-filter');
    categoryFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            const category = this.dataset.category;
            filterServicesByCategory(category);
        });
    });

    // Sistema de avaliação por estrelas
    const starRatings = document.querySelectorAll('.star-rating');
    starRatings.forEach(rating => {
        const stars = rating.querySelectorAll('.star');
        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const ratingValue = index + 1;
                updateStarRating(rating, ratingValue);
            });
            
            star.addEventListener('mouseenter', function() {
                highlightStars(rating, index + 1);
            });
        });
        
        rating.addEventListener('mouseleave', function() {
            const currentRating = rating.dataset.rating || 0;
            highlightStars(rating, currentRating);
        });
    });

    // Notificações
    checkNotifications();
    setInterval(checkNotifications, 30000); // Verificar a cada 30 segundos

    // Chat
    initializeChat();
});

// Função de debounce para otimizar performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Busca de sugestões
function searchSuggestions(query) {
    fetch(`api/search-suggestions.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySuggestions(data);
        })
        .catch(error => {
            console.error('Erro na busca de sugestões:', error);
        });
}

// Exibir sugestões de busca
function displaySuggestions(suggestions) {
    let suggestionsContainer = document.querySelector('.search-suggestions');
    
    if (!suggestionsContainer) {
        suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'search-suggestions';
        document.querySelector('.search-container').appendChild(suggestionsContainer);
    }
    
    if (suggestions.length === 0) {
        suggestionsContainer.style.display = 'none';
        return;
    }
    
    const suggestionsHTML = suggestions.map(suggestion => 
        `<div class="suggestion-item" onclick="selectSuggestion('${suggestion.text}')">${suggestion.text}</div>`
    ).join('');
    
    suggestionsContainer.innerHTML = suggestionsHTML;
    suggestionsContainer.style.display = 'block';
}

// Selecionar sugestão
function selectSuggestion(text) {
    document.querySelector('.search-box').value = text;
    document.querySelector('.search-suggestions').style.display = 'none';
    document.querySelector('.search-box').closest('form').submit();
}

// Filtrar serviços por categoria
function filterServicesByCategory(category) {
    const serviceCards = document.querySelectorAll('.service-card');
    
    serviceCards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
            card.classList.add('fade-in-up');
        } else {
            card.style.display = 'none';
        }
    });
    
    // Atualizar filtros ativos
    document.querySelectorAll('.category-filter').forEach(filter => {
        filter.classList.remove('active');
    });
    document.querySelector(`[data-category="${category}"]`).classList.add('active');
}

// Sistema de avaliação por estrelas
function updateStarRating(ratingElement, value) {
    ratingElement.dataset.rating = value;
    highlightStars(ratingElement, value);
    
    // Enviar avaliação para o servidor
    const serviceId = ratingElement.dataset.serviceId;
    if (serviceId) {
        submitRating(serviceId, value);
    }
}

function highlightStars(ratingElement, count) {
    const stars = ratingElement.querySelectorAll('.star');
    stars.forEach((star, index) => {
        if (index < count) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function submitRating(serviceId, rating) {
    fetch('api/submit-rating.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            service_id: serviceId,
            rating: rating
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Avaliação enviada com sucesso!', 'success');
        } else {
            showNotification('Erro ao enviar avaliação.', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao enviar avaliação.', 'error');
    });
}

// Sistema de notificações
function checkNotifications() {
    if (!isLoggedIn()) return;
    
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            updateNotificationBadge(data.unread_count);
            if (data.notifications.length > 0) {
                displayNotifications(data.notifications);
            }
        })
        .catch(error => {
            console.error('Erro ao verificar notificações:', error);
        });
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function displayNotifications(notifications) {
    const container = document.querySelector('.notifications-container');
    if (!container) return;
    
    const notificationsHTML = notifications.map(notification => `
        <div class="notification-item ${notification.lida ? '' : 'unread'}" data-id="${notification.id_notificacao}">
            <div class="notification-content">
                <p>${notification.mensagem}</p>
                <small>${formatDate(notification.data_criacao)}</small>
            </div>
            ${!notification.lida ? '<button class="mark-read-btn" onclick="markAsRead(' + notification.id_notificacao + ')">Marcar como lida</button>' : ''}
        </div>
    `).join('');
    
    container.innerHTML = notificationsHTML;
}

function markAsRead(notificationId) {
    fetch('api/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            notification_id: notificationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            checkNotifications(); // Atualizar lista
        }
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}

// Sistema de chat
function initializeChat() {
    const chatContainer = document.querySelector('.chat-container');
    if (!chatContainer) return;
    
    // Carregar mensagens
    loadChatMessages();
    
    // Configurar envio de mensagens
    const messageForm = document.querySelector('.message-form');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }
    
    // Atualizar mensagens periodicamente
    setInterval(loadChatMessages, 5000);
}

function loadChatMessages() {
    const chatId = document.querySelector('.chat-container').dataset.chatId;
    if (!chatId) return;
    
    fetch(`api/chat-messages.php?chat_id=${chatId}`)
        .then(response => response.json())
        .then(data => {
            displayChatMessages(data.messages);
        })
        .catch(error => {
            console.error('Erro ao carregar mensagens:', error);
        });
}

function displayChatMessages(messages) {
    const messagesContainer = document.querySelector('.chat-messages');
    if (!messagesContainer) return;
    
    const messagesHTML = messages.map(message => `
        <div class="message ${message.is_own ? 'own' : 'other'}">
            <div class="message-content">${message.mensagem}</div>
            <div class="message-time">${formatTime(message.data_envio)}</div>
        </div>
    `).join('');
    
    messagesContainer.innerHTML = messagesHTML;
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function sendMessage() {
    const messageInput = document.querySelector('.message-input');
    const chatId = document.querySelector('.chat-container').dataset.chatId;
    const message = messageInput.value.trim();
    
    if (!message) return;
    
    fetch('api/send-message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            chat_id: chatId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            loadChatMessages();
        } else {
            showNotification('Erro ao enviar mensagem.', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao enviar mensagem.', 'error');
    });
}

// Funções utilitárias
function isLoggedIn() {
    return document.body.classList.contains('logged-in');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' às ' + date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Validação de formulários
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    return isValid;
}

// Máscara para campos
function applyMasks() {
    const phoneFields = document.querySelectorAll('.phone-mask');
    phoneFields.forEach(field => {
        field.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    });
    
    const cpfFields = document.querySelectorAll('.cpf-mask');
    cpfFields.forEach(field => {
        field.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    });
}

// Inicializar máscaras quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', applyMasks);

