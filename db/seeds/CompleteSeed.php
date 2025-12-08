<?php

use Phinx\Seed\AbstractSeed;

/**
 * Seed completo - Dados de exemplo para desenvolvimento e testes
 * 
 * Este seed cria:
 * - 1 Tenant principal
 * - 3 Usuários (admin, editor, viewer)
 * - 5 Clientes de exemplo (customers)
 * 
 * Execute: vendor/bin/phinx seed:run -s CompleteSeed
 */
class CompleteSeed extends AbstractSeed
{
    public function run(): void
    {
        $adapter = $this->getAdapter();
        $connection = $adapter->getConnection();
        $now = date('Y-m-d H:i:s');
        
        echo "🌱 Iniciando seed completo...\n\n";
        
        // ============================================
        // 1. CRIAR TENANT
        // ============================================
        echo "📦 Criando tenant...\n";
        
        // Verifica se já existe um tenant
        $existingTenant = $adapter->fetchAll("SELECT * FROM tenants WHERE slug = 'empresa-exemplo' LIMIT 1");
        
        if (!empty($existingTenant)) {
            $tenant = $existingTenant[0];
            $tenantId = (int)$tenant['id'];
            echo "   ℹ️  Tenant já existe: {$tenant['name']} (ID: {$tenantId})\n";
        } else {
            // Gera API key
            $apiKey = bin2hex(random_bytes(32)); // 64 caracteres hexadecimais
            
            $tenantData = [
                'name' => 'Empresa Exemplo',
                'slug' => 'empresa-exemplo',
                'api_key' => $apiKey,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now
            ];
            
            $this->table('tenants')->insert([$tenantData])->saveData();
            
            // Busca o tenant criado
            $tenant = $adapter->fetchAll("SELECT * FROM tenants WHERE slug = 'empresa-exemplo' LIMIT 1")[0];
            $tenantId = (int)$tenant['id'];
            
            echo "   ✅ Tenant criado: {$tenant['name']} (ID: {$tenantId})\n";
            echo "   🔑 API Key: {$apiKey}\n";
        }
        
        echo "\n";
        
        // ============================================
        // 2. CRIAR USUÁRIOS
        // ============================================
        echo "👥 Criando usuários...\n";
        
        $users = [
            [
                'tenant_id' => $tenantId,
                'email' => 'admin@empresa.com',
                'password_hash' => password_hash('admin123', PASSWORD_BCRYPT),
                'name' => 'Administrador',
                'cpf' => '123.456.789-00',
                'status' => 'active',
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'tenant_id' => $tenantId,
                'email' => 'editor@empresa.com',
                'password_hash' => password_hash('editor123', PASSWORD_BCRYPT),
                'name' => 'Editor',
                'cpf' => '234.567.890-11',
                'status' => 'active',
                'role' => 'editor',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'tenant_id' => $tenantId,
                'email' => 'viewer@empresa.com',
                'password_hash' => password_hash('viewer123', PASSWORD_BCRYPT),
                'name' => 'Visualizador',
                'cpf' => '345.678.901-22',
                'status' => 'active',
                'role' => 'viewer',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        
        foreach ($users as $user) {
            // Verifica se usuário já existe
            $stmt = $connection->prepare("SELECT * FROM users WHERE tenant_id = ? AND email = ?");
            $stmt->execute([$user['tenant_id'], $user['email']]);
            $existing = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (empty($existing)) {
                $this->table('users')->insert([$user])->saveData();
                echo "   ✅ Usuário criado: {$user['email']} (Role: {$user['role']})\n";
            } else {
                echo "   ℹ️  Usuário já existe: {$user['email']}\n";
            }
        }
        
        echo "\n";
        
        // ============================================
        // 3. CRIAR CLIENTES (CUSTOMERS)
        // ============================================
        echo "👤 Criando clientes...\n";
        
        // Busca o admin para usar como referência
        $stmt = $connection->prepare("SELECT id FROM users WHERE tenant_id = ? AND email = 'admin@empresa.com' LIMIT 1");
        $stmt->execute([$tenantId]);
        $adminUser = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $adminUserId = !empty($adminUser) ? (int)$adminUser[0]['id'] : null;
        
        $customers = [
            [
                'tenant_id' => $tenantId,
                'stripe_customer_id' => 'cus_exemplo001',
                'email' => 'cliente1@exemplo.com',
                'name' => 'João Silva',
                'metadata' => json_encode([
                    'phone' => '(11) 98765-4321',
                    'created_by' => $adminUserId
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'tenant_id' => $tenantId,
                'stripe_customer_id' => 'cus_exemplo002',
                'email' => 'cliente2@exemplo.com',
                'name' => 'Maria Santos',
                'metadata' => json_encode([
                    'phone' => '(11) 97654-3210',
                    'created_by' => $adminUserId
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'tenant_id' => $tenantId,
                'stripe_customer_id' => 'cus_exemplo003',
                'email' => 'cliente3@exemplo.com',
                'name' => 'Pedro Oliveira',
                'metadata' => json_encode([
                    'phone' => '(11) 96543-2109',
                    'created_by' => $adminUserId
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'tenant_id' => $tenantId,
                'stripe_customer_id' => 'cus_exemplo004',
                'email' => 'cliente4@exemplo.com',
                'name' => 'Ana Costa',
                'metadata' => json_encode([
                    'phone' => '(11) 95432-1098',
                    'created_by' => $adminUserId
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'tenant_id' => $tenantId,
                'stripe_customer_id' => 'cus_exemplo005',
                'email' => 'cliente5@exemplo.com',
                'name' => 'Carlos Ferreira',
                'metadata' => json_encode([
                    'phone' => '(11) 94321-0987',
                    'created_by' => $adminUserId
                ]),
                'created_at' => $now,
                'updated_at' => $now
            ]
        ];
        
        foreach ($customers as $customer) {
            // Verifica se cliente já existe
            $stmt = $connection->prepare("SELECT * FROM customers WHERE stripe_customer_id = ? LIMIT 1");
            $stmt->execute([$customer['stripe_customer_id']]);
            $existing = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (empty($existing)) {
                $this->table('customers')->insert([$customer])->saveData();
                echo "   ✅ Cliente criado: {$customer['name']} ({$customer['email']})\n";
            } else {
                echo "   ℹ️  Cliente já existe: {$customer['name']}\n";
            }
        }
        
        echo "\n";
        
        // ============================================
        // RESUMO
        // ============================================
        echo "═══════════════════════════════════════════════════════════\n";
        echo "✨ SEED COMPLETO FINALIZADO!\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        echo "📋 DADOS CRIADOS:\n";
        echo "   - 1 Tenant: Empresa Exemplo\n";
        echo "   - 3 Usuários (admin, editor, viewer)\n";
        echo "   - 5 Clientes de exemplo\n\n";
        
        echo "🔑 CREDENCIAIS DE ACESSO:\n";
        echo "   Admin: admin@empresa.com / admin123\n";
        echo "   Editor: editor@empresa.com / editor123\n";
        echo "   Viewer: viewer@empresa.com / viewer123\n\n";
        
        if (isset($apiKey)) {
            echo "🔐 API KEY DO TENANT:\n";
            echo "   {$apiKey}\n";
            echo "   Use no header: Authorization: Bearer {$apiKey}\n\n";
        }
        
        echo "💡 Use estes dados para testar o sistema!\n";
    }
}

