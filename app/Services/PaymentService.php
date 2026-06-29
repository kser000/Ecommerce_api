<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\Payment;
use App\Payment\ChargeResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 繳費的資料庫交易單元。
 * 只負責本地資料一致性；呼叫外部金流的動作放在交易之外（由 OrderService 編排）。
 *
 * createPending()：交易一 — 建立 PENDING Payment 紀錄，立即 commit。
 * finalize()：交易二 — 依金流結果更新付款狀態；成功才將訂單轉為 paid，失敗則歸還庫存。
 */
class PaymentService
{
    /**
     * 建立一筆 PENDING 繳費紀錄（立即 commit，不含外部金流呼叫）。
     */
    public function createPending(Order $order, string $paymentMethod = 'simulated'): Payment
    {
        return DB::transaction(function () use ($order, $paymentMethod) {
            if ($order->status !== 'pending_payment') {
                throw new BusinessException("此訂單目前不可付款（狀態：{$order->status}）。");
            }

            return Payment::create([
                'order_id'        => $order->id,
                'amount'          => $order->total_amount,
                'payment_method'  => $paymentMethod,
                'idempotency_key' => 'ord_' . $order->id . '_' . Str::random(8),
                'status'          => 'pending',
            ]);
        });
    }

    /**
     * 依金流結果完成付款：
     * - 成功 → 訂單轉為 paid
     * - 失敗 → 訂單轉為 cancelled，歸還庫存
     * 具冪等性：同一筆 Payment 若已是 success，直接回傳，不重複處理。
     */
    /**
     * 依金流結果完成付款。
     * 刻意將 throw 放在 transaction 之外，讓失敗狀態（cancelled + 還原庫存）能確實 commit，
     * 對應 Java 的 @Transactional(noRollbackFor = BusinessException.class) 模式。
     */
    public function finalize(Payment $payment, ChargeResult $result): Order
    {
        DB::transaction(function () use ($payment, $result) {
            $payment->refresh();

            if ($payment->status === 'success') {
                return;
            }

            if (! $result->success) {
                $payment->update([
                    'status'            => 'failed',
                    'gateway_reference' => $result->reference,
                ]);

                $order = $payment->order()->with('items.product')->first();
                $this->restoreStock($order);
                $order->update(['status' => 'cancelled']);

                return; // 不在 transaction 內丟例外，讓 commit 先發生
            }

            $payment->update([
                'status'            => 'success',
                'gateway_reference' => $result->reference,
            ]);

            $payment->order->update(['status' => 'paid']);
        });

        // Transaction commit 後再丟例外，對應 noRollbackFor 模式
        $payment->refresh();
        if ($payment->status === 'failed') {
            throw new BusinessException('付款失敗：' . $result->message);
        }

        return $payment->order()->with('items')->first();
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock', $item->quantity);
            }
        }
    }
}
