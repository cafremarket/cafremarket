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
use App\Models\Config;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $config = Config::findOrFail($shopId);

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

            return response('success', 200);
        }

        return response('error', 405);
    }
}
