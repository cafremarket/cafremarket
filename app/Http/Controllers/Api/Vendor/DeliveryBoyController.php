<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDeliveryBoyRequest;
use App\Http\Resources\DeliveryBoyResource;
use App\Repositories\DeliveryBoy\DeliveryBoyRepository;
use Illuminate\Http\Request;

class DeliveryBoyController extends Controller
{
    private $delivery_boy;

    /**
     * construct
     */
    public function __construct(DeliveryBoyRepository $delivery_boy)
    {
        parent::__construct();
        $this->delivery_boy = $delivery_boy;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return DeliveryBoyResource::collection($this->delivery_boy->all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateDeliveryBoyRequest $request)
    {
        $this->delivery_boy->store($request);

        return response()->json(['message' => trans('api.delivery_boy_created_successfully')], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new DeliveryBoyResource($this->delivery_boy->find($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $this->delivery_boy->update($request, $id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.delivery_boy_updated_successfully')], 200);
    }

    /**
     * Delete a delivery boy — no trash/restore state, this is immediate and
     * permanent. Kept under the "trash" name/route for API compatibility with
     * existing app builds that still call it.
     */
    public function trash($id)
    {
        try {
            $this->delivery_boy->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.delivery_boy_deleted_successfully')], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $this->delivery_boy->destroy($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json(['message' => trans('api.delivery_boy_deleted_successfully')], 200);
    }
}
