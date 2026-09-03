<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Role;
use Illuminate\Validation\Rule;

class CreateMerchantRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Request::user()->isFromPlatform();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        Request::merge(['role_id' => Role::MERCHANT]); // Set role_id

        if ($this->filled('nuit')) {
            $this->merge(['nuit' => preg_replace('/\s+/', '', strtoupper((string) $this->input('nuit')))]);
        }

        $rules = [
            'name' => 'required|max:255',
            'legal_name' => 'required',
            'seller_type' => 'required|in:individual,company',
            'nuit' => 'required|string|min:9|max:20',
            'slug' => [
                'required',
                'alpha_dash',
                'max:255',
                Rule::unique('shops', 'slug')->whereNull('deleted_at'),
            ],
            'shop_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shops', 'name')->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'external_url' => 'nullable|url',
            'password' => 'required|min:6',
            'active' => 'required',
            'image' => 'max:'.config('system_settings.max_img_size_limit_kb').'|mimes:jpg,jpeg,png,gif,svg',
        ];

        if (is_incevio_package_loaded('otp-login')) {
            $rules['phone'] = [
                'required',
                'string',
                Rule::unique('users', 'phone')->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }
}
