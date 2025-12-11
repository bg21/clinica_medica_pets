/**
 * ✅ OTIMIZAÇÃO: JavaScript principal movido para arquivo externo
 * Isso permite cache do navegador e melhor performance
 */

// ✅ Invalidação de cache em outras abas
if (typeof BroadcastChannel !== 'undefined') {
    const cacheChannel = new BroadcastChannel('cache_invalidation');
    cacheChannel.addEventListener('message', (event) => {
        if (event.data && event.data.action === 'clear') {
            const pattern = event.data.pattern;
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                if (key.startsWith('api_cache_') && key.includes(pattern)) {
                    localStorage.removeItem(key);
                }
            });
        }
    });
}

// ✅ Listener para eventos de storage (fallback para navegadores sem BroadcastChannel)
// Nota: storage event só é disparado quando mudanças vêm de OUTRAS abas
window.addEventListener('storage', (event) => {
    if (event.key && event.key.startsWith('cache_clear_') && event.newValue) {
        const pattern = event.newValue;
        const keys = Object.keys(localStorage);
        keys.forEach(key => {
            if (key.startsWith('api_cache_') && key.includes(pattern)) {
                localStorage.removeItem(key);
            }
        });
    }
});

// Aguarda DOM estar pronto
document.addEventListener('DOMContentLoaded', () => {
    // ✅ CORREÇÃO: Garantir que overlay esteja fechado ao carregar
    const overlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('sidebar');
    if (overlay) {
        overlay.classList.remove('show');
    }
    // Em desktop, garantir que sidebar não tenha classe show
    if (window.innerWidth >= 769 && sidebar) {
        sidebar.classList.remove('show');
    }
    
    // ✅ CORREÇÃO: Lê SESSION_ID dinamicamente do localStorage
    // Verifica se é administrador SaaS primeiro, depois usuário normal
    let sessionId = localStorage.getItem('saas_admin_session_id') || localStorage.getItem('session_id') || (typeof SESSION_ID !== 'undefined' ? SESSION_ID : null);
    
    // Fallback: tenta obter da query string (para desenvolvimento) e salva no localStorage
    const urlParams = new URLSearchParams(window.location.search);
    if (!sessionId) {
        const saasAdminSessionId = urlParams.get('saas_admin_session_id');
        const normalSessionId = urlParams.get('session_id');
        
        if (saasAdminSessionId) {
            sessionId = saasAdminSessionId;
            localStorage.setItem('saas_admin_session_id', saasAdminSessionId);
        } else if (normalSessionId) {
            sessionId = normalSessionId;
            localStorage.setItem('session_id', normalSessionId);
        }
    }
    
    // Remove session_id da URL se estiver presente (segurança)
    let urlChanged = false;
    if (urlParams.has('session_id')) {
        urlParams.delete('session_id');
        urlChanged = true;
    }
    if (urlParams.has('saas_admin_session_id')) {
        urlParams.delete('saas_admin_session_id');
        urlChanged = true;
    }
    if (urlChanged) {
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }
    
    if (!sessionId) {
        // Redireciona apenas se não tiver session_id
        // Se estiver em /admin-plans, redireciona para login de administrador SaaS
        const currentPath = window.location.pathname;
        const redirectUrl = currentPath.includes('/admin-plans') ? '/saas-admin/login' : '/login';
        
        setTimeout(() => {
            window.location.href = redirectUrl;
        }, 100);
        return;
    }
    
    // Verifica sessão em background (não bloqueia a página)
    // Usa AbortController para timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos timeout (aumentado)
    
    // Para administradores SaaS, não precisa verificar /v1/auth/me (não existe endpoint para isso)
    const isSaasAdmin = localStorage.getItem('saas_admin_session_id') !== null || (sessionId && urlParams.get('saas_admin_session_id'));
    
    if (isSaasAdmin) {
        // Administradores SaaS não precisam verificar sessão via /v1/auth/me
        clearTimeout(timeoutId);
        return;
    }
    
    fetch(API_URL + '/v1/auth/me', {
        headers: {
            'Authorization': 'Bearer ' + sessionId,
            'Content-Type': 'application/json'
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        
        // Se não for OK, tenta ler a resposta JSON para ver o erro
        if (!response.ok) {
            return response.json().then(errData => {
                // Se for erro 401 (não autorizado), redireciona
                if (response.status === 401) {
                    throw new Error('Sessão inválida ou expirada');
                }
                // Para outros erros, apenas loga mas não redireciona
                console.warn('Erro ao verificar sessão:', errData);
                throw new Error(errData.message || 'Erro ao verificar sessão');
            });
        }
        
        return response.json();
    })
    .then(data => {
        // Atualiza dados do usuário e verifica role para mostrar/ocultar itens admin
        if (data && data.data) {
            const userRole = data.data.role || data.data.user?.role || USER?.role;
            updateSidebarForRole(userRole);
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        
        // Ignora timeout (AbortError) - não é um erro real
        if (error.name === 'AbortError') {
            console.warn('Timeout ao verificar sessão (ignorado)');
            return;
        }
        
        // Ignora erros de rede (offline, etc) - não redireciona
        if (error.message && (
            error.message.includes('Failed to fetch') ||
            error.message.includes('NetworkError') ||
            error.message.includes('Network request failed')
        )) {
            console.warn('Erro de rede ao verificar sessão (ignorado):', error.message);
            return;
        }
        
        // Só redireciona se for erro 401 (não autorizado) ou sessão inválida
        if (error.message && (
            error.message.includes('Sessão inválida') ||
            error.message.includes('expirada') ||
            error.message.includes('401')
        )) {
            console.warn('Sessão inválida, redirecionando...');
            
            // Verifica se é administrador SaaS antes de redirecionar
            const isSaasAdmin = localStorage.getItem('saas_admin_session_id') !== null;
            
            // Limpa dados de sessão
            localStorage.removeItem('session_id');
            localStorage.removeItem('saas_admin_session_id');
            localStorage.removeItem('user');
            localStorage.removeItem('tenant');
            
            // Redireciona para o login apropriado
            if (isSaasAdmin) {
                window.location.href = '/saas-admin/login';
            } else {
                window.location.href = '/login';
            }
        } else {
            // Para outros erros, apenas loga mas não redireciona
            console.warn('Erro ao verificar sessão (não crítico):', error.message || error);
        }
    });
    
    // Verifica role do usuário atual (do PHP) para mostrar/ocultar itens
    if (USER && USER.role) {
        updateSidebarForRole(USER.role);
    }
});

// Funções globais
async function logout() {
    const confirmed = await showConfirmModal('Deseja realmente sair?', 'Confirmar Logout', 'Sair', 'btn-primary');
    if (confirmed) {
        const sessionId = localStorage.getItem('session_id') || (typeof SESSION_ID !== 'undefined' ? SESSION_ID : null);
        
        if (sessionId) {
            fetch(API_URL + '/v1/auth/logout', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + sessionId
                }
            }).finally(() => {
                localStorage.removeItem('session_id');
                localStorage.removeItem('user');
                localStorage.removeItem('tenant');
                window.location.href = '/login';
            });
        } else {
            // Se não tem session, apenas limpa e redireciona
            localStorage.removeItem('session_id');
            localStorage.removeItem('user');
            localStorage.removeItem('tenant');
            window.location.href = '/login';
        }
    }
}

