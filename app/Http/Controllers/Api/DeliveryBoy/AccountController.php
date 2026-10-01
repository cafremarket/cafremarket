<?php

namespace App\Http\Controllers\Api\DeliveryBoy;

use App\Helpers\ApiAlert;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryBoy\UpdateProfileRequest;
use App\Http\Resources\DeliveryBoyResource;
use App\Http\Resources\ShopLightResource;
use App\Models\DeliveryBoy;
use App\Models\Shop;
use App\Repositories\DeliveryBoy\DeliveryBoyRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    use ApiAlert;

    protected $model;

    private $deliveryBoy;

    /**
     * construct
     */
    public function __construct(DeliveryBoyRepository $deliveryBoy)
    {
        parent::__construct();
        $this->deliveryBoy = $deliveryBoy;
    }

    /**
     * Delivery boy profile
     *
     * @return [delivery boy]
     */
    public function profile()
    {
        return new DeliveryBoyResource(Auth::guard('delivery_boy-api')->user());
    }

    /**
     * Delivery boy profile update
     *
     * @return [json] $object
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::guard('delivery_boy-api')->user();

        if (config('app.demo') == true && $user->id <= config('system.demo.delivery_boys')) {
            return ['warning' => trans('messages.demo_restriction')];
        }

        // shop_id and status are set by the shop, not by the rider.
        $user->update($request->only(['first_name', 'last_name', 'nice_name', 'email', 'phone_number', 'dob', 'sex']));

        if ($request->hasFile('avatar') || $request->has('delete_avatar')) {
            $user->deleteImage();
        }

        if ($request->has('avatar')) {
            $file = create_file_from_base64($request->get('avatar'));

            $user->saveImage($file, 'avatar');
        }

        return new DeliveryBoyResource($user);
    }

    /**
     * Getting vendor name
     *
     * @return [json] $object
     */
    public function vendor()
    {
        $shop_id = Auth::guard('delivery_boy-api')->user()->shop_id;
        $shop = Shop::findOrFail($shop_id);

        return new ShopLightResource($shop);
    }

    /**
     * Delete the rider's account. A rider is one person across stores (the
     * same email can switch stores without a password), so every store
     * account under this email is deleted.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete()
    {
        $user = Auth::guard('delivery_boy-api')->user();

        try {
            DB::transaction(function () use ($user) {
                $accounts = DeliveryBoy::where('email', $user->email)->get();

                foreach ($accounts as $account) {
                    $account->deleteAccount();
                }

                Log::info('Delivery account deleted from app', ['ids' => $accounts->pluck('id')->all()]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.account_deleted_successfully')], 200);
    }
}
