<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamType;
use App\Models\Customer;
use App\Models\Pet;
use App\Services\StripeService;
use App\Services\Logger;

/**
 * Service para integrar exames com sistema de pagamentos
 */
class ExamService
{
    private StripeService $stripeService;
    private Exam $examModel;
    private ExamType $examTypeModel;
    private Customer $customerModel;
    private Pet $petModel;

    public function __construct(
        StripeService $stripeService,
        Exam $examModel,
        ExamType $examTypeModel,
        Customer $customerModel,
        Pet $petModel
    ) {
        $this->stripeService = $stripeService;
        $this->examModel = $examModel;
        $this->examTypeModel = $examTypeModel;
        $this->customerModel = $customerModel;
        $this->petModel = $petModel;
    }

    /**
     * Cria exame com integração de pagamento
     * 
     * @param int $tenantId ID do tenant
     * @param array $examData Dados do exame
     * @param string|null $priceId ID do preço no Stripe (opcional)
     * @param bool $autoCharge Se true, cobra automaticamente (padrão: false)
     * @return array Dados do exame criado e invoice (se aplicável)
     * @throws \RuntimeException Se validações falharem
     */
    public function createExamWithPayment(
        int $tenantId,
        array $examData,
        ?string $priceId = null,
        bool $autoCharge = false
    ): array {
        // 1. Valida se customer tem stripe_customer_id
        $customer = $this->customerModel->findByTenantAndId($tenantId, (int)$examData['client_id']);
        if (!$customer) {
            throw new \RuntimeException("Cliente não encontrado");
        }

        if (empty($customer['stripe_customer_id']) && $priceId) {
            throw new \RuntimeException("Cliente não possui Stripe Customer ID. É necessário criar o customer no Stripe primeiro.");
        }

        // 2. Cria o exame
        $examId = $this->examModel->create($tenantId, $examData);
        $exam = $this->examModel->findById($examId);

        $result = [
            'exam' => $exam,
            'invoice' => null
        ];

        // 3. Se price_id foi fornecido, cria invoice
        if ($priceId && !empty($customer['stripe_customer_id'])) {
            try {
                $invoice = $this->createInvoiceForExam(
                    $tenantId,
                    $examId,
                    $customer['stripe_customer_id'],
                    $priceId,
                    $examData,
                    $autoCharge
                );

                // 4. Atualiza exame com invoice_id
                $this->examModel->update($examId, [
                    'stripe_invoice_id' => $invoice->id
                ]);

                $exam = $this->examModel->findById($examId);
                $result['exam'] = $exam;
                $result['invoice'] = [
                    'id' => $invoice->id,
                    'status' => $invoice->status,
                    'amount_due' => $invoice->amount_due / 100,
                    'amount_paid' => $invoice->amount_paid / 100,
                    'currency' => strtoupper($invoice->currency),
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                    'invoice_pdf' => $invoice->invoice_pdf
                ];

                Logger::info("Exame criado com invoice", [
                    'tenant_id' => $tenantId,
                    'exam_id' => $examId,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer['id']
                ]);
            } catch (\Exception $e) {
                Logger::error("Erro ao criar invoice para exame", [
                    'tenant_id' => $tenantId,
                    'exam_id' => $examId,
                    'error' => $e->getMessage()
                ]);
                // Não falha o exame se invoice falhar, apenas loga o erro
                // O exame pode ser pago depois
            }
        }

        return $result;
    }

