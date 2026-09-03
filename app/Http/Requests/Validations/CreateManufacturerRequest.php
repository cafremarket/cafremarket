<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateManufacturerRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->isFromMerchant() && $this->user()->merchantId();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $shopId = Auth::user()->merchantId();

        $slug = $this->input('slug');
        if (blank($slug) && $this->filled('name')) {
            $slug = Str::slug($this->input('name'));
        }

        // Keep merchant manufacturer slugs shop-scoped (avoids collisions with platform brands).
        if ($shopId && filled($slug)) {
            $suffix = '-s'.$shopId;
            if (! str_ends_with($slug, $suffix) && ! str_contains($slug, $suffix.'-')) {
                $slug .= $suffix;
            }
        }

        $this->merge([
            'shop_id' => $shopId,
            'active' => $this->input('active', 1),
            'slug' => $slug,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $shopId = Auth::user()->merchantId();

        return [
            'name' => [
                'bail',
                'required',
                Rule::unique('manufacturers', 'name')->where(function ($query) use ($shopId) {
                    return $query->where('shop_id', $shopId);
                }),
            ],
            'slug' => 'bail|required|unique:manufacturers,slug',
            'email' => 'nullable|email|max:255',
            'url' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'country_id' => 'nullable|integer',
            'active' => 'nullable',
            'images.logo' => 'nullable|mimes:jpg,jpeg,png,gif,svg|max:'.config('system_settings.max_img_size_limit_kb'),
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'images.logo.max' => trans('validation.brand_logo_max'),
            'images.logo.mimes' => trans('validation.brand_logo_mimes'),
        ];
    }
}
