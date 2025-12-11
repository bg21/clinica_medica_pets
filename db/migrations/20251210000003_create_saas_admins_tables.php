<?php

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Criar tabelas de administradores do SaaS
 * 
 * Permite gerenciar usuários master/administradores do SaaS
 * que podem criar planos, módulos e gerenciar o sistema
 */
class CreateSaasAdminsTables extends AbstractMigration
{
    public function change()
    {
        // Tabela de administradores do SaaS
        $admins = $this->table('saas_admins', [
            'id' => 'id',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Administradores master do SaaS'
        ]);
        
        $admins->addColumn('email', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Email do administrador'])
               ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Hash da senha (bcrypt)'])
               ->addColumn('name', 'string', ['limit' => 255, 'null' => false, 'comment' => 'Nome do administrador'])
               ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => 'Se o administrador está ativo'])
               ->addColumn('last_login_at', 'datetime', ['null' => true, 'comment' => 'Último login'])
               ->addColumn('last_login_ip', 'string', ['limit' => 45, 'null' => true, 'comment' => 'IP do último login'])
               ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
               ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'null' => false])
               ->addIndex(['email'], ['unique' => true])
               ->addIndex(['is_active'])
               ->create();

        // Tabela de sessões de administradores do SaaS
        $sessions = $this->table('saas_admin_sessions', [
            'id' => false,
            'primary_key' => 'id',
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'Sessões de administradores do SaaS'
        ]);
        
        $sessions->addColumn('id', 'string', ['limit' => 64, 'null' => false, 'comment' => 'Session ID (token)'])
                 ->addColumn('admin_id', 'integer', ['null' => false, 'signed' => false, 'comment' => 'FK para saas_admins.id'])
                 ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true, 'comment' => 'IP do cliente'])
                 ->addColumn('user_agent', 'text', ['null' => true, 'comment' => 'User-Agent do cliente'])
                 ->addColumn('expires_at', 'datetime', ['null' => false, 'comment' => 'Data de expiração da sessão'])
                 ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
                 ->addIndex(['admin_id'])
                 ->addIndex(['expires_at'])
                 ->addIndex(['id'], ['unique' => true])
                 ->addForeignKey('admin_id', 'saas_admins', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                 ->create();
    }
}

