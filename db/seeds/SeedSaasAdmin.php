<?php

use Phinx\Seed\AbstractSeed;

/**
 * Seed: Criar primeiro administrador master do SaaS
 * 
 * Cria o primeiro administrador com email e senha padrão
 * IMPORTANTE: Altere a senha após o primeiro login!
 */
class SeedSaasAdmin extends AbstractSeed
{
    public function run(): void
    {
        // Verifica se já existe algum administrador
        $existing = $this->query("SELECT COUNT(*) as count FROM saas_admins")->fetch();
        if ($existing && (int)$existing['count'] > 0) {
            echo "AVISO: Já existem administradores no sistema. Pulando criação do admin padrão.\n";
            return;
        }
        
        // Dados do primeiro administrador
        $email = 'admin@saas.local';
        $password = 'admin123'; // ⚠️ ALTERE ESTA SENHA APÓS O PRIMEIRO LOGIN!
        $name = 'Administrador Master';
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        $this->table('saas_admins')->insert([
            [
                'email' => $email,
                'password_hash' => $passwordHash,
                'name' => $name,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ])->saveData();
        
        echo "✅ Administrador master criado com sucesso!\n";
        echo "   Email: {$email}\n";
        echo "   Senha: {$password}\n";
        echo "   ⚠️  IMPORTANTE: Altere a senha após o primeiro login!\n";
    }
}

