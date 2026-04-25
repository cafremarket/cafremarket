<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EmolaCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Note: spec field is misspelled as "reqeustId"
            'reqeustId' => ['required', 'string'],
            'transId' => ['required', 'string'],
            'refNo' => ['required', 'string'],
            'errorCode' => ['required', 'string'],
            'message' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ResponseCode' => '1',
                'ResponseMessage' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        Log::info('eMola callback received', [
            'reqeustId' => $data['reqeustId'],
            'transId' => $data['transId'],
            'refNo' => $data['refNo'],
            'errorCode' => $data['errorCode'],
        ]);

        $order = Order::query()
            ->where('emola_trans_id', $data['transId'])
            ->orWhere('emola_ref_no', $data['refNo'])
            ->latest('id')
            ->first();

        if ($order) {
            $order->emola_request_id = $data['reqeustId'];
            $order->payment_ref_id = $order->payment_ref_id ?: $data['reqeustId'];
            $order->emola_error_code = $data['errorCode'];
            $order->emola_message = $data['message'];
            $order->save();

            if ($data['errorCode'] === '0') {
                $order->markAsPaid();
            } else {
                $order->payment_status = Order::PAYMENT_STATUS_PENDING;
                $order->order_status_id = Order::STATUS_PAYMENT_ERROR;
                $order->save();
            }
        } else {
            Log::warning('eMola callback: order not found', [
                'reqeustId' => $data['reqeustId'],
                'transId' => $data['transId'],
                'refNo' => $data['refNo'],
            ]);
        }

        return response()->json([
            'ResponseCode' => '0',
            'ResponseMessage' => 'OK',
        ]);
    }
}

