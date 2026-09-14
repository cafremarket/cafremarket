<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use Illuminate\Support\Facades\Auth;

class UpdatePolicyPageRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->isFromPlatform();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'position' => 'required|string',
            'visibility' => 'required|in:1,2',
            'published_at' => 'nullable|date',
            'images.cover' => 'nullable|mimes:jpg,jpeg,png,gif,svg|max:5120',
        ];
    }
}
