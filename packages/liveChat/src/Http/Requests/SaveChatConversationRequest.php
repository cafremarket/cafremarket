<?php

namespace Incevio\Package\LiveChat\Http\Requests;

use App\Http\Requests\Request;
use App\Models\Reply;
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
        if (Auth::guard('vendor_api')->check() && $this->route('chat')) {
            return (int) $this->route('chat')->shop_id
                === (int) Auth::guard('vendor_api')->user()->merchantId();
        }

        return true;
    }

    /**
     * Merge auth fields before validation so multipart file uploads are not affected.
     * Also normalizes `payload`: every web composer submits via FormData (even
     * for text-only sends), which can only carry it as a JSON string — decode
     * it here so the `array` rule below (and every controller reading
     * `$request->input('payload')`) always sees a real array, regardless of
     * whether the client sent JSON body (array) or multipart (string).
     */
    protected function prepareForValidation()
    {
        $payload = $this->input('payload');
        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $this->merge(['payload' => $decoded]);
            }
        }

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
            'type' => 'nullable|string|in:'.implode(',', [
                Reply::TYPE_TEXT,
                Reply::TYPE_ATTACHMENT,
                Reply::TYPE_LOCATION,
                Reply::TYPE_CONTACT,
                Reply::TYPE_PRODUCT_SHARE,
                Reply::TYPE_ORDER_SHARE,
            ]),
            'payload' => 'nullable|array',
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
