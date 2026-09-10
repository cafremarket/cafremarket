<?php

namespace App\Repositories\DeliveryBoy;

use App\Models\DeliveryBoy;
use App\Repositories\BaseRepository;
use App\Repositories\Concerns\ScopesMerchantShop;
use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EloquentDeliveryBoy extends EloquentRepository implements BaseRepository, DeliveryBoyRepository
{
    use ScopesMerchantShop;

    protected $model;

    public function __construct(DeliveryBoy $deliveryBoy)
    {
        $this->model = $deliveryBoy;
    }

    public function all()
    {
        if (Auth::user()->isFromPlatform()) {
            $result = $this->model->with('shop:id,name');
        } else {
            $result = $this->model->with('avatarImage')->mine();
        }

        return $result->orderBy('id', 'desc')->get();
    }

    public function store($request)
    {
        if (! Auth::user()->isFromPlatform()) {
            $request->merge([
                'shop_id' => Auth::user()->merchantId(),
            ]);
        }

        // This email already has a rider account in another store — reuse that
        // password (a straight copy, not a reference) so this new row keeps
        // working under its own password even if the other store's account is
        // later deleted, and the rider can log into both with one password.
        if (! $request->filled('password')) {
            $existing = DeliveryBoy::where('email', $request->email)
                ->whereNotNull('password')
                ->first();

            if ($existing) {
                $request->merge(['password' => $existing->password]);
            }
        }

        $deliveryBoy = parent::store($request);

        $deliveryBoy->saveAddress($request);

        if ($request->hasFile('image')) {
            $deliveryBoy->saveImage($request->file('image'));
        }

        return $deliveryBoy;
    }

    public function update(Request $request, $id)
    {
        $deliveryBoy = parent::update($request, $id);

        if ($request->hasFile('image')) {
            $deliveryBoy->deleteImage();
        }

        if ($request->hasFile('image')) {
            $deliveryBoy->saveImage($request->file('image'));
        }

        return $deliveryBoy;
    }

    /**
     * Delete a delivery boy outright — there's no trash/restore state for
     * this model, so this is a real, immediate, permanent delete.
     */
    public function destroy($id)
    {
        $deliveryBoy = $this->model->findOrFail($id);

        $deliveryBoy->flushImages();

        return $deliveryBoy->delete();
    }

    /**
     * Same as destroy(), for multiple ids at once — no trash/restore state.
     */
    public function massDestroy($ids)
    {
        $deliveryBoys = $this->model->whereIn('id', $ids)->get();

        foreach ($deliveryBoys as $deliveryBoy) {
            $deliveryBoy->flushImages();
        }

        return $this->model->whereIn('id', $ids)->delete();
    }
}
