<?php

/**
 * Arquivo principal da aplicação
 * Configura FlightPHP e rotas
 */

// ✅ OTIMIZAÇÃO: Compressão de resposta (gzip/deflate)
if (extension_loaded('zlib') && !ob_get_level()) {
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    if (strpos($acceptEncoding, 'gzip') !== false) {
        ob_start('ob_gzhandler');
    } elseif (strpos($acceptEncoding, 'deflate') !== false) {
        ob_start('ob_deflatehandler');
    } else {
        ob_start();
    }
}

// Servir arquivos estáticos da pasta /app (front-end)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('/^\/app\//', $requestUri)) {
    $filePath = __DIR__ . $requestUri;
    
    // Verificar se arquivo existe e é seguro (dentro da pasta public/app)
    if (file_exists($filePath) && is_file($filePath)) {
        $realPath = realpath($filePath);
        $publicPath = realpath(__DIR__);
        
        // Verificar se o arquivo está dentro de public/app
        if ($realPath && strpos($realPath, $publicPath . DIRECTORY_SEPARATOR . 'app') === 0) {
            $ext = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'html' => 'text/html; charset=utf-8',
                'js' => 'application/javascript; charset=utf-8',
                'css' => 'text/css; charset=utf-8',
                'json' => 'application/json; charset=utf-8',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf'
            ];
            
            header('Content-Type: ' . ($mimeTypes[$ext] ?? 'text/plain'));
            // ✅ OTIMIZAÇÃO: Cache agressivo para assets estáticos (1 ano)
            header('Cache-Control: public, max-age=31536000, immutable');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            // Remove headers que expõem informações do servidor
            header_remove('X-Powered-By');
            readfile($filePath);
            exit;
        }
    }
    
    // Arquivo não encontrado
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>404 - Not Found</title></head><body><h1>404 - Arquivo não encontrado</h1></body></html>';
    exit;
}

// Servir arquivos estáticos da pasta /img (imagens padrão)
if (preg_match('/^\/img\//', $requestUri)) {
    $filePath = __DIR__ . $requestUri;
    
    if (file_exists($filePath) && is_file($filePath)) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];
        
        header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=2592000'); // 1 mês
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        readfile($filePath);
        exit;
    }
}

// Servir arquivos da pasta /storage (logos, documentos, etc)
if (preg_match('/^\/storage\//', $requestUri)) {
    $filePath = __DIR__ . '/..' . $requestUri;
    
    // Verificar se arquivo existe e é seguro (dentro da pasta storage)
    if (file_exists($filePath) && is_file($filePath)) {
        $realPath = realpath($filePath);
        $storagePath = realpath(__DIR__ . '/../storage');
        
        // Verificar se o arquivo está dentro de storage
        if ($realPath && $storagePath && strpos($realPath, $storagePath) === 0) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            $mimeTypes = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
                'ico' => 'image/x-icon'
            ];
            
            header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
            // Cache para imagens (1 mês)
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'ico'])) {
                header('Cache-Control: public, max-age=2592000');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
            }
            header_remove('X-Powered-By');
            readfile($filePath);
            exit;
        }
    }
    
    // Arquivo não encontrado
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><title>404 - Not Found</title></head><body><h1>404 - Arquivo não encontrado</h1></body></html>';
    exit;
}

// Servir arquivos CSS da pasta /css
if (preg_match('/^\/css\//', $requestUri)) {
    $filePath = __DIR__ . $requestUri;
    
    if (file_exists($filePath) && is_file($filePath)) {
        $realPath = realpath($filePath);
        $publicPath = realpath(__DIR__);
        
        // Verificar se o arquivo está dentro de public/css
        if ($realPath && strpos($realPath, $publicPath . DIRECTORY_SEPARATOR . 'css') === 0) {
            header('Content-Type: text/css; charset=utf-8');
            // ✅ OTIMIZAÇÃO: Cache agressivo para CSS (1 ano)
            header('Cache-Control: public, max-age=31536000, immutable');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
            // Remove headers que expõem informações do servidor
            header_remove('X-Powered-By');
            readfile($filePath);
            exit;
        }
    }
    
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega configurações
require_once __DIR__ . '/../config/config.php';
Config::load();

// Inicializa FlightPHP
use flight\Engine;
use App\Core\Container;
use App\Core\ContainerBindings;

$app = new Engine();

// Configura JSON como padrão
$app->set('flight.handle_errors', true);
$app->set('flight.log_errors', true);

// Suprime warnings de compatibilidade do FlightPHP com PHP 8.2
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Middleware de CORS e Headers de Segurança
$app->before('start', function() {
    // Remove headers que expõem informações do servidor
    // Ocultar versão do PHP
    header_remove('X-Powered-By');
    
    // Ocultar informações do servidor web (se configurado no servidor)
    // Nota: Para Apache, configure no .htaccess: ServerTokens Prod
    // Para Nginx, configure: server_tokens off;
    
    // ✅ OTIMIZAÇÃO: Cache de headers para respostas JSON (5 minutos)
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($requestUri, '/v1/') === 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Cache-Control: private, max-age=300'); // 5 minutos para APIs
    }
    
    // Headers de Segurança (sempre aplicados)
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    // ✅ CORREÇÃO: Adicionado cdn.jsdelivr.net em connect-src para permitir source maps
    // ✅ CORREÇÃO: Adicionado data: em font-src para permitir fontes inline (FullCalendar e outros)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
           "img-src 'self' data: https:; " .
           "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
           "connect-src 'self' https://cdn.jsdelivr.net; " .
           "frame-ancestors 'none';";
    header("Content-Security-Policy: {$csp}");
    
    // HSTS (apenas em HTTPS)
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // CORS - Configuração Segura
    // Lista de origens permitidas (ajuste conforme necessário)
    $allowedOrigins = Config::get('CORS_ALLOWED_ORIGINS', '');
    $allowedOriginsArray = !empty($allowedOrigins) ? explode(',', $allowedOrigins) : [];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
    
    // Em desenvolvimento, permite localhost e origens configuradas
    if (Config::isDevelopment()) {
        if ($origin && (
            strpos($origin, 'http://localhost') === 0 ||
            strpos($origin, 'http://127.0.0.1') === 0 ||
            in_array($origin, $allowedOriginsArray)
        )) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
        } elseif (!$origin) {
            // Se não há origem (requisição direta), permite
            header('Access-Control-Allow-Origin: *');
        }
    } else {
        // Em produção, apenas origens explicitamente permitidas
        if ($origin && in_array($origin, $allowedOriginsArray)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
        } else {
            // Rejeita requisições de origens não permitidas
            // (não define header, o que faz o navegador bloquear)
        }
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400'); // Cache preflight por 24h
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
});

// ✅ NOVO: Middleware de versionamento de API (deve vir antes de autenticação)
$app->before('start', function() use ($app) {
    $apiVersionMiddleware = new \App\Middleware\ApiVersionMiddleware();
    $apiVersionMiddleware->handle();
});

// ✅ NOVO: Middleware de verificação de assinatura (deve vir após autenticação)
$app->before('start', function() use ($app) {
    $subscriptionMiddleware = new \App\Middleware\SubscriptionMiddleware();
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Verifica se deve aplicar o middleware nesta rota
    if (!$subscriptionMiddleware->shouldCheck($requestUri)) {
        return; // Rota excluída, não verifica
    }
    
    // Verifica assinatura
    $result = $subscriptionMiddleware->check();
    
    if ($result !== null) {
        // Bloqueia acesso
        $httpCode = $result['http_code'] ?? 402;
        
        // Se for requisição de API, retorna JSON
        if (strpos($requestUri, '/v1/') === 0) {
            $app->json([
                'error' => true,
                'message' => $result['message'],
                'code' => $result['code'] ?? 'SUBSCRIPTION_REQUIRED',
                'subscription_status' => $result['status'] ?? null
            ], $httpCode);
        } else {
            // Se for página web, redireciona para página de assinatura
            header('Location: /subscription-required?reason=' . urlencode($result['code']));
        }
        
        $app->stop();
        exit;
    }
});

