<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendorShop;
use App\Events\Shop\ConfigUpdated;
use App\Events\Shop\DownForMaintainace;
// use App\Common\Authorizable;
use App\Events\Shop\ShopIsLive;
use App\Events\Shop\ShopUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\MerchantVerifyRequest;
use App\Http\Requests\Validations\ToggleMaintenanceModeRequest;
use App\Http\Requests\Validations\UpdateBasicConfigRequest;
use App\Http\Requests\Validations\UpdateConfigRequest;
use App\Http\Resources\ShopSettingResource;
use App\Http\Resources\VendorShopConfigResource;
use App\Models\Attachment;
use App\Models\Config;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ConfigController extends Controller
{
    use ResolvesVendorShop;

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
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return new ShopSettingResource($this->shop());
    }

    /**
     * Display the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function configs()
    {
        $shopId = $this->merchantShopId();

        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $config = Config::with('shop')->findOrFail($shopId);

        return response()->json(
            (new VendorShopConfigResource($config))->resolve(request())
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $shop_id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBasicConfigRequest $request, $shop_id)
    {
        $this->assertOwnsShop((int) $shop_id);

        $shop = Shop::findOrFail($shop_id);

        $shop->update($request->all());

        if ($request->hasFile('logo') || ($request->input('delete_logo') == 1)) {
            $shop->deleteLogo();
        }

        if ($request->hasFile('logo')) {
            $shop->saveImage($request->file('logo'), 'logo');
        }

        if ($request->hasFile('cover_image') || ($request->input('delete_cover_image') == 1)) {
            $shop->deleteCoverImage();
        }

        if ($request->hasFile('cover_image')) {
            $shop->saveImage($request->file('cover_image'), 'cover');
        }

        if ($request->hasFile('stamp_image') || ($request->input('delete_stamp_image') == 1)) {
            $shop->deleteStampImage();
        }

        if ($request->hasFile('stamp_image')) {
            $shop->saveImage($request->file('stamp_image'), 'stamp');
        }

        event(new ShopUpdated($shop));

        return response()->json(['message' => trans('api.config_updated_successfully')], 200);
    }

    /**
     * Update shop configs
     *
     * @param  UpdateConfigRequest  $request
     * @param  int  $config
     * @return void
     */
    public function updateConfigs(UpdateConfigRequest $request, $config)
    {
        $this->assertOwnsShop((int) $config);

        $settings = Config::findOrFail($config);
        $user = Auth::guard('vendor_api')->user() ?? Auth::user();

        if ($settings->update($request->only($settings->getFillable()))) {
            if ($user) {
                event(new ConfigUpdated($settings->shop, $user));
            }

            clearShopConfigCache($settings->shop_id); // Clear cached values

            return response()->json(['message' => trans('api.config_updated_successfully')]);
        }

        return response()->json(['message' => trans('responses.error')], 405);
    }

    public function verificationStatus(Request $request)
    {
        $shopId = $this->merchantShopId();
        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $shop = Shop::with('config.attachments')->findOrFail($shopId);
        $config = $shop->config;

        return response()->json([
            'data' => array_merge([
                'verified' => $shop->isVerified(),
                'verification_status' => $shop->getVerificationStatus(),
                'verification_request_status' => $config?->verificationRequestStatus(),
                'pending_verification' => (bool) optional($config)->pending_verification,
                'verification_rejection_reason' => optional($config)->verification_rejection_reason,
                'verification_rejected_at' => optional($config?->verification_rejected_at)->toIso8601String(),
                'can_submit_request' => (bool) optional($config)->canSubmitVerificationRequest(),
                'documents' => $config?->attachments?->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'name' => $attachment->name,
                        'size' => $attachment->size,
                        'url' => url('api/vendor/attachment/'.$attachment->id.'/download'),
                    ];
                }) ?? [],
            ], $this->verificationMetaPayload($config)),
        ]);
    }

    public function submitVerification(Request $request)
    {
        $shopId = $this->merchantShopId();
        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $config = Config::findOrFail($shopId);

        if (! $config->canSubmitVerificationRequest()) {
            return response()->json([
                'message' => trans('messages.verification_request_not_allowed'),
            ], 422);
        }

        $request->validate([
            'national_id' => 'nullable|string|max:64',
            'verified_phone' => 'nullable|string|max:32',
            'verified_address' => 'nullable|string|max:500',
            'nuit' => 'nullable|string|max:64',
            'business_license' => 'nullable|string|max:128',
            'business_registration' => 'nullable|string|max:128',
            'documents' => 'nullable|array|min:1',
            'documents.*' => 'file|max:'.(config('system_settings.max_img_size_limit_kb') * 4),
        ]);

        $meta = array_filter([
            'national_id' => $request->input('national_id'),
            'verified_phone' => $request->input('verified_phone'),
            'verified_address' => $request->input('verified_address'),
            'nuit' => $request->input('nuit'),
            'business_license' => $request->input('business_license'),
            'business_registration' => $request->input('business_registration'),
        ], fn ($v) => $v !== null && $v !== '');

        if ($request->hasFile('documents')) {
            $config->saveAttachments($request->file('documents'));
        } elseif (! $config->attachments()->exists()) {
            return response()->json([
                'message' => trans('validation.required', ['attribute' => 'documents']),
            ], 422);
        }

        $config->update([
            'pending_verification' => 1,
            'verification_rejection_reason' => null,
            'verification_rejected_at' => null,
            'verification_meta' => array_merge($config->verification_meta ?? [], $meta),
        ]);

        clearShopConfigCache($shopId);

        return response()->json([
            'message' => trans('messages.verification_request_submitted'),
        ]);
    }

    public function downloadVerificationAttachment(Request $request, Attachment $attachment)
    {
        $shopId = $this->merchantShopId();
        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $config = Config::findOrFail($shopId);
        abort_unless(
            $config->attachments()->where('attachments.id', $attachment->id)->exists(),
            403,
            trans('responses.unauthorized')
        );

        if (Storage::exists($attachment->path)) {
            return Storage::download($attachment->path, $attachment->name);
        }

        return response()->json(['message' => trans('messages.file_not_exist')], 404);
    }

    /**
     * Display the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function verify(MerchantVerifyRequest $request)
    {
        $config = Config::findOrFail(Auth::user()->merchantId());

        return view('admin.config.verify', compact('config'));
    }

    /**
     * Display the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function saveVerificationData(MerchantVerifyRequest $request)
    // {
    //     $config = Config::findOrFail(Auth::user()->merchantId());

    //     if ($request->hasFile('documents')) {
    //         $config->saveAttachments($request->file('documents'));
    //     }

    //     $config->update(['pending_verification' => 1]);

    //     clearShopConfigCache($shop_id); // Clear cached values

    //     return response()->json(['message' => trans('messages.updated', ['model' => $this->model_name])], 200);
    // }

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

        // $this->authorize('update', $config); // Check permission

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
        $this->assertOwnsShop((int) $id);

        if (config('app.demo') == true && $id <= config('system.demo.shops', 2)) {
            return response('error', 444);
        }

        $config = Config::findOrFail($id);

        // $this->authorize('update', $config); // Check permission

        $config->maintenance_mode = ! $config->maintenance_mode;

        if ($config->save()) {
            if ($config->maintenance_mode) {
                event(new DownForMaintainace($config->shop));
            } else {
                event(new ShopIsLive($config->shop));
            }

            clearShopConfigCache($config->shop_id); // Clear cached values

            return response()->json([
                'message' => trans('api.config_updated_successfully'),
                'maintenance_mode' => (bool) $config->maintenance_mode,
            ], 200);
        }

        return response('error', 405);
    }

    /**
     * Toggle shop active status (activate / deactivate store).
     */
    public function toggleShopActive(Request $request, $shop_id)
    {
        $this->assertOwnsShop((int) $shop_id);

        if (config('app.demo') == true && $shop_id <= config('system.demo.shops', 2)) {
            return response('error', 444);
        }

        $shop = Shop::findOrFail($shop_id);
        $shop->active = ! $shop->active;
        $shop->save();

        event(new ShopUpdated($shop));

        return response()->json([
            'message' => trans('api.config_updated_successfully'),
            'active' => (bool) $shop->active,
        ]);
    }

    /**
     * Toggle e-commerce (store live / paused).
     */
    public function toggleActiveEcommerce(Request $request, $config_id)
    {
        $this->assertOwnsShop((int) $config_id);

        $config = Config::findOrFail($config_id);
        $config->active_ecommerce = ! $config->active_ecommerce;
        $config->save();

        clearShopConfigCache($config->shop_id);

        return response()->json([
            'message' => trans('api.config_updated_successfully'),
            'active_ecommerce' => (bool) $config->active_ecommerce,
        ]);
    }

    protected function verificationMetaPayload(?Config $config): array
    {
        $meta = $config?->verification_meta ?? [];

        return [
            'national_id' => $meta['national_id'] ?? '',
            'verified_phone' => $meta['verified_phone'] ?? '',
            'verified_address' => $meta['verified_address'] ?? '',
            'nuit' => $meta['nuit'] ?? '',
            'business_license' => $meta['business_license'] ?? '',
            'business_registration' => $meta['business_registration'] ?? '',
        ];
    }
}