// Sidebar mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) return;
    
    // ✅ CORREÇÃO: Só funciona em mobile (largura < 769px)
    if (window.innerWidth >= 769) {
        return;
    }
    
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) return;
    
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
}

// ✅ CORREÇÃO: Fechar sidebar ao redimensionar para desktop
window.addEventListener('resize', () => {
    if (window.innerWidth >= 769) {
        closeSidebar();
    }
});

// ✅ OTIMIZAÇÃO: Cache simples no frontend (localStorage) com melhor performance
// Protege contra redeclaração (caso o script seja carregado múltiplas vezes)
if (typeof window.cache === 'undefined') {
    window.cache = {
    get: (key) => {
        try {
            const item = localStorage.getItem('api_cache_' + key);
            if (!item) return null;
            const { data, expires } = JSON.parse(item);
            if (expires && Date.now() > expires) {
                localStorage.removeItem('api_cache_' + key);
                return null;
            }
            return data;
        } catch (e) {
            return null;
        }
    },
    set: (key, data, ttl = 60000) => {
        try {
            const item = {
                data,
                expires: Date.now() + ttl
            };
            localStorage.setItem('api_cache_' + key, JSON.stringify(item));
        } catch (e) {
            // Ignora erros de localStorage (quota excedida, etc)
        }
    },
    clear: (pattern) => {
        try {
            const keys = Object.keys(localStorage);
            keys.forEach(key => {
                // ✅ CORREÇÃO: Procura por chaves que contenham o padrão (não apenas que comecem)
                // A chave do cache é: api_cache_ + endpoint + method + body
                if (key.startsWith('api_cache_') && key.includes(pattern)) {
                    localStorage.removeItem(key);
                }
            });
            
            // ✅ Invalida cache em outras abas usando BroadcastChannel
            if (typeof BroadcastChannel !== 'undefined') {
                const channel = new BroadcastChannel('cache_invalidation');
                channel.postMessage({ action: 'clear', pattern: pattern });
            }
            
            // ✅ Fallback: usa localStorage para notificar outras abas
            try {
                const notificationKey = 'cache_clear_' + Date.now();
                localStorage.setItem(notificationKey, pattern);
                // Remove imediatamente para não poluir o localStorage
                setTimeout(() => {
                    try {
                        localStorage.removeItem(notificationKey);
                    } catch (e) {}
                }, 100);
            } catch (e) {
                // Ignora se não suportado
            }
        } catch (e) {        }
    }
};
} // Fecha if (typeof window.cache === 'undefined')