// Middleware de autenticação (suporta API Key e Session ID)
$app->before('start', function() use ($app) {
    // Rotas públicas (sem autenticação)
    $publicRoutes = [
        '/', '/v1/webhook', '/health', '/health/detailed', 
        '/v1/auth/login', '/v1/auth/register', '/v1/auth/register-employee', '/v1/auth/csrf-token', // Rotas de autenticação públicas
        '/v1/saas-admin/login', '/v1/saas-admin/logout', // Rotas de login de administradores SaaS
            '/login', '/register', '/saas-admin/login', // Rotas de view de login
            '/index', '/checkout', '/success', '/cancel', '/api-docs', '/api-docs/ui',
            '/subscription-required', '/stripe-connect/success',
        // Rotas autenticadas (serão verificadas individualmente via getAuthenticatedUserData())
        '/dashboard', '/customers', '/subscriptions', '/products', '/prices', '/reports',
        '/users', '/permissions', '/audit-logs',
        '/customer-details', '/customer-invoices', '/subscription-details', '/subscription-history',
        '/product-details', '/price-details', '/user-details', '/invoice-details', '/coupon-details', // ✅ CORREÇÃO: Adicionadas rotas de detalhes
        '/invoices', '/refunds', '/coupons', '/promotion-codes', '/settings',
        '/transactions', '/transaction-details', '/disputes', '/charges', '/payouts',
        '/invoice-items', '/tax-rates', '/payment-methods', '/billing-portal',
        // Rotas de administração (verificam autenticação individualmente)
        '/traces', '/performance-metrics',
        // Rotas de Clínica Veterinária (verificam autenticação individualmente)
        '/clinic/dashboard', '/clinic/pets', '/clinic/professionals', '/clinic/specialties', '/clinic/professional-schedule', '/clinic/appointments', '/clinic/exams', '/clinic/budgets', '/clinic/commissions', '/clinic/reports', '/clinic/search', '/schedule', '/clinic-settings',
        // ✅ CORREÇÃO: Rota de métricas Stripe (view - verifica autenticação individualmente)
        '/stripe-metrics'
    ];
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Permite CSS e arquivos estáticos
    if (strpos($requestUri, '/css/') === 0 || strpos($requestUri, '/app/') === 0) {
        return;
    }
    
    // ✅ CORREÇÃO: Rotas de view (não começam com /v1/) não precisam de header Authorization
    // Elas usam getAuthenticatedUserData() internamente que pode ler de cookie ou query string
    if (strpos($requestUri, '/v1/') !== 0) {
        // É uma rota de view, não de API - permite passar (a rota verificará autenticação internamente)
        // Rotas públicas já estão na lista e serão permitidas
        if (in_array($requestUri, $publicRoutes) || strpos($requestUri, '/api-docs') === 0) {
            return;
        }
        // Se não está na lista de públicas, mas é uma view, permite passar
        // (a rota verificará autenticação via getAuthenticatedUserData())
        return;
    }
    
    // Para rotas de API (/v1/*), verifica se está na lista de públicas
    if (in_array($requestUri, $publicRoutes) || strpos($requestUri, '/api-docs') === 0) {
        return;
    }

    // Obtém o request do FlightPHP
    $request = $app->request();
    
    // Tenta obter o header Authorization de várias formas
    $authHeader = null;
    
    // Primeiro tenta getallheaders()
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    }
    
    // Fallback: verifica $_SERVER (formato HTTP_AUTHORIZATION)
    if (!$authHeader) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
    }
    
    // Se ainda não encontrou, verifica todas as variáveis $_SERVER que começam com HTTP_
    if (!$authHeader) {
        foreach ($_SERVER as $key => $value) {
            if (strtoupper($key) === 'HTTP_AUTHORIZATION' || strtoupper($key) === 'REDIRECT_HTTP_AUTHORIZATION') {
                $authHeader = $value;
                break;
            }
        }
    }
    
    // Se ainda não encontrou, tenta via FlightPHP request
    if (!$authHeader) {
        try {
            if (method_exists($request, 'getHeader')) {
                $authHeader = $request->getHeader('Authorization') ?? $request->getHeader('authorization');
            }
        } catch (\Exception $e) {
            // Ignora
        }
    }
    
    // Se não tem header, retorna erro
    if (!$authHeader) {
        // ✅ SEGURANÇA: Não expõe informações sensíveis mesmo em desenvolvimento
        $debug = Config::isDevelopment() ? [
            'server_keys_count' => count(array_keys($_SERVER)),
            'has_authorization' => isset($_SERVER['HTTP_AUTHORIZATION']),
            'request_uri' => $requestUri,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'all_headers_keys' => function_exists('getallheaders') ? array_keys(getallheaders() ?? []) : []
        ] : null;
        $app->json(['error' => 'Token de autenticação não fornecido', 'debug' => $debug], 401);
        $app->stop();
        exit;
    }
    
    // Extrai o Bearer token
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $app->json(['error' => 'Formato de token inválido'], 401);
        $app->stop();
        exit;
    }
    
    $token = trim($matches[1]);
    
    // ✅ CACHE: Verifica cache de autenticação (TTL: 5 minutos)
    $cacheKey = "auth:token:" . hash('sha256', $token);
    $cachedAuth = \App\Services\CacheService::getJson($cacheKey);
    
    if ($cachedAuth !== null) {
        // Usa dados do cache
        if (isset($cachedAuth['saas_admin_id'])) {
            // Autenticação via Session ID de administrador SaaS
            Flight::set('saas_admin_id', (int)$cachedAuth['saas_admin_id']);
            Flight::set('saas_admin_email', $cachedAuth['saas_admin_email']);
            Flight::set('saas_admin_name', $cachedAuth['saas_admin_name']);
            Flight::set('is_saas_admin', true);
            Flight::set('is_user_auth', false);
            Flight::set('is_master', false);
            Flight::set('tenant_id', null);
        } elseif (isset($cachedAuth['user_id'])) {
            // Autenticação via Session ID (usuário)
            Flight::set('user_id', (int)$cachedAuth['user_id']);
            Flight::set('user_role', $cachedAuth['user_role'] ?? 'viewer');
            Flight::set('user_email', $cachedAuth['user_email']);
            Flight::set('user_name', $cachedAuth['user_name']);
            Flight::set('tenant_id', (int)$cachedAuth['tenant_id']);
            Flight::set('tenant_name', $cachedAuth['tenant_name']);
            Flight::set('is_user_auth', true);
            Flight::set('is_master', false);
            Flight::set('is_saas_admin', false);
        } else {
            // Autenticação via API Key (tenant)
            Flight::set('tenant_id', (int)$cachedAuth['tenant_id']);
            Flight::set('tenant', $cachedAuth['tenant'] ?? null);
            Flight::set('is_master', $cachedAuth['is_master'] ?? false);
            Flight::set('is_user_auth', false);
            Flight::set('is_saas_admin', false);
        }
        return;
    }
    
    // Se não há cache, valida normalmente
    // Tenta primeiro como Session ID de administrador SaaS
    $saasAdminSessionModel = new \App\Models\SaasAdminSession();
    $saasAdminSession = $saasAdminSessionModel->validate($token);
    
    if ($saasAdminSession) {
        // Autenticação via Session ID de administrador SaaS
        $authData = [
            'saas_admin_id' => (int)$saasAdminSession['admin_id'],
            'saas_admin_email' => $saasAdminSession['email'],
            'saas_admin_name' => $saasAdminSession['name'],
            'is_saas_admin' => true,
            'is_user_auth' => false,
            'is_master' => false,
            'tenant_id' => null
        ];
        
        Flight::set('saas_admin_id', $authData['saas_admin_id']);
        Flight::set('saas_admin_email', $authData['saas_admin_email']);
        Flight::set('saas_admin_name', $authData['saas_admin_name']);
        Flight::set('is_saas_admin', true);
        Flight::set('is_user_auth', false);
        Flight::set('is_master', false);
        Flight::set('tenant_id', null);
        
        // ✅ Salva no cache
        \App\Services\CacheService::setJson($cacheKey, $authData, 300);
        return;
    }
    
    // Se não é admin SaaS, tenta como Session ID (usuário autenticado)
    $userSessionModel = new \App\Models\UserSession();
    $session = $userSessionModel->validate($token);
    
    if ($session) {
        // Autenticação via Session ID (usuário)
        $authData = [
            'user_id' => (int)$session['user_id'],
            'user_role' => $session['role'] ?? 'viewer',
            'user_email' => $session['email'],
            'user_name' => $session['name'],
            'tenant_id' => (int)$session['tenant_id'],
            'tenant_name' => $session['tenant_name'],
            'is_user_auth' => true,
            'is_master' => false,
            'is_saas_admin' => false
        ];
        
        Flight::set('user_id', $authData['user_id']);
        Flight::set('user_role', $authData['user_role']);
        Flight::set('user_email', $authData['user_email']);
        Flight::set('user_name', $authData['user_name']);
        Flight::set('tenant_id', $authData['tenant_id']);
        Flight::set('tenant_name', $authData['tenant_name']);
        Flight::set('is_user_auth', true);
        Flight::set('is_master', false);
        Flight::set('is_saas_admin', false);
        
        // ✅ Salva no cache
        \App\Services\CacheService::setJson($cacheKey, $authData, 300);
        return;
    }
    
    // Se não é Session ID, tenta como API Key (tenant)
    $tenantModel = new \App\Models\Tenant();
    $tenant = $tenantModel->findByApiKey($token);
    
    if (!$tenant) {
        // Verifica master key (usando hash_equals para prevenir timing attacks)
        $masterKey = Config::get('API_MASTER_KEY');
        if ($masterKey && hash_equals($masterKey, $token)) {
            $authData = [
                'tenant_id' => null,
                'is_master' => true,
                'is_user_auth' => false
            ];
            
            Flight::set('tenant_id', null);
            Flight::set('is_master', true);
            Flight::set('is_user_auth', false);
            
            // ✅ Salva no cache
            \App\Services\CacheService::setJson($cacheKey, $authData, 300);
            return;
        }
        
        // ✅ SEGURANÇA: Não expõe informações sensíveis sobre tokens mesmo em desenvolvimento
        $debug = Config::isDevelopment() ? [
            'token_length' => strlen($token),
            'token_format_valid' => preg_match('/^[a-zA-Z0-9]+$/', $token) === 1
        ] : null;
        $app->json(['error' => 'Token inválido', 'debug' => $debug], 401);
        $app->stop();
        exit;
    }
    
    if ($tenant['status'] !== 'active') {
        $app->json(['error' => 'Tenant inativo'], 401);
        $app->stop();
        exit;
    }

    // Autenticação via API Key (tenant)
    $authData = [
        'tenant_id' => (int)$tenant['id'],
        'tenant' => $tenant,
        'is_master' => false,
        'is_user_auth' => false
    ];
    
    Flight::set('tenant_id', (int)$tenant['id']);
    Flight::set('tenant', $tenant);
    Flight::set('is_master', false);
    Flight::set('is_user_auth', false);
    
    // ✅ Salva no cache
    \App\Services\CacheService::setJson($cacheKey, $authData, 300);
});

// Inicializa serviços (injeção de dependência)
$rateLimiterService = new \App\Services\RateLimiterService();
$tenantRateLimitModel = new \App\Models\TenantRateLimit();
$tenantRateLimitService = new \App\Services\TenantRateLimitService($tenantRateLimitModel);
$rateLimitMiddleware = new \App\Middleware\RateLimitMiddleware($rateLimiterService, $tenantRateLimitService);

