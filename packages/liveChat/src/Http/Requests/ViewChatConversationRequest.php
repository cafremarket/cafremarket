<?php

namespace Incevio\Package\LiveChat\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class ViewChatConversationRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $chat = $this->route('chat');
        $user = Auth::guard('vendor_api')->user() ?? $this->user();

        if (! $user || ! $chat) {
            return false;
        }

        return (int) $user->merchantId() === (int) $chat->shop_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
