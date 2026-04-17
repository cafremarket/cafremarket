<?php

namespace Incevio\Package\LiveChat\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class SaveChatConversationRequest extends Request
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
     * Merge auth fields before validation so multipart file uploads are not affected.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'customer_id' => $this->customer_id(),
            'user_id' => $this->shop_user_id(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'message' => 'required',
        ];
    }

    /**
     * Return shop user id
     */
    private function shop_user_id()
    {
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user()->id;
        }

        if (Auth::guard('vendor_api')->check()) {
            return Auth::guard('vendor_api')->user()->id;
        }

        return null;
    }

    /**
     * Return customer id
     */
    private function customer_id()
    {
        if (Auth::guard('customer')->check()) {
            return Auth::guard('customer')->user()->id;
        }

        if (Auth::guard('api')->check()) {
            return Auth::guard('api')->user()->id;
        }

        return null;
    }
}