// Inicializa middleware de validação de tamanho de payload
$payloadSizeMiddleware = new \App\Middleware\PayloadSizeMiddleware();

// ✅ TRACING: Inicializa middleware de tracing (deve ser o PRIMEIRO)
$tracingMiddleware = new \App\Middleware\TracingMiddleware();

// Inicializa middleware de auditoria
$auditLogModel = new \App\Models\AuditLog();
$auditMiddleware = new \App\Middleware\AuditMiddleware($auditLogModel);

// Inicializa middleware de performance
$performanceMiddleware = new \App\Middleware\PerformanceMiddleware();

// ✅ TRACING: Middleware de tracing (deve executar ANTES de outros middlewares)
$app->before('start', function() use ($tracingMiddleware) {
    $tracingMiddleware->before();
});

// ✅ SEGURANÇA: Middleware de proteção CSRF (antes de processar requisições de mutação)
$csrfMiddleware = new \App\Middleware\CsrfMiddleware();
$app->before('start', function() use ($csrfMiddleware) {
    $csrfMiddleware->validate();
});

// Middleware de Validação de Tamanho de Payload (antes de processar requisições)
$app->before('start', function() use ($payloadSizeMiddleware, $app) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Aplica apenas em métodos que podem ter payload
    if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
        // Endpoints críticos têm limite mais restritivo (512KB)
        $criticalEndpoints = [
            '/v1/customers',
            '/v1/subscriptions',
            '/v1/products',
            '/v1/prices',
            '/v1/auth/login',
            '/v1/users'
        ];
        
        $isCritical = false;
        foreach ($criticalEndpoints as $endpoint) {
            if (strpos($requestUri, $endpoint) === 0) {
                $isCritical = true;
                break;
            }
        }
        
        if ($isCritical) {
            $allowed = $payloadSizeMiddleware->checkStrict();
        } else {
            $allowed = $payloadSizeMiddleware->check();
        }
        
        if (!$allowed) {
            // Resposta já foi enviada pelo middleware
            $app->stop();
            exit;
        }
    }
});

// Middleware de Rate Limiting (após autenticação)
$app->before('start', function() use ($rateLimitMiddleware, $app) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    // Rotas públicas têm rate limiting mais restritivo (exceto webhook que tem limite maior)
    $publicRoutes = ['/', '/health', '/health/detailed'];
    
    if (in_array($requestUri, $publicRoutes)) {
        // Rate limit mais restritivo para rotas públicas
        $allowed = $rateLimitMiddleware->check($requestUri, [
            'limit' => 10,  // 10 requisições
            'window' => 60  // por minuto
        ]);
        
        if (!$allowed) {
            $app->stop();
            exit;
        }
        return;
    }
    
    // Webhook tem limite maior (200/min) mas ainda é controlado
    if ($requestUri === '/v1/webhook' && $method === 'POST') {
        // O middleware já aplica limite de 200/min para webhooks
        $allowed = $rateLimitMiddleware->check($requestUri);
        
        if (!$allowed) {
            $app->stop();
            exit;
        }
        return;
    }
    
    // Endpoints de criação têm limite mais baixo
    $createEndpoints = [
        '/v1/customers',
        '/v1/subscriptions',
        '/v1/products',
        '/v1/prices',
        '/v1/coupons',
        '/v1/promotion-codes',
        '/v1/payment-intents',
        '/v1/refunds',
        '/v1/invoice-items',
        '/v1/tax-rates',
        '/v1/setup-intents',
        '/v1/payouts'
    ];
    
    if ($method === 'POST' && in_array($requestUri, $createEndpoints)) {
        // Limite diferenciado por endpoint (o middleware já aplica)
        $allowed = $rateLimitMiddleware->check($requestUri);
        
        if (!$allowed) {
            $app->stop();
            exit;
        }
        return;
    }
    
    // Endpoints de atualização também têm limites específicos
    $updateEndpoints = [
        '/v1/customers',
        '/v1/subscriptions',
        '/v1/products',
        '/v1/prices',
        '/v1/coupons',
        '/v1/promotion-codes',
        '/v1/invoice-items',
        '/v1/tax-rates',
        '/v1/charges',
        '/v1/disputes'
    ];
    
    if ($method === 'PUT' && in_array($requestUri, $updateEndpoints)) {
        $allowed = $rateLimitMiddleware->check($requestUri);
        
        if (!$allowed) {
            $app->stop();
            exit;
        }
        return;
    }
    
    // Endpoints de exclusão têm limite ainda mais restritivo
    $deleteEndpoints = [
        '/v1/subscriptions',
        '/v1/products',
        '/v1/coupons',
        '/v1/invoice-items',
        '/v1/subscription-items',
        '/v1/customers/payment-methods'
    ];
    
    if ($method === 'DELETE' && in_array($requestUri, $deleteEndpoints)) {
        $allowed = $rateLimitMiddleware->check($requestUri);
        
        if (!$allowed) {
            $app->stop();
            exit;
        }
        return;
    }
    
    // Rate limit padrão para outros endpoints
    $allowed = $rateLimitMiddleware->check($requestUri);
    
    if (!$allowed) {
        // Rate limit excedido - resposta já foi enviada pelo middleware
        $app->stop();
        exit;
    }
});

// Middleware de Auditoria - Captura início da requisição
$app->before('start', function() use ($auditMiddleware) {
    $auditMiddleware->captureRequest();
});

// Middleware de Performance - Captura início da requisição
$app->before('start', function() use ($performanceMiddleware) {
    $performanceMiddleware->captureRequest();
});

// ✅ NOVO: Container de Injeção de Dependências
$container = new Container();
ContainerBindings::register($container);

// ✅ NOVO: Sistema de Eventos
use App\Core\EventDispatcher;
use App\Core\EventListeners;
$dispatcher = $container->make(EventDispatcher::class);
EventListeners::register($dispatcher, $container);
$app->set('event_dispatcher', $dispatcher);

// Inicializa serviços via container (singletons)
$stripeService = $container->make(\App\Services\StripeService::class);
$paymentService = $container->make(\App\Services\PaymentService::class);

// Inicializa controllers via container
$customerController = $container->make(\App\Controllers\CustomerController::class);
$checkoutController = $container->make(\App\Controllers\CheckoutController::class);
$saasController = $container->make(\App\Controllers\SaasController::class);
$stripeConnectController = $container->make(\App\Controllers\StripeConnectController::class);
$subscriptionController = $container->make(\App\Controllers\SubscriptionController::class);
$webhookController = $container->make(\App\Controllers\WebhookController::class);
$billingPortalController = $container->make(\App\Controllers\BillingPortalController::class);
$invoiceController = $container->make(\App\Controllers\InvoiceController::class);
$priceController = $container->make(\App\Controllers\PriceController::class);
$paymentController = $container->make(\App\Controllers\PaymentController::class);
$statsController = $container->make(\App\Controllers\StatsController::class);
$couponController = $container->make(\App\Controllers\CouponController::class);
$productController = $container->make(\App\Controllers\ProductController::class);
$promotionCodeController = $container->make(\App\Controllers\PromotionCodeController::class);
$setupIntentController = $container->make(\App\Controllers\SetupIntentController::class);
$subscriptionItemController = $container->make(\App\Controllers\SubscriptionItemController::class);
$taxRateController = $container->make(\App\Controllers\TaxRateController::class);
$invoiceItemController = $container->make(\App\Controllers\InvoiceItemController::class);
$balanceTransactionController = $container->make(\App\Controllers\BalanceTransactionController::class);
$disputeController = $container->make(\App\Controllers\DisputeController::class);
$chargeController = $container->make(\App\Controllers\ChargeController::class);
$payoutController = $container->make(\App\Controllers\PayoutController::class);
$reportController = $container->make(\App\Controllers\ReportController::class);
$auditLogController = $container->make(\App\Controllers\AuditLogController::class);
$performanceController = $container->make(\App\Controllers\PerformanceController::class);
$healthCheckController = $container->make(\App\Controllers\HealthCheckController::class);
$swaggerController = $container->make(\App\Controllers\SwaggerController::class);
$stripeMetricsController = $container->make(\App\Controllers\StripeMetricsController::class);
$planLimitsController = $container->make(\App\Controllers\PlanLimitsController::class);
$adminPlansController = new \App\Controllers\AdminPlansController();

// Rota raiz - informações da API
$app->route('GET /', function() use ($app) {
    $app->json([
        'name' => 'SaaS Payments API',
        'version' => '1.0.0',
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => Config::env(),
        'endpoints' => [
            'health' => '/health',
            'health_detailed' => '/health/detailed',
            'customers' => '/v1/customers',
            'checkout' => '/v1/checkout',
            'subscriptions' => '/v1/subscriptions',
            'webhook' => '/v1/webhook',
            'billing-portal' => '/v1/billing-portal',
            'invoices' => '/v1/invoices/:id',
            'prices' => '/v1/prices',
            'payment-intents' => '/v1/payment-intents',
            'refunds' => '/v1/refunds',
            'stats' => '/v1/stats',
            'coupons' => '/v1/coupons',
            'promotion-codes' => '/v1/promotion-codes',
            'setup-intents' => '/v1/setup-intents',
            'subscription-items' => '/v1/subscription-items',
            'balance-transactions' => '/v1/balance-transactions',
            'disputes' => '/v1/disputes',
            'payouts' => '/v1/payouts',
            'audit-logs' => '/v1/audit-logs',
            'reports' => '/v1/reports',
            'auth' => '/v1/auth',
            'users' => '/v1/users',
            'permissions' => '/v1/permissions',
            'clinic_pets' => '/v1/clinic/pets',
            'clinic_professionals' => '/v1/clinic/professionals',
            'clinic_appointments' => '/v1/clinic/appointments'
        ],
        'documentation' => 'Consulte o README.md para mais informações'
    ]);
});

