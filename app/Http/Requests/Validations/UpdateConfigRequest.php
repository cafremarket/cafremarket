<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class UpdateConfigRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::guard('vendor_api')->user() ?? $this->user();

        if (! $user) {
            return false;
        }

        $shopId = (int) $user->merchantId();
        $configId = (int) $this->route('config');

        return $shopId > 0 && $shopId === $configId;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }
}
