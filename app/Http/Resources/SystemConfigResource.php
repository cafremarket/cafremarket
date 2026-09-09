<?php

namespace App\Http\Resources;

use App\Helpers\ListHelper;
use App\Models\Currency;
use App\Models\System;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class SystemConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $settings = is_array($this->resource)
            ? $this->resource
            : (array) $this->resource;

        $attr = function (string $key, $default = null) use ($settings) {
            return array_key_exists($key, $settings) ? $settings[$key] : $default;
        };

        // declaring it outside the if block to avoid undefined error. Need to consider conditional merging in return statement
        $selectedCurrencyDetails = null;

        // Get the api compatibilities settings
        if ($request->is('api/system_configs')) {
            $compatibility = System::$api_compatibility['customer'];

            if (is_incevio_package_loaded('dynamic-currency')) {
                $ip = get_visitor_IP();

                $dynamicCurrencyId = DB::table('visitors')
                    ->where('ip', $ip)->value('selected_currency_id');

                if ($dynamicCurrencyId) {
                    $selectedCurrencyDetails = Currency::where('id', $dynamicCurrencyId)->first();
                }
            }
        } elseif ($request->is('api/deliveryboy/*')) {
            $compatibility = System::$api_compatibility['delivery'];
        } elseif ($request->is('api/vendor/*')) {
            $compatibility = System::$api_compatibility['vendor'];
        }

        $currency = $attr('currency');
        $currencyArr = is_array($currency)
            ? $currency
            : (is_object($currency) ? (array) $currency : null);

        return [
            'maintenance_mode' => (bool) $attr('maintenance_mode'),
            // "install_verion" => System::VERSION, // Need to fix the spelling in app end also, will be removed soon
            'install_version' => System::VERSION,
            'compatible_app_version' => $this->when(isset($compatibility), $compatibility),
            'name' => $attr('name'),
            'slogan' => $attr('slogan'),
            'legal_name' => $attr('legal_name'),
            'platform_logo' => get_logo_url('system', 'full'),
            'email' => $attr('email'),
            'worldwide_business_area' => (bool) $attr('worldwide_business_area'),
            'timezone_id' => $attr('timezone_id'),
            'currency_id' => $attr('currency_id'),
            'default_language' => $attr('default_language'),
            'ask_customer_for_email_subscription' => (bool) $attr('ask_customer_for_email_subscription'),
            'can_cancel_order_within' => $attr('can_cancel_order_within'),
            'support_phone' => $attr('support_phone'),
            'support_phone_toll_free' => $attr('support_phone_toll_free'),
            'support_email' => $attr('support_email'),
            'facebook_link' => $attr('facebook_link'),
            'google_plus_link' => $attr('google_plus_link'),
            'twitter_link' => $attr('twitter_link'),
            'pinterest_link' => $attr('pinterest_link'),
            'instagram_link' => $attr('instagram_link'),
            'youtube_link' => $attr('youtube_link'),
            'length_unit' => $attr('length_unit'),
            'weight_unit' => $attr('weight_unit'),
            'valume_unit' => $attr('valume_unit'),
            'decimals' => $attr('decimals'),
            'show_currency_symbol' => (bool) $attr('show_currency_symbol'),
            'show_space_after_symbol' => (bool) $attr('show_space_after_symbol'),
            'max_img_size_limit_kb' => $attr('max_img_size_limit_kb'),
            'show_item_conditions' => (bool) $attr('show_item_conditions'),
            'address_default_country' => $attr('address_default_country'),
            'address_default_state' => $attr('address_default_state'),
            'show_address_title' => (bool) $attr('show_address_title'),
            'address_show_country' => (bool) $attr('address_show_country'),
            'address_show_map' => (bool) $attr('address_show_map'),
            'allow_guest_checkout' => (bool) $attr('allow_guest_checkout'),
            'enable_chat' => true,
            'vendor_get_paid' => (bool) vendor_get_paid_directly(),
            'currency' => $currencyArr ? [
                'name' => $currencyArr['name'] ?? null,
                'iso_code' => $currencyArr['iso_code'] ?? null,
                'symbol' => $currencyArr['symbol'] ?? null,
                'symbol_first' => (bool) ($currencyArr['symbol_first'] ?? false),
                'subunit' => $currencyArr['subunit'] ?? null,
                'decimal_mark' => $currencyArr['decimal_mark'] ?? null,
                'thousands_separator' => $currencyArr['thousands_separator'] ?? null,
            ] : null,
            'selected_currency' => $selectedCurrencyDetails ? [
                'name' => $selectedCurrencyDetails->name,
                'iso_code' => $selectedCurrencyDetails->iso_code,
                'symbol' => $selectedCurrencyDetails->symbol,
                'symbol_first' => (bool) $selectedCurrencyDetails->symbol_first,
                'subunit' => $selectedCurrencyDetails->subunit,
                'decimal_mark' => $selectedCurrencyDetails->decimal_mark,
                'thousands_separator' => $selectedCurrencyDetails->thousands_separator,
            ] : null,
            'active_languages' => ListHelper::availableLocales()->pluck('language', 'code')->toArray(),

            'smart_form_id_for_registration' => $request->is('api/vendor/*')
                ? $attr('smart_form_id_for_vendor_additional_info')
                : $attr('smart_form_id_for_customer_registration_form'),

            'show_terms_and_conditions_on_registration' => $request->is('api/vendor/*')
                ? (bool) $attr('show_vendor_terms_and_conditions', false)
                : (bool) $attr('show_customer_terms_and_conditions', false),

            'smart_form_id_for_contact_us_page' => $request->is('api/vendor/*')
                ? $attr('smart_form_id_for_selling_page')
                : $attr('smart_form_id_for_contact_us_page'),

            'publicly_show_affiliate_commission' => (bool) $attr('publicly_show_affiliate_commission', false),

            'disable_other_gender' => config('system.disable_other_gender'),
        ];
    }
}
