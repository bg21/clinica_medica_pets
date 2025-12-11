<?php
/**
 * View de Produtos
 */
?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-box text-primary"></i>
                Produtos
            </h1>
            <p class="text-muted mb-0">Gerencie seus produtos e planos</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProductModal">
            <i class="bi bi-plus-circle"></i> Novo Produto
        </button>
    </div>

    <div id="alertContainer"></div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Total de Produtos</p>
                            <h2 class="mb-0 fw-bold" id="totalProductsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-box fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Produtos Ativos</p>
                            <h2 class="mb-0 fw-bold" id="activeProductsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Com Imagens</p>
                            <h2 class="mb-0 fw-bold" id="withImagesStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-image fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card kpi-card-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1 small fw-medium">Inativos</p>
                            <h2 class="mb-0 fw-bold" id="inactiveProductsStat">-</h2>
                        </div>
                        <div class="kpi-icon">
                            <i class="bi bi-x-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Nome, descrição...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="true">Ativos</option>
                        <option value="false">Inativos</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" onclick="loadProducts()">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-secondary w-100" onclick="loadProducts()" title="Atualizar">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Produtos -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-grid me-2"></i>
                Lista de Produtos
            </h5>
            <span class="badge bg-primary" id="productsCountBadge">0</span>
        </div>
        <div class="card-body">
            <div class="row" id="productsGrid">
                <div class="col-12 text-center py-5" id="loadingProducts">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Carregando produtos...</p>
                </div>
            </div>
            <div id="emptyState" class="text-center py-5" style="display: none;">
                <i class="bi bi-box fs-1 text-muted"></i>
                <h5 class="mt-3 text-muted">Nenhum produto encontrado</h5>
                <p class="text-muted">Comece criando seu primeiro produto.</p>
                <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#createProductModal">
                    <i class="bi bi-plus-circle"></i> Criar Produto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Criar Produto -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createProductForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="active" id="productActive" checked>
                            <label class="form-check-label" for="productActive">
                                Produto ativo
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagens (URLs, uma por linha)</label>
                        <textarea class="form-control" name="images" id="productImages" rows="3" placeholder="https://exemplo.com/imagem1.jpg"></textarea>
                        <div class="invalid-feedback" id="imagesError"></div>
                        <small class="text-muted">Digite uma URL por linha. URLs devem começar com http:// ou https://</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Produto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Produto -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm">
                <input type="hidden" id="editProductId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="editProductName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" id="editProductDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="active" id="editProductActive">
                            <label class="form-check-label" for="editProductActive">
                                Produto ativo
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Imagens (URLs, uma por linha)</label>
                        <textarea class="form-control" name="images" id="editProductImages" rows="3" placeholder="https://exemplo.com/imagem1.jpg"></textarea>
                        <div class="invalid-feedback" id="editImagesError"></div>
                        <small class="text-muted">Digite uma URL por linha. URLs devem começar com http:// ou https://</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
let products = [];

let currentPage = 1;
let pageSize = 20;

document.addEventListener('DOMContentLoaded', () => {
    // Carrega dados imediatamente
    loadProducts();
    
    // Debounce na busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => {
            currentPage = 1;
            loadProducts();
        }, 500));
    }
    
    // Validação de URLs de imagens
    const imagesInput = document.getElementById('productImages');
    if (imagesInput) {
        imagesInput.addEventListener('blur', () => {
            const imagesText = imagesInput.value.trim();
            if (!imagesText) {
                imagesInput.classList.remove('is-invalid');
                document.getElementById('imagesError').textContent = '';
                return;
            }
            
            const urls = imagesText.split('\n').filter(url => url.trim());
            const urlPattern = /^https?:\/\/.+/;
            const invalidUrls = urls.filter(url => !urlPattern.test(url.trim()));
            
            if (invalidUrls.length > 0) {
                imagesInput.classList.add('is-invalid');
                document.getElementById('imagesError').textContent = `${invalidUrls.length} URL(s) inválida(s). URLs devem começar com http:// ou https://`;
            } else {
                imagesInput.classList.remove('is-invalid');
                document.getElementById('imagesError').textContent = '';
            }
        });
    }
    
    // Form criar produto
    document.getElementById('createProductForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        // Processa imagens
        if (data.images) {
            const urls = data.images.split('\n').filter(url => url.trim());
            const urlPattern = /^https?:\/\/.+/;
            const invalidUrls = urls.filter(url => !urlPattern.test(url));
            
            if (invalidUrls.length > 0) {
                showAlert(`URL(s) de imagem inválida(s). URLs devem começar com http:// ou https://`, 'danger');
                return;
            }
            
            data.images = urls;
        }
        
        // Processa active
        data.active = document.getElementById('productActive').checked;
        
        try {
            const response = await apiRequest('/v1/products', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            // Limpa cache após criar produto
            if (typeof cache !== 'undefined' && cache.clear) {
                cache.clear('/v1/products');
            }
            
            showAlert('Produto criado com sucesso!', 'success');
            
            // ✅ CORREÇÃO: Marca que produto foi criado para forçar refresh na listagem
            sessionStorage.setItem('productJustCreated', 'true');
            bootstrap.Modal.getInstance(document.getElementById('createProductModal')).hide();
            e.target.reset();
            imagesInput.classList.remove('is-invalid');
            document.getElementById('imagesError').textContent = '';
            loadProducts();
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    });
    
});

