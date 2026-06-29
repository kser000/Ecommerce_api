<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payment\ChargeCommand;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private PaymentGateway $gateway,
        private PaymentService $paymentService,
    ) {}

    /**
     * 結帳（第一階段）：扣庫存、建訂單、建 PENDING Payment、取得付款 URL。
     * 外部金流呼叫刻意放在 DB transaction 之外，避免因網路延遲持有 DB 鎖。
     *
     * @return array{order: Order, payment_url: string}
     */
    public function checkout(User $user, ?string $note = null): array
    {
        $cart = $user->cart()->with('items.product')->first();

        if (! $cart || $cart->items->isEmpty()) {
            throw new BusinessException('Cart is empty.');
        }

        // 交易一：扣庫存 + 建訂單（pending_payment）
        $order = DB::transaction(function () use ($user, $cart, $note) {
            $total      = '0';
            $orderItems = [];

            foreach ($cart->items->sortBy('product_id') as $item) {
                $product = $item->product;

                if (! $product || ! $product->is_active) {
                    throw new BusinessException("Product \"{$product?->name}\" is no longer available.");
                }

                $product = Product::lockForUpdate()->findOrFail($product->id);

                if ($product->stock < $item->quantity) {
                    throw new BusinessException("Insufficient stock for \"{$product->name}\".");
                }

                $product->decrement('stock', $item->quantity);

                $subtotal = bcmul((string) $product->price, (string) $item->quantity, 2);
                $total    = bcadd($total, $subtotal, 2);

                $orderItems[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $product->price,
                    'quantity'     => $item->quantity,
                ];
            }

            $order = Order::create([
                'user_id'      => $user->id,
                'status'       => 'pending_payment',
                'total_amount' => $total,
                'note'         => $note,
            ]);

            $order->items()->createMany($orderItems);
            $cart->items()->delete();

            return $order;
        });

        // 交易二：建立 PENDING Payment（在 transaction 外，獨立 commit）
        $payment = $this->paymentService->createPending($order);

        // 呼叫金流取得付款 URL（在所有 DB transaction 之外）
        $initResult = $this->gateway->initiate(new ChargeCommand(
            merchantTradeNo: $payment->idempotency_key,
            amount: (string) $order->total_amount,
            paymentMethod: $payment->payment_method,
            description: "Order #{$order->id}",
            customerEmail: $user->email,
        ));

        $payment->update(['gateway_reference' => $initResult->providerReference]);

        return [
            'order'       => $order->load('items'),
            'payment_url' => $initResult->paymentUrl,
        ];
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        if (! $order->canTransitionTo($newStatus)) {
            throw new BusinessException(
                "Cannot transition order from \"{$order->status}\" to \"{$newStatus}\"."
            );
        }

        $order->update(['status' => $newStatus]);

        return $order;
    }
}
