<?php

namespace App\Repositories\Address;

use App\Models\Address;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EloquentAddress extends EloquentRepository implements AddressRepository, BaseRepository
{
    protected $model;

    public function __construct(Address $address)
    {
        $this->model = $address;
    }

    public function addresses($addressable_type, $addressable_id)
    {
        $addressable = $this->getAddressableModel($addressable_type, $addressable_id);

        $data['addressable_type'] = strtolower(class_basename($addressable));

        $data['addressable'] = $addressable;

        $data['addresses'] = $addressable->addresses()->with('country', 'state')->get();

        return $data;
    }

    public function getAddressableModel($addressable_type, $addressable_id)
    {
        $addressableClass = get_qualified_model($addressable_type);

        $addressable = new $addressableClass;

        return $addressable->find($addressable_id);
    }

    public function update(Request $request, $id)
    {
        $address = $this->model->findOrFail($id);

        if ($address->addressable_type == \App\Models\System::class) {
            Cache::forget('platform_address_string');
        }

        $address->update($request->all());

        app(\App\Services\Geo\GeocodeService::class)->applyToAddress($address->fresh());
    }

    public function store(Request $request)
    {
        $model = $this->model->create($request->all());

        app(\App\Services\Geo\GeocodeService::class)->applyToAddress($model);

        if ($request->hasFile('images')) {
            foreach ($request->images as $type => $file) {
                $model->saveImage($file, $type);
            }
        }

        if ($request->hasFile('image')) {
            $model->saveImage($request->image);
        }

        return $model;
    }
}
