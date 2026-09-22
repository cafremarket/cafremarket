<?php

namespace Incevio\Package\LiveChat\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class ChatConversationRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::guard('customer')->check() || Auth::guard('api')->check();
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

    /**
     * Return JSON for the chat widget (never an HTML login redirect).
     */
    protected function failedAuthorization()
    {
        abort(response()->json([
            'message' => trans('theme.login_to_chat'),
            'code' => 'login_required',
        ], 401)->header('Cache-Control', 'no-store, private'));
    }
}
