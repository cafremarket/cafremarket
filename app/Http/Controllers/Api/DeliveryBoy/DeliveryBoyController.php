<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryBoyController extends Controller
{
    public function status(Request $request)
    {
        $rider = Auth::guard('delivery_boy-api')->user();

        if ($request->has('is_online')) {
            $rider->is_online = $request->boolean('is_online');
            $rider->save();
        }

        return response()->json([
            'status' => (bool) $rider->status,
            'is_online' => (bool) $rider->is_online,
        ]);
    }
}
