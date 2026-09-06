<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class RegisterCustomerRequest extends Request
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
        $rules = [
            'name' => 'required|min:3|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                // Soft-deleted (trash) emails can be reclaimed as a new active account.
                Rule::unique('customers', 'email')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:6|confirmed',
            'agree' => 'required',
        ];

        if (is_incevio_package_loaded('buyerGroup')) {
            $rules['buyer_group_id'] = 'required|exists:buyer_groups,id';
        }

        if (is_incevio_package_loaded('otp-login')) {
            // Email/password app register may send only a country code — do not require phone.
            $rules['phone'] = [
                'nullable',
                'string',
                'max:255',
                Rule::unique('customers', 'phone')->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.unique' => trans('validation.register_email_unique'),
        ];
    }
}