    /**
     * Cria invoice no Stripe para um exame
     * 
     * @param int $tenantId ID do tenant
     * @param int $examId ID do exame
     * @param string $stripeCustomerId ID do customer no Stripe
     * @param string $priceId ID do preço no Stripe
     * @param array $examData Dados do exame
     * @param bool $autoCharge Se true, finaliza e cobra automaticamente
     * @return \Stripe\Invoice
     */
    private function createInvoiceForExam(
        int $tenantId,
        int $examId,
        string $stripeCustomerId,
        string $priceId,
        array $examData,
        bool $autoCharge = false
    ): \Stripe\Invoice {
        // Busca dados do pet e tipo de exame para descrição
        $pet = null;
        if (!empty($examData['pet_id'])) {
            $pet = $this->petModel->findByTenantAndId($tenantId, (int)$examData['pet_id']);
        }

        $examType = null;
        if (!empty($examData['exam_type_id'])) {
            $examType = $this->examTypeModel->findByTenantAndId($tenantId, (int)$examData['exam_type_id']);
        }

        $petName = $pet ? $pet['name'] : 'Pet';
        $examTypeName = $examType ? $examType['name'] : 'Exame';
        $examDate = $examData['exam_date'] ?? '';

        // Cria invoice
        $invoice = $this->stripeService->createInvoice([
            'customer' => $stripeCustomerId,
            'auto_advance' => $autoCharge, // Se true, cobra automaticamente
            'collection_method' => $autoCharge ? 'charge_automatically' : 'send_invoice',
            'description' => "Exame: {$examTypeName} - {$petName}",
            'metadata' => [
                'exam_id' => $examId,
                'tenant_id' => $tenantId,
                'pet_id' => $examData['pet_id'] ?? null,
                'exam_date' => $examDate,
                'exam_type_id' => $examData['exam_type_id'] ?? null
            ]
        ]);

        // Adiciona item à invoice
        $invoiceItemData = [
            'customer' => $stripeCustomerId,
            'invoice' => $invoice->id,
            'price' => $priceId,
            'description' => "{$examTypeName} - {$petName} - " . date('d/m/Y', strtotime($examDate)),
            'metadata' => [
                'exam_id' => $examId,
                'tenant_id' => $tenantId
            ]
        ];

        // Se tiver conta Stripe Connect, usa ela
        $stripeAccountId = null; // TODO: Buscar do tenant se necessário
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
     * Processa pagamento de um exame existente
     * 
     * @param int $tenantId ID do tenant
     * @param int $examId ID do exame
     * @param string $priceId ID do preço no Stripe
     * @param bool $autoCharge Se true, cobra automaticamente
     * @return array Dados do invoice criado
     * @throws \RuntimeException Se exame não existir ou já tiver invoice
     */
    public function processExamPayment(
        int $tenantId,
        int $examId,
        string $priceId,
        bool $autoCharge = false
    ): array {
        // Busca exame
        $exam = $this->examModel->findByTenantAndId($tenantId, $examId);
        if (!$exam) {
            throw new \RuntimeException("Exame não encontrado");
        }

        // Verifica se já tem invoice
        if (!empty($exam['stripe_invoice_id'])) {
            throw new \RuntimeException("Exame já possui invoice. Use o endpoint de atualizar invoice.");
        }

        // Busca customer
        $customer = $this->customerModel->findByTenantAndId($tenantId, (int)$exam['client_id']);
        if (!$customer || empty($customer['stripe_customer_id'])) {
            throw new \RuntimeException("Cliente não possui Stripe Customer ID");
        }

        // Cria invoice
        $invoice = $this->createInvoiceForExam(
            $tenantId,
            $examId,
            $customer['stripe_customer_id'],
            $priceId,
            $exam,
            $autoCharge
        );

        // Atualiza exame
        $this->examModel->update($examId, [
            'stripe_invoice_id' => $invoice->id
        ]);

        Logger::info("Pagamento processado para exame", [
            'tenant_id' => $tenantId,
            'exam_id' => $examId,
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
     * Obtém invoice de um exame
     * 
     * @param int $tenantId ID do tenant
     * @param int $examId ID do exame
     * @return array|null Dados do invoice ou null se não existir
     */
    public function getExamInvoice(int $tenantId, int $examId): ?array
    {
        $exam = $this->examModel->findByTenantAndId($tenantId, $examId);
        if (!$exam || empty($exam['stripe_invoice_id'])) {
            return null;
        }

        try {
            $invoice = $this->stripeService->getInvoice($exam['stripe_invoice_id']);
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
            Logger::error("Erro ao obter invoice do exame", [
                'exam_id' => $examId,
                'invoice_id' => $exam['stripe_invoice_id'],
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}

