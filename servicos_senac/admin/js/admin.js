// JavaScript para o Painel Administrativo

document.addEventListener('DOMContentLoaded', function() {
    // Toggle da sidebar
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Fechar sidebar ao clicar fora (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Modais
    initializeModals();
    
    // Tabelas
    initializeTables();
    
    // Formulários
    initializeForms();
    
    // Confirmações de exclusão
    initializeDeleteConfirmations();
    
    // Filtros e busca
    initializeFilters();
    
    // Notificações
    initializeNotifications();
    
    // Charts (se houver)
    initializeCharts();
});

// Inicializar modais
function initializeModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    const modalCloses = document.querySelectorAll('.modal-close');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
            }
        });
    });
    
    modalCloses.forEach(close => {
        close.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.classList.remove('active');
        });
    });
    
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
}

// Inicializar tabelas
function initializeTables() {
    // Ordenação de tabelas
    const sortableHeaders = document.querySelectorAll('.sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const column = this.cellIndex;
            const isAscending = this.classList.contains('asc');
            
            sortTable(table, column, !isAscending);
            
            // Atualizar classes
            sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
            this.classList.add(isAscending ? 'desc' : 'asc');
        });
    });
    
    // Seleção múltipla
    const selectAllCheckbox = document.querySelector('.select-all');
    const rowCheckboxes = document.querySelectorAll('.row-select');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });
    }
    
    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
            
            // Atualizar select all
            if (selectAllCheckbox) {
                const checkedCount = document.querySelectorAll('.row-select:checked').length;
                selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
            }
        });
    });
}

// Ordenar tabela
function sortTable(table, column, ascending) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aText = a.cells[column].textContent.trim();
        const bText = b.cells[column].textContent.trim();
        
        // Tentar converter para número
        const aNum = parseFloat(aText.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bText.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return ascending ? aNum - bNum : bNum - aNum;
        }
        
        // Comparação de texto
        return ascending ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Atualizar ações em lote
function updateBulkActions() {
    const checkedCount = document.querySelectorAll('.row-select:checked').length;
    const bulkActions = document.querySelector('.bulk-actions');
    
    if (bulkActions) {
        if (checkedCount > 0) {
            bulkActions.style.display = 'block';
            bulkActions.querySelector('.selected-count').textContent = checkedCount;
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

// Inicializar formulários
function initializeForms() {
    // Validação em tempo real
    const forms = document.querySelectorAll('.admin-form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error')) {
                    validateField(this);
                }
            });
        });
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Por favor, corrija os erros no formulário.', 'error');
            }
        });
    });
    
    // Upload de arquivos
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const preview = this.parentElement.querySelector('.file-preview');
                if (preview) {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.innerHTML = `<p>Arquivo selecionado: ${file.name}</p>`;
                    }
                }
            }
        });
    });
}

// Validar campo
function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Campo obrigatório
    if (field.required && !value) {
        isValid = false;
        errorMessage = 'Este campo é obrigatório.';
    }
    
    // Email
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Email inválido.';
        }
    }
    
    // Telefone
    if (field.classList.contains('phone-mask') && value) {
        const phoneRegex = /^\(\d{2}\) \d{5}-\d{4}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            errorMessage = 'Telefone inválido.';
        }
    }
    
    // CPF
    if (field.classList.contains('cpf-mask') && value) {
        if (!validateCPF(value)) {
            isValid = false;
            errorMessage = 'CPF inválido.';
        }
    }
    
    // Atualizar UI
    const errorElement = field.parentElement.querySelector('.field-error');
    if (isValid) {
        field.classList.remove('error');
        if (errorElement) {
            errorElement.remove();
        }
    } else {
        field.classList.add('error');
        if (!errorElement) {
            const error = document.createElement('div');
            error.className = 'field-error';
            error.textContent = errorMessage;
            field.parentElement.appendChild(error);
        } else {
            errorElement.textContent = errorMessage;
        }
    }
    
    return isValid;
}

