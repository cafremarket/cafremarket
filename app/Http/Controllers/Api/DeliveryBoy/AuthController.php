<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Helpers\ApiAlert;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryBoy\LoginRequest;
use App\Http\Requests\DeliveryBoy\UpdatePasswordRequest;
use App\Http\Resources\DeliveryBoyResource;
use App\Models\DeliveryBoy;
use App\Notifications\DeliveryBoy\PasswordReset;
use App\Services\FCMService;
use App\Services\Auth\JwtAuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiAlert;

    /**
     * login
     *
     * @param  [request]
     * @return [json] logged in delivery boy details
     */
    public function login(LoginRequest $request)
    {
        // A rider who works for more than one store has one account row per
        // store (email is only unique within a store) — find every row this
        // email+password combination matches.
        $matches = DeliveryBoy::matchingAccounts($request->email, $request->password);

        if ($matches->isEmpty()) {
            return response()->json(['message' => trans('api.auth_failed')], 401);
        }

        if ($request->filled('shop_id')) {
            $deliveryBoy = $matches->firstWhere('shop_id', (int) $request->shop_id);

            if (! $deliveryBoy) {
                return response()->json(['message' => trans('api.auth_failed')], 401);
            }
        } elseif ($matches->count() > 1) {
            return response()->json([
                'choose_store' => true,
                'message' => trans('api.multiple_stores_found'),
                'stores' => $matches->map(fn ($account) => [
                    'shop_id' => $account->shop_id,
                    'shop_name' => optional($account->shop)->name,
                    'shop_logo' => get_storage_file_url(optional(optional($account->shop)->image)->path, 'mini'),
                ])->values(),
            ], 200);
        } else {
            $deliveryBoy = $matches->first();
        }

        $deliveryBoy->generateToken('delivery_boy');

        if ($request->filled('fcm_token')) {
            $deliveryBoy->fcm_token = FCMService::normalizeToken($request->fcm_token) ?: null;
            $deliveryBoy->save();
        }

        return new DeliveryBoyResource($deliveryBoy);
    }

    /**
     * List every store the currently logged-in rider's email is registered
     * under, so the app can offer a "Switch Store" option.
     */
    public function myStores(Request $request)
    {
        $current = Auth::guard('delivery_boy-api')->user();

        $accounts = DeliveryBoy::where('email', $current->email)
            ->with('shop:id,name')
            ->get();

        return response()->json([
            'stores' => $accounts->map(fn ($account) => [
                'shop_id' => $account->shop_id,
                'shop_name' => optional($account->shop)->name,
                'shop_logo' => get_storage_file_url(optional(optional($account->shop)->image)->path, 'mini'),
                'is_current' => $account->id === $current->id,
            ])->values(),
        ]);
    }

    /**
     * Switch to a different store's rider account for this same email.
     * No password re-entry — the caller is already authenticated as a rider
     * with this email (via a valid bearer token for one of these accounts),
     * so hopping to another store account under that same email doesn't
     * need re-proving identity.
     */
    public function switchStore(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer',
        ]);

        $current = Auth::guard('delivery_boy-api')->user();

        $deliveryBoy = DeliveryBoy::where('email', $current->email)
            ->where('shop_id', $request->shop_id)
            ->first();

        if (! $deliveryBoy) {
            return response()->json(['message' => trans('api.auth_failed')], 401);
        }

        $deliveryBoy->generateToken('delivery_boy');

        return new DeliveryBoyResource($deliveryBoy);
    }

    /**
     * logout
     *
     * @param  [request]
     * @return [json] $string
     */
    public function logout(Request $request)
    {
        $deliveryBoy = Auth::guard('delivery_boy-api')->user();

        if ($deliveryBoy) {
            app(JwtAuthService::class)->invalidate($deliveryBoy, 'delivery_boy');
        }

        return response()->json(trans('api.auth_out'), 200);
    }

    /**
     * update password
     *
     * @param  [request]
     * @return [json] $string
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::guard('delivery_boy-api')->user();

        if (! Hash::check($request->get('oldpassword'), $user->password)) {
            return $this->error(trans('api.old_password_doesnt_matched'));
        }

        if (Hash::check($request->get('newpassword'), $user->password)) {
            return $this->error(trans('api.new_password_cant_be_the_old_password'));
        }

        try {
            $user->password = $request->get('newpassword');
            $user->save();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        // DeliveryBoy::where('id', Auth::guard('delivery_boy-api')->id())->update([
        //     'password' =>  Hash::make($request->get('newpassword'))
        // ]);

        return $this->success(trans('api.password_update'));
    }

    /**
     * forgot password
     *
     * @param  [request]
     * @return [json] $string
     */
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $deliveryBoy = DeliveryBoy::where('email', $request->email)->first();

        if (! $deliveryBoy) {
            return response()->json(['message' => trans('api.email_account_not_found')], 404);
        }

        $token = generateUniqueNumber();

        $passwordReset = DB::table('password_resets')
            ->updateOrInsert(['email' => $deliveryBoy->email], [
                'email' => $deliveryBoy->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]);

        if ($deliveryBoy && $passwordReset) {
            safe_notify($deliveryBoy, new PasswordReset($token), 'delivery boy password reset');
        }

        return response()->json(['message' => trans('api.password_reset_email')], 201);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function token(Request $request)
    {
        $token = '';

        if ($request->token) {
            $token = $request->token;
        }

        $passwordReset = DB::table('password_resets')
            ->where('token', $token)->first();

        if (! $passwordReset) {
            return response()->json([
                'message' => trans('api.password_reset_token_404'),
            ], 404);
        }

        if (Carbon::parse($passwordReset->created_at)->addMinutes(720)->isPast()) {
            DB::table('password_resets')->where('token', $token)->delete();

            return response()->json(['message' => trans('api.password_reset_token_404')], 404);
        }

        return response()->json($passwordReset);
    }

    /**
     * Reset password
     *
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     */
    public function reset(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6',
            'token' => 'required|string',
        ]);

        $passwordReset = DB::table('password_resets')
            ->where('token', $request->token)->first();

        if (! $passwordReset) {
            return response()->json(['message' => trans('api.password_reset_token_404')], 404);
        }

        $deliveryBoy = DeliveryBoy::where('email', $passwordReset->email)->first();
        if (! $deliveryBoy) {
            return response()->json(['message' => trans('api.email_account_not_found')], 404);
        }

        $deliveryBoy->password = $request->password;
        $deliveryBoy->save();

        DB::table('password_resets')->where('token', $request->token)->delete();

        // $deliveryBoy->notify(new PasswordResetSuccess($customer));

        return response()->json(['message' => trans('api.password_reset_successful')], 200);
    }
}
