<?php

namespace App\Controllers;

use App\Models\Pet;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Professional;
use App\Utils\PermissionHelper;
use App\Utils\ResponseHelper;
use Flight;
use Config;

/**
 * Controller para Busca Avançada
 */
class SearchController
{
    private Pet $petModel;
    private Customer $customerModel;
    private Appointment $appointmentModel;
    private Professional $professionalModel;

    public function __construct(
        Pet $petModel,
        Customer $customerModel,
        Appointment $appointmentModel,
        Professional $professionalModel
    ) {
        $this->petModel = $petModel;
        $this->customerModel = $customerModel;
        $this->appointmentModel = $appointmentModel;
        $this->professionalModel = $professionalModel;
    }

    /**
     * Busca global (pets, clientes, agendamentos)
     * GET /v1/clinic/search
     */
    public function globalSearch(): void
    {
        try {
            PermissionHelper::require('view_pets'); // Permissão mínima para buscar

            $tenantId = Flight::get('tenant_id');
            if ($tenantId === null) {
                ResponseHelper::sendUnauthorizedError('Não autenticado', ['action' => 'global_search']);
                return;
            }

            $query = Flight::request()->query->getData();
            $searchTerm = trim($query['q'] ?? '');
            $types = isset($query['types']) ? explode(',', $query['types']) : ['pets', 'customers', 'appointments'];
            $limit = isset($query['limit']) ? (int)$query['limit'] : 20;

            if (empty($searchTerm)) {
                ResponseHelper::sendValidationError(
                    'Termo de busca obrigatório',
                    ['q' => 'O termo de busca não pode estar vazio'],
                    ['action' => 'global_search']
                );
                return;
            }

            $results = [
                'pets' => [],
                'customers' => [],
                'appointments' => [],
                'professionals' => []
            ];

            // Busca Pets
            if (in_array('pets', $types)) {
                $pets = $this->searchPets($tenantId, $searchTerm, $limit);
                $results['pets'] = $pets;
            }

            // Busca Customers
            if (in_array('customers', $types)) {
                $customers = $this->searchCustomers($tenantId, $searchTerm, $limit);
                $results['customers'] = $customers;
            }

            // Busca Appointments
            if (in_array('appointments', $types)) {
                $appointments = $this->searchAppointments($tenantId, $searchTerm, $limit);
                $results['appointments'] = $appointments;
            }

            // Busca Professionals
            if (in_array('professionals', $types)) {
                $professionals = $this->searchProfessionals($tenantId, $searchTerm, $limit);
                $results['professionals'] = $professionals;
            }

            // Conta total de resultados
            $totalResults = count($results['pets']) + count($results['customers']) + 
                          count($results['appointments']) + count($results['professionals']);

            ResponseHelper::sendSuccess([
                'query' => $searchTerm,
                'types' => $types,
                'total_results' => $totalResults,
                'results' => $results
            ]);
        } catch (\Throwable $e) {
            if (Config::isDevelopment()) {
                error_log("ERRO na busca global: " . $e->getMessage());
                error_log("Arquivo: " . $e->getFile() . ":" . $e->getLine());
                error_log("Trace: " . $e->getTraceAsString());
            }
            ResponseHelper::sendGenericError(
                $e,
                'Erro ao realizar busca',
                'SEARCH_ERROR',
                ['tenant_id' => $tenantId ?? null]
            );
        }
    }

    /**
     * Busca pets por nome, chip, espécie, raça
     */
    private function searchPets(int $tenantId, string $searchTerm, int $limit): array
    {
        $db = \App\Utils\Database::getInstance();
        $searchPattern = '%' . $searchTerm . '%';
        
        $stmt = $db->prepare(
            "SELECT p.*, c.name as customer_name, c.phone as customer_phone
             FROM pets p
             LEFT JOIN customers c ON p.customer_id = c.id
             WHERE p.tenant_id = :tenant_id
             AND p.deleted_at IS NULL
             AND (
                 p.name LIKE :search
                 OR p.chip LIKE :search
                 OR p.species LIKE :search
                 OR p.breed LIKE :search
                 OR c.name LIKE :search
             )
             ORDER BY p.name ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':search', $searchPattern, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca customers por nome, email, telefone, CPF
     */
    private function searchCustomers(int $tenantId, string $searchTerm, int $limit): array
    {
        $db = \App\Utils\Database::getInstance();
        $searchPattern = '%' . $searchTerm . '%';
        
        $stmt = $db->prepare(
            "SELECT *
             FROM customers
             WHERE tenant_id = :tenant_id
             AND deleted_at IS NULL
             AND (
                 name LIKE :search
                 OR email LIKE :search
                 OR phone LIKE :search
                 OR cpf LIKE :search
             )
             ORDER BY name ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':search', $searchPattern, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca appointments por pet, customer, professional, tipo, observações
     */
    private function searchAppointments(int $tenantId, string $searchTerm, int $limit): array
    {
        $db = \App\Utils\Database::getInstance();
        $searchPattern = '%' . $searchTerm . '%';
        
        $stmt = $db->prepare(
            "SELECT a.*, 
                    p.name as pet_name,
                    c.name as customer_name,
                    pr.name as professional_name
             FROM appointments a
             LEFT JOIN pets p ON a.pet_id = p.id
             LEFT JOIN customers c ON a.customer_id = c.id
             LEFT JOIN professionals pr ON a.professional_id = pr.id
             WHERE a.tenant_id = :tenant_id
             AND a.deleted_at IS NULL
             AND (
                 p.name LIKE :search
                 OR c.name LIKE :search
                 OR pr.name LIKE :search
                 OR a.type LIKE :search
                 OR a.notes LIKE :search
             )
             ORDER BY a.appointment_date DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':search', $searchPattern, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca professionals por nome, CRMV, email, telefone
     */
    private function searchProfessionals(int $tenantId, string $searchTerm, int $limit): array
    {
        $db = \App\Utils\Database::getInstance();
        $searchPattern = '%' . $searchTerm . '%';
        
        $stmt = $db->prepare(
            "SELECT *
             FROM professionals
             WHERE tenant_id = :tenant_id
             AND status = 'active'
             AND (
                 name LIKE :search
                 OR crmv LIKE :search
                 OR email LIKE :search
                 OR phone LIKE :search
             )
             ORDER BY name ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':search', $searchPattern, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

