<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDeliveryBoyRequest;
use App\Http\Requests\Validations\UpdateDeliveryBoyRequest;
use App\Models\DeliveryBoy;
use Illuminate\Http\Request;

class PlatformDeliveryBoyController extends Controller
{
    public function index()
    {
        $deliveryBoys = DeliveryBoy::platformRiders()
            ->with('avatarImage')
            ->orderByDesc('id')
            ->get();

        $trashes = DeliveryBoy::platformRiders()->onlyTrashed()->get();

        return view('admin.platform_deliveryboy.index', compact('deliveryBoys', 'trashes'));
    }

    public function create()
    {
        return view('admin.platform_deliveryboy._create');
    }

    public function store(CreateDeliveryBoyRequest $request)
    {
        $data = $request->validated();
        $data['type'] = DeliveryBoy::TYPE_PLATFORM;
        $data['shop_id'] = null;

        $deliveryBoy = DeliveryBoy::create($data);

        if ($request->hasFile('image')) {
            $deliveryBoy->saveImage($request->file('image'));
        }

        return back()->with('success', trans('messages.created', ['model' => trans('app.platform_rider')]));
    }

    public function edit(DeliveryBoy $platform_rider)
    {
        abort_unless($platform_rider->isPlatform(), 404);

        return view('admin.platform_deliveryboy._edit', ['deliveryboy' => $platform_rider]);
    }

    public function update(UpdateDeliveryBoyRequest $request, DeliveryBoy $platform_rider)
    {
        abort_unless($platform_rider->isPlatform(), 404);

        $platform_rider->update($request->validated());

        if ($request->hasFile('image')) {
            $platform_rider->deleteImage();
            $platform_rider->saveImage($request->file('image'));
        }

        return back()->with('success', trans('messages.updated', ['model' => trans('app.platform_rider')]));
    }

    public function destroy(DeliveryBoy $platform_rider)
    {
        abort_unless($platform_rider->isPlatform(), 404);

        $platform_rider->flushImages();
        $platform_rider->forceDelete();

        return back()->with('success', trans('messages.deleted', ['model' => trans('app.platform_rider')]));
    }

    public function trash(DeliveryBoy $platform_rider)
    {
        abort_unless($platform_rider->isPlatform(), 404);
        $platform_rider->delete();

        return back()->with('success', trans('messages.trashed', ['model' => trans('app.platform_rider')]));
    }

    public function restore($platform_rider_id)
    {
        $deliveryBoy = DeliveryBoy::platformRiders()->onlyTrashed()->findOrFail($platform_rider_id);
        $deliveryBoy->restore();

        return back()->with('success', trans('messages.restored', ['model' => trans('app.platform_rider')]));
    }
}
