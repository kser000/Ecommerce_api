<?php

namespace App\Payment;

/**
 * 送給外部金流的扣款請求。
 * merchantTradeNo 為我方產生的唯一交易編號（idempotency key）。
 */
readonly class ChargeCommand
{
    public function __construct(
        public string $merchantTradeNo,
        public string $amount,
        public string $paymentMethod,
        public string $description,
        public string $customerEmail,
    ) {}
}
