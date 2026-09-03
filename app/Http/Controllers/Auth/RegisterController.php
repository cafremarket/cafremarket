<?php

namespace App\Http\Controllers\Auth;

use App\Events\Shop\ShopCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\RegisterMerchantRequest;
use App\Jobs\CreateCustomerFromMerchant;
use App\Jobs\CreateShopForMerchant;
use App\Jobs\SubscribeShopToNewPlan;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Notifications\Auth\SendVerificationEmail as EmailVerificationNotification;
use App\Notifications\SuperAdmin\VendorRegistered as VendorRegisteredNotification;
use App\Providers\RouteServiceProvider;
use App\Services\Auth\JwtAuthService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::DASHBOARD;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('verify');
    }

    /**
     * Show the application registration form.
     *
     * @param  string  $plan  subscription plan
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm($plan = null)
    {
        return view('auth.register', compact('plan'));
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(RegisterMerchantRequest $request)
    {
        DB::beginTransaction();

        if (is_incevio_package_loaded('otp-login')) {
            $phone = $request->input('phone');

            send_otp_code($phone, 'vendor.register');

            try {
                $merchant = $this->create($request->all());

                // Dispatching Shop create job
                CreateShopForMerchant::dispatch($merchant, $request->all());

                if (is_subscription_enabled()) {
                    SubscribeShopToNewPlan::dispatch($merchant, $request->input('plan'));
                }

                if (! customer_can_register()) {
                    // Dispatching customer create job
                    CreateCustomerFromMerchant::dispatch($merchant);
                }
            } catch (\Exception $e) {
                // rollback the transaction and log the error
                DB::rollback();
                Log::error('Vendor Registration Failed: '.$e->getMessage());

                // Set error messages:
                $error = new MessageBag;
                $error->add('errors', trans('responses.vendor_config_failed'));

                return redirect()->to($this->vendorRegisterUrl())->withErrors($error)->withInput();
            }

            // Everything is fine. Now commit the transaction
            DB::commit();

            // Trigger after registration events
            $this->triggerAfterEvents($merchant);

            // Send notification to Admin
            if (config('system_settings.notify_when_vendor_registered')) {
                $system = System::orderBy('id', 'asc')->first();
                safe_notify($system->superAdmin(), new VendorRegisteredNotification($merchant), 'vendor registered');
            }

            return redirect()->route('vendor.phoneverification.notice')->with(['phone_number' => $phone]);
        }

        try {
            $merchant = $this->create($request->all());

            if (! customer_can_register()) {
                CreateCustomerFromMerchant::dispatch($merchant);
            }

            CreateShopForMerchant::dispatch($merchant, $request->all());

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

            return redirect()->to($this->vendorRegisterUrl())->withErrors($error)->withInput();
        }

        // Everything is fine. Now commit the transaction
        DB::commit();

        $this->loginMerchant($request, $merchant);

        // Trigger after registration events
        $this->triggerAfterEvents($merchant);

        // Send notification to Admin
        if (config('system_settings.notify_when_vendor_registered')) {
            $system = System::orderBy('id', 'asc')->first();
            safe_notify($system->superAdmin(), new VendorRegisteredNotification($merchant), 'vendor registered');
        }

        return $this->registered($request, $merchant) ?? $this->withAuthCookie(redirect($this->redirectPath()), $merchant);
    }

    /**
     * Log the new merchant in and align session + JWT cookies (prevents CSRF / Page Expired
     * on the next store-panel requests after registration).
     */
    protected function loginMerchant(Request $request, User $merchant): void
    {
        Auth::guard('web')->login($merchant);

        // SessionGuard::login already migrates the session; only mark confirmation time.
        if ($request->hasSession()) {
            $request->session()->put('auth.password_confirmed_at', time());
        }
    }

    /**
     * Attach JWT auth cookie after a successful registration redirect.
     */
    protected function withAuthCookie($response, User $merchant)
    {
        $jwt = app(JwtAuthService::class)->issue($merchant, 'web');
        $cookie = app(JwtAuthService::class)->makeCookie('web', $jwt, false);

        return $response->withCookie($cookie);
    }

    /**
     * Registration form URL for the current flow (selling page vs legacy /register).
     */
    protected function vendorRegisterUrl(?string $plan = null): string
    {
        if ($this->isSellingRegistrationRequest()) {
            return $plan ? route('selling.register', $plan) : route('selling.register');
        }

        return $plan ? route('vendor.register', $plan) : route('vendor.register');
    }

    protected function isSellingRegistrationRequest(): bool
    {
        return request()->routeIs('selling.register', 'selling.register.submit')
            || request()->is('selling/register')
            || request()->is('selling/register/*');
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
        event(new Registered($merchant));

        // Trigger shop created event
        event(new ShopCreated($merchant->owns));

        // Send email verification notification
        safe_notify($merchant, new EmailVerificationNotification($merchant), 'merchant email verification');
    }

    /**
     * Verify the User the given token.
     *
     * @param  string|null  $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify($token = null)
    {
        if (! $token) {
            $user = Auth::user();

            $user->verification_token = Str::random(40);

            if ($user->save()) {
                safe_notify($user, new EmailVerificationNotification($user), 'user email verification');

                return redirect()->back()->with('success', trans('auth.verification_link_sent'));
            }

            return redirect()->back()->with('success', trans('auth.verification_link_sent'));
        }

        $user = User::where('verification_token', $token)->first();

        if (! $user) {
            return redirect()->route('admin.admin.dashboard')
                ->with('success', trans('auth.invalid_token'));
        }

        $user->verification_token = null;

        if ($user->save()) {
            return redirect()->route('admin.admin.dashboard')
                ->with('success', trans('auth.verification_successful'));
        }

        return redirect()->route('admin.admin.dashboard')->with('error', trans('auth.verification_failed'));
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered($request, $user)
    {
        if ($user->isFromMerchant() && $user->shop) {
            $response = redirect()
                ->route('merchant.verify')
                ->with('success', trans('messages.seller_registration_complete_verify_store'));

            return $this->withAuthCookie($response, $user);
        }

        return $this->withAuthCookie(redirect($this->redirectPath()), $user);
    }
}
