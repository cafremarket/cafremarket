<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Inventory;
use Illuminate\Validation\Rule;

class ProductSearchRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'q' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:120',
            'province' => 'nullable|string|max:120',
            'country_id' => 'nullable|integer|exists:countries,id',
            'state_id' => 'nullable|integer|exists:states,id',
            'price_min' => 'numeric',
            'price_max' => 'numeric',
            'has_offers' => 'sometimes|accepted',
            'new_arrivals' => 'sometimes|accepted',
            'free_shipping' => 'sometimes|accepted',
            'item_condition' => Rule::in(Inventory::CONDITIONS),
            'sort_by' => Rule::in(['price_asc', 'price_desc', 'newest', 'oldest']),
        ];
    }

    /**
     * Require a keyword unless the user is narrowing by zone, location, or category
     * (e.g. header country/state selects submit without typing q).
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $q = trim((string) $this->input('q', ''));

            if ($q !== '') {
                return;
            }

            if ($this->filled('country_id')
                || $this->filled('state_id')
                || $this->filled('location')
                || $this->filled('province')
                || $this->filled('in')
                || $this->filled('ingrp')
                || ($this->filled('insubgrp') && (string) $this->input('insubgrp') !== 'all')
            ) {
                return;
            }

            $validator->errors()->add('q', trans('validation.required', ['attribute' => 'q']));
        });
    }
}
