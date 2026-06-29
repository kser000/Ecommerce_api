<?php

namespace App\Contracts;

use App\Payment\ChargeCommand;
use App\Payment\PaymentInitResult;

/**
 * 金流抽象介面（非同步模式）。
 *
 * initiate()：發動付款，回傳付款頁 URL，不含最終結果。
 * 最終結果由金流以 webhook / callback 通知，再交給 PaymentService::finalize() 處理。
 *
 * 程式只依賴這個介面：真實環境用 StripePaymentGateway，
 * 本機 / 測試用 SimulatedPaymentGateway，靠 PAYMENT_PROVIDER 環境變數切換。
 */
interface PaymentGateway
{
    public function initiate(ChargeCommand $command): PaymentInitResult;
}
