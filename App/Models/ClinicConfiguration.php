<?php

namespace App\Models;

/**
 * Model para gerenciar configurações da clínica
 */
class ClinicConfiguration extends BaseModel
{
    protected string $table = 'clinic_configurations';
    protected bool $usesSoftDeletes = false;

    /**
     * Busca configurações de um tenant
     * 
     * @param int $tenantId ID do tenant
     * @return array|null Configurações encontradas ou null
     */
    public function findByTenant(int $tenantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE tenant_id = :tenant_id 
             LIMIT 1"
        );
        $stmt->execute(['tenant_id' => $tenantId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    /**
     * Cria ou atualiza configurações de um tenant
     * 
     * @param int $tenantId ID do tenant
     * @param array $data Dados das configurações
     * @return int ID da configuração (criada ou atualizada)
     */
    public function upsertConfiguration(int $tenantId, array $data): int
    {
        $existing = $this->findByTenant($tenantId);
        
        // Remove tenant_id dos dados (será usado separadamente)
        unset($data['tenant_id']);
        
        // Valida e sanitiza dados
        $allowedFields = [
            // Informações básicas
            'clinic_name', 'clinic_phone', 'clinic_email', 'clinic_address',
            'clinic_city', 'clinic_state', 'clinic_zip_code', 'clinic_logo',
            'clinic_description', 'clinic_website',
            // Horários de funcionamento
            'opening_time_monday', 'closing_time_monday',
            'opening_time_tuesday', 'closing_time_tuesday',
            'opening_time_wednesday', 'closing_time_wednesday',
            'opening_time_thursday', 'closing_time_thursday',
            'opening_time_friday', 'closing_time_friday',
            'opening_time_saturday', 'closing_time_saturday',
            'opening_time_sunday', 'closing_time_sunday',
            // Configurações operacionais
            'default_appointment_duration', 'time_slot_interval',
            'allow_online_booking', 'require_confirmation',
            'cancellation_hours', 'metadata'
        ];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                // Converte booleanos
                if (in_array($field, ['allow_online_booking', 'require_confirmation'])) {
                    $updateData[$field] = (bool)$data[$field];
                } 
                // Converte inteiros
                elseif (in_array($field, ['default_appointment_duration', 'time_slot_interval', 'cancellation_hours'])) {
                    $updateData[$field] = isset($data[$field]) ? (int)$data[$field] : null;
                }
                // Strings - trim e sanitize
                elseif (is_string($data[$field])) {
                    $updateData[$field] = trim($data[$field]);
                    // Converte string vazia para null em campos opcionais
                    if ($updateData[$field] === '' && in_array($field, [
                        'clinic_phone', 'clinic_email', 'clinic_address', 'clinic_city',
                        'clinic_state', 'clinic_zip_code', 'clinic_logo', 'clinic_description',
                        'clinic_website', 'metadata'
                    ])) {
                        $updateData[$field] = null;
                    }
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }
        
        if ($existing) {
            // Atualiza
            $this->update($existing['id'], $updateData);
            return $existing['id'];
        } else {
            // Cria
            $updateData['tenant_id'] = $tenantId;
            return $this->insert($updateData);
        }
    }

    /**
     * Valida dados de configuração
     * 
     * @param array $data Dados para validar
     * @return array Array vazio se válido, array com erros se inválido
     */
    public function validateConfiguration(array $data): array
    {
        $errors = [];
        
        // Valida email se fornecido
        if (!empty($data['clinic_email']) && !filter_var($data['clinic_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['clinic_email'] = 'Email inválido';
        }
        
        // Valida website se fornecido
        if (!empty($data['clinic_website'])) {
            $website = $data['clinic_website'];
            if (!preg_match('/^https?:\/\/.+/', $website)) {
                $website = 'https://' . $website;
            }
            if (!filter_var($website, FILTER_VALIDATE_URL)) {
                $errors['clinic_website'] = 'Website inválido';
            }
        }
        
        // Valida duração padrão (deve ser positiva)
        if (isset($data['default_appointment_duration']) && $data['default_appointment_duration'] < 1) {
            $errors['default_appointment_duration'] = 'Duração deve ser maior que zero';
        }
        
        // Valida intervalo (deve ser positivo)
        if (isset($data['time_slot_interval']) && $data['time_slot_interval'] < 1) {
            $errors['time_slot_interval'] = 'Intervalo deve ser maior que zero';
        }
        
        // Valida horas de cancelamento (deve ser positivo)
        if (isset($data['cancellation_hours']) && $data['cancellation_hours'] < 0) {
            $errors['cancellation_hours'] = 'Horas de cancelamento não podem ser negativas';
        }
        
        // Valida horários (se início fornecido, fim também deve ser)
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            $opening = "opening_time_{$day}";
            $closing = "closing_time_{$day}";
            
            if (!empty($data[$opening]) && empty($data[$closing])) {
                $errors[$closing] = "Horário de fechamento de {$day} é obrigatório se horário de abertura for fornecido";
            }
            
            if (empty($data[$opening]) && !empty($data[$closing])) {
                $errors[$opening] = "Horário de abertura de {$day} é obrigatório se horário de fechamento for fornecido";
            }
            
            // Valida se horário de fechamento é posterior ao de abertura
            if (!empty($data[$opening]) && !empty($data[$closing])) {
                // Converte para DateTime para comparação correta
                $openingTime = \DateTime::createFromFormat('H:i:s', $data[$opening]);
                if (!$openingTime) {
                    $openingTime = \DateTime::createFromFormat('H:i', $data[$opening]);
                }
                
                $closingTime = \DateTime::createFromFormat('H:i:s', $data[$closing]);
                if (!$closingTime) {
                    $closingTime = \DateTime::createFromFormat('H:i', $data[$closing]);
                }
                
                if ($openingTime && $closingTime) {
                    if ($closingTime <= $openingTime) {
                        $errors[$closing] = "Horário de fechamento de {$day} deve ser posterior ao horário de abertura";
                    }
                }
            }
        }
        
        return $errors;
    }
}