// Rotas de documentação Swagger/OpenAPI (públicas)
$app->route('GET /api-docs', [$swaggerController, 'getSpec']);
$app->route('GET /api-docs/ui', [$swaggerController, 'getUI']);

// Rotas de Health Check
$app->route('GET /health', [$healthCheckController, 'basic']);
$app->route('GET /health/detailed', [$healthCheckController, 'detailed']);

// Rota de debug (apenas em desenvolvimento)
if (Config::isDevelopment()) {
    $app->route('GET /debug', function() use ($app) {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        
        $httpHeaders = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $httpHeaders[$key] = $value;
            }
        }
        
        $app->json([
            'getallheaders_exists' => function_exists('getallheaders'),
            'headers_from_getallheaders' => $headers,
            'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NOT SET',
            'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT SET',
            'all_http_headers' => $httpHeaders
        ]);
    });
}

// Rotas de clientes
$app->route('POST /v1/customers', [$customerController, 'create']);
$app->route('GET /v1/customers', [$customerController, 'list']);
$app->route('GET /v1/customers/@id', [$customerController, 'get']);
$app->route('PUT /v1/customers/@id', [$customerController, 'update']);
$app->route('GET /v1/customers/@id/subscriptions', [$customerController, 'listSubscriptions']);
$app->route('GET /v1/customers/@id/invoices', [$customerController, 'listInvoices']);
$app->route('GET /v1/customers/@id/payment-methods', [$customerController, 'listPaymentMethods']);
$app->route('PUT /v1/customers/@id/payment-methods/@pm_id', [$customerController, 'updatePaymentMethod']);
$app->route('DELETE /v1/customers/@id/payment-methods/@pm_id', [$customerController, 'deletePaymentMethod']);
$app->route('POST /v1/customers/@id/payment-methods/@pm_id/set-default', [$customerController, 'setDefaultPaymentMethod']);
$app->route('POST /v1/customers/@id/payment-methods/remove-expired', [$customerController, 'removeExpiredPaymentMethods']);

// Rotas de checkout
$app->route('POST /v1/checkout', [$checkoutController, 'create']);
$app->route('GET /v1/checkout/@id', [$checkoutController, 'get']);

// Rotas SaaS - Planos e checkout para assinatura do SaaS
$app->route('GET /v1/saas/plans', [$saasController, 'listPlans']);
$app->route('POST /v1/saas/checkout', [$saasController, 'createCheckout']);

// Rotas Stripe Connect
$app->route('POST /v1/stripe-connect/onboarding', [$stripeConnectController, 'createOnboarding']);
$app->route('GET /v1/stripe-connect/account', [$stripeConnectController, 'getAccount']);
$app->route('POST /v1/stripe-connect/api-key', [$stripeConnectController, 'saveApiKey']);
$app->route('POST /v1/stripe-connect/login-link', [$stripeConnectController, 'createLoginLink']);
$app->route('GET /v1/stripe-connect/balance', [$stripeConnectController, 'getBalance']);
$app->route('GET /v1/stripe-connect/transfers', [$stripeConnectController, 'listTransfers']);

// Rotas de assinaturas
$app->route('POST /v1/subscriptions', [$subscriptionController, 'create']);
$app->route('GET /v1/subscriptions', [$subscriptionController, 'list']);
$app->route('GET /v1/subscriptions/current', [$subscriptionController, 'getCurrent']);
$app->route('GET /v1/subscriptions/@id', [$subscriptionController, 'get']);
$app->route('PUT /v1/subscriptions/@id', [$subscriptionController, 'update']);
$app->route('DELETE /v1/subscriptions/@id', [$subscriptionController, 'cancel']);
$app->route('POST /v1/subscriptions/@id/reactivate', [$subscriptionController, 'reactivate']);
$app->route('POST /v1/subscriptions/@id/schedule-plan-change', [$subscriptionController, 'schedulePlanChange']);
$app->route('POST /v1/subscriptions/@id/pause', [$subscriptionController, 'pause']);
$app->route('POST /v1/subscriptions/@id/resume', [$subscriptionController, 'resume']);
$app->route('GET /v1/subscriptions/@id/history', [$subscriptionController, 'history']);
$app->route('GET /v1/subscriptions/@id/history/stats', [$subscriptionController, 'historyStats']);

// Rotas de Limites de Planos e Módulos
$app->route('GET /v1/plan-limits', [$planLimitsController, 'getAll']);
$app->route('GET /v1/plan-limits/plans', [$planLimitsController, 'getAllPlans']);
$app->route('GET /v1/plan-limits/modules', [$planLimitsController, 'getAvailableModules']);
$app->route('GET /v1/plan-limits/check-module/@moduleId', [$planLimitsController, 'checkModule']);

// Rotas de Administradores do SaaS
$saasAdminController = new \App\Controllers\SaasAdminController();
$app->route('POST /v1/saas-admin/login', [$saasAdminController, 'login']);
$app->route('POST /v1/saas-admin/logout', [$saasAdminController, 'logout']);
$app->route('GET /v1/saas-admin/admins', [$saasAdminController, 'listAdmins']);
$app->route('POST /v1/saas-admin/admins', [$saasAdminController, 'createAdmin']);
$app->route('PUT /v1/saas-admin/admins/@id', [$saasAdminController, 'updateAdmin']);
$app->route('DELETE /v1/saas-admin/admins/@id', [$saasAdminController, 'deleteAdmin']);

// Rotas Administrativas - Gerenciar Planos e Módulos
$adminPlansController = new \App\Controllers\AdminPlansController();
$app->route('GET /v1/admin/plans', [$adminPlansController, 'listPlans']);
$app->route('GET /v1/admin/plans/@id', [$adminPlansController, 'getPlan']);
$app->route('POST /v1/admin/plans', [$adminPlansController, 'createPlan']);
$app->route('PUT /v1/admin/plans/@id', [$adminPlansController, 'updatePlan']);
$app->route('DELETE /v1/admin/plans/@id', [$adminPlansController, 'deletePlan']);
$app->route('GET /v1/admin/modules', [$adminPlansController, 'listModules']);
$app->route('POST /v1/admin/modules', [$adminPlansController, 'createModule']);
$app->route('PUT /v1/admin/modules/@id', [$adminPlansController, 'updateModule']);
$app->route('DELETE /v1/admin/modules/@id', [$adminPlansController, 'deleteModule']);

// Rota de webhook
$app->route('POST /v1/webhook', [$webhookController, 'handle']);

// Rota de portal de cobrança
$app->route('POST /v1/billing-portal', [$billingPortalController, 'create']);

// Rotas de faturas
$app->route('GET /v1/invoices', [$invoiceController, 'list']);
$app->route('GET /v1/invoices/@id', [$invoiceController, 'get']);

// Rotas de preços
$app->route('GET /v1/prices', [$priceController, 'list']);
$app->route('POST /v1/prices', [$priceController, 'create']);
$app->route('GET /v1/prices/@id', [$priceController, 'get']);
$app->route('PUT /v1/prices/@id', [$priceController, 'update']);

// Rotas de produtos
$app->route('GET /v1/products', [$productController, 'list']);
$app->route('POST /v1/products', [$productController, 'create']);
$app->route('GET /v1/products/@id', [$productController, 'get']);
$app->route('PUT /v1/products/@id', [$productController, 'update']);
$app->route('DELETE /v1/products/@id', [$productController, 'delete']);
$app->route('DELETE /v1/prices/@id', [$priceController, 'delete']);

// Rotas de pagamentos
$app->route('POST /v1/payment-intents', [$paymentController, 'createPaymentIntent']);
$app->route('POST /v1/refunds', [$paymentController, 'createRefund']);

// Rotas de estatísticas
$app->route('GET /v1/stats', [$statsController, 'get']);

// Rotas de cupons
$app->route('POST /v1/coupons', [$couponController, 'create']);
$app->route('GET /v1/coupons', [$couponController, 'list']);
$app->route('GET /v1/coupons/@id', [$couponController, 'get']);
$app->route('PUT /v1/coupons/@id', [$couponController, 'update']);
$app->route('DELETE /v1/coupons/@id', [$couponController, 'delete']);

// Rotas de códigos promocionais
$app->route('POST /v1/promotion-codes', [$promotionCodeController, 'create']);
$app->route('GET /v1/promotion-codes', [$promotionCodeController, 'list']);
$app->route('GET /v1/promotion-codes/@id', [$promotionCodeController, 'get']);
$app->route('PUT /v1/promotion-codes/@id', [$promotionCodeController, 'update']);

// Rotas de Setup Intents
$app->route('POST /v1/setup-intents', [$setupIntentController, 'create']);
$app->route('GET /v1/setup-intents/@id', [$setupIntentController, 'get']);
$app->route('POST /v1/setup-intents/@id/confirm', [$setupIntentController, 'confirm']);

// Rotas de Subscription Items
$app->route('POST /v1/subscriptions/@subscription_id/items', [$subscriptionItemController, 'create']);
$app->route('GET /v1/subscriptions/@subscription_id/items', [$subscriptionItemController, 'list']);
$app->route('GET /v1/subscription-items/@id', [$subscriptionItemController, 'get']);
$app->route('PUT /v1/subscription-items/@id', [$subscriptionItemController, 'update']);
$app->route('DELETE /v1/subscription-items/@id', [$subscriptionItemController, 'delete']);

// Rotas de Tax Rates
$app->route('POST /v1/tax-rates', [$taxRateController, 'create']);
$app->route('GET /v1/tax-rates', [$taxRateController, 'list']);
$app->route('GET /v1/tax-rates/@id', [$taxRateController, 'get']);
$app->route('PUT /v1/tax-rates/@id', [$taxRateController, 'update']);

