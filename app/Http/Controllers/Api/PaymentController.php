<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Payment;
use App\Payment\ChargeResult;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payments', description: 'Payment webhook simulation (dev only)')]
class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * 模擬金流 callback（開發 / 測試用）。
     * 真實環境的 callback 由外部金流直接呼叫（e.g. Stripe Webhook）。
     *
     * 使用方式：
     *   POST /api/payments/dev/complete?key={idempotency_key}&result=success
     *   POST /api/payments/dev/complete?key={idempotency_key}&result=fail
     */
    #[OA\Post(
        path: '/api/payments/dev/complete',
        summary: '模擬金流 callback（result=success|fail）',
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'key', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'result', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['success', 'fail'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order updated'),
            new OA\Response(response: 422, description: 'Payment failed'),
        ]
    )]
    public function devComplete(Request $request): mixed
    {
        $payment = Payment::where('idempotency_key', $request->query('key'))->firstOrFail();

        $result = $request->query('result', 'success') === 'success'
            ? ChargeResult::success('DEV-' . time())
            : ChargeResult::failure('dev simulated failure');

        try {
            $order = $this->paymentService->finalize($payment, $result);

            return new OrderResource($order->load('items'));
        } catch (BusinessException $e) {
            // 付款失敗已記錄為 FAILED，吞掉例外仍回 422 給前端
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