async function loadProducts() {
    const gridEl = document.getElementById('productsGrid');
    if (!gridEl) {
        console.error('Elemento productsGrid não encontrado');
        return;
    }
    
    // Cria ou obtém o elemento de loading
    let loadingEl = document.getElementById('loadingProducts');
    if (!loadingEl) {
        // Se não existe, cria dentro do grid
        gridEl.innerHTML = `
            <div class="col-12 text-center py-5" id="loadingProducts">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Carregando produtos...</p>
            </div>
        `;
        loadingEl = document.getElementById('loadingProducts');
    }
    
    try {
        if (loadingEl) {
            loadingEl.style.display = 'block';
        }
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('limit', pageSize);
        
        const statusFilter = document.getElementById('statusFilter')?.value;
        // ✅ CORREÇÃO: Por padrão, mostra apenas produtos ativos (consistência com prices)
        if (statusFilter) {
            params.append('active', statusFilter);
        } else {
            // Padrão: mostrar apenas produtos ativos (mesma lógica da página de prices)
            params.append('active', 'true');
        }
        
        const search = document.getElementById('searchInput')?.value.trim();
        if (search) {
            params.append('search', search);
        }
        
        // ✅ CORREÇÃO: Adiciona parâmetro refresh para forçar atualização após criar produto
        // Se acabou de criar um produto, força refresh
        const justCreated = sessionStorage.getItem('productJustCreated') === 'true';
        if (justCreated) {
            params.append('refresh', 'true');
            sessionStorage.removeItem('productJustCreated');
        }
        
        const url = '/v1/products?' + params.toString();
        const response = await apiRequest(url, {
            cacheTTL: justCreated ? 0 : 15000 // Sem cache se acabou de criar
        });
        
        products = response.data || [];
        
        renderProducts();
    } catch (error) {
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
        gridEl.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> 
                    <strong>Erro ao carregar produtos:</strong> ${escapeHtml(error.message)}
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="loadProducts()">
                        <i class="bi bi-arrow-clockwise"></i> Tentar novamente
                    </button>
                </div>
            </div>
        `;
    } finally {
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }
}

function renderProducts() {
    const grid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    const countBadge = document.getElementById('productsCountBadge');
    const loadingEl = document.getElementById('loadingProducts');
    
    if (loadingEl) {
        loadingEl.style.display = 'none';
    }
    
    if (products.length === 0) {
        grid.innerHTML = '';
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        if (countBadge) countBadge.textContent = '0';
        return;
    }
    
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    if (countBadge) {
        countBadge.textContent = formatNumber(products.length);
    }
    
    // Calcula estatísticas
    const stats = calculateProductStats();
    updateProductStats(stats);
    
    grid.innerHTML = products.map(product => {
        const hasImages = product.images && product.images.length > 0;
        const description = product.description || 'Sem descrição';
        const truncatedDesc = description.length > 100 ? description.substring(0, 100) + '...' : description;
        
        return `
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card h-100 product-card">
                ${hasImages ? `
                    <img src="${escapeHtml(product.images[0])}" class="card-img-top" style="height: 200px; object-fit: cover;" alt="${escapeHtml(product.name)}" onerror="this.style.display='none';">
                ` : ''}
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${escapeHtml(product.name)}</h5>
                    <p class="card-text text-muted small flex-grow-1">${escapeHtml(truncatedDesc)}</p>
                    <div class="d-flex justify-content-between align-items-center mt-auto">
                        <span class="badge bg-${product.active ? 'success' : 'secondary'}">
                            ${product.active ? 'Ativo' : 'Inativo'}
                        </span>
                        <div class="btn-group" role="group">
                            <a href="/product-details?id=${product.id}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="editProduct('${product.id}')" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteProduct('${product.id}')" title="Excluir">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top-0">
                    <small class="text-muted">
                        <code class="small">${escapeHtml(product.id)}</code>
                    </small>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

function calculateProductStats() {
    const total = products.length;
    const active = products.filter(p => p.active).length;
    const inactive = products.filter(p => !p.active).length;
    const withImages = products.filter(p => p.images && p.images.length > 0).length;
    
    return { total, active, inactive, withImages };
}

function updateProductStats(stats) {
    document.getElementById('totalProductsStat').textContent = formatNumber(stats.total);
    document.getElementById('activeProductsStat').textContent = formatNumber(stats.active);
    document.getElementById('inactiveProductsStat').textContent = formatNumber(stats.inactive);
    document.getElementById('withImagesStat').textContent = formatNumber(stats.withImages);
}

// Função auxiliar para escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}


async function editProduct(productId) {
    try {
        // Busca dados do produto
        const response = await apiRequest(`/v1/products/${productId}`);
        const product = response.data;
        
        // Preenche o formulário
        document.getElementById('editProductId').value = product.id;
        document.getElementById('editProductName').value = product.name || '';
        document.getElementById('editProductDescription').value = product.description || '';
        document.getElementById('editProductActive').checked = product.active === true;
        
        // Preenche imagens (uma por linha)
        const imagesText = (product.images || []).join('\n');
        document.getElementById('editProductImages').value = imagesText;
        
        // Limpa erros anteriores
        document.getElementById('editImagesError').textContent = '';
        document.getElementById('editProductImages').classList.remove('is-invalid');
        
        // Abre o modal
        const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
        modal.show();
    } catch (error) {
        console.error('Erro ao carregar produto:', error);
        showAlert(error.message || 'Erro ao carregar dados do produto', 'danger');
    }
}

// Handler do formulário de edição
document.getElementById('editProductForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const productId = formData.get('id');
    
    // Valida imagens
    const imagesInput = document.getElementById('editProductImages');
    const imagesText = imagesInput.value.trim();
    const imagesError = document.getElementById('editImagesError');
    
    let images = [];
    if (imagesText) {
        const imageUrls = imagesText.split('\n').map(url => url.trim()).filter(url => url);
        const invalidUrls = imageUrls.filter(url => !url.match(/^https?:\/\//));
        
        if (invalidUrls.length > 0) {
            imagesInput.classList.add('is-invalid');
            imagesError.textContent = 'Todas as URLs devem começar com http:// ou https://';
            return;
        }
        
        if (imageUrls.length > 20) {
            imagesInput.classList.add('is-invalid');
            imagesError.textContent = 'Máximo de 20 imagens permitidas';
            return;
        }
        
        images = imageUrls;
    }
    
    const data = {
        name: formData.get('name'),
        description: formData.get('description') || null,
        active: formData.get('active') === 'on',
        images: images.length > 0 ? images : null
    };
    
    try {
        const response = await apiRequest(`/v1/products/${productId}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
        
        // Limpa cache após editar produto
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/products');
        }
        
        // ✅ CORREÇÃO: Marca que produto foi editado para forçar refresh na listagem
        sessionStorage.setItem('productJustCreated', 'true');
        
        showAlert('Produto atualizado com sucesso!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
        e.target.reset();
        imagesInput.classList.remove('is-invalid');
        imagesError.textContent = '';
        loadProducts();
    } catch (error) {
        showAlert(error.message, 'danger');
    }
});

async function deleteProduct(productId) {
    const confirmed = await showConfirmModal(
        'Tem certeza que deseja remover este produto? Esta ação não pode ser desfeita.',
        'Confirmar Exclusão',
        'Remover Produto'
    );
    if (!confirmed) return;
    
    try {
        const response = await apiRequest(`/v1/products/${productId}`, {
            method: 'DELETE'
        });
        
        // Limpa cache após deletar produto
        if (typeof cache !== 'undefined' && cache.clear) {
            cache.clear('/v1/products');
        }
        
        // ✅ CORREÇÃO: Marca que produto foi deletado para forçar refresh na listagem
        sessionStorage.setItem('productJustCreated', 'true');
        
        // Verifica se foi deletado ou apenas desativado
        const wasDeleted = response.data?.deleted === true;
        const message = wasDeleted 
            ? 'Produto removido com sucesso!' 
            : 'Produto desativado com sucesso (tinha preços associados). Ele não aparecerá mais na lista de produtos ativos.';
        
        showAlert(message, wasDeleted ? 'success' : 'warning');
        
        // Recarrega a lista
        loadProducts();
    } catch (error) {
        console.error('Erro ao deletar produto:', error);
        showAlert(error.message || 'Erro ao remover produto', 'danger');
    }
}
</script>