// Rotas de Invoice Items
$app->route('POST /v1/invoice-items', [$invoiceItemController, 'create']);
$app->route('GET /v1/invoice-items', [$invoiceItemController, 'list']);
$app->route('GET /v1/invoice-items/@id', [$invoiceItemController, 'get']);
$app->route('PUT /v1/invoice-items/@id', [$invoiceItemController, 'update']);
$app->route('DELETE /v1/invoice-items/@id', [$invoiceItemController, 'delete']);

// Rotas de Balance Transactions
$app->route('GET /v1/balance-transactions', [$balanceTransactionController, 'list']);
$app->route('GET /v1/balance-transactions/@id', [$balanceTransactionController, 'get']);

// Rotas de Disputes
$app->route('GET /v1/disputes', [$disputeController, 'list']);
$app->route('GET /v1/disputes/@id', [$disputeController, 'get']);
$app->route('PUT /v1/disputes/@id', [$disputeController, 'update']);

// Rotas de Charges
$app->route('GET /v1/charges', [$chargeController, 'list']);
$app->route('GET /v1/charges/@id', [$chargeController, 'get']);
$app->route('PUT /v1/charges/@id', [$chargeController, 'update']);

// Rotas de Payouts
$app->route('GET /v1/payouts', [$payoutController, 'list']);
$app->route('GET /v1/payouts/@id', [$payoutController, 'get']);
$app->route('POST /v1/payouts', [$payoutController, 'create']);
$app->route('POST /v1/payouts/@id/cancel', [$payoutController, 'cancel']);

// Rotas de Audit Logs
$app->route('GET /v1/audit-logs', [$auditLogController, 'list']);
$app->route('GET /v1/audit-logs/@id', [$auditLogController, 'get']);

// Rotas de Métricas de Performance
$app->route('GET /v1/metrics/performance', [$performanceController, 'list']);
$app->route('GET /v1/metrics/performance/alerts', [$performanceController, 'alerts']);
$app->route('GET /v1/metrics/performance/slowest', [$performanceController, 'slowest']);

// ✅ TRACING: Rotas de Tracing de Requisições
$traceController = $container->make(\App\Controllers\TraceController::class);
$app->route('GET /v1/traces/@request_id', [$traceController, 'get']);
$app->route('GET /v1/traces/search', [$traceController, 'search']);

// Rotas de Relatórios e Analytics
$app->route('GET /v1/reports/revenue', [$reportController, 'revenue']);
$app->route('GET /v1/reports/subscriptions', [$reportController, 'subscriptions']);
$app->route('GET /v1/reports/churn', [$reportController, 'churn']);
$app->route('GET /v1/reports/customers', [$reportController, 'customers']);
$app->route('GET /v1/reports/payments', [$reportController, 'payments']);
$app->route('GET /v1/reports/mrr', [$reportController, 'mrr']);
$app->route('GET /v1/reports/arr', [$reportController, 'arr']);

// ✅ NOVO: Rotas de Métricas Stripe
$app->route('GET /v1/stripe-metrics', [$stripeMetricsController, 'getAll']);
$app->route('GET /v1/stripe-metrics/mrr', [$stripeMetricsController, 'getMRR']);
$app->route('GET /v1/stripe-metrics/churn', [$stripeMetricsController, 'getChurn']);
$app->route('GET /v1/stripe-metrics/conversion', [$stripeMetricsController, 'getConversion']);
$app->route('GET /v1/stripe-metrics/arr', [$stripeMetricsController, 'getARR']);
$app->route('GET /v1/stripe-metrics/alerts', [$stripeMetricsController, 'getAlerts']);
$app->route('GET /v1/stripe-metrics/critical-failures', [$stripeMetricsController, 'getCriticalFailures']);

// Rota de página de login (HTML)
$app->route('GET /login', function() use ($app) {
    // Detecta URL base automaticamente
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove query string e fragmentos da URL
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $basePath = dirname($requestUri);
    
    // Constrói URL base
    if ($scriptName === '/' || $scriptName === '\\') {
        $baseUrl = $protocol . '://' . $host;
    } else {
        $baseUrl = $protocol . '://' . $host . rtrim($scriptName, '/');
    }
    
    $apiUrl = rtrim($baseUrl, '/');
    
    // Renderiza a view
    \App\Utils\View::render('login', ['apiUrl' => $apiUrl]);
});

// Rota de página de login para administradores SaaS (master)
$app->route('GET /saas-admin/login', function() use ($app) {
    // Detecta URL base automaticamente
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove query string e fragmentos da URL
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $basePath = dirname($requestUri);
    
    // Constrói URL base
    if ($scriptName === '/' || $scriptName === '\\') {
        $baseUrl = $protocol . '://' . $host;
    } else {
        $baseUrl = $protocol . '://' . $host . rtrim($scriptName, '/');
    }
    
    $apiUrl = rtrim($baseUrl, '/');
    
    // Renderiza a view de login de administrador SaaS
    \App\Utils\View::render('saas-admin-login', ['apiUrl' => $apiUrl]);
});

// Helper para obter dados do usuário autenticado
function getAuthenticatedUserData() {
    $sessionId = null;
    $headers = [];
    
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    } else {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$headerName] = $value;
            }
        }
    }
    
    // Tenta obter session_id do header Authorization
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $sessionId = trim($matches[1]);
    }
    
    // Fallback: tenta obter de cookie (se houver)
    if (!$sessionId && isset($_COOKIE['session_id'])) {
        $sessionId = $_COOKIE['session_id'];
    }
    
    // Fallback: tenta obter de query string (para desenvolvimento)
    if (!$sessionId && isset($_GET['session_id'])) {
        $sessionId = $_GET['session_id'];
    }
    
    // Tenta obter session_id de administrador SaaS (cookie ou query string)
    $saasAdminSessionId = null;
    if (isset($_COOKIE['saas_admin_session_id'])) {
        $saasAdminSessionId = $_COOKIE['saas_admin_session_id'];
    } elseif (isset($_GET['saas_admin_session_id'])) {
        $saasAdminSessionId = $_GET['saas_admin_session_id'];
    }
    
    $user = null;
    $tenant = null;
    
    // Prioriza verificação de administrador SaaS se houver session_id específico
    if ($saasAdminSessionId) {
        $saasAdminSessionModel = new \App\Models\SaasAdminSession();
        $saasAdminSession = $saasAdminSessionModel->validate($saasAdminSessionId);
        
        if ($saasAdminSession) {
            // É um administrador SaaS
            $user = [
                'id' => (int)$saasAdminSession['admin_id'],
                'email' => $saasAdminSession['email'],
                'name' => $saasAdminSession['name'],
                'role' => 'saas_admin',
                'is_saas_admin' => true
            ];
            $tenant = null; // Administradores SaaS não têm tenant
            Flight::set('is_saas_admin', true);
            Flight::set('saas_admin_id', (int)$saasAdminSession['admin_id']);
            return [$user, $tenant, $saasAdminSessionId];
        }
    }
    
    // Se não é admin SaaS, tenta verificar sessão normal
    if ($sessionId) {
        // Primeiro tenta verificar se é sessão de administrador SaaS (caso sessionId seja usado para ambos)
        $saasAdminSessionModel = new \App\Models\SaasAdminSession();
        $saasAdminSession = $saasAdminSessionModel->validate($sessionId);
        
        if ($saasAdminSession) {
            // É um administrador SaaS
            $user = [
                'id' => (int)$saasAdminSession['admin_id'],
                'email' => $saasAdminSession['email'],
                'name' => $saasAdminSession['name'],
                'role' => 'saas_admin',
                'is_saas_admin' => true
            ];
            $tenant = null; // Administradores SaaS não têm tenant
            Flight::set('is_saas_admin', true);
            Flight::set('saas_admin_id', (int)$saasAdminSession['admin_id']);
        } else {
            // Tenta verificar se é sessão de usuário normal
            $userSessionModel = new \App\Models\UserSession();
            $session = $userSessionModel->validate($sessionId);
            
            if ($session) {
                $user = [
                    'id' => (int)$session['user_id'],
                    'email' => $session['email'],
                    'name' => $session['name'],
                    'role' => $session['role'] ?? 'viewer',
                    'is_saas_admin' => false
                ];
                $tenant = [
                    'id' => (int)$session['tenant_id'],
                    'name' => $session['tenant_name']
                ];
                Flight::set('is_saas_admin', false);
            }
        }
    }
    
    return [$user, $tenant, $sessionId];
}

// Helper para verificar se é administrador SaaS e mostrar view de não disponível
function checkSaasAdminAndRender($user, $apiUrl, $tenant, $title, $currentPage, $viewName, $isClinicFeature = true) {
    if ($user && ($user['is_saas_admin'] ?? false)) {
        $viewToRender = $isClinicFeature ? 'clinic-not-available' : 'not-available-for-saas';
        \App\Utils\View::render($viewToRender, [
            'apiUrl' => $apiUrl,
            'user' => $user,
            'tenant' => $tenant,
            'title' => $title . ' - Não Disponível',
            'currentPage' => $currentPage
        ], true);
        return true;
    }
    return false;
}

// Helper para verificar acesso a módulo e mostrar view de não disponível se necessário
function checkModuleAccessAndRender($user, $apiUrl, $tenant, $title, $currentPage, $moduleId) {
    $tenantId = $tenant['id'] ?? null;
    if (!$tenantId) {
        return false; // Deixa outros middlewares tratarem
    }

    $moduleMiddleware = new \App\Middleware\ModuleAccessMiddleware();
    $check = $moduleMiddleware->check($moduleId);
    
    if ($check) {
        // Módulo não disponível - mostra página de upgrade
        \App\Utils\View::render('module-not-available', [
            'apiUrl' => $apiUrl,
            'user' => $user,
            'tenant' => $tenant,
            'title' => 'Módulo Não Disponível',
            'currentPage' => $currentPage,
            'moduleName' => $check['module_name'] ?? ucfirst($moduleId),
            'currentPlan' => $check['current_plan'] ?? 'seu plano atual',
            'upgradeUrl' => $check['upgrade_url'] ?? '/my-subscription'
        ], true);
        return true;
    }
    
    return false;
}

