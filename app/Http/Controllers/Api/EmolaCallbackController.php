<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Emola\EmolaCallbackPayload;
use App\Services\Emola\EmolaOrderPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmolaCallbackController extends Controller
{
    public function __invoke(Request $request, EmolaOrderPaymentService $emolaOrders)
    {
        Log::info('eMola callback received', [
            'content_type' => $request->header('Content-Type'),
            'ip' => $request->ip(),
            'keys' => array_keys($request->all()),
        ]);

        $data = EmolaCallbackPayload::fromRequest($request);

        if ($data === null) {
            Log::warning('eMola callback: invalid payload', [
                'body_preview' => substr((string) $request->getContent(), 0, 500),
            ]);

            return response()->json([
                'ResponseCode' => '1',
                'ResponseMessage' => 'Invalid payload',
            ], 422);
        }

        $emolaOrders->processCallbackPayload($data);

        return response()->json([
            'ResponseCode' => '0',
            'ResponseMessage' => 'OK',
        ]);
    }
}
