<?php
/**
 * View de Configurações
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-gear text-primary"></i>
                Configurações
            </h1>
            <p class="text-muted mb-0">Gerencie as configurações do sistema</p>
        </div>
    </div>

    <div id="alertContainer"></div>

    <div class="row">
        <div class="col-md-12">
            <!-- Configurações Gerais -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-sliders me-2"></i>
                        Configurações Gerais
                    </h5>
                </div>
                <div class="card-body">
                    <form id="settingsForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">URL Base da API</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-link-45deg"></i>
                                    </span>
                                    <input type="text" class="form-control" id="apiUrl" 
                                           value="<?php echo htmlspecialchars($apiUrl ?? '', ENT_QUOTES); ?>"
                                           placeholder="https://api.exemplo.com">
                                </div>
                                <small class="text-muted">URL base para requisições à API</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Tema</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-palette"></i>
                                    </span>
                                    <select class="form-select" id="theme">
                                        <option value="light">Claro</option>
                                        <option value="dark">Escuro</option>
                                        <option value="auto">Automático</option>
                                    </select>
                                </div>
                                <small class="text-muted">Escolha o tema da interface</small>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Salvar Configurações
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetSettings()">
                                <i class="bi bi-arrow-counterclockwise"></i> Restaurar Padrões
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estatísticas do Sistema -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Estatísticas do Sistema
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="checkApiStatus()" title="Verificar Status">
                        <i class="bi bi-arrow-clockwise"></i> Verificar
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="text-muted mb-1 small fw-medium">Versão da API</p>
                                    <h5 class="mb-0" id="apiVersion">-</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="text-muted mb-1 small fw-medium">Status</p>
                                    <span id="apiStatus" class="badge bg-success">Online</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="text-muted mb-1 small fw-medium">Ambiente</p>
                                    <h5 class="mb-0" id="apiEnvironment">-</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="text-muted mb-1 small fw-medium">Última Verificação</p>
                                    <small class="text-muted" id="lastCheck">-</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Carrega configurações salvas
    const savedApiUrl = localStorage.getItem('apiUrl');
    if (savedApiUrl) {
        document.getElementById('apiUrl').value = savedApiUrl;
    }
    
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.getElementById('theme').value = savedTheme;
    
    checkApiStatus();
    
    document.getElementById('settingsForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const apiUrl = document.getElementById('apiUrl').value.trim();
        const theme = document.getElementById('theme').value;
        
        // Validação básica
        if (apiUrl && !isValidUrl(apiUrl)) {
            showAlert('URL inválida. Por favor, informe uma URL válida (ex: https://api.exemplo.com)', 'warning');
            return;
        }
        
        localStorage.setItem('apiUrl', apiUrl);
        localStorage.setItem('theme', theme);
        
        // Aplica tema imediatamente
        applyTheme(theme);
        
        showAlert('Configurações salvas com sucesso!', 'success');
    });
});

function resetSettings() {
    if (confirm('Tem certeza que deseja restaurar as configurações padrão?')) {
        localStorage.removeItem('apiUrl');
        localStorage.removeItem('theme');
        
        document.getElementById('apiUrl').value = '';
        document.getElementById('theme').value = 'light';
        
        applyTheme('light');
        
        showAlert('Configurações restauradas para os padrões!', 'success');
    }
}

function applyTheme(theme) {
    // Remove classes de tema existentes
    document.body.classList.remove('theme-light', 'theme-dark');
    
    if (theme === 'dark') {
        document.body.classList.add('theme-dark');
    } else if (theme === 'auto') {
        // Detecta preferência do sistema
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.body.classList.add(prefersDark ? 'theme-dark' : 'theme-light');
    } else {
        document.body.classList.add('theme-light');
    }
}

function isValidUrl(string) {
    try {
        const url = new URL(string);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch (_) {
        return false;
    }
}

async function checkApiStatus() {
    try {
        const response = await fetch(API_URL + '/');
        const data = await response.json();
        
        document.getElementById('apiVersion').textContent = data.version || '-';
        document.getElementById('apiStatus').textContent = data.status === 'ok' ? 'Online' : 'Offline';
        document.getElementById('apiStatus').className = data.status === 'ok' ? 'badge bg-success' : 'badge bg-danger';
        document.getElementById('apiEnvironment').textContent = data.environment || '-';
        document.getElementById('lastCheck').textContent = new Date().toLocaleString('pt-BR');
    } catch (error) {
        document.getElementById('apiStatus').textContent = 'Erro';
        document.getElementById('apiStatus').className = 'badge bg-danger';
    }
}
</script>

