<?php

namespace App\Controllers;

use App\Models\ProfessionalSchedule;
use App\Models\ScheduleBlock;
use App\Models\Professional;
use App\Models\Appointment;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use Config;

/**
 * Controller para gerenciar agendas de profissionais
 * 
 * Regras de acesso:
 * - Veterinário: vê apenas sua própria agenda
 * - Atendente/Admin: vê agendas de todos os profissionais
 */
class ProfessionalScheduleController
{
    /**
     * Obtém agenda de um profissional
     * GET /v1/clinic/professionals/:id/schedule
     */
    public function getSchedule(string $professionalId): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            $userId = Flight::get('user_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_professional_schedule']);
                return;
            }
            
            $professionalIdInt = (int)$professionalId;
            
            // Verifica se profissional existe e pertence ao tenant
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, $professionalIdInt);
            
            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'get_professional_schedule', 'professional_id' => $professionalIdInt]);
                return;
            }
            
            // Verifica permissão: veterinário só vê sua própria agenda
            if (!$this->canViewSchedule($tenantId, $userId, $professionalIdInt)) {
                ResponseHelper::sendForbiddenError('Você não tem permissão para ver esta agenda', ['action' => 'get_professional_schedule', 'professional_id' => $professionalIdInt]);
                return;
            }
            
            $scheduleModel = new ProfessionalSchedule();
            $schedule = $scheduleModel->findByProfessional($tenantId, $professionalIdInt);
            
            // Busca bloqueios também
            $blockModel = new ScheduleBlock();
            $blocks = $blockModel->findByProfessional($tenantId, $professionalIdInt);
            
            ResponseHelper::sendSuccess([
                'professional_id' => $professionalIdInt,
                'professional_name' => $professional['name'] ?? '',
                'schedule' => $schedule,
                'blocks' => $blocks
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao obter agenda: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao obter agenda do profissional',
                'PROFESSIONAL_SCHEDULE_GET_ERROR',
                ['action' => 'get_professional_schedule', 'professional_id' => $professionalId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
    
    /**
     * Salva/atualiza agenda de um profissional
     * POST /v1/clinic/professionals/:id/schedule
     */
    public function saveSchedule(string $professionalId): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            $userId = Flight::get('user_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'save_professional_schedule']);
                return;
            }
            
            $professionalIdInt = (int)$professionalId;
            
            // Verifica se profissional existe
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, $professionalIdInt);
            
            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'save_professional_schedule', 'professional_id' => $professionalIdInt]);
                return;
            }
            
            // Verifica permissão: veterinário só pode editar sua própria agenda
            if (!$this->canEditSchedule($tenantId, $userId, $professionalIdInt)) {
                ResponseHelper::sendForbiddenError('Você não tem permissão para editar esta agenda', ['action' => 'save_professional_schedule', 'professional_id' => $professionalIdInt]);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if (!is_array($data) || !isset($data['schedule']) || !is_array($data['schedule'])) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['schedule' => 'É necessário enviar um array de horários'],
                    ['action' => 'save_professional_schedule']
                );
                return;
            }
            
            $scheduleModel = new ProfessionalSchedule();
            
            // Remove horários existentes
            $scheduleModel->deleteByProfessional($tenantId, $professionalIdInt);
            
            // Cria novos horários
            $created = [];
            foreach ($data['schedule'] as $daySchedule) {
                if (!isset($daySchedule['day_of_week']) || !isset($daySchedule['start_time']) || !isset($daySchedule['end_time'])) {
                    continue;
                }
                
                $dayOfWeek = (int)$daySchedule['day_of_week'];
                $startTime = $daySchedule['start_time'];
                $endTime = $daySchedule['end_time'];
                $isAvailable = $daySchedule['is_available'] ?? true;
                
                // Valida dia da semana (0-6)
                if ($dayOfWeek < 0 || $dayOfWeek > 6) {
                    continue;
                }
                
                $scheduleId = $scheduleModel->upsertSchedule(
                    $tenantId,
                    $professionalIdInt,
                    $dayOfWeek,
                    $startTime,
                    $endTime,
                    $isAvailable
                );
                
                $created[] = $scheduleId;
            }
            
            // Busca agenda atualizada
            $updatedSchedule = $scheduleModel->findByProfessional($tenantId, $professionalIdInt);
            
            ResponseHelper::sendSuccess($updatedSchedule, 'Agenda salva com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao salvar agenda: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao salvar agenda do profissional',
                'PROFESSIONAL_SCHEDULE_SAVE_ERROR',
                ['action' => 'save_professional_schedule', 'professional_id' => $professionalId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
    
    /**
     * Calcula horários disponíveis para um profissional em uma data
     * GET /v1/clinic/appointments/available-slots?professional_id=:id&date=YYYY-MM-DD&duration=30
     */
    public function getAvailableSlots(): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'get_available_slots']);
                return;
            }
            
            $queryParams = Flight::request()->query->getData();
            $professionalId = isset($queryParams['professional_id']) ? (int)$queryParams['professional_id'] : null;
            $date = $queryParams['date'] ?? null;
            $duration = isset($queryParams['duration']) ? (int)$queryParams['duration'] : 30; // minutos
            
            if (!$professionalId || !$date) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['professional_id' => 'ID do profissional é obrigatório', 'date' => 'Data é obrigatória'],
                    ['action' => 'get_available_slots']
                );
                return;
            }
            
            // Valida data
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateObj) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['date' => 'Data deve estar no formato YYYY-MM-DD'],
                    ['action' => 'get_available_slots']
                );
                return;
            }
            
            // Obtém dia da semana (0=domingo, 6=sábado)
            $dayOfWeek = (int)$dateObj->format('w');
            
            // Busca horário do profissional para este dia
            $scheduleModel = new ProfessionalSchedule();
            $schedule = $scheduleModel->findByProfessionalAndDay($tenantId, $professionalId, $dayOfWeek);
            
            if (!$schedule || !$schedule['is_available']) {
                ResponseHelper::sendSuccess([]);
                return;
            }
            
            // Busca bloqueios para esta data
            $blockModel = new ScheduleBlock();
            $blocks = $blockModel->findByProfessional($tenantId, $professionalId, $date, $date);
            
            // Busca agendamentos existentes para esta data
            $appointmentModel = new Appointment();
            $appointments = $appointmentModel->findAll([
                'tenant_id' => $tenantId,
                'professional_id' => $professionalId
            ], ['appointment_date' => 'ASC']);
            
            // Filtra agendamentos do dia
            $dayAppointments = array_filter($appointments, function($apt) use ($date) {
                $aptDate = date('Y-m-d', strtotime($apt['appointment_date']));
                return $aptDate === $date && in_array($apt['status'], ['scheduled', 'confirmed']);
            });
            
            // Calcula horários disponíveis
            $availableSlots = $this->calculateAvailableSlots(
                $schedule['start_time'],
                $schedule['end_time'],
                $duration,
                $date,
                $blocks,
                $dayAppointments
            );
            
            ResponseHelper::sendSuccess($availableSlots);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao calcular horários disponíveis: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao calcular horários disponíveis',
                'AVAILABLE_SLOTS_ERROR',
                ['action' => 'get_available_slots', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
    
    /**
     * Cria um bloqueio de agenda
     * POST /v1/clinic/schedule-blocks
     */
    public function createBlock(): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            $userId = Flight::get('user_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'create_schedule_block']);
                return;
            }
            
            $data = \App\Utils\RequestCache::getJsonInput();
            
            if (!is_array($data)) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['data' => 'Os dados devem ser um objeto JSON válido'],
                    ['action' => 'create_schedule_block']
                );
                return;
            }
            
            $professionalId = isset($data['professional_id']) ? (int)$data['professional_id'] : null;
            $startDatetime = $data['start_datetime'] ?? null;
            $endDatetime = $data['end_datetime'] ?? null;
            $reason = $data['reason'] ?? null;
            
            if (!$professionalId || !$startDatetime || !$endDatetime) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    [
                        'professional_id' => 'ID do profissional é obrigatório',
                        'start_datetime' => 'Data/hora de início é obrigatória',
                        'end_datetime' => 'Data/hora de fim é obrigatória'
                    ],
                    ['action' => 'create_schedule_block']
                );
                return;
            }
            
            // Verifica se profissional existe
            $professionalModel = new Professional();
            $professional = $professionalModel->findByTenantAndId($tenantId, $professionalId);
            
            if (!$professional) {
                ResponseHelper::sendNotFoundError('Profissional', ['action' => 'create_schedule_block', 'professional_id' => $professionalId]);
                return;
            }
            
            // Verifica permissão
            if (!$this->canEditSchedule($tenantId, $userId, $professionalId)) {
                ResponseHelper::sendForbiddenError('Você não tem permissão para criar bloqueio nesta agenda', ['action' => 'create_schedule_block', 'professional_id' => $professionalId]);
                return;
            }
            
            // Valida datas
            $startObj = \DateTime::createFromFormat('Y-m-d H:i:s', $startDatetime);
            $endObj = \DateTime::createFromFormat('Y-m-d H:i:s', $endDatetime);
            
            if (!$startObj || !$endObj) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['start_datetime' => 'Formato inválido (use YYYY-MM-DD HH:MM:SS)', 'end_datetime' => 'Formato inválido (use YYYY-MM-DD HH:MM:SS)'],
                    ['action' => 'create_schedule_block']
                );
                return;
            }
            
            if ($endObj <= $startObj) {
                ResponseHelper::sendValidationError(
                    'Dados inválidos',
                    ['end_datetime' => 'Data/hora de fim deve ser posterior à data/hora de início'],
                    ['action' => 'create_schedule_block']
                );
                return;
            }
            
            $blockModel = new ScheduleBlock();
            $blockId = $blockModel->insert([
                'tenant_id' => $tenantId,
                'professional_id' => $professionalId,
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'reason' => $reason
            ]);
            
            $block = $blockModel->findById($blockId);
            
            ResponseHelper::sendCreated($block, 'Bloqueio criado com sucesso');
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao criar bloqueio: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao criar bloqueio de agenda',
                'SCHEDULE_BLOCK_CREATE_ERROR',
                ['action' => 'create_schedule_block', 'tenant_id' => $tenantId ?? null]
            );
        }
    }
    
    /**
     * Deleta um bloqueio de agenda
     * DELETE /v1/clinic/schedule-blocks/:id
     */
    public function deleteBlock(string $blockId): void
    {
        try {
            PermissionHelper::require('view_professionals');
            
            $tenantId = Flight::get('tenant_id');
            $userId = Flight::get('user_id');
            
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'delete_schedule_block']);
                return;
            }
            
            $blockModel = new ScheduleBlock();
            $block = $blockModel->findById((int)$blockId);
            
            if (!$block || $block['tenant_id'] != $tenantId) {
                ResponseHelper::sendNotFoundError('Bloqueio', ['action' => 'delete_schedule_block', 'block_id' => $blockId]);
                return;
            }
            
            // Verifica permissão
            if (!$this->canEditSchedule($tenantId, $userId, (int)$block['professional_id'])) {
                ResponseHelper::sendForbiddenError('Você não tem permissão para deletar este bloqueio', ['action' => 'delete_schedule_block', 'block_id' => $blockId]);
                return;
            }
            
            $blockModel->delete((int)$blockId);
            
            ResponseHelper::sendNoContent();
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO ao deletar bloqueio: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
            }
            
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao deletar bloqueio de agenda',
                'SCHEDULE_BLOCK_DELETE_ERROR',
                ['action' => 'delete_schedule_block', 'block_id' => $blockId, 'tenant_id' => $tenantId ?? null]
            );
        }
    }
    
    /**
     * Verifica se o usuário pode ver a agenda de um profissional
     * 
     * @param int $tenantId ID do tenant
     * @param int|null $userId ID do usuário (null se API Key)
     * @param int $professionalId ID do profissional
     * @return bool True se pode ver
     */
    private function canViewSchedule(int $tenantId, ?int $userId, int $professionalId): bool
    {
        // Se é API Key (userId null), pode ver tudo
        if ($userId === null) {
            return true;
        }
        
        // Busca profissional para verificar user_id
        $professionalModel = new Professional();
        $professional = $professionalModel->findByTenantAndId($tenantId, $professionalId);
        
        if (!$professional) {
            return false;
        }
        
        // Se o profissional está vinculado a um usuário e é o mesmo usuário, pode ver
        if (!empty($professional['user_id']) && $professional['user_id'] == $userId) {
            return true;
        }
        
        // Verifica se o usuário tem permissão de admin ou é atendente
        // Atendentes podem ver todas as agendas
        $userModel = new \App\Models\User();
        $user = $userModel->findById($userId);
        
        if (!$user || $user['tenant_id'] != $tenantId) {
            return false;
        }
        
        // Admin pode ver tudo
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Verifica se o usuário é atendente (não é veterinário/profissional)
        // Busca se o usuário tem um profissional vinculado
        $userProfessional = $professionalModel->findByUserId($tenantId, $userId);
        
        // Se o usuário NÃO é um profissional (é atendente), pode ver todas as agendas
        if (!$userProfessional) {
            return true;
        }
        
        // Se o usuário é um profissional diferente, não pode ver
        return false;
    }
    
    /**
     * Verifica se o usuário pode editar a agenda de um profissional
     * 
     * @param int $tenantId ID do tenant
     * @param int|null $userId ID do usuário (null se API Key)
     * @param int $professionalId ID do profissional
     * @return bool True se pode editar
     */
    private function canEditSchedule(int $tenantId, ?int $userId, int $professionalId): bool
    {
        // Mesma lógica de visualização por enquanto
        // Pode ser expandido no futuro para permissões mais granulares
        return $this->canViewSchedule($tenantId, $userId, $professionalId);
    }
    
    /**
     * Calcula horários disponíveis baseado em:
     * - Horário de trabalho do profissional
     * - Bloqueios de agenda
     * - Agendamentos existentes
     * 
     * @param string $startTime Hora de início (HH:MM:SS)
     * @param string $endTime Hora de fim (HH:MM:SS)
     * @param int $duration Duração em minutos
     * @param string $date Data (YYYY-MM-DD)
     * @param array $blocks Bloqueios
     * @param array $appointments Agendamentos existentes
     * @return array Array de horários disponíveis (formato HH:MM)
     */
    private function calculateAvailableSlots(
        string $startTime,
        string $endTime,
        int $duration,
        string $date,
        array $blocks,
        array $appointments
    ): array {
        $availableSlots = [];
        
        // Converte horários para minutos do dia
        $startMinutes = $this->timeToMinutes($startTime);
        $endMinutes = $this->timeToMinutes($endTime);
        $durationMinutes = $duration;
        
        // Cria array de minutos ocupados
        $occupiedMinutes = [];
        
        // Adiciona bloqueios
        foreach ($blocks as $block) {
            $blockStart = new \DateTime($block['start_datetime']);
            $blockEnd = new \DateTime($block['end_datetime']);
            
            // Se o bloqueio é do mesmo dia
            if ($blockStart->format('Y-m-d') === $date) {
                $blockStartMinutes = (int)$blockStart->format('H') * 60 + (int)$blockStart->format('i');
                $blockEndMinutes = (int)$blockEnd->format('H') * 60 + (int)$blockEnd->format('i');
                
                for ($m = $blockStartMinutes; $m < $blockEndMinutes; $m++) {
                    $occupiedMinutes[$m] = true;
                }
            }
        }
        
        // Adiciona agendamentos
        foreach ($appointments as $apt) {
            $aptDateTime = new \DateTime($apt['appointment_date']);
            $aptStartMinutes = (int)$aptDateTime->format('H') * 60 + (int)$aptDateTime->format('i');
            $aptDuration = (int)($apt['duration_minutes'] ?? 30);
            $aptEndMinutes = $aptStartMinutes + $aptDuration;
            
            for ($m = $aptStartMinutes; $m < $aptEndMinutes; $m++) {
                $occupiedMinutes[$m] = true;
            }
        }
        
        // Gera slots disponíveis
        $currentMinutes = $startMinutes;
        while ($currentMinutes + $durationMinutes <= $endMinutes) {
            // Verifica se o slot está livre
            $isFree = true;
            for ($m = $currentMinutes; $m < $currentMinutes + $durationMinutes; $m++) {
                if (isset($occupiedMinutes[$m])) {
                    $isFree = false;
                    break;
                }
            }
            
            if ($isFree) {
                $availableSlots[] = $this->minutesToTime($currentMinutes);
            }
            
            // Avança de 15 em 15 minutos (pode ser configurável)
            $currentMinutes += 15;
        }
        
        return $availableSlots;
    }
    
    /**
     * Converte hora (HH:MM:SS) para minutos do dia
     * 
     * @param string $time Hora no formato HH:MM:SS
     * @return int Minutos desde meia-noite
     */
    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return (int)$parts[0] * 60 + (int)$parts[1];
    }
    
    /**
     * Converte minutos do dia para hora (HH:MM)
     * 
     * @param int $minutes Minutos desde meia-noite
     * @return string Hora no formato HH:MM
     */
    private function minutesToTime(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $mins);
    }
}