// Helper para detectar URL base
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    
    if ($scriptName === '/' || $scriptName === '\\') {
        return $protocol . '://' . $host;
    }
    
    return $protocol . '://' . $host . rtrim($scriptName, '/');
}

// Rota de dashboard (requer autenticação)
$app->route('GET /dashboard', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    // Se não houver usuário autenticado, redireciona para login
    // O JavaScript também fará essa verificação, mas isso garante no servidor
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('dashboard', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Dashboard',
        'currentPage' => 'dashboard'
    ], true);
});

// Rota de clientes
$app->route('GET /customers', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('customers', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Clientes',
        'currentPage' => 'customers'
    ], true);
});

// Rota de assinaturas
$app->route('GET /subscriptions', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('subscriptions', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Assinaturas',
        'currentPage' => 'subscriptions'
    ], true);
});

// Rota de produtos
$app->route('GET /products', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('products', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Produtos',
        'currentPage' => 'products'
    ], true);
});

// Rota de preços
$app->route('GET /prices', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('prices', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Preços',
        'currentPage' => 'prices'
    ], true);
});

// Rota de relatórios
$app->route('GET /reports', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('reports', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Relatórios',
        'currentPage' => 'reports'
    ], true);
});

// Rota de usuários (apenas admin)
$app->route('GET /users', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        $app->json(['error' => 'Acesso negado'], 403);
        return;
    }
    
    \App\Utils\View::render('users', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Usuários',
        'currentPage' => 'users'
    ], true);
});

// Rota de permissões (apenas admin)
$app->route('GET /permissions', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        $app->json(['error' => 'Acesso negado'], 403);
        return;
    }
    
    \App\Utils\View::render('permissions', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Permissões',
        'currentPage' => 'permissions'
    ], true);
});

// Rota de logs de auditoria (apenas admin)
$app->route('GET /audit-logs', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        $app->json(['error' => 'Acesso negado'], 403);
        return;
    }
    
    \App\Utils\View::render('audit-logs', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Logs de Auditoria',
        'currentPage' => 'audit-logs'
    ], true);
});

// ✅ TRACING: Rota de tracing de requisições (requer permissão view_audit_logs)
$app->route('GET /traces', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('traces', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Tracing de Requisições',
        'currentPage' => 'traces'
    ], true);
});

// Rota de métricas de performance (requer permissão)
$app->route('GET /performance-metrics', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    // Verifica permissão (será verificado no frontend também)
    \App\Utils\View::render('performance-metrics', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Métricas de Performance',
        'currentPage' => 'performance-metrics'
    ], true);
});

// ✅ NOVO: Rota de dashboard de métricas Stripe (requer permissão)
$app->route('GET /stripe-metrics', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    // Verifica permissão (será verificado no frontend também)
    \App\Utils\View::render('stripe-metrics', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Dashboard de Métricas Stripe',
        'currentPage' => 'stripe-metrics'
    ], true);
});

// Rotas de Clínica Veterinária (Views)
$app->route('GET /clinic/pets', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Pets', 'clinic-pets', 'clinic/pets')) {
        return;
    }
    
    // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "pets"
    if (checkModuleAccessAndRender($user, $apiUrl, $tenant, 'Pets', 'clinic-pets', 'pets')) {
        return;
    }
    
    \App\Utils\View::render('clinic/pets', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Pets',
        'currentPage' => 'clinic-pets'
    ], true);
});

$app->route('GET /clinic/professionals', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Profissionais', 'clinic-professionals', 'clinic/professionals')) {
        return;
    }
    
    \App\Utils\View::render('clinic/professionals', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Profissionais',
        'currentPage' => 'clinic-professionals'
    ], true);
});

$app->route('GET /clinic/appointments', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Agendamentos', 'clinic-appointments', 'clinic/appointments')) {
        return;
    }
    
    // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "appointments"
    if (checkModuleAccessAndRender($user, $apiUrl, $tenant, 'Agendamentos', 'clinic-appointments', 'appointments')) {
        return;
    }
    
    \App\Utils\View::render('clinic/appointments', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Agendamentos',
        'currentPage' => 'clinic-appointments'
    ], true);
});

$app->route('GET /clinic/exams', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Exames', 'clinic-exams', 'clinic/exams')) {
        return;
    }
    
    // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "exams"
    if (checkModuleAccessAndRender($user, $apiUrl, $tenant, 'Exames', 'clinic-exams', 'exams')) {
        return;
    }
    
    \App\Utils\View::render('clinic/exams', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Exames',
        'currentPage' => 'clinic-exams'
    ], true);
});

$app->route('GET /clinic/budgets', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Orçamentos', 'clinic-budgets', 'clinic/budgets')) {
        return;
    }
    
    // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial" (orçamentos fazem parte do financeiro)
    if (checkModuleAccessAndRender($user, $apiUrl, $tenant, 'Orçamentos', 'clinic-budgets', 'financial')) {
        return;
    }
    
    \App\Utils\View::render('clinic/budgets', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Orçamentos',
        'currentPage' => 'clinic-budgets'
    ], true);
});

$app->route('GET /clinic/commissions', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Comissões', 'clinic-commissions', 'clinic/commissions')) {
        return;
    }
    
    // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "financial" (comissões fazem parte do financeiro)
    if (checkModuleAccessAndRender($user, $apiUrl, $tenant, 'Comissões', 'clinic-commissions', 'financial')) {
        return;
    }
    
    \App\Utils\View::render('clinic/commissions', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Comissões',
        'currentPage' => 'clinic-commissions'
    ], true);
});

$app->route('GET /clinic/specialties', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Especialidades', 'clinic-specialties', 'clinic/specialties')) {
        return;
    }
    
    \App\Utils\View::render('clinic/specialties', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Especialidades',
        'currentPage' => 'clinic-specialties'
    ], true);
});

$app->route('GET /clinic/dashboard', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Dashboard Clínica', 'clinic-dashboard', 'clinic/dashboard')) {
        return;
    }
    
    \App\Utils\View::render('clinic/dashboard', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Dashboard da Clínica',
        'currentPage' => 'clinic-dashboard'
    ], true);
});

$app->route('GET /clinic/professional-schedule', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Agenda de Profissionais', 'clinic-professional-schedule', 'clinic/professional-schedule')) {
        return;
    }
    
    // ✅ NOVO: Verifica se o tenant tem acesso ao módulo "appointments" (agenda de profissionais faz parte de appointments)
    if (checkModuleAccessAndRender($user, $apiUrl, $tenant, 'Agenda de Profissionais', 'clinic-professional-schedule', 'appointments')) {
        return;
    }
    
    \App\Utils\View::render('clinic/professional-schedule', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Agenda de Profissionais',
        'currentPage' => 'clinic-professional-schedule'
    ], true);
});


// Rota de registro (pública)
$app->route('GET /register', function() use ($app) {
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('register', ['apiUrl' => $apiUrl]);
});

// Rota de assinatura necessária (pública)
$app->route('GET /subscription-required', function() use ($app) {
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('subscription-required', ['apiUrl' => $apiUrl]);
});

// Rota de escolha de plano (requer autenticação)
// Rota de Meus Módulos
$app->route('GET /my-modules', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    // ✅ CORREÇÃO: Verifica se é SaaS admin e mostra mensagem "Disponível apenas para clínicas"
    // SaaS admins não têm módulos de plano, eles gerenciam os módulos que vendem. Esta página é para clínicas verem seus módulos.
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Meus Módulos', 'my-modules', 'my-modules', true)) {
        return;
    }
    
    \App\Utils\View::render('my-modules', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Meus Módulos',
        'currentPage' => 'my-modules'
    ], true);
});

// Rota Administrativa - Gerenciar Planos e Módulos
$app->route('GET /admin-plans', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    // Debug: log dos dados recebidos
    \App\Services\Logger::debug("Acesso a /admin-plans", [
        'user' => $user,
        'tenant' => $tenant,
        'session_id' => $sessionId ? substr($sessionId, 0, 20) . '...' : null,
        'is_saas_admin' => $user['is_saas_admin'] ?? false,
        'flight_is_saas_admin' => Flight::get('is_saas_admin')
    ]);
    
    if (!$user) {
        \App\Services\Logger::warning("Tentativa de acesso a /admin-plans sem autenticação");
        header('Location: /saas-admin/login');
        exit;
    }
    
    // Verifica se é administrador SaaS
    if (!($user['is_saas_admin'] ?? false)) {
        \App\Services\Logger::warning("Tentativa de acesso a /admin-plans sem ser saas_admin", [
            'user_role' => $user['role'] ?? 'unknown'
        ]);
        $app->json(['error' => 'Acesso negado. Apenas administradores do SaaS podem acessar.'], 403);
        return;
    }
    
    \App\Utils\View::render('admin-plans', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Gerenciar Planos e Módulos',
        'currentPage' => 'admin-plans'
    ], true);
});

// Rota de sucesso após assinatura (requer autenticação)
$app->route('GET /subscription-success', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    $sessionId = $_GET['session_id'] ?? null;
    
    \App\Utils\View::render('subscription-success', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'session_id' => $sessionId
    ]);
});

// Rota de index/planos (pública)
$app->route('GET /index', function() use ($app) {
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('index', ['apiUrl' => $apiUrl]);
});

