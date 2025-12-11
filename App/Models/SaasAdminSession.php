<?php

namespace App\Models;

/**
 * Model para gerenciar sessões de administradores do SaaS
 */
class SaasAdminSession extends BaseModel
{
    protected string $table = 'saas_admin_sessions';

    /**
     * Cria uma nova sessão
     * 
     * @param int $adminId ID do administrador
     * @param string|null $ipAddress IP do cliente
     * @param string|null $userAgent User-Agent do cliente
     * @param int $hours Duração da sessão em horas (padrão: 24)
     * @return string Session ID (token)
     */
    public function create(int $adminId, ?string $ipAddress = null, ?string $userAgent = null, int $hours = 24): string
    {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        $this->insert([
            'id' => $sessionId,
            'admin_id' => $adminId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => $expiresAt
        ]);

        return $sessionId;
    }

    /**
     * Valida sessão e retorna dados do administrador
     * 
     * @param string $sessionId Token da sessão
     * @return array|null Dados da sessão com informações do administrador, ou null se inválida
     */
    public function validate(string $sessionId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, 
                    a.email, a.name, a.is_active as admin_active
             FROM {$this->table} s
             INNER JOIN saas_admins a ON s.admin_id = a.id
             WHERE s.id = :session_id 
             AND s.expires_at > NOW()
             AND a.is_active = 1"
        );
        $stmt->execute(['session_id' => $sessionId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Remove sessão (logout)
     * 
     * @param string $sessionId Token da sessão
     * @return bool Sucesso da operação
     */
    public function deleteSession(string $sessionId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :session_id");
        $stmt->execute(['session_id' => $sessionId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove todas as sessões de um administrador
     * 
     * @param int $adminId ID do administrador
     * @return bool Sucesso da operação
     */
    public function deleteByAdmin(int $adminId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE admin_id = :admin_id");
        $stmt->execute(['admin_id' => $adminId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Limpa sessões expiradas
     * 
     * @return int Número de sessões removidas
     */
    public function cleanExpired(): int
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }
}