// Validar CPF
function validateCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
        return false;
    }
    
    let sum = 0;
    for (let i = 0; i < 9; i++) {
        sum += parseInt(cpf.charAt(i)) * (10 - i);
    }
    let remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.charAt(9))) return false;
    
    sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += parseInt(cpf.charAt(i)) * (11 - i);
    }
    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.charAt(10))) return false;
    
    return true;
}

// Inicializar confirmações de exclusão
function initializeDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const itemName = this.dataset.itemName || 'este item';
            const confirmMessage = `Tem certeza que deseja excluir ${itemName}? Esta ação não pode ser desfeita.`;
            
            if (confirm(confirmMessage)) {
                // Se for um link, navegar para o URL
                if (this.tagName === 'A') {
                    window.location.href = this.href;
                }
                // Se for um formulário, submeter
                else if (this.form) {
                    this.form.submit();
                }
            }
        });
    });
}

// Inicializar filtros
function initializeFilters() {
    const filterInputs = document.querySelectorAll('.filter-input');
    const filterSelects = document.querySelectorAll('.filter-select');
    
    filterInputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            applyFilters();
        }, 300));
    });
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            applyFilters();
        });
    });
    
    // Limpar filtros
    const clearFiltersBtn = document.querySelector('.clear-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            filterInputs.forEach(input => input.value = '');
            filterSelects.forEach(select => select.selectedIndex = 0);
            applyFilters();
        });
    }
}

// Aplicar filtros
function applyFilters() {
    const table = document.querySelector('.admin-table table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const filters = {};
    
    // Coletar valores dos filtros
    document.querySelectorAll('.filter-input, .filter-select').forEach(filter => {
        const column = filter.dataset.column;
        const value = filter.value.toLowerCase().trim();
        if (value) {
            filters[column] = value;
        }
    });
    
    // Aplicar filtros
    rows.forEach(row => {
        let show = true;
        
        Object.keys(filters).forEach(column => {
            const cell = row.querySelector(`[data-column="${column}"]`);
            if (cell) {
                const cellText = cell.textContent.toLowerCase().trim();
                if (!cellText.includes(filters[column])) {
                    show = false;
                }
            }
        });
        
        row.style.display = show ? '' : 'none';
    });
    
    // Atualizar contador de resultados
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const resultCount = document.querySelector('.result-count');
    if (resultCount) {
        resultCount.textContent = `${visibleRows.length} de ${rows.length} resultados`;
    }
}

// Inicializar notificações
function initializeNotifications() {
    // Auto-hide para alertas
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

// Mostrar notificação
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        ${message}
    `;
    
    const container = document.querySelector('.admin-content');
    container.insertBefore(notification, container.firstChild);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Inicializar gráficos (placeholder)
function initializeCharts() {
    // Aqui você pode adicionar bibliotecas de gráficos como Chart.js
    // Por exemplo:
    /*
    const ctx = document.getElementById('myChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                // dados do gráfico
            },
            options: {
                // opções do gráfico
            }
        });
    }
    */
}

// Função debounce
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

// Exportar dados
function exportData(format, table) {
    const rows = table.querySelectorAll('tr');
    let data = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => cell.textContent.trim());
        data.push(rowData);
    });
    
    if (format === 'csv') {
        exportToCSV(data);
    } else if (format === 'json') {
        exportToJSON(data);
    }
}

// Exportar para CSV
function exportToCSV(data) {
    const csv = data.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dados.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Exportar para JSON
function exportToJSON(data) {
    const headers = data[0];
    const rows = data.slice(1);
    const json = rows.map(row => {
        const obj = {};
        headers.forEach((header, index) => {
            obj[header] = row[index];
        });
        return obj;
    });
    
    const blob = new Blob([JSON.stringify(json, null, 2)], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'dados.json';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Atualizar status em tempo real
function updateStatus(id, status, type) {
    fetch(`api/update-status.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: id,
            status: status,
            type: type
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Status atualizado com sucesso!', 'success');
            // Atualizar UI se necessário
        } else {
            showNotification('Erro ao atualizar status.', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao atualizar status.', 'error');
    });
}

