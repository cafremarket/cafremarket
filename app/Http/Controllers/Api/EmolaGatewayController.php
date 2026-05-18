<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Emola\EmolaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmolaGatewayController extends Controller
{
    public function __construct(private readonly EmolaClient $client)
    {
    }

    /**
     * Initiate C2B USSD push (standalone API / testing).
     */
    public function pay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^(86|87)\d{7}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
            'order_id' => ['required'],
        ]);

        $transId = $this->client->generateTransId();
        $refNo = 'REF'.preg_replace('/[^A-Za-z0-9]/', '', (string) $validated['order_id']);
        $refNo = substr($refNo, 0, 20);
        $amount = (string) intval($validated['amount']);

        $order = Order::find($validated['order_id']);

        try {
            $res = $this->client->pushPayment(
                $validated['phone'],
                $amount,
                $transId,
                $refNo,
            );

            if ($order) {
                $order->emola_trans_id = $transId;
                $order->emola_ref_no = $refNo;
                $order->emola_gwtransid = $res->gwtransid;
                $order->emola_gateway_error = $res->gatewayError;
                $order->emola_gateway_description = $res->gatewayDescription;
                $original = $res->originalData ?? [];
                $order->emola_error_code = $original['errorCode'] ?? null;
                $order->emola_message = $original['message'] ?? null;
                $order->emola_request_id = $original['reqeustId'] ?? null;
                $order->order_status_id = Order::STATUS_WAITING_FOR_PAYMENT;
                $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                $order->save();
            }

            $accepted = $res->isUssdPushAccepted();
            $status = $accepted ? 200 : 502;

            return response()->json([
                'message' => $accepted
                    ? 'Payment request sent. Customer will receive USSD prompt.'
                    : $res->failureMessage(),
                'trans_id' => $transId,
                'ref_no' => $refNo,
                'ok' => $accepted,
                'gateway_error' => $res->gatewayError,
                'gateway_description' => $res->gatewayDescription,
                'original' => $res->originalData,
            ], $status);
        } catch (\Throwable $e) {
            Log::error('eMola pay failed', ['order_id' => $validated['order_id'], 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Payment failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Query transaction status (pushUssdQueryTrans).
     */
    public function status(string $transId, Request $request): JsonResponse
    {
        $transType = (string) $request->query('trans_type', 'C2B');
        $res = $this->client->pushUssdQueryTrans($transId, $transType);

        return response()->json([
            'ok' => $res->ok(),
            'gateway_error' => $res->gatewayError,
            'gateway_description' => $res->gatewayDescription,
            'gwtransid' => $res->gwtransid,
            'original' => $res->originalData,
        ]);
    }

    /**
     * Partner account balance (queryAccountBalance).
     */
    public function balance(): JsonResponse
    {
        $res = $this->client->queryAccountBalance();

        return response()->json([
            'ok' => $res->ok(),
            'gateway_error' => $res->gatewayError,
            'gateway_description' => $res->gatewayDescription,
            'original' => $res->originalData,
        ]);
    }

    /**
     * Lookup beneficiary name by MSISDN.
     */
    public function beneficiary(Request $request): JsonResponse
    {
        $request->validate([
            'msisdn' => ['required', 'string', 'regex:/^(86|87)\d{7}$/'],
        ]);

        $transId = $this->client->generateTransId();
        $res = $this->client->queryBeneficiaryName($transId, $request->input('msisdn'));

        return response()->json([
            'ok' => $res->ok(),
            'trans_id' => $transId,
            'gateway_error' => $res->gatewayError,
            'original' => $res->originalData,
        ]);
    }

    /**
     * Find order by eMola trans_id (support / admin tooling).
     */
    public function orderByTransId(string $transId): JsonResponse
    {
        $order = Order::query()
            ->where(function ($q) use ($transId) {
                $q->where('emola_trans_id', $transId)
                    ->orWhere('emola_ref_no', $transId);
            })
            ->latest('id')
            ->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
            'emola_trans_id' => $order->emola_trans_id,
            'emola_ref_no' => $order->emola_ref_no,
            'emola_error_code' => $order->emola_error_code,
            'emola_message' => $order->emola_message,
        ]);
    }
}
