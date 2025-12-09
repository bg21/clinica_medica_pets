<?php
/**
 * Layout base para todas as views
 * 
 * @var string $title Título da página
 * @var string $apiUrl URL base da API
 * @var array|null $user Dados do usuário autenticado
 * @var array|null $tenant Dados do tenant
 * @var string $currentPage Página atual (para highlight no menu)
 */

// Define se deve mostrar seções do SaaS (Stripe)
$showSaaSFeatures = ($user['role'] ?? '') === 'admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8'); ?> - Sistema SaaS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
    
    <!-- ✅ OTIMIZAÇÃO: Preconnect para CDN (reduz latência) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    
    <!-- ✅ OTIMIZAÇÃO: CSS crítico primeiro (render-blocking) -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <!-- ✅ CORREÇÃO: Removido integrity inválido (causava erro de bloqueio) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" crossorigin="anonymous">
    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="/css/dashboard.css?v=<?php 
        $cssFile = __DIR__ . '/../../public/css/dashboard.css';
        echo (file_exists($cssFile) ? filemtime($cssFile) : time()); 
    ?>">
    
    <!-- ✅ OTIMIZAÇÃO: Security Helper com defer (não bloqueia renderização) -->
    <script src="/app/security.js" defer></script>
    <!-- ✅ Validações Frontend (formulários e formatos Stripe) -->
    <script src="/app/validations.js?v=<?php 
        $validationsFile = __DIR__ . '/../../public/app/validations.js';
        echo (file_exists($validationsFile) ? filemtime($validationsFile) : time()); 
    ?>" defer></script>