// ✅ OTIMIZAÇÃO: Helper para fazer requisições autenticadas com cache e retry
async function apiRequest(endpoint, options = {}) {
    const cacheKey = endpoint + (options.method || 'GET') + JSON.stringify(options.body || '');
    const useCache = !options.method || options.method === 'GET';
    const cacheTTL = options.cacheTTL || 30000; // 30 segundos padrão
    
    // Tenta obter do cache primeiro (apenas para GET)
    if (useCache && !options.skipCache) {
        const cached = window.cache.get(cacheKey);
        if (cached !== null) {
            return cached;
        }
    }
    
    // Detecta se é FormData
    const isFormData = options.isFormData || (options.body instanceof FormData);
    
    // ✅ CORREÇÃO: Lê SESSION_ID dinamicamente do localStorage (fallback para constante)
    // Verifica se é administrador SaaS primeiro, depois usuário normal
    let sessionId = localStorage.getItem('saas_admin_session_id') || localStorage.getItem('session_id') || (typeof SESSION_ID !== 'undefined' ? SESSION_ID : null);
    
    // Fallback: tenta obter da query string (para desenvolvimento)
    if (!sessionId) {
        const urlParams = new URLSearchParams(window.location.search);
        sessionId = urlParams.get('saas_admin_session_id') || urlParams.get('session_id');
    }
    
    if (!sessionId) {
        throw new Error('Sessão não encontrada. Por favor, faça login novamente.');
    }
    
    const defaultOptions = {
        headers: {
            'Authorization': 'Bearer ' + sessionId
        }
    };
    
    // Só adiciona Content-Type se não for FormData (o navegador adiciona automaticamente)
    if (!isFormData) {
        defaultOptions.headers['Content-Type'] = 'application/json';
    }
    
    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...(options.headers || {})
        }
    };
    
    // Remove Content-Type se for FormData (deixa o navegador definir)
    if (isFormData && mergedOptions.headers['Content-Type']) {
        delete mergedOptions.headers['Content-Type'];
    }
    
    // ✅ OTIMIZAÇÃO: Retry automático para falhas de rede
    let retries = options.retries || 0;
    let lastError;
    
    while (retries >= 0) {
        try {
            const response = await fetch(API_URL + endpoint, mergedOptions);
            const data = await response.json();
            
            if (!response.ok) {
                const error = new Error(data.message || data.error || 'Erro na requisição');
                // Adiciona dados completos da resposta ao erro para facilitar tratamento
                error.response = data;
                error.status = response.status;
                throw error;
            }
            
            // Salva no cache (apenas para GET bem-sucedido)
            if (useCache && response.ok) {
                window.cache.set(cacheKey, data, cacheTTL);
            }
            
            return data;
        } catch (error) {
            lastError = error;
            if (retries > 0 && (error.name === 'TypeError' || error.message.includes('network'))) {
                retries--;
                await new Promise(resolve => setTimeout(resolve, 1000)); // Aguarda 1s antes de retry
                continue;
            }
            throw error;
        }
    }
    
    throw lastError;
}

