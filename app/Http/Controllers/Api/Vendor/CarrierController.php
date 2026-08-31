<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateCarrierRequest;
use App\Http\Requests\Validations\UpdateCarrierRequest;
use App\Http\Resources\CarrirResource;
use App\Models\Carrier;
use App\Repositories\Carrier\CarrierRepository;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    private $carrier;

    public function __construct(CarrierRepository $carrier)
    {
        parent::__construct();
        $this->carrier = $carrier;
    }

    public function index(Request $request)
    {
        $filter = $request->get('filter');
        $query = Carrier::mine();

        if ($filter === 'trash') {
            $query = $query->onlyTrashed();
        }

        $carriers = $query->paginate(config('mobile_app.view_listing_per_page', 8));

        return CarrirResource::collection($carriers);
    }

    public function store(CreateCarrierRequest $request)
    {
        $carrier = $this->carrier->store($request);

        return response()->json([
            'message' => trans('messages.created', ['model' => trans('app.model.carrier')]),
            'data' => new CarrirResource($carrier),
        ], 201);
    }

    public function show(Carrier $carrier)
    {
        abort_unless($carrier->shop_id === auth()->user()->merchantId(), 403);

        return new CarrirResource($carrier);
    }

    public function update(UpdateCarrierRequest $request, Carrier $carrier)
    {
        abort_unless($carrier->shop_id === auth()->user()->merchantId(), 403);

        $this->carrier->update($request, $carrier->id);

        return response()->json([
            'message' => trans('messages.updated', ['model' => trans('app.model.carrier')]),
            'data' => new CarrirResource($carrier->fresh()),
        ]);
    }

    public function trash(Carrier $carrier)
    {
        abort_unless($carrier->shop_id === auth()->user()->merchantId(), 403);

        $this->carrier->trash($carrier->id);

        return response()->json(['message' => trans('messages.trashed', ['model' => trans('app.model.carrier')])]);
    }

    public function restore($carrier_id)
    {
        $carrier = Carrier::onlyTrashed()->findOrFail($carrier_id);
        abort_unless($carrier->shop_id === auth()->user()->merchantId(), 403);

        $this->carrier->restore($carrier_id);

        return response()->json(['message' => trans('messages.restored', ['model' => trans('app.model.carrier')])]);
    }

    public function destroy($carrier_id)
    {
        $carrier = Carrier::withTrashed()->findOrFail($carrier_id);
        abort_unless($carrier->shop_id === auth()->user()->merchantId(), 403);

        $this->carrier->destroy($carrier_id);

        return response()->json(['message' => trans('messages.deleted', ['model' => trans('app.model.carrier')])]);
    }
}
