<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Pet;
use App\Services\StripeService;
use App\Services\Logger;

/**
 * Service para integrar agendamentos com sistema de pagamentos
 */
class AppointmentService
{
    private StripeService $stripeService;
    private Appointment $appointmentModel;
    private Customer $customerModel;
    private Pet $petModel;

    public function __construct(
        StripeService $stripeService,
        Appointment $appointmentModel,
        Customer $customerModel,
        Pet $petModel
    ) {
        $this->stripeService = $stripeService;
        $this->appointmentModel = $appointmentModel;
        $this->customerModel = $customerModel;
        $this->petModel = $petModel;
    }

    /**
     * Cria agendamento com integração de pagamento
     * 
     * @param int $tenantId ID do tenant
     * @param array $appointmentData Dados do agendamento
     * @param string|null $priceId ID do preço no Stripe (opcional)
     * @param bool $autoCharge Se true, cobra automaticamente (padrão: false)
     * @return array Dados do agendamento criado e invoice (se aplicável)
     * @throws \RuntimeException Se validações falharem
     */
    public function createAppointmentWithPayment(
        int $tenantId,
        array $appointmentData,
        ?string $priceId = null,
        bool $autoCharge = false
    ): array {
        // 1. Valida se customer tem stripe_customer_id
        $customer = $this->customerModel->findByTenantAndId($tenantId, (int)$appointmentData['customer_id']);
        if (!$customer) {
            throw new \RuntimeException("Customer não encontrado");
        }

        if (empty($customer['stripe_customer_id']) && $priceId) {
            throw new \RuntimeException("Customer não possui Stripe Customer ID. É necessário criar o customer no Stripe primeiro.");
        }

        // 2. Cria o agendamento
        $appointmentId = $this->appointmentModel->create($tenantId, $appointmentData);
        $appointment = $this->appointmentModel->findById($appointmentId);

        $result = [
            'appointment' => $appointment,
            'invoice' => null
        ];

        // 3. Se price_id foi fornecido, cria invoice
        if ($priceId && !empty($customer['stripe_customer_id'])) {
            try {
                $invoice = $this->createInvoiceForAppointment(
                    $tenantId,
                    $appointmentId,
                    $customer['stripe_customer_id'],
                    $priceId,
                    $appointmentData,
                    $autoCharge
                );

                // 4. Atualiza agendamento com invoice_id
                $this->appointmentModel->update($appointmentId, [
                    'stripe_invoice_id' => $invoice->id
                ]);

                $appointment = $this->appointmentModel->findById($appointmentId);
                $result['appointment'] = $appointment;
                $result['invoice'] = [
                    'id' => $invoice->id,
                    'status' => $invoice->status,
                    'amount_due' => $invoice->amount_due / 100,
                    'amount_paid' => $invoice->amount_paid / 100,
                    'currency' => strtoupper($invoice->currency),
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                    'invoice_pdf' => $invoice->invoice_pdf
                ];

                Logger::info("Agendamento criado com invoice", [
                    'tenant_id' => $tenantId,
                    'appointment_id' => $appointmentId,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer['id']
                ]);
            } catch (\Exception $e) {
                Logger::error("Erro ao criar invoice para agendamento", [
                    'tenant_id' => $tenantId,
                    'appointment_id' => $appointmentId,
                    'error' => $e->getMessage()
                ]);
                // Não falha o agendamento se invoice falhar, apenas loga o erro
                // O agendamento pode ser pago depois
            }
        }

        return $result;
    }

    /**
     * Cria invoice no Stripe para um agendamento
     * 
     * @param int $tenantId ID do tenant
     * @param int $appointmentId ID do agendamento
     * @param string $stripeCustomerId ID do customer no Stripe
     * @param string $priceId ID do preço no Stripe
     * @param array $appointmentData Dados do agendamento
     * @param bool $autoCharge Se true, finaliza e cobra automaticamente
     * @return \Stripe\Invoice
     */
    private function createInvoiceForAppointment(
        int $tenantId,
        int $appointmentId,
        string $stripeCustomerId,
        string $priceId,
        array $appointmentData,
        bool $autoCharge = false
    ): \Stripe\Invoice {
        // Busca dados do pet para descrição
        $pet = null;
        if (!empty($appointmentData['pet_id'])) {
            $pet = $this->petModel->findByTenantAndId($tenantId, (int)$appointmentData['pet_id']);
        }

        $petName = $pet ? $pet['name'] : 'Pet';
        $appointmentDate = $appointmentData['appointment_date'] ?? '';
        $appointmentType = $appointmentData['type'] ?? 'consulta';

        // Cria invoice
        $invoice = $this->stripeService->createInvoice([
            'customer' => $stripeCustomerId,
            'auto_advance' => $autoCharge, // Se true, cobra automaticamente
            'collection_method' => $autoCharge ? 'charge_automatically' : 'send_invoice',
            'description' => "Agendamento: {$appointmentType} - {$petName}",
            'metadata' => [
                'appointment_id' => $appointmentId,
                'tenant_id' => $tenantId,
                'pet_id' => $appointmentData['pet_id'] ?? null,
                'appointment_date' => $appointmentDate,
                'appointment_type' => $appointmentType
            ]
        ]);

        // Adiciona item à invoice
        $invoiceItemData = [
            'customer' => $stripeCustomerId,
            'invoice' => $invoice->id,
            'price' => $priceId,
            'description' => "{$appointmentType} - {$petName} - " . date('d/m/Y H:i', strtotime($appointmentDate)),
            'metadata' => [
                'appointment_id' => $appointmentId,
                'tenant_id' => $tenantId
            ]
        ];

        // Se tiver conta Stripe Connect, usa ela
        if ($stripeAccountId) {
            $invoiceItemData['stripe_account_id'] = $stripeAccountId;
        }

        $this->stripeService->createInvoiceItem($invoiceItemData);

        // Se autoCharge, finaliza e cobra
        if ($autoCharge) {
            $finalizeOptions = [];
            if ($stripeAccountId) {
                $finalizeOptions['stripe_account_id'] = $stripeAccountId;
            }
            $invoice = $this->stripeService->finalizeInvoice($invoice->id, $finalizeOptions);
        }

        return $invoice;
    }

