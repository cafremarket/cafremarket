<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Events\Shop\ShopCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\RegisterMerchantRequest;
use App\Http\Resources\MerchantRegistrationResource;
use App\Http\Resources\MerchantResource;
use App\Jobs\CreateShopForMerchant;
use App\Jobs\SubscribeShopToNewPlan;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Notifications\Auth\SendVerificationEmail as EmailVerificationNotification;
use App\Notifications\Auth\UserResetPasswordNotification as SendPasswordResetEmail;
use App\Notifications\SuperAdmin\VendorRegistered as VendorRegisteredNotification;
use App\Notifications\User\PasswordUpdated as PasswordResetSuccess;
use App\Services\Auth\JwtAuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register as merchant
     *
     * @return MerchantResource
     */
    public function register(RegisterMerchantRequest $request)
    {
        $phone = Str::start(trim($request->phone), '+');

        if (is_incevio_package_loaded('otp-login')) {
            send_otp_code($phone);
        }

        $data = $request->all();
        $data['phone'] = $phone;

        DB::beginTransaction();

        try {
            $merchant = $this->create($data);

            $merchant->generateToken('vendor_api');

            // Dispatching Shop create job
            CreateShopForMerchant::dispatch($merchant, $data);

            if (is_incevio_package_loaded('otp-login')) {
                Auth::guard()->login($merchant);
            }

            // Create subscription when enabled
            if (is_subscription_enabled()) {
                SubscribeShopToNewPlan::dispatch($merchant, $request->input('plan'));
            }
        } catch (\Exception $e) {

            // rollback the transaction and log the error
            DB::rollback();
            Log::error('Vendor Registration Failed: '.$e->getMessage());

            // Set error messages:
            $error = new MessageBag;
            $error->add('errors', trans('responses.vendor_config_failed'));

            return response()->json($error);
        }

        // Everything is fine. Now commit the transaction
        DB::commit();

        // Trigger after registration events
        $this->triggerAfterEvents($merchant);

        // Send notification to Admin
        if (config('system_settings.notify_when_vendor_registered')) {
            $system = System::orderBy('id', 'asc')->first();
            safe_notify($system->superAdmin(), new VendorRegisteredNotification($merchant), 'api vendor registered');
        }

        return new MerchantResource($merchant);
    }

    /**
     * Get the form fields for vendor registration.
     *
     * @return \App\Http\Resources\MerchantRegistrationResource
     */
    public function getRegisterFormFields()
    {
        return new MerchantRegistrationResource;
    }

    /**
     * Merchant login
     *
     * @return MerchantResource
     */
    public function login(Request $request)
    {
        if (is_incevio_package_loaded('otp-login') && $request->has('phone')) {
            $phone = Str::start(trim($request->phone), '+');

            if (! User::where('phone', $phone)->exists()) {
                return response()->json(['message' => trans('packages.otp-login.not_registered')], 302);
            }

            try {
                send_otp_code($phone, null);
            } catch (\Exception $e) {
                return response()->json(['message' => trans('packages.otp-login.phone_session_expired')], 302);
            }

            return response()->json(['message' => trans('packages.otp-login.verification_code_sent')], 200);
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        $user = User::where('email', $credentials['email'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            $user->generateToken('vendor_api');

            return new MerchantResource($user);
        }

        return response()->json(['message' => trans('api.auth_failed')], 401);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $request_data)
    {
        $data = [
            'name' => $request_data['name'] ?? $request_data['shop_name'],
            'email' => $request_data['email'],
            'password' => $request_data['password'],
            'verification_token' => Str::random(40),
            'role_id' => Role::MERCHANT,
        ];

        if (is_incevio_package_loaded('otp-login')) {
            $data['phone'] = $request_data['phone'];
        }

        return User::create($data);
    }

    /**
     * Trigger some events after a valid registration.
     *
     * @return void
     */
    protected function triggerAfterEvents(User $merchant)
    {
        // Trigger the systems default event
        event(new \Illuminate\Auth\Events\Registered($merchant));

        // Trigger shop created event
        event(new ShopCreated($merchant->owns));

        // Send email verification notification
        safe_notify($merchant, new EmailVerificationNotification($merchant), 'api merchant verification');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::guard('vendor_api')->user();

        if ($user) {
            app(JwtAuthService::class)->invalidate($user, 'vendor_api');
        }

        return response()->json(trans('api.auth_out'), 200);
    }

    /** reset password link send
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgot(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => trans('api.email_account_not_found')], 404);
        }

        $token = Str::random(60);
        $url = url('/password/reset/'.$token);

        $passwordReset = DB::table('password_resets')
            ->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => Carbon::now(),
                ]
            );

        if ($user && $passwordReset) {
            safe_notify($user, new SendPasswordResetEmail($token, $url), 'api vendor password reset');
        }

        return response()->json(['message' => trans('api.password_reset_link_sent')], 201);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function token($token)
    {
        $passwordReset = DB::table('password_resets')
            ->where('token', $token)->first();

        if (! $passwordReset) {
            return response()->json([
                'message' => trans('api.password_reset_token_404'),
            ], 404);
        }

        if (Carbon::parse($passwordReset->created_at)->addMinutes(720)->isPast()) {
            DB::table('password_resets')->where('token', $token)->delete();

            return response()->json([
                'message' => trans('api.password_reset_token_invalid'),
            ], 404);
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
            'password' => 'required|string|min:6|confirmed',
            'token' => 'required|string',
        ]);

        $passwordReset = DB::table('password_resets')
            ->where('token', $request->token)->first();

        if (! $passwordReset) {
            return response()->json([
                'message' => trans('api.password_reset_token_404'),
            ], 404);
        }

        $user = User::where('email', $passwordReset->email)->first();
        if (! $user) {
            return response()->json([
                'message' => trans('api.email_account_not_found'),
            ], 404);
        }

        $user->password = $request->password;
        $user->save();

        DB::table('password_resets')->where('token', $request->token)->delete();

        safe_notify($user, new PasswordResetSuccess($user), 'api vendor password reset success');

        return response()->json([
            'message' => trans('api.password_reset_successful'),
        ], 200);
    }
}
