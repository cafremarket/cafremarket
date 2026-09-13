<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;
use App\Models\Popup;
use Illuminate\Validation\Rule;

class CreatePopupRequest extends Request
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'headline' => 'nullable|max:255',
            'description' => 'nullable|max:1000',
            'button_label' => 'nullable|max:60',
            'button_link' => 'nullable|max:255',
            'bg_color' => 'nullable|string|max:20',
            'hide_text' => 'nullable|boolean',
            'platform' => ['required', Rule::in([Popup::PLATFORM_ALL, Popup::PLATFORM_WEB, Popup::PLATFORM_APP])],
            'page' => ['required', Rule::in([
                Popup::PAGE_ALL, Popup::PAGE_HOME, Popup::PAGE_PRODUCT,
                Popup::PAGE_CATEGORY, Popup::PAGE_CART, Popup::PAGE_CHECKOUT,
            ])],
            'user_type' => ['required', Rule::in([Popup::USER_TYPE_ALL, Popup::USER_TYPE_GUEST, Popup::USER_TYPE_CUSTOMER])],
            'frequency' => ['required', Rule::in([
                Popup::FREQUENCY_EVERY_PAGE_LOAD, Popup::FREQUENCY_ONCE_PER_SESSION,
                Popup::FREQUENCY_ONCE_PER_DAY, Popup::FREQUENCY_ONCE_ONLY,
            ])],
            'delay_ms' => 'nullable|integer|min:0|max:60000',
            'priority' => 'nullable|integer|min:1|max:999',
            'active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'images.feature' => 'nullable|mimes:jpg,jpeg,png,gif,svg,webp',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
            'hide_text' => $this->boolean('hide_text'),
            'delay_ms' => (int) $this->input('delay_ms', 2000),
            'priority' => (int) $this->input('priority', 100),
            'bg_color' => $this->input('bg_color') ?: null,
        ]);
    }
}
