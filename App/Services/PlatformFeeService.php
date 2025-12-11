<?php

namespace App\Services;

use Config;
use App\Services\Logger;

/**
 * Serviço para gerenciar taxa de plataforma
 * 
 * Permite cobrar uma taxa percentual sobre pagamentos das clínicas
 * Desativado por padrão, pode ser ativado via configuração
 */
class PlatformFeeService
{
    /**
     * Verifica se taxa de plataforma está ativada
     * 
     * @return bool True se está ativada
     */
    public static function isEnabled(): bool
    {
        $enabled = Config::get('PLATFORM_FEE_ENABLED', 'false');
        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Obtém percentual da taxa de plataforma
     * 
     * @return float Percentual (ex: 2.5 para 2.5%)
     */
    public static function getFeePercentage(): float
    {
        if (!self::isEnabled()) {
            return 0.0;
        }

        $percentage = Config::get('PLATFORM_FEE_PERCENTAGE', '0');
        return (float)$percentage;
    }

    /**
     * Calcula taxa de plataforma para um valor
     * 
     * @param int $amount Valor em centavos (ex: 10000 = R$ 100,00)
     * @return int Taxa em centavos
     */
    public static function calculateFee(int $amount): int
    {
        if (!self::isEnabled()) {
            return 0;
        }

        $percentage = self::getFeePercentage();
        if ($percentage <= 0) {
            return 0;
        }

        // Calcula taxa: (amount * percentage) / 100
        $fee = (int)round(($amount * $percentage) / 100);
        
        Logger::debug("Taxa de plataforma calculada", [
            'amount' => $amount,
            'percentage' => $percentage,
            'fee' => $fee
        ]);

        return $fee;
    }

    /**
     * Calcula valor líquido após deduzir taxa
     * 
     * @param int $amount Valor bruto em centavos
     * @return int Valor líquido em centavos
     */
    public static function calculateNetAmount(int $amount): int
    {
        $fee = self::calculateFee($amount);
        return $amount - $fee;
    }

    /**
     * Aplica taxa de plataforma em um PaymentIntent ou Charge
     * 
     * NOTA: Para aplicar taxa de plataforma, você precisa usar "destination charges"
     * ou criar um Transfer separado após o pagamento ser confirmado.
     * 
     * Este método apenas registra a taxa calculada. A implementação real deve ser
     * feita no momento da criação do PaymentIntent usando 'on_behalf_of' e 'transfer_data',
     * ou criando um Transfer após o pagamento ser confirmado.
     * 
     * @param string $paymentIntentId ID do PaymentIntent
     * @param string|null $stripeAccountId ID da conta Connect (clínica)
     * @return bool True se taxa foi calculada e registrada
     */
    public static function applyFeeToPayment(string $paymentIntentId, ?string $stripeAccountId = null): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        try {
            // Busca PaymentIntent para obter valor
            $stripeService = new StripeService();
            $options = [];
            if ($stripeAccountId) {
                $options['stripe_account'] = $stripeAccountId;
            }
            
            $paymentIntent = $stripeService->getClient()->paymentIntents->retrieve(
                $paymentIntentId,
                $options
            );

            $amount = $paymentIntent->amount;
            $fee = self::calculateFee($amount);

            if ($fee <= 0) {
                return false;
            }

            // ✅ NOTA: Para aplicar a taxa, você precisa:
            // 1. Usar destination charges ao criar o PaymentIntent (com 'on_behalf_of' e 'transfer_data')
            // 2. OU criar um Transfer após o pagamento ser confirmado
            // Por enquanto, apenas registramos a taxa calculada
            
            Logger::info("Taxa de plataforma calculada (não aplicada automaticamente)", [
                'payment_intent_id' => $paymentIntentId,
                'stripe_account_id' => $stripeAccountId,
                'amount' => $amount,
                'fee' => $fee,
                'note' => 'Taxa deve ser aplicada via destination charges ou Transfer manual'
            ]);

            return true;
        } catch (\Exception $e) {
            Logger::error("Erro ao calcular taxa de plataforma", [
                'payment_intent_id' => $paymentIntentId,
                'stripe_account_id' => $stripeAccountId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

