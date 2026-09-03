<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Shop;

class UpdateBasicConfigRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Request::user()->merchantId() == Request::route('shop');
    }

    /**
     * Merchants may not see/edit legal_name; keep existing or fall back to shop name.
     */
    protected function prepareForValidation(): void
    {
        $legalName = trim((string) $this->input('legal_name', ''));

        if ($legalName !== '') {
            return;
        }

        $shop = Shop::find(Request::route('shop'));
        $fallback = $shop?->legal_name
            ?: $this->input('name')
            ?: $shop?->name
            ?: 'Store';

        $this->merge(['legal_name' => $fallback]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = Request::route('shop'); // Current model ID

        return [
            'name' => 'required',
            'slug' => 'required|alpha_dash|unique:shops,slug,'.$id.',id,deleted_at,NULL',
            'legal_name' => 'required',
            'email' => 'required|email|max:255|unique:shops,email,'.$id,
            'external_url' => 'nullable|url',
            'logo' => 'max:'.config('system_settings.max_img_size_limit_kb').'|mimes:jpg,jpeg,png,gif,svg',
            'cover_image' => 'nullable|mimes:jpg,jpeg,png,gif,svg',
            'service_radius_km' => 'nullable|numeric|min:1|max:100',
            'delivery_capability' => 'nullable|in:shop_only,system_only,both',
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
            'image.max' => trans('validation.brand_logo_max'),
            'image.mimes' => trans('validation.brand_logo_mimes'),
        ];
    }
}
