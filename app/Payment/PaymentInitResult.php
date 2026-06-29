<?php

namespace App\Payment;

/**
 * 「發動付款」的結果（非同步模式）。
 * 此時還沒有最終扣款結果，只拿到金流端交易序號與付款頁 URL，
 * 真正的成功 / 失敗稍後由金流以 webhook / callback 通知。
 */
readonly class PaymentInitResult
{
    public function __construct(
        public string $providerReference,
        public string $paymentUrl,
    ) {}
}