// Rota de checkout (pública)
$app->route('GET /checkout', function() use ($app) {
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('checkout', ['apiUrl' => $apiUrl]);
});

// Rota de success (pública)
$app->route('GET /success', function() use ($app) {
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('success', ['apiUrl' => $apiUrl]);
});

// Rota de cancel (pública)
$app->route('GET /cancel', function() use ($app) {
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('cancel', ['apiUrl' => $apiUrl]);
});


// Rota de agenda
$app->route('GET /schedule', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Calendário', 'schedule', 'schedule')) {
        return;
    }
    
    \App\Utils\View::render('schedule', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Agenda', 'currentPage' => 'schedule'
    ], true);
});


// Rota de especialidades
$app->route('GET /specialties', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('specialties', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Especialidades', 'currentPage' => 'specialties'
    ], true);
});

// Rota de detalhes do cliente
$app->route('GET /customer-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('customer-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes do Cliente', 'currentPage' => 'customers'
    ], true);
});

// Rota de faturas do cliente
$app->route('GET /customer-invoices', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('customer-invoices', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Faturas do Cliente', 'currentPage' => 'customers'
    ], true);
});

// Rota de detalhes da assinatura
$app->route('GET /subscription-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('subscription-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes da Assinatura', 'currentPage' => 'subscriptions'
    ], true);
});

// Rota de detalhes do produto
$app->route('GET /product-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('product-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes do Produto', 'currentPage' => 'products'
    ], true);
});

// Rota de detalhes do preço
$app->route('GET /price-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('price-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes do Preço', 'currentPage' => 'prices'
    ], true);
});

// Rota de detalhes do cupom
$app->route('GET /coupon-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('coupon-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes do Cupom', 'currentPage' => 'coupons'
    ], true);
});

// Rota de detalhes do usuário
$app->route('GET /user-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        $app->json(['error' => 'Acesso negado'], 403);
        return;
    }
    
    \App\Utils\View::render('user-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes do Usuário', 'currentPage' => 'users'
    ], true);
});

// Rota de detalhes da fatura
$app->route('GET /invoice-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('invoice-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes da Fatura', 'currentPage' => 'invoices'
    ], true);
});

// Rota de faturas
$app->route('GET /invoices', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('invoices', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Faturas', 'currentPage' => 'invoices'
    ], true);
});

// Rota de reembolsos
$app->route('GET /refunds', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('refunds', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Reembolsos', 'currentPage' => 'refunds'
    ], true);
});

// Rota de cupons
$app->route('GET /coupons', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('coupons', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Cupons', 'currentPage' => 'coupons'
    ], true);
});

// Rota de códigos promocionais
$app->route('GET /promotion-codes', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('promotion-codes', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Códigos Promocionais', 'currentPage' => 'promotion-codes'
    ], true);
});

// Rota de configurações
$app->route('GET /settings', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('settings', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Configurações', 'currentPage' => 'settings'
    ], true);
});

// Rota de histórico de assinaturas
$app->route('GET /subscription-history', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('subscription-history', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Histórico de Assinaturas', 'currentPage' => 'subscriptions'
    ], true);
});

// Rota de transações
$app->route('GET /transactions', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('transactions', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Transações', 'currentPage' => 'transactions'
    ], true);
});

// Rota de detalhes da transação
$app->route('GET /transaction-details', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    \App\Utils\View::render('transaction-details', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Detalhes da Transação', 'currentPage' => 'transactions'
    ], true);
});

// Rota de disputas
$app->route('GET /disputes', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('disputes', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Disputas', 'currentPage' => 'disputes'
    ], true);
});

// Rota de cobranças
$app->route('GET /charges', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('charges', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Cobranças', 'currentPage' => 'charges'
    ], true);
});

// Rota de saques
$app->route('GET /payouts', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('payouts', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Saques', 'currentPage' => 'payouts'
    ], true);
});

// Rota de itens de fatura
$app->route('GET /invoice-items', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('invoice-items', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Itens de Fatura', 'currentPage' => 'invoice-items'
    ], true);
});

// Rota de taxas de imposto
$app->route('GET /tax-rates', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('tax-rates', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Taxas de Imposto', 'currentPage' => 'tax-rates'
    ], true);
});

// Rota de métodos de pagamento
$app->route('GET /payment-methods', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('payment-methods', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Métodos de Pagamento', 'currentPage' => 'payment-methods'
    ], true);
});

// Rota de portal de cobrança
$app->route('GET /billing-portal', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    \App\Utils\View::render('billing-portal', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Portal de Cobrança', 'currentPage' => 'billing-portal'
    ], true);
});

// Rota de Minha Assinatura (para o tenant gerenciar sua própria assinatura)
$app->route('GET /my-subscription', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    // ✅ CORREÇÃO: Verifica se é SaaS admin e mostra mensagem "Disponível apenas para clínicas"
    // SaaS admins não assinam planos, eles vendem planos. Esta página é para clínicas gerenciarem suas assinaturas.
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Minha Assinatura', 'my-subscription', 'my-subscription', true)) {
        return;
    }
    
    \App\Utils\View::render('my-subscription', [
        'apiUrl' => $apiUrl,
        'user' => $user,
        'tenant' => $tenant,
        'title' => 'Minha Assinatura',
        'currentPage' => 'my-subscription'
    ], true);
});

// Rotas de Autenticação (públicas - não precisam de autenticação)
$authController = $container->make(\App\Controllers\AuthController::class);
$app->route('POST /v1/auth/register', [$authController, 'register']); // Registro de tenant e primeiro usuário
$app->route('POST /v1/auth/register-employee', [$authController, 'registerEmployee']); // Registro de funcionário
$app->route('POST /v1/auth/login', [$authController, 'login']);
$app->route('POST /v1/auth/logout', [$authController, 'logout']);
$app->route('GET /v1/auth/me', [$authController, 'me']);
$app->route('GET /v1/auth/csrf-token', [$authController, 'getCsrfToken']); // ✅ NOVO: Endpoint para obter token CSRF

// Rotas de Usuários (apenas admin)
$userController = $container->make(\App\Controllers\UserController::class);
$app->route('GET /v1/users', [$userController, 'list']);
$app->route('GET /v1/users/@id', [$userController, 'get']);
$app->route('POST /v1/users', [$userController, 'create']);
$app->route('PUT /v1/users/@id', [$userController, 'update']);
$app->route('DELETE /v1/users/@id', [$userController, 'delete']);
$app->route('PUT /v1/users/@id/role', [$userController, 'updateRole']);

// Rotas de Permissões (apenas admin)
$permissionController = $container->make(\App\Controllers\PermissionController::class);
$app->route('GET /v1/permissions', [$permissionController, 'listAvailable']);
$app->route('GET /v1/users/@id/permissions', [$permissionController, 'listUserPermissions']);
$app->route('POST /v1/users/@id/permissions', [$permissionController, 'grant']);
$app->route('DELETE /v1/users/@id/permissions/@permission', [$permissionController, 'revoke']);

// Rotas de Clínica Veterinária
$petController = $container->make(\App\Controllers\PetController::class);
$app->route('POST /v1/clinic/pets', [$petController, 'create']);
$app->route('GET /v1/clinic/pets', [$petController, 'list']);
$app->route('GET /v1/clinic/pets/@id', [$petController, 'get']);
$app->route('PUT /v1/clinic/pets/@id', [$petController, 'update']);
$app->route('DELETE /v1/clinic/pets/@id', [$petController, 'delete']);
$app->route('GET /v1/clinic/pets/customer/@customer_id', [$petController, 'listByCustomer']);

$professionalController = $container->make(\App\Controllers\ProfessionalController::class);
$app->route('POST /v1/clinic/professionals', [$professionalController, 'create']);
$app->route('GET /v1/clinic/professionals', [$professionalController, 'list']);
$app->route('GET /v1/clinic/professionals/active', [$professionalController, 'listActive']);
$app->route('GET /v1/clinic/professionals/roles', [$professionalController, 'listRoles']);
$app->route('GET /v1/clinic/professionals/@id', [$professionalController, 'get']);
$app->route('GET /v1/clinic/professionals/@id/suggested-price', [$professionalController, 'getSuggestedPrice']);
$app->route('PUT /v1/clinic/professionals/@id', [$professionalController, 'update']);
$app->route('DELETE /v1/clinic/professionals/@id', [$professionalController, 'delete']);

$clinicSpecialtyController = $container->make(\App\Controllers\ClinicSpecialtyController::class);
$app->route('POST /v1/clinic/specialties', [$clinicSpecialtyController, 'create']);
$app->route('GET /v1/clinic/specialties', [$clinicSpecialtyController, 'list']);
$app->route('GET /v1/clinic/specialties/@id', [$clinicSpecialtyController, 'get']);
$app->route('PUT /v1/clinic/specialties/@id', [$clinicSpecialtyController, 'update']);
$app->route('DELETE /v1/clinic/specialties/@id', [$clinicSpecialtyController, 'delete']);

$appointmentController = $container->make(\App\Controllers\AppointmentController::class);
$app->route('POST /v1/clinic/appointments', [$appointmentController, 'create']);
$app->route('GET /v1/clinic/appointments', [$appointmentController, 'list']);
$app->route('GET /v1/clinic/appointments/@id', [$appointmentController, 'get']);
$app->route('PUT /v1/clinic/appointments/@id', [$appointmentController, 'update']);
$app->route('DELETE /v1/clinic/appointments/@id', [$appointmentController, 'delete']);
$app->route('GET /v1/clinic/appointments/pet/@pet_id', [$appointmentController, 'listByPet']);
$app->route('GET /v1/clinic/appointments/professional/@professional_id', [$appointmentController, 'listByProfessional']);
$app->route('POST /v1/clinic/appointments/@id/pay', [$appointmentController, 'pay']);
$app->route('GET /v1/clinic/appointments/@id/invoice', [$appointmentController, 'getInvoice']);
$app->route('POST /v1/clinic/appointments/@id/confirm', [$appointmentController, 'confirm']);
$app->route('POST /v1/clinic/appointments/@id/complete', [$appointmentController, 'complete']);

