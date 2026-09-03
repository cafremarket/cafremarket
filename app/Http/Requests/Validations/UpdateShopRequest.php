<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class UpdateShopRequest extends Request
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
        if ($this->has('remove_from_pending_verification_list')) {
            return [];
        }

        $id = Request::segment(count(Request::segments())); // Current model ID

        if ($this->filled('nuit')) {
            $this->merge(['nuit' => preg_replace('/\s+/', '', strtoupper((string) $this->input('nuit')))]);
        }

        return [
            'name' => 'required',
            'legal_name' => 'required',
            'seller_type' => 'required|in:individual,company',
            'nuit' => 'nullable|string|min:9|max:20',
            'email' => 'required|email|max:255|unique:shops,email,'.$id,
            'external_url' => 'nullable|url',
            'image' => 'max:'.config('system_settings.max_img_size_limit_kb').'|mimes:jpg,jpeg,png,gif,svg',
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
