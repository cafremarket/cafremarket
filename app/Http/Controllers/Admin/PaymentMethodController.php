<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Config;
// use App\Common\Authorizable;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    // use Authorizable;

    private $model_name;

    public function __construct()
    {
        parent::__construct();

        $this->model_name = trans('app.model.payment_method');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $config = $this->checkPermission($request, 'view');

        /**
         * When admin get paid but still give option to vendors on/off a active payment method
         */
        if (! vendor_get_paid_directly()) {
            return view('admin.config.payment-method.on_off');
        }

        return view('admin.config.payment-method.index');
    }

    /**
     * Activate a payment method.
     */
    public function activate(Request $request, $id)
    {
        $config = $this->checkPermission($request);
        $paymentMethod = PaymentMethod::findOrFail($id);

        $config->paymentMethods()->syncWithoutDetaching($id);

        if (! vendor_get_paid_directly()) {
            return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
        }

        $redirect = $this->getActivationRedirect($paymentMethod->code);

        if ($redirect) {
            return $redirect;
        }

        if (SystemConfig::isPaymentConfigured($paymentMethod->code)) {
            return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
        }

        return back()->with('error', trans('messages.failed', ['model' => $this->model_name]));
    }

    /**
     * Get the appropriate activation redirect route for payment methods.
     */
    private function getActivationRedirect(string $paymentCode)
    {
        $routes = [
            'paypal' => 'admin.setting.paypal.activate',
            'mpesa' => 'admin.setting.mpesa.activate',
            'wire' => 'admin.setting.manualPaymentMethod.activate',
            'cod' => 'admin.setting.manualPaymentMethod.activate',
        ];

        return isset($routes[$paymentCode])
            ? redirect()->route($routes[$paymentCode], $paymentCode)
            : null;
    }

    public function deactivate(Request $request, $id)
    {
        if (config('app.demo') == true) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $config = $this->checkPermission($request);

        $paymentMethod = PaymentMethod::findOrFail($id);

        $config->paymentMethods()->detach($id);

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    public function activateManualPaymentMethod(Request $request, $code)
    {
        $config = $this->checkPermission($request);

        $paymentMethod = PaymentMethod::where('code', $code)->firstOrFail();

        $config->manualPaymentMethods()->syncWithoutDetaching($paymentMethod);

        $paymentMethod = $config->manualPaymentMethods->find($paymentMethod);

        return view('admin.config.payment-method.manual', compact('paymentMethod'));
    }

    public function deactivateManualPaymentMethod(Request $request, $code)
    {
        if (config('app.demo') == true) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $config = $this->checkPermission($request);

        $paymentMethod = PaymentMethod::where('code', $code)->firstOrFail();

        $config->manualPaymentMethods()->detach($paymentMethod->id);

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    public function updateManualPaymentMethod(Request $request, $code)
    {
        if (config('app.demo') == true) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $config = $this->checkPermission($request);

        $paymentMethod = PaymentMethod::where('code', $code)->firstOrFail();

        $data = [
            'additional_details' => $request->input('additional_details'),
            'payment_instructions' => $request->input('payment_instructions'),
        ];

        $config->manualPaymentMethods()->updateExistingPivot($paymentMethod->id, $data);

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Dedicated page for the "additional details" (shown while choosing a
     * payment method) and "payment instructions" (shown on the order
     * confirmation page) text of every manual payment method — pulled out of
     * the wallet settings form so admin can find and edit it directly.
     *
     * These are the global wallet_payment_info_{code} / wallet_payment_instructions_{code}
     * options, which is what actually drives the storefront when the platform
     * (not the vendor) is the one getting paid — see Order::manualPaymentInstructions().
     * When vendors get paid directly, each shop's own instructions are edited
     * per-shop from the Payment Methods page instead.
     */
    public function paymentInstructions()
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        $manualPaymentMethods = PaymentMethod::where('type', PaymentMethod::TYPE_MANUAL)
            ->orderBy('order')
            ->get();

        return view('admin.config.payment-method.instructions', compact('manualPaymentMethods'));
    }

    /**
     * Save the manual payment instructions for every manual payment method at once.
     */
    public function updatePaymentInstructions(Request $request)
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        if (config('app.demo') == true) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $manualPaymentMethods = PaymentMethod::where('type', PaymentMethod::TYPE_MANUAL)->get();

        foreach ($manualPaymentMethods as $method) {
            update_or_create_option_table_record(
                "wallet_payment_info_{$method->code}",
                $request->input("wallet_payment_info_{$method->code}")
            );

            update_or_create_option_table_record(
                "wallet_payment_instructions_{$method->code}",
                $request->input("wallet_payment_instructions_{$method->code}")
            );
        }

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Check permission
     *
     * @return $config
     */
    private function checkPermission(Request $request, $action = 'update')
    {
        $config = Config::findOrFail($request->user()->merchantId());

        $this->authorize($action, $config);

        return $config;
    }
}