    /**
     * Processa pagamento de um agendamento existente
     * 
     * @param int $tenantId ID do tenant
     * @param int $appointmentId ID do agendamento
     * @param string $priceId ID do preço no Stripe
     * @param bool $autoCharge Se true, cobra automaticamente
     * @return array Dados do invoice criado
     * @throws \RuntimeException Se agendamento não existir ou já tiver invoice
     */
    public function processAppointmentPayment(
        int $tenantId,
        int $appointmentId,
        string $priceId,
        bool $autoCharge = false
    ): array {
        // Busca agendamento
        $appointment = $this->appointmentModel->findByTenantAndId($tenantId, $appointmentId);
        if (!$appointment) {
            throw new \RuntimeException("Agendamento não encontrado");
        }

        // Verifica se já tem invoice
        if (!empty($appointment['stripe_invoice_id'])) {
            throw new \RuntimeException("Agendamento já possui invoice. Use o endpoint de atualizar invoice.");
        }

        // Busca customer
        $customer = $this->customerModel->findByTenantAndId($tenantId, (int)$appointment['customer_id']);
        if (!$customer || empty($customer['stripe_customer_id'])) {
            throw new \RuntimeException("Customer não possui Stripe Customer ID");
        }

        // Cria invoice
        $invoice = $this->createInvoiceForAppointment(
            $tenantId,
            $appointmentId,
            $customer['stripe_customer_id'],
            $priceId,
            $appointment,
            $autoCharge
        );

        // Atualiza agendamento
        $this->appointmentModel->update($appointmentId, [
            'stripe_invoice_id' => $invoice->id
        ]);

        Logger::info("Pagamento processado para agendamento", [
            'tenant_id' => $tenantId,
            'appointment_id' => $appointmentId,
            'invoice_id' => $invoice->id
        ]);

        return [
            'invoice' => [
                'id' => $invoice->id,
                'status' => $invoice->status,
                'amount_due' => $invoice->amount_due / 100,
                'amount_paid' => $invoice->amount_paid / 100,
                'currency' => strtoupper($invoice->currency),
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
                'invoice_pdf' => $invoice->invoice_pdf
            ]
        ];
    }

    /**
     * Obtém invoice de um agendamento
     * 
     * @param int $tenantId ID do tenant
     * @param int $appointmentId ID do agendamento
     * @return array|null Dados do invoice ou null se não existir
     */
    public function getAppointmentInvoice(int $tenantId, int $appointmentId): ?array
    {
        $appointment = $this->appointmentModel->findByTenantAndId($tenantId, $appointmentId);
        if (!$appointment || empty($appointment['stripe_invoice_id'])) {
            return null;
        }

        try {
            $invoice = $this->stripeService->getInvoice($appointment['stripe_invoice_id']);
            return [
                'id' => $invoice->id,
                'status' => $invoice->status,
                'amount_due' => $invoice->amount_due / 100,
                'amount_paid' => $invoice->amount_paid / 100,
                'currency' => strtoupper($invoice->currency),
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
                'invoice_pdf' => $invoice->invoice_pdf,
                'paid' => $invoice->paid,
                'created' => date('Y-m-d H:i:s', $invoice->created)
            ];
        } catch (\Exception $e) {
            Logger::error("Erro ao obter invoice do agendamento", [
                'appointment_id' => $appointmentId,
                'invoice_id' => $appointment['stripe_invoice_id'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Atualiza status do agendamento baseado no status do invoice
     * Chamado via webhook quando invoice é pago
     * 
     * @param string $invoiceId ID do invoice no Stripe
     * @return bool True se agendamento foi atualizado
     */
    public function updateAppointmentStatusFromInvoice(string $invoiceId): bool
    {
        try {
            // Busca agendamento pelo invoice_id
            $appointment = $this->appointmentModel->findBy('stripe_invoice_id', $invoiceId);
            if (!$appointment) {
                Logger::warning("Agendamento não encontrado para invoice", [
                    'invoice_id' => $invoiceId
                ]);
                return false;
            }

            // Busca invoice no Stripe
            $invoice = $this->stripeService->getInvoice($invoiceId);

            // Atualiza status do agendamento baseado no status do invoice
            $newStatus = $appointment['status'];
            if ($invoice->paid && $invoice->status === 'paid') {
                // Se invoice foi pago, confirma agendamento
                if ($appointment['status'] === 'scheduled') {
                    $newStatus = 'confirmed';
                }
            } elseif ($invoice->status === 'void' || $invoice->status === 'uncollectible') {
                // Se invoice foi cancelado ou não pago, cancela agendamento
                $newStatus = 'cancelled';
            }

            if ($newStatus !== $appointment['status']) {
                $this->appointmentModel->update($appointment['id'], [
                    'status' => $newStatus
                ]);

                Logger::info("Status do agendamento atualizado via webhook", [
                    'appointment_id' => $appointment['id'],
                    'old_status' => $appointment['status'],
                    'new_status' => $newStatus,
                    'invoice_id' => $invoiceId,
                    'invoice_status' => $invoice->status
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Logger::error("Erro ao atualizar status do agendamento via invoice", [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

