<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function saveToken(Request $request)
    {
        $user = Auth::user();
        $raw = $request->input('token') ?? $request->query('token');
        $token = FCMService::normalizeToken($raw);
        if ($token === '') {
            return response()->json(['message' => 'Token is required'], 422);
        }

        $user->fcm_token = $token;
        $user->save();

        return response()->json(['message' => 'Token saved successfully'], 200);
    }

    public function getNotifications()
    {
        $notifications = auth()->user()->shop->notifications;

        return NotificationResource::collection($notifications);
    }
}