</head>
<body>
    <!-- Mobile Header Bar -->
    <div class="mobile-header-bar">
        <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="mobile-header-logo">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </div>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo desktop-logo">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </div>
        
        <nav>
            <ul class="nav-menu">
                <?php if ($showSaaSFeatures): ?>
                <!-- Seções do SaaS (Stripe) - Apenas para Admin -->
                <li class="nav-section">
                    <span class="nav-section-title">Principal</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/dashboard" class="nav-link <?php echo ($currentPage ?? '') === 'dashboard' ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/customers" class="nav-link <?php echo ($currentPage ?? '') === 'customers' ? 'active' : ''; ?>">
                                <i class="bi bi-people"></i>
                                <span>Customers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/subscriptions" class="nav-link <?php echo ($currentPage ?? '') === 'subscriptions' ? 'active' : ''; ?>">
                                <i class="bi bi-credit-card"></i>
                                <span>Assinaturas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/products" class="nav-link <?php echo ($currentPage ?? '') === 'products' ? 'active' : ''; ?>">
                                <i class="bi bi-box"></i>
                                <span>Produtos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/prices" class="nav-link <?php echo ($currentPage ?? '') === 'prices' ? 'active' : ''; ?>">
                                <i class="bi bi-tag"></i>
                                <span>Preços</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-section">
                    <span class="nav-section-title">Financeiro</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/transactions" class="nav-link <?php echo ($currentPage ?? '') === 'transactions' ? 'active' : ''; ?>">
                                <i class="bi bi-arrow-left-right"></i>
                                <span>Transações</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/invoices" class="nav-link <?php echo ($currentPage ?? '') === 'invoices' ? 'active' : ''; ?>">
                                <i class="bi bi-receipt"></i>
                                <span>Faturas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/refunds" class="nav-link <?php echo ($currentPage ?? '') === 'refunds' ? 'active' : ''; ?>">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>Reembolsos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/disputes" class="nav-link <?php echo ($currentPage ?? '') === 'disputes' ? 'active' : ''; ?>">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Disputas</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/charges" class="nav-link <?php echo ($currentPage ?? '') === 'charges' ? 'active' : ''; ?>">
                                <i class="bi bi-credit-card-2-front"></i>
                                <span>Cobranças</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/payouts" class="nav-link <?php echo ($currentPage ?? '') === 'payouts' ? 'active' : ''; ?>">
                                <i class="bi bi-bank"></i>
                                <span>Saques</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/invoice-items" class="nav-link <?php echo ($currentPage ?? '') === 'invoice-items' ? 'active' : ''; ?>">
                                <i class="bi bi-list-ul"></i>
                                <span>Itens de Fatura</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/tax-rates" class="nav-link <?php echo ($currentPage ?? '') === 'tax-rates' ? 'active' : ''; ?>">
                                <i class="bi bi-percent"></i>
                                <span>Taxas de Imposto</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-section">
                    <span class="nav-section-title">Assinaturas</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/subscription-history" class="nav-link <?php echo ($currentPage ?? '') === 'subscription-history' ? 'active' : ''; ?>">
                                <i class="bi bi-clock-history"></i>
                                <span>Histórico</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-section">
                    <span class="nav-section-title">Promoções</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/coupons" class="nav-link <?php echo ($currentPage ?? '') === 'coupons' ? 'active' : ''; ?>">
                                <i class="bi bi-ticket-perforated"></i>
                                <span>Cupons</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/promotion-codes" class="nav-link <?php echo ($currentPage ?? '') === 'promotion-codes' ? 'active' : ''; ?>">
                                <i class="bi bi-tag"></i>
                                <span>Códigos Promocionais</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-section">
                    <span class="nav-section-title">Pagamentos</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/payment-methods" class="nav-link <?php echo ($currentPage ?? '') === 'payment-methods' ? 'active' : ''; ?>">
                                <i class="bi bi-wallet2"></i>
                                <span>Métodos de Pagamento</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/billing-portal" class="nav-link <?php echo ($currentPage ?? '') === 'billing-portal' ? 'active' : ''; ?>">
                                <i class="bi bi-door-open"></i>
                                <span>Portal de Cobrança</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if ($showSaaSFeatures): ?>
                <li class="nav-section">
                    <span class="nav-section-title">Relatórios</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/reports" class="nav-link <?php echo ($currentPage ?? '') === 'reports' ? 'active' : ''; ?>">
                                <i class="bi bi-graph-up"></i>
                                <span>Relatórios</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <li class="nav-section">
                    <span class="nav-section-title">Administração</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/users" class="nav-link <?php echo ($currentPage ?? '') === 'users' ? 'active' : ''; ?>">
                                <i class="bi bi-person-gear"></i>
                                <span>Usuários</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/permissions" class="nav-link <?php echo ($currentPage ?? '') === 'permissions' ? 'active' : ''; ?>">
                                <i class="bi bi-shield-check"></i>
                                <span>Permissões</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/audit-logs" class="nav-link <?php echo ($currentPage ?? '') === 'audit-logs' ? 'active' : ''; ?>">
                                <i class="bi bi-journal-text"></i>
                                <span>Logs de Auditoria</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/traces" class="nav-link <?php echo ($currentPage ?? '') === 'traces' ? 'active' : ''; ?>">
                                <i class="bi bi-diagram-3"></i>
                                <span>Tracing de Requisições</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/performance-metrics" class="nav-link <?php echo ($currentPage ?? '') === 'performance-metrics' ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer"></i>
                                <span>Métricas de Performance</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-section">
                    <span class="nav-section-title">Clínica Veterinária</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/clinic/dashboard" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-dashboard' ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/pets" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-pets' ? 'active' : ''; ?>">
                                <i class="bi bi-heart-pulse"></i>
                                <span>Pets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/professionals" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-professionals' ? 'active' : ''; ?>">
                                <i class="bi bi-person-badge"></i>
                                <span>Profissionais</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/specialties" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-specialties' ? 'active' : ''; ?>">
                                <i class="bi bi-briefcase"></i>
                                <span>Especialidades</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/professional-schedule" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-professional-schedule' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-week"></i>
                                <span>Agenda de Profissionais</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/search" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-search' ? 'active' : ''; ?>">
                                <i class="bi bi-search"></i>
                                <span>Busca Avançada</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/appointments" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-appointments' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar-check"></i>
                                <span>Agendamentos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/exams" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-exams' ? 'active' : ''; ?>">
                                <i class="bi bi-clipboard-pulse"></i>
                                <span>Exames</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic/reports" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-reports' ? 'active' : ''; ?>">
                                <i class="bi bi-graph-up"></i>
                                <span>Relatórios</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/schedule" class="nav-link <?php echo ($currentPage ?? '') === 'schedule' ? 'active' : ''; ?>">
                                <i class="bi bi-calendar3"></i>
                                <span>Calendário</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/clinic-settings" class="nav-link <?php echo ($currentPage ?? '') === 'clinic-settings' ? 'active' : ''; ?>">
                                <i class="bi bi-gear"></i>
                                <span>Configurações da Clínica</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-section">
                    <span class="nav-section-title">Configurações</span>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="/settings" class="nav-link <?php echo ($currentPage ?? '') === 'settings' ? 'active' : ''; ?>">
                                <i class="bi bi-gear"></i>
                                <span>Configurações</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/stripe-connect" class="nav-link <?php echo ($currentPage ?? '') === 'stripe-connect' ? 'active' : ''; ?>">
                                <i class="bi bi-link-45deg"></i>
                                <span>Conectar Stripe</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" onclick="logout(); return false;">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sair</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php echo $content ?? ''; ?>
    </main>

    <!-- ✅ Modal de Confirmação Reutilizável -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirmar Ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">
                    Tem certeza que deseja realizar esta ação?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmModalButton">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ OTIMIZAÇÃO: Bootstrap JS com defer e integrity (não bloqueia renderização) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    
    <!-- ✅ OTIMIZAÇÃO: Variáveis globais inline (necessárias antes do script externo) -->
    <script>
        const API_URL = <?php echo json_encode($apiUrl ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const SESSION_ID = localStorage.getItem('session_id');
        const USER = <?php echo json_encode($user ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const TENANT = <?php echo json_encode($tenant ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    
    <!-- ✅ OTIMIZAÇÃO: JavaScript principal em arquivo externo (permite cache do navegador) -->
    <script src="/app/dashboard.js?v=<?php 
        $jsFile = __DIR__ . '/../../public/app/dashboard.js';
        echo (file_exists($jsFile) ? filemtime($jsFile) : time()); 
    ?>" defer></script>
    
    <?php if (isset($scripts)): ?>
        <?php echo $scripts; ?>
    <?php endif; ?>
</body>
</html>

