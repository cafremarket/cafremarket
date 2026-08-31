<?php

namespace App\Http\Controllers\Admin;

use App\Events\Shop\ConfigUpdated;
use App\Events\Shop\DownForMaintainace;
use App\Events\Shop\ShopIsLive;
use App\Events\Shop\ShopUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\MerchantVerifyRequest;
use App\Http\Requests\Validations\ToggleMaintenanceModeRequest;
use App\Http\Requests\Validations\UpdateBasicConfigRequest;
use App\Http\Requests\Validations\UpdateConfigRequest;
use App\Models\Config;
use App\Models\PdfTemplate;
use App\Models\Shop;
use App\Services\Geo\GeocodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ConfigController extends Controller
{
    // use Authorizable;

    private $model_name;

    /**
     * construct
     */
    public function __construct()
    {
        parent::__construct();

        $this->model_name = trans('app.model.config');
    }

    /**
     * Display the resource.
     *
     * @return \Illuminate\View\View
     */
    public function viewGeneralSetting()
    {
        // $files = \Illuminate\Support\Facades\Storage::disk('google')->allFiles();
        $shop = Shop::findOrFail(Auth::user()->merchantId());

        $shop_config = Config::find(Auth::user()->merchantId(), [
            'bank_name',
            'ac_holder_name',
            'ac_number',
            'ac_iban',
            'ac_swift_bic_code',
            'ac_routing_number',
            'ac_type',
            'ac_bank_address',
        ]);

        return view('admin.config.general', compact('shop', 'shop_config'));
    }

    /**
     * Display the resource.
     *
     * @return \Illuminate\View\View
     */
    public function view()
    {
        $config = Config::findOrFail(Auth::user()->merchantId());

        $this->authorize('view', $config); // Check permission

        $order_invoice_pdf_templates = PdfTemplate::active()->where('type', PdfTemplate::TYPE_ORDER_INVOICE)->get()->pluck('name', 'id');

        $shipping_label_pdf_templates = PdfTemplate::active()->where('type', PdfTemplate::TYPE_SHIPPING_LABEL)->get()->pluck('name', 'id');

        return view('admin.config.index', compact('config', 'order_invoice_pdf_templates', 'shipping_label_pdf_templates'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateBasicConfig(UpdateBasicConfigRequest $request, $id)
    {
        $config = Config::findOrFail($id);

        if (config('app.demo') == true && $config->shop_id <= config('system.demo.shops', 2)) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $this->authorize('update', $config); // Check permission

        $data = $request->all();

        if (! $request->user()->isFromPlatform()) {
            unset($data['delivery_capability']);
        }

        $config->shop->update($data);

        event(new ShopUpdated($config->shop));

        if ($request->hasFile('logo') || ($request->input('delete_logo') == 1)) {
            $config->shop->deleteLogo();
        }

        if ($request->hasFile('logo')) {
            $config->shop->saveImage($request->file('logo'), 'logo');
        }

        if ($request->hasFile('cover_image') || ($request->input('delete_cover_image') == 1)) {
            $config->shop->deleteCoverImage();
        }

        if ($request->hasFile('cover_image')) {
            $config->shop->saveImage($request->file('cover_image'), 'cover');
        }

        if ($request->hasFile('stamp_image') || ($request->input('delete_stamp_image') == 1)) {
            $config->shop->deleteStampImage();
        }

        if ($request->hasFile('stamp_image')) {
            $config->shop->saveImage($request->file('stamp_image'), 'stamp');
        }

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Update configurations
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateConfig(UpdateConfigRequest $request, $id)
    {
        if (config('app.demo') == true && $id <= config('system.demo.shops', 2)) {
            return response('error', 444);
        }

        $config = Config::findOrFail($id);

        $this->authorize('update', $config); // Check permission

        if ($config->update($request->all())) {
            event(new ConfigUpdated($config->shop, Auth::user()));

            clearShopConfigCache($id); // Clear cached values

            return response('success', 200);
        }

        return response('error', 405);
    }

    /**
     * Display the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function verify(MerchantVerifyRequest $request)
    {
        $config = Config::findOrFail(Auth::user()->merchantId());

        return view('admin.account.verify', compact('config'));
    }

    /**
     * Display the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function saveVerificationData(MerchantVerifyRequest $request)
    {
        $shop_id = Auth::user()->merchantId();

        $config = Config::findOrFail($shop_id);

        if (! $config->canSubmitVerificationRequest()) {
            return back()->with('error', trans('messages.verification_request_not_allowed'));
        }

        if ($request->hasFile('documents')) {
            $config->saveAttachments($request->file('documents'));
        }

        $config->load('attachments');

        if ($config->attachments->isEmpty()) {
            return back()->with('error', trans('messages.verification_documents_required'));
        }

        if (config('hyperlocal.require_store_location_for_verification', true)
            && ! $config->shop->hasStoreLocation()) {
            return back()->with('error', trans('app.store_location_required'));
        }

        if (! filled(trim((string) ($config->support_phone ?: Auth::user()->phone)))) {
            return back()->with('error', trans('messages.verification_phone_required'));
        }

        $wasRejected = $config->wasVerificationRejected();

        $config->update([
            'pending_verification' => 1,
            'verification_rejection_reason' => null,
            'verification_rejected_at' => null,
        ]);

        clearShopConfigCache($shop_id); // Clear cached values

        return back()->with(
            'success',
            $wasRejected
                ? trans('messages.verification_request_resubmitted')
                : trans('messages.verification_request_submitted')
        );
    }

    /**
     * Save store location during merchant onboarding / verification.
     */
    public function saveStoreLocation(Request $request)
    {
        $user = Auth::user();

        if (! $user->merchantId() || (int) $user->shop->id !== (int) $user->merchantId()) {
            abort(403);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:32',
        ]);

        $shop = $user->shop;
        $address = $shop->storeAddress();

        if (! $address) {
            $address = $shop->addresses()->create([
                'address_type' => 'Primary',
                'address_title' => $shop->name,
                'address_line_1' => $request->input('address_line_1', $shop->name),
                'city' => $request->input('city', ''),
                'zip_code' => $request->input('zip_code', '00000'),
                'country_id' => config('system_settings.address_default_country'),
                'state_id' => config('system_settings.address_default_state'),
                'phone' => optional($shop->config)->support_phone ?? $user->phone,
            ]);
        }

        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;

        if ($request->filled('address_line_1')) {
            $address->address_line_1 = $request->address_line_1;
        }

        if ($request->filled('city')) {
            $address->city = $request->city;
        }

        if ($request->filled('zip_code')) {
            $address->zip_code = $request->zip_code;
        }

        if (! $request->filled('address_line_1')) {
            $formatted = app(GeocodeService::class)->reverseGeocode(
                (float) $request->latitude,
                (float) $request->longitude
            );

            if ($formatted) {
                $address->address_line_1 = Str::limit($formatted, 255, '');
            }
        }

        $address->save();
        $shop->update(['primary_address_id' => $address->id]);

        return back()->with('success', trans('app.store_location_set'));
    }

    /**
     * Toggle Maintenance Mode of the given id, Its uses the ajax middleware
     *
     * @param  string  $node
     * @return \Illuminate\Http\Response
     */
    public function toggleNotification(Request $request, $node)
    {
        $config = Config::findOrFail($request->user()->merchantId());

        if (config('app.demo') == true && $config->shop_id <= config('system.demo.shops', 2)) {
            return response('error', 444);
        }

        $this->authorize('update', $config); // Check permission

        $config->$node = ! $config->$node;

        if ($config->save()) {
            event(new ConfigUpdated($config->shop, Auth::user()));

            clearShopConfigCache($config->shop_id); // Clear cached values

            return response('success', 200);
        }

        return response('error', 405);
    }

    /**
     * Toggle Maintenance Mode of the given id, Its uses the ajax middleware
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleMaintenanceMode(ToggleMaintenanceModeRequest $request, $id)
    {
        if (config('app.demo') == true && $id <= config('system.demo.shops', 2)) {
            return response('error', 444);
        }

        $config = Config::findOrFail($id);

        $this->authorize('update', $config); // Check permission

        $config->maintenance_mode = ! $config->maintenance_mode;

        if ($config->save()) {
            if ($config->maintenance_mode) {
                event(new DownForMaintainace($config->shop));
            } else {
                event(new ShopIsLive($config->shop));
            }

            clearShopConfigCache($config->shop_id); // Clear cached values

            return response('success', 200);
        }

        return response('error', 405);
    }

    /**
     * Populate the edit form for bank details
     *
     * @param  int  $shopId
     * @return \Illuminate\Http\Response
     */
    public function editBankInfo($shopId)
    {
        $bankInfo = Config::find($shopId, [
            'shop_id',
            'bank_name',
            'ac_holder_name',
            'ac_number',
            'ac_type',
            'ac_routing_number',
            'ac_swift_bic_code',
            'ac_iban',
            'ac_bank_address',
        ]);

        return view('admin.config._update_bank_info', compact('bankInfo'));
    }

    /**
     * Update the bank information
     *
     * @return \Illuminate\Http\Response
     */
    public function updateBankInfo(Request $request)
    {
        $shop_id = Auth::user()->merchantId();

        Config::find($shop_id)->update($request->all());

        clearShopConfigCache($shop_id); // Clear cached values

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }
}
