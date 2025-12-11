<?php

namespace App\Controllers;

use App\Services\StripeService;
use App\Services\Logger;
use App\Utils\ResponseHelper;
use App\Utils\ErrorHandler;
use Flight;
use Config;

/**
 * Controller para gerenciar faturas
 */
class InvoiceController
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Lista todas as faturas (para administradores SaaS)
     * GET /v1/invoices
     */
    public function list(): void
    {
        try {
            $isSaasAdmin = Flight::get('is_saas_admin') ?? false;
            
            if (!$isSaasAdmin) {
                ResponseHelper::sendUnauthorizedError('Acesso negado. Apenas administradores do SaaS podem listar todas as faturas.', ['action' => 'list_invoices']);
                return;
            }
            
            $queryParams = Flight::request()->query;
            $limit = isset($queryParams['limit']) ? min(100, max(1, (int)$queryParams['limit'])) : 20;
            $startingAfter = $queryParams['starting_after'] ?? null;
            
            $options = ['limit' => $limit];
            if ($startingAfter) {
                $options['starting_after'] = $startingAfter;
            }
            if (!empty($queryParams['status'])) {
                $options['status'] = $queryParams['status'];
            }
            if (!empty($queryParams['customer'])) {
                $options['customer'] = $queryParams['customer'];
            }
            
            // Busca diretamente do Stripe usando conta principal
            $stripeClient = $this->stripeService->getClient();
            
            // ✅ CORREÇÃO: Expande customer para obter nome e email
            $options['expand'] = ['data.customer'];
            $invoices = $stripeClient->invoices->all($options);
            
            $invoicesData = [];
            foreach ($invoices->data as $invoice) {
                // Extrai informações do customer
                $customerName = null;
                $customerEmail = null;
                
                if (is_object($invoice->customer)) {
                    $customerName = $invoice->customer->name ?? null;
                    $customerEmail = $invoice->customer->email ?? null;
                } elseif (is_string($invoice->customer)) {
                    // Se customer não foi expandido, busca
                    try {
                        $customer = $stripeClient->customers->retrieve($invoice->customer);
                        $customerName = $customer->name ?? null;
                        $customerEmail = $customer->email ?? null;
                    } catch (\Exception $e) {
                        // Ignora erro
                    }
                }
                
                $invoicesData[] = [
                    'id' => $invoice->id,
                    'customer' => is_string($invoice->customer) ? $invoice->customer : $invoice->customer->id,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'amount_paid' => $invoice->amount_paid / 100,
                    'amount_due' => $invoice->amount_due / 100,
                    'currency' => strtoupper($invoice->currency),
                    'status' => $invoice->status,
                    'paid' => $invoice->paid,
                    'invoice_pdf' => $invoice->invoice_pdf,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                    'created' => date('Y-m-d H:i:s', $invoice->created)
                ];
            }
            
            ResponseHelper::sendSuccess($invoicesData, 200, null, [
                'has_more' => $invoices->has_more,
                'total' => count($invoicesData)
            ]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao listar faturas', 'INVOICE_LIST_ERROR', ['action' => 'list_invoices']);
        }
    }

    /**
     * Obtém fatura por ID
     * GET /v1/invoices/:id
     */
    public function get(string $id): void
    {
        try {
            $invoice = $this->stripeService->getInvoice($id);

            ResponseHelper::sendSuccess([
                'id' => $invoice->id,
                'customer' => $invoice->customer,
                'amount_paid' => $invoice->amount_paid / 100,
                'amount_due' => $invoice->amount_due / 100,
                'currency' => strtoupper($invoice->currency),
                'status' => $invoice->status,
                'paid' => $invoice->paid,
                'invoice_pdf' => $invoice->invoice_pdf,
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
                'created' => date('Y-m-d H:i:s', $invoice->created)
            ]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            ResponseHelper::sendNotFoundError('Fatura', ['action' => 'get_invoice', 'invoice_id' => $id]);
        } catch (\Exception $e) {
            ResponseHelper::sendGenericError($e, 'Erro ao obter fatura', 'INVOICE_GET_ERROR', ['action' => 'get_invoice', 'invoice_id' => $id]);
        }
    }
}

