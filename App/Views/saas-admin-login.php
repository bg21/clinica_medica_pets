<?php
/**
 * View de Login para Administradores do SaaS
 * 
 * @var string $apiUrl URL base da API
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Administrador SaaS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .sidebar-overlay,
        .mobile-header-bar,
        .sidebar {
            display: none !important;
            pointer-events: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 10;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .form-control {
            position: relative;
            z-index: 10;
            pointer-events: auto !important;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            z-index: 11;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: transform 0.2s;
            position: relative;
            z-index: 10;
            pointer-events: auto !important;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            position: relative;
            z-index: 10;
            pointer-events: auto !important;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="admin-badge">
                <i class="bi bi-shield-lock"></i> Administrador SaaS
            </div>
            <h1>Acesso Master</h1>
            <p>Faça login para gerenciar planos e módulos</p>
        </div>
        
        <div id="alertContainer"></div>
        
        <form id="loginForm">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input 
                        type="email" 
                        class="form-control" 
                        id="email" 
                        name="email" 
                        placeholder="admin@saas.local"
                        required
                        autocomplete="email"
                    >
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login w-100" id="loginButton">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="loadingSpinner"></span>
                <span id="buttonText">Entrar</span>
            </button>
        </form>
        
        <div class="back-link">
            <a href="/login">
                <i class="bi bi-arrow-left"></i> Voltar para login de clínica
            </a>
        </div>
    </div>
    
    <script>
        const API_URL = '<?php echo $apiUrl; ?>';
        
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const loginButton = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            // Validações básicas
            if (!email || !email.includes('@')) {
                showError('Por favor, informe um email válido');
                return;
            }
            
            if (!password || password.length < 3) {
                showError('Por favor, informe uma senha válida');
                return;
            }
            
            // Desabilita botão e mostra loading
            loginButton.disabled = true;
            buttonText.textContent = 'Entrando...';
            loadingSpinner.classList.remove('d-none');
            
            try {
                console.log('Enviando requisição de login:', { email, url: API_URL + '/v1/saas-admin/login' });
                
                const response = await fetch(API_URL + '/v1/saas-admin/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });
                
                console.log('Resposta recebida:', { status: response.status, statusText: response.statusText });
                
                let data;
                try {
                    data = await response.json();
                    console.log('Dados da resposta:', data);
                } catch (jsonError) {
                    const text = await response.text();
                    console.error('Erro ao parsear JSON:', jsonError, 'Resposta:', text);
                    showError('Erro ao processar resposta do servidor. Verifique os logs do console.');
                    resetLoginButton();
                    return;
                }
                
                if (response.ok && data.success) {
                    // Salva session_id no localStorage
                    localStorage.setItem('saas_admin_session_id', data.data.session_id);
                    localStorage.setItem('saas_admin', JSON.stringify(data.data.admin));
                    
                    // Salva session_id em cookie também
                    const expires = new Date();
                    expires.setTime(expires.getTime() + (7 * 24 * 60 * 60 * 1000)); // 7 dias
                    document.cookie = `saas_admin_session_id=${data.data.session_id}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
                    
                    showSuccess('Login realizado com sucesso! Redirecionando...');
                    
                    // Redireciona para admin-plans (sem session_id na URL, já está no localStorage e cookie)
                    setTimeout(() => {
                        window.location.href = '/admin-plans';
                    }, 1000);
                } else {
                    // Mostra mensagem de erro mais detalhada
                    let errorMsg = data.message || 'Erro ao fazer login. Verifique suas credenciais.';
                    if (data.errors && Object.keys(data.errors).length > 0) {
                        errorMsg += ' ' + Object.values(data.errors).join(', ');
                    }
                    console.error('Erro no login:', { status: response.status, data });
                    showError(errorMsg);
                    resetLoginButton();
                    document.getElementById('password').focus();
                    document.getElementById('password').select();
                }
            } catch (error) {
                console.error('Erro ao fazer login:', error);
                showError('Erro de conexão. Verifique sua internet e tente novamente. Erro: ' + error.message);
                resetLoginButton();
            }
        });
        
        function showError(message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
        
        function showSuccess(message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
        
        function resetLoginButton() {
            const loginButton = document.getElementById('loginButton');
            const buttonText = document.getElementById('buttonText');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            loginButton.disabled = false;
            buttonText.textContent = 'Entrar';
            loadingSpinner.classList.add('d-none');
        }
        
        // Carrega email salvo se houver
        const savedEmail = localStorage.getItem('saas_admin_email');
        if (savedEmail) {
            document.getElementById('email').value = savedEmail;
        }
        
        // Salva email quando o usuário digitar
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value.trim();
            if (email) {
                localStorage.setItem('saas_admin_email', email);
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

