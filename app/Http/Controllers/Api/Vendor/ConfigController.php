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
use App\Services\Geo\GeocodeService;
use App\Services\Shop\ShopAddressChangeService;
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

        $shop = Shop::with(['config.attachments', 'owner'])->findOrFail($shopId);
        $config = $shop->config;
        $owner = $shop->owner;

        return response()->json([
            'data' => array_merge([
                'verified' => $shop->isVerified(),
                'verification_status' => $shop->getVerificationStatus(),
                'verification_request_status' => $config?->verificationRequestStatus(),
                'pending_verification' => (bool) optional($config)->pending_verification,
                'verification_rejection_reason' => optional($config)->verification_rejection_reason,
                'verification_rejected_at' => optional($config?->verification_rejected_at)->toIso8601String(),
                'can_submit_request' => (bool) optional($config)->canSubmitVerificationRequest(),
                'documents' => $this->attachmentsPayload($config?->attachments),
                'id_verified' => (bool) $shop->id_verified,
                'address_verified' => (bool) $shop->address_verified,
                'phone_verified' => (bool) $shop->phone_verified,
                'requires_store_documents' => (bool) $shop->requiresBusinessDocuments(),
                'has_store_location' => (bool) $shop->hasStoreLocation(),
                'person_documents' => $this->attachmentsPayload($config?->personVerificationAttachments()),
                'store_documents' => $this->attachmentsPayload($config?->storeVerificationAttachments()),
                'store_address' => $this->storeAddressPayload($shop->storeAddress()),
                'pending_address_change' => app(ShopAddressChangeService::class)->hasPendingRequest($shopId),
            ], $this->verificationMetaPayload($config), [
                'shop_name' => $shop->name,
                'seller_type' => $shop->seller_type,
                'seller_type_label' => $shop->sellerTypeLabel(),
                'nuit' => $shop->nuit ?: ($config?->verification_meta['nuit'] ?? ''),
                'support_phone' => optional($config)->support_phone ?: optional($owner)->phone,
                'support_email' => optional($config)->support_email ?: optional($owner)->email,
            ]),
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
            'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:'.(config('system_settings.max_img_size_limit_kb') * 4),
            'document_type' => 'nullable|in:person,store',
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
            $created = $config->saveAttachments($request->file('documents'));
            $config->registerVerificationAttachmentIds(
                collect($created)->pluck('id')->all(),
                $request->input('document_type', 'person')
            );
        }

        $config->load('attachments');

        if (! $config->hasPersonVerificationDocuments()) {
            return response()->json([
                'message' => trans('messages.verification_documents_required'),
            ], 422);
        }

        if ($config->requiresStoreVerificationDocuments() && ! $config->hasStoreVerificationDocuments()) {
            return response()->json([
                'message' => trans('messages.verification_documents_required'),
            ], 422);
        }

        if (config('hyperlocal.require_store_location_for_verification', true) && ! $config->shop->hasStoreLocation()) {
            return response()->json([
                'message' => trans('app.store_location_required'),
            ], 422);
        }

        if (! filled(trim((string) $config->support_phone))) {
            return response()->json([
                'message' => trans('messages.verification_phone_required'),
            ], 422);
        }

        if (! filled(trim((string) $config->support_email))) {
            return response()->json([
                'message' => trans('messages.verification_email_required'),
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

    public function saveVerificationContact(Request $request)
    {
        $shopId = $this->merchantShopId();
        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $config = Config::findOrFail($shopId);

        if (! $config->canSubmitVerificationRequest()) {
            return response()->json(['message' => trans('messages.verification_request_not_allowed')], 422);
        }

        $request->validate([
            'support_phone' => 'required|string|max:32',
            'support_email' => 'required|email|max:255',
        ]);

        $config->update([
            'support_phone' => $request->input('support_phone'),
            'support_email' => $request->input('support_email'),
        ]);

        clearShopConfigCache($shopId);

        return response()->json([
            'message' => trans('messages.verification_phone_saved'),
            'support_phone' => $config->support_phone,
            'support_email' => $config->support_email,
        ]);
    }

    public function saveVerificationLocation(Request $request)
    {
        $shopId = $this->merchantShopId();
        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $config = Config::findOrFail($shopId);

        if (! $config->canSubmitVerificationRequest()) {
            return response()->json(['message' => trans('messages.verification_request_not_allowed')], 422);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address_title' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'zip_code' => 'nullable|string|max:32',
            'country_id' => 'required|integer',
            'state_id' => 'nullable|integer',
            'phone' => 'required|string|max:32',
        ]);

        $shop = $config->shop;
        $address = $shop->storeAddress();
        $addressChanges = app(ShopAddressChangeService::class);
        $user = Auth::guard('vendor_api')->user() ?? Auth::user();

        if ($address && $addressChanges->requiresApproval($shop, $address)) {
            try {
                $addressChanges->submitRequest($shop, $address, $request, $user);
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => trans('messages.address_change_request_submitted'),
                'pending_address_change' => true,
            ]);
        }

        if (! $address) {
            $address = $shop->addresses()->create([
                'address_type' => 'Primary',
                'address_title' => $request->input('address_title', $shop->name),
                'address_line_1' => $request->input('address_line_1'),
                'address_line_2' => $request->input('address_line_2'),
                'landmark' => $request->input('landmark'),
                'city' => $request->input('city', ''),
                'zip_code' => $request->input('zip_code', '00000'),
                'country_id' => $request->input('country_id', config('system_settings.address_default_country')),
                'state_id' => $request->input('state_id', config('system_settings.address_default_state')),
                'phone' => $request->input('phone', $config->support_phone),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);
        } else {
            $address->fill($request->only([
                'address_title', 'address_line_1', 'address_line_2', 'landmark',
                'city', 'zip_code', 'country_id', 'state_id', 'phone',
            ]));
            $address->latitude = $request->input('latitude');
            $address->longitude = $request->input('longitude');
            $address->save();
        }

        if (! $address->latitude || ! $address->longitude) {
            app(GeocodeService::class)->applyToAddress($address->fresh());
            $address->refresh();
        }

        $shop->update(['primary_address_id' => $address->id]);
        clearShopConfigCache($shopId);

        return response()->json([
            'message' => trans('app.store_location_set'),
            'pending_address_change' => false,
            'store_address' => $this->storeAddressPayload($address),
        ]);
    }

    public function uploadVerificationDocuments(Request $request)
    {
        $shopId = $this->merchantShopId();
        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $config = Config::findOrFail($shopId);

        if (! $config->canSubmitVerificationRequest()) {
            return response()->json(['message' => trans('messages.verification_request_not_allowed')], 422);
        }

        $request->validate([
            'documents' => 'required',
            'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            'document_type' => 'required|in:person,store',
        ]);

        $files = $request->file('documents');
        if (empty($files)) {
            $files = data_get($request->allFiles(), 'documents');
        }
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }

        $created = $config->saveAttachments($files);
        $documentType = (string) $request->input('document_type', 'person');
        $config->registerVerificationAttachmentIds(
            collect($created)->pluck('id')->all(),
            $documentType
        );

        clearShopConfigCache($shopId);
        $config->unsetRelation('attachments');

        $personDocuments = $this->attachmentsPayload($config->personVerificationAttachments());
        $storeDocuments = $this->attachmentsPayload($config->storeVerificationAttachments());
        if ($documentType === 'person' && empty($personDocuments)) {
            $personDocuments = $this->attachmentsPayload($created);
        }
        if ($documentType === 'store' && empty($storeDocuments)) {
            $storeDocuments = $this->attachmentsPayload($created);
        }

        return response()->json([
            'message' => trans('messages.verification_documents_uploaded'),
            'person_documents' => $personDocuments,
            'store_documents' => $storeDocuments,
            'documents' => $this->attachmentsPayload($config->attachments()->get()),
        ]);
    }

    public function replaceVerificationDocument(Request $request, Attachment $attachment)
    {
        if ($this->wantsVerificationDocumentDelete($request)) {
            return $this->deleteVerificationDocument($attachment);
        }

        $config = $this->authorizeVerificationDocument($attachment);

        $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $file = $request->file('document');

        if (Storage::exists($attachment->path)) {
            Storage::delete($attachment->path);
        }

        $attachment->update([
            'path' => Storage::putFile(attachment_storage_dir(), $file),
            'name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
        ]);

        clearShopConfigCache($config->id);

        $config->unsetRelation('attachments');

        return response()->json([
            'message' => trans('messages.verification_document_replaced'),
            'person_documents' => $this->attachmentsPayload($config->personVerificationAttachments()),
            'store_documents' => $this->attachmentsPayload($config->storeVerificationAttachments()),
        ]);
    }

    public function deleteVerificationDocument(Attachment $attachment)
    {
        $config = $this->authorizeVerificationDocument($attachment);

        $config->deleteAttachment($attachment);
        $config->unregisterVerificationAttachmentId((int) $attachment->id);
        clearShopConfigCache($config->id);

        return response()->json([
            'message' => trans('messages.file_deleted'),
            'person_documents' => $this->attachmentsPayload($config->personVerificationAttachments()),
            'store_documents' => $this->attachmentsPayload($config->storeVerificationAttachments()),
        ]);
    }

    protected function wantsVerificationDocumentDelete(Request $request): bool
    {
        return strtoupper((string) $request->input('action')) === 'DELETE'
            || strtoupper((string) $request->input('_method')) === 'DELETE';
    }

    protected function authorizeVerificationDocument(Attachment $attachment): Config
    {
        $shopId = $this->merchantShopId();
        abort_unless($shopId > 0, 404, trans('responses.not_found', ['model' => $this->model_name]));

        $config = Config::findOrFail($shopId);

        $ownsAttachment = $config->attachments()
            ->where('attachments.id', $attachment->id)
            ->exists();

        $isConfigMorph = in_array($attachment->attachable_type, [
            Config::class,
            'App\\Models\\Config',
            'App\\Config',
            'config',
        ], true) && (int) $attachment->attachable_id === (int) $shopId;

        $isRegistered = in_array((int) $attachment->id, array_merge(
            $config->personVerificationAttachmentIds(),
            $config->storeVerificationAttachmentIds()
        ), true);

        if (! $ownsAttachment && ! $isConfigMorph && ! $isRegistered) {
            abort(403, trans('responses.unauthorized'));
        }

        if (! $config->canSubmitVerificationRequest()) {
            abort(403, trans('messages.verification_request_not_allowed'));
        }

        return $config;
    }

    protected function attachmentsPayload($attachments): array
    {
        if (! $attachments) {
            return [];
        }

        return collect($attachments)->map(function ($attachment) {
            return [
                'id' => (int) $attachment->id,
                'name' => $attachment->name,
                'size' => (int) $attachment->size,
                'extension' => $attachment->extension,
                'url' => url('api/vendor/attachment/'.$attachment->id.'/download'),
            ];
        })->values()->all();
    }

    protected function storeAddressPayload($address): ?array
    {
        if (! $address) {
            return null;
        }

        return [
            'address_title' => $address->address_title,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'landmark' => $address->landmark,
            'city' => $address->city,
            'zip_code' => $address->zip_code,
            'country_id' => $address->country_id,
            'state_id' => $address->state_id,
            'phone' => $address->phone,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ];
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
