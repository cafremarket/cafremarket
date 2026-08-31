<?php

namespace App\Http\Requests\Validations;

use App\Http\Requests\Request;

class MerchantVerifyRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = $this->user();

        return $user
            && $user->merchantId()
            && $user->shop
            && $user->shop->config
            && (int) $user->shop->id === (int) $user->merchantId();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];

        if ($this->isMethod('post') && $this->routeIs('merchant.verify.documents.store') && $this->hasFile('documents')) {
            $rules['documents'] = 'required|array|min:1';
            $rules['documents.*'] = 'file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['document_type'] = 'required|in:person,store';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->isMethod('post') || ! $this->routeIs('merchant.verify.submit', 'admin.setting.verify.submit')) {
                return;
            }

            $shop = $this->user()->shop;
            $config = $shop?->config;

            if ($config && ! $config->hasPersonVerificationDocuments()) {
                $validator->errors()->add('person_documents', trans('messages.verification_person_documents_required'));
            }

            if ($config && ! $config->hasStoreVerificationDocuments()) {
                $validator->errors()->add('store_documents', trans('messages.verification_store_documents_required'));
            }

            if (! config('hyperlocal.require_store_location_for_verification', true)) {
                return;
            }

            if ($shop && ! $shop->hasStoreLocation()) {
                $validator->errors()->add('store_location', trans('app.store_location_required'));
            }

            $phone = trim((string) (optional($config)->support_phone ?: optional($shop?->owner)->phone));
            if ($config && $phone === '') {
                $validator->errors()->add('support_phone', trans('messages.verification_phone_required'));
            }

            $email = trim((string) (optional($config)->support_email ?: optional($shop?->owner)->email));
            if ($config && $email === '') {
                $validator->errors()->add('support_email', trans('messages.verification_email_required'));
            }
        });
    }
}