// ✅ OTIMIZAÇÃO: Helper para debounce (melhor performance)
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

// Helper para mostrar alertas
function showAlert(message, type = 'info', containerId = 'alertContainer') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // ✅ CORREÇÃO: Mapeia ícones para cada tipo de alerta
    const iconMap = {
        'danger': 'exclamation-triangle',
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<i class="bi bi-${iconMap[type] || 'info-circle'}-fill"></i> ${message}`;
    container.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// ✅ OTIMIZAÇÃO: Helper para formatar moeda (cache de formatter)
// Protege contra redeclaração
if (typeof window.currencyFormatters === 'undefined') {
    window.currencyFormatters = {};
}
function formatCurrency(value, currency = 'BRL') {
    if (!window.currencyFormatters[currency]) {
        window.currencyFormatters[currency] = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: currency.toLowerCase()
        });
    }
    return window.currencyFormatters[currency].format(value / 100);
}

// ✅ OTIMIZAÇÃO: Helper para formatar data (cache de formatter)
// ✅ CORREÇÃO: Protege contra redeclaração - usa apenas window.dateFormatter
if (typeof window.dateFormatter === 'undefined') {
    window.dateFormatter = new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function formatDate(timestamp) {
    if (!timestamp) return '-';
    // ✅ CORREÇÃO: Usa window.dateFormatter diretamente (sem criar const local)
    
    // Se for string de data MySQL (YYYY-MM-DD HH:MM:SS), converte
    if (typeof timestamp === 'string' && timestamp.match(/^\d{4}-\d{2}-\d{2}/)) {
        const date = new Date(timestamp);
        return window.dateFormatter.format(date);
    }
    // Se for timestamp Unix (número)
    const date = new Date(timestamp * 1000);
    return window.dateFormatter.format(date);
}

// Atualiza sidebar baseado no role do usuário
function updateSidebarForRole(role) {
    // Encontra a seção de administração procurando pelo link de usuários
    const adminLinks = document.querySelectorAll('.nav-link[href="/users"]');
    adminLinks.forEach(link => {
        const adminSection = link.closest('.nav-section');
        if (adminSection) {
            if (role !== 'admin') {
                // Oculta toda a seção de administração se não for admin
                adminSection.style.display = 'none';
            } else {
                // Mostra a seção de administração se for admin
                adminSection.style.display = 'block';
            }
        }
    });
}

// ✅ Modal de Confirmação Reutilizável (substitui confirm() nativo)
function showConfirmModal(message, title = 'Confirmar Ação', confirmText = 'Confirmar', confirmClass = 'btn-danger') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('confirmModalLabel');
        const modalBody = document.getElementById('confirmModalBody');
        const confirmButton = document.getElementById('confirmModalButton');
        
        // Configura o modal
        modalTitle.textContent = title;
        modalBody.textContent = message;
        confirmButton.textContent = confirmText;
        confirmButton.className = `btn ${confirmClass}`;
        
        // Remove listeners anteriores
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        
        // Adiciona listener para confirmar
        newConfirmButton.addEventListener('click', () => {
            const bsModal = bootstrap.Modal.getInstance(modal);
            bsModal.hide();
            resolve(true);
        });
        
        // Adiciona listener para cancelar
        modal.addEventListener('hidden.bs.modal', function onHidden() {
            modal.removeEventListener('hidden.bs.modal', onHidden);
            resolve(false);
        }, { once: true });
        
        // Mostra o modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    });
}