// Rotas de Exames
$examController = $container->make(\App\Controllers\ExamController::class);
$app->route('POST /v1/clinic/exams', [$examController, 'create']);
$app->route('GET /v1/clinic/exams', [$examController, 'list']);
$app->route('GET /v1/clinic/exams/@id', [$examController, 'get']);
$app->route('PUT /v1/clinic/exams/@id', [$examController, 'update']);
$app->route('DELETE /v1/clinic/exams/@id', [$examController, 'delete']);
$app->route('GET /v1/clinic/exams/pet/@pet_id', [$examController, 'listByPet']);
$app->route('GET /v1/clinic/exams/professional/@professional_id', [$examController, 'listByProfessional']);
$app->route('POST /v1/clinic/exams/@id/pay', [$examController, 'pay']);
$app->route('GET /v1/clinic/exams/@id/invoice', [$examController, 'getInvoice']);

// Rotas de Tipos de Exame (gerenciadas pelo ExamController)
$app->route('GET /v1/clinic/exam-types', [$examController, 'listExamTypes']);
$app->route('POST /v1/clinic/exam-types', [$examController, 'createExamType']);
$app->route('PUT /v1/clinic/exam-types/@id', [$examController, 'updateExamType']);
$app->route('DELETE /v1/clinic/exam-types/@id', [$examController, 'deleteExamType']);
$app->route('GET /v1/clinic/exam-types/@id', [$examController, 'getExamType']);
$app->route('GET /v1/clinic/exam-types/@id/count', [$examController, 'countExamsByType']);

// Rotas de Orçamentos
$budgetController = $container->make(\App\Controllers\BudgetController::class);
$app->route('POST /v1/clinic/budgets', [$budgetController, 'create']);
$app->route('GET /v1/clinic/budgets', [$budgetController, 'list']);
$app->route('GET /v1/clinic/budgets/@id', [$budgetController, 'get']);
$app->route('PUT /v1/clinic/budgets/@id', [$budgetController, 'update']);
$app->route('DELETE /v1/clinic/budgets/@id', [$budgetController, 'delete']);
$app->route('POST /v1/clinic/budgets/@id/convert', [$budgetController, 'convert']);

// Rotas de Comissões (rotas específicas ANTES das rotas com parâmetros)
$commissionController = $container->make(\App\Controllers\CommissionController::class);
$app->route('GET /v1/clinic/commissions', [$commissionController, 'list']);
// Rotas específicas primeiro (antes de @id)
$app->route('GET /v1/clinic/commissions/config', [$commissionController, 'getConfig']);
$app->route('PUT /v1/clinic/commissions/config', [$commissionController, 'updateConfig']);
$app->route('GET /v1/clinic/commissions/stats', [$commissionController, 'getGeneralStats']);
$app->route('GET /v1/clinic/commissions/stats/user/@user_id', [$commissionController, 'getUserStats']);
// Rotas com parâmetros por último
$app->route('GET /v1/clinic/commissions/@id', [$commissionController, 'get']);
$app->route('POST /v1/clinic/commissions/@id/mark-paid', [$commissionController, 'markPaid']);

$professionalScheduleController = $container->make(\App\Controllers\ProfessionalScheduleController::class);
$app->route('GET /v1/clinic/professionals/@id/schedule', [$professionalScheduleController, 'getSchedule']);
$app->route('POST /v1/clinic/professionals/@id/schedule', [$professionalScheduleController, 'saveSchedule']);
$app->route('GET /v1/clinic/appointments/available-slots', [$professionalScheduleController, 'getAvailableSlots']);
$app->route('POST /v1/clinic/schedule-blocks', [$professionalScheduleController, 'createBlock']);
$app->route('DELETE /v1/clinic/schedule-blocks/@id', [$professionalScheduleController, 'deleteBlock']);

// Rotas de Configurações da Clínica
$clinicController = $container->make(\App\Controllers\ClinicController::class);
$app->route('GET /v1/clinic/configuration', [$clinicController, 'getConfiguration']);
$app->route('PUT /v1/clinic/configuration', [$clinicController, 'updateConfiguration']);
$app->route('POST /v1/clinic/logo', [$clinicController, 'uploadLogo']);

// Rotas de Dashboard da Clínica
$clinicDashboardController = $container->make(\App\Controllers\ClinicDashboardController::class);
$app->route('GET /v1/clinic/dashboard/kpis', [$clinicDashboardController, 'getKPIs']);
$app->route('GET /v1/clinic/dashboard/appointments-stats', [$clinicDashboardController, 'getAppointmentsStats']);
$app->route('GET /v1/clinic/dashboard/upcoming-appointments', [$clinicDashboardController, 'getUpcomingAppointments']);

// Rotas de Busca Avançada
$searchController = $container->make(\App\Controllers\SearchController::class);
$app->route('GET /v1/clinic/search', [$searchController, 'globalSearch']);

// Rotas de Relatórios da Clínica
$clinicReportController = $container->make(\App\Controllers\ClinicReportController::class);
$app->route('GET /v1/clinic/reports/appointments', [$clinicReportController, 'appointmentsReport']);
$app->route('GET /v1/clinic/reports/exams', [$clinicReportController, 'examsReport']);
$app->route('GET /v1/clinic/reports/vaccinations', [$clinicReportController, 'vaccinationsReport']);
$app->route('GET /v1/clinic/reports/financial', [$clinicReportController, 'financialReport']);
$app->route('GET /v1/clinic/reports/top-pets', [$clinicReportController, 'topPetsReport']);

// Rotas de Upload de Arquivos
$fileController = $container->make(\App\Controllers\FileController::class);
$app->route('POST /v1/files/pets/@id/photo', [$fileController, 'uploadPetPhoto']);
$app->route('DELETE /v1/files/pets/@id/photo', [$fileController, 'deletePetPhoto']);
$app->route('POST /v1/files/customers/@id/photo', [$fileController, 'uploadCustomerPhoto']);
$app->route('POST /v1/files/professionals/@id/photo', [$fileController, 'uploadProfessionalPhoto']);

// View de Relatórios da Clínica
$app->route('GET /clinic/reports', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        Flight::redirect('/login');
        return;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Relatórios Clínica', 'clinic-reports', 'clinic/reports')) {
        return;
    }
    
    \App\Utils\View::render('clinic/reports', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Relatórios da Clínica', 'currentPage' => 'clinic-reports'
    ], true);
});

// View de Busca Avançada
$app->route('GET /clinic/search', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Busca Avançada', 'clinic-search', 'clinic/search')) {
        return;
    }
    
    \App\Utils\View::render('clinic/search', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Busca Avançada', 'currentPage' => 'clinic-search'
    ], true);
});

// View de Configurações da Clínica
$app->route('GET /clinic-settings', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user) {
        header('Location: /login');
        exit;
    }
    
    if (checkSaasAdminAndRender($user, $apiUrl, $tenant, 'Configurações da Clínica', 'clinic-settings', 'clinic-settings')) {
        return;
    }
    
    \App\Utils\View::render('clinic-settings', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Configurações da Clínica', 'currentPage' => 'clinic-settings'
    ], true);
});

// View de Stripe Connect
$app->route('GET /stripe-connect', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user || !$tenant) {
        Flight::redirect('/login');
        return;
    }
    
    \App\Utils\View::render('stripe-connect', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Conectar Stripe', 'currentPage' => 'stripe-connect'
    ], true);
});

// View de Sucesso do Stripe Connect
$app->route('GET /stripe-connect/success', function() use ($app) {
    [$user, $tenant, $sessionId] = getAuthenticatedUserData();
    $apiUrl = getBaseUrl();
    
    if (!$user || !$tenant) {
        Flight::redirect('/login');
        return;
    }
    
    \App\Utils\View::render('stripe-connect-success', [
        'apiUrl' => $apiUrl, 'user' => $user, 'tenant' => $tenant,
        'title' => 'Stripe Conectado com Sucesso', 'currentPage' => 'stripe-connect'
    ], true);
});

// Tratamento de erros
$app->map('notFound', function() use ($app, $auditMiddleware) {
    $auditMiddleware->logResponse(404);
    // PerformanceMiddleware já registra métricas automaticamente via shutdown function
    $app->json(['error' => 'Rota não encontrada'], 404);
});

$app->map('error', function(\Throwable $ex) use ($app, $auditMiddleware) {
    try {
        \App\Utils\ErrorHandler::logException($ex);
    } catch (\Exception $e) {
        // Ignora erros de log
    }
    
    $auditMiddleware->logResponse(500);
    // PerformanceMiddleware já registra métricas automaticamente via shutdown function
    
    // Em desenvolvimento, mostra mais detalhes do erro
    $response = \App\Utils\ErrorHandler::prepareErrorResponse($ex, 'Erro interno do servidor', 'INTERNAL_SERVER_ERROR');
    
    // Adiciona detalhes em desenvolvimento
    if (Config::isDevelopment()) {
        $response['debug'] = [
            'message' => $ex->getMessage(),
            'file' => $ex->getFile(),
            'line' => $ex->getLine(),
            'trace' => array_slice($ex->getTrace(), 0, 5) // Primeiros 5 níveis do trace
        ];
    }
    
    $app->json($response, 500);
});

// ✅ OTIMIZAÇÃO: AuditMiddleware já usa register_shutdown_function internamente
// Não precisa de outro aqui (já foi otimizado para não bloquear resposta)

// Inicia aplicação
$app->start();

