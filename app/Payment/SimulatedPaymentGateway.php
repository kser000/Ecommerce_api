<?php

namespace App\Payment;

use App\Contracts\PaymentGateway;

/**
 * 模擬金流（開發 / 測試用，預設啟用）。
 * initiate() 不會真的去外部，只回一個指向本機 dev endpoint 的「付款連結」，
 * 由 POST /api/payments/dev/complete 模擬金流稍後打進來的 callback。
 */
class SimulatedPaymentGateway implements PaymentGateway
{
    public function initiate(ChargeCommand $command): PaymentInitResult
    {
        $reference  = 'SIM-' . time();
        $paymentUrl = url('/api/payments/dev/complete') . '?key=' . $command->merchantTradeNo . '&result=success';

        return new PaymentInitResult($reference, $paymentUrl);
    }
}
