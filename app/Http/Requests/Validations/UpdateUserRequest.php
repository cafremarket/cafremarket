<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = $this->route('user');

        if (Auth::guard('vendor_api')->check() && $user) {
            return (int) $user->shop_id
                === (int) Auth::guard('vendor_api')->user()->merchantId();
        }

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
            'name' => 'bail|required|max:255',
            'email' => 'email|max:255|unique:users,email,'.$this->route('user'),
            'role_id' => 'required',
            'active' => 'required',
            'image' => 'mimes:jpg,jpeg,png,svg',
        ];
    }
}
