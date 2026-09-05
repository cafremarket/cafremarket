<?php

use App\Helpers\ListHelper;
use App\Models\Cancellation;
use App\Models\Cart;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\CategorySubGroup;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryBoy;
use App\Models\Dispute;
use App\Models\Inventory;
use App\Models\Manufacturer;
use App\Models\Message;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PdfTemplate;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\Shop;
use App\Models\State;
use App\Models\System;
use App\Models\Tax;
use App\Models\User;
use App\Models\Wishlist;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

if (! function_exists('get_platform_title')) {
    /**
     * Return shop title or the application title
     */
    function get_platform_title()
    {
        return config('system_settings.name') ?? config('app.name');
    }
}

if (! function_exists('clean_rich_html')) {
    /**
     * Render user-pasted HTML without Word/Docs colors, fonts, or backgrounds.
     */
    function clean_rich_html($html)
    {
        if ($html === null || $html === '') {
            return '';
        }

        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="caf-rich-root">'.$html.'</div>';
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//*[@style or @color or @bgcolor or @face or @size or @class]') as $node) {
            if ($node instanceof DOMElement) {
                $node->removeAttribute('style');
                $node->removeAttribute('color');
                $node->removeAttribute('bgcolor');
                $node->removeAttribute('face');
                $node->removeAttribute('size');
                $node->removeAttribute('class');
            }
        }

        $root = $dom->getElementById('caf-rich-root');
        $out = '';
        if ($root) {
            foreach ($root->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $plain = trim(html_entity_decode(strip_tags($out), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plain = trim($plain, " \t\n\r\0\x0B\"“”'");

        if ($plain !== '' && ! preg_match('/<(p|ul|ol|li|br|h[1-6]|table|img|a)\b/i', $out)) {
            return e($plain);
        }

        return $out;
    }
}

if (! function_exists('get_platform_address')) {
    /**
     * return platforms address in html formate
     */
    function get_platform_address()
    {
        $system = System::orderBy('id', 'asc')->first();

        return $system->primaryAddress->toHtml();
    }
}

// Get address as array:
if (! function_exists('get_platform_address_string')) {
    /**
     * return platforms address in html formate
     */
    function get_platform_address_string()
    {
        // Retrieve and set to Cache
        return Cache::rememberForever('platform_address_string', function () {
            $system = System::orderBy('id', 'asc')->first();

            return $system->primaryAddress ? $system->primaryAddress->toString() : '';
        });
    }
}

if (! function_exists('system_cache_remember_for')) {
    /**
     * Return cache time
     */
    function system_cache_remember_for($minute = 10)
    {
        return Carbon::now()->addMinutes($minute);
    }
}

if (! function_exists('get_site_title')) {
    /**
     * Return shop title or the application title
     */
    function get_site_title()
    {
        if (Auth::guard('web')->check() && Auth::user()->isFromMerchant() && Auth::user()->shop) {
            return Auth::user()->shop->name;
        }

        return get_platform_title();
    }
}

if (! function_exists('mp_route')) {
    /**
     * Resolve a merchant-panel route from an admin route name when available.
     */
    function mp_route(string $name, $parameters = [], $absolute = true)
    {
        $merchantName = str_starts_with($name, 'admin.')
            ? 'merchant.'.substr($name, 6)
            : $name;

        if (\Illuminate\Support\Facades\Route::has($merchantName)) {
            return route($merchantName, $parameters, $absolute);
        }

        return route($name, $parameters, $absolute);
    }
}

if (! function_exists('mp_url')) {
    /**
     * Build a merchant-panel URL from an admin-style path.
     */
    function mp_url(string $path = ''): string
    {
        $path = ltrim($path, '/');

        if ($path === 'admin' || $path === '') {
            return route('merchant.dashboard');
        }

        if (str_starts_with($path, 'admin/')) {
            return url('merchant/'.substr($path, 6));
        }

        return url($path);
    }
}

if (! function_exists('mp_is')) {
    /**
     * Match the current request against merchant-panel paths.
     */
    function mp_is(string $pattern): bool
    {
        $pattern = str_replace('admin/', 'merchant/', $pattern);

        return request()->is($pattern);
    }
}

if (! function_exists('mp_is_any')) {
    function mp_is_any(array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (mp_is($pattern)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('panel_route_name')) {
    /**
     * Resolve the correct route name for admin or merchant panel context.
     */
    function panel_route_name(string $name): string
    {
        if (request()->is('merchant/*') && \Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->isFromMerchant()) {
            $merchantName = str_starts_with($name, 'admin.')
                ? 'merchant.'.substr($name, 6)
                : $name;

            if (\Illuminate\Support\Facades\Route::has($merchantName)) {
                return $merchantName;
            }
        }

        return $name;
    }
}

if (! function_exists('panel_route')) {
    function panel_route(string $name, $parameters = [], $absolute = true)
    {
        return route(panel_route_name($name), $parameters, $absolute);
    }
}

if (! function_exists('get_system_currency')) {
    function get_system_currency()
    {
        return config('system_settings.currency.iso_code', config('system.fallback_currency', 'USD'));
    }
}

if (! function_exists('is_billing_info_required')) {
    function is_billing_info_required()
    {
        return is_subscription_enabled() && config('system_settings.required_card_upfront');
    }
}

if (! function_exists('requires_stripe_card_for_subscription')) {
    /**
     * Whether merchants must add a Stripe card before subscribing.
     */
    function requires_stripe_card_for_subscription()
    {
        return is_billing_info_required()
            && ! \App\Models\SystemConfig::isBillingThroughWallet()
            && \App\Models\SystemConfig::isPaymentConfigured('stripe');
    }
}

if (! function_exists('get_subscription_payment_methods')) {
    /**
     * Payment methods allowed for vendor subscription billing (wallet balance, M-Pesa, eMola).
     *
     * @return array<int, array{code: string, name: string}>
     */
    function get_subscription_payment_methods(): array
    {
        $allowed = ['wallet', 'mpesa', 'emola'];
        $methods = [
            [
                'code' => 'wallet',
                'name' => trans('messages.subscription_pay_wallet'),
            ],
        ];

        $walletMethodIds = get_from_option_table('wallet_payment_methods', []);
        $configured = \App\Models\PaymentMethod::find($walletMethodIds);

        foreach ($configured as $paymentMethod) {
            if (! in_array($paymentMethod->code, $allowed, true)) {
                continue;
            }

            if (! \App\Models\SystemConfig::isPaymentConfigured($paymentMethod->code)) {
                continue;
            }

            $methods[] = [
                'code' => $paymentMethod->code,
                'name' => $paymentMethod->name,
            ];
        }

        return $methods;
    }
}

if (! function_exists('subscription_plan_label')) {
    /**
     * Human-readable subscription plan name for a plan_id.
     */
    function subscription_plan_label($planId): ?string
    {
        if ($planId === null || $planId === '') {
            return null;
        }

        static $labels = [];

        if (! array_key_exists($planId, $labels)) {
            $name = \App\Models\SubscriptionPlan::query()
                ->where('plan_id', $planId)
                ->value('name');

            $labels[$planId] = $name ?: (string) $planId;
        }

        return $labels[$planId];
    }
}

if (! function_exists('subscription_charges_immediately')) {
    /**
     * Whether the shop must pay the plan fee now (no active shop generic trial).
     */
    function subscription_charges_immediately(\App\Models\User $merchant, \App\Models\SubscriptionPlan $plan): bool
    {
        if ((float) $plan->cost <= 0) {
            return false;
        }

        $shop = method_exists($merchant, 'merchantShop')
            ? ($merchant->merchantShop() ?? $merchant->shop)
            : $merchant->shop;

        if (
            $shop
            && $shop->onGenericTrial()
            && $shop->trial_ends_at
            && $shop->trial_ends_at->isFuture()
        ) {
            return false;
        }

        return true;
    }
}

if (! function_exists('get_currency_symbol')) {
    function get_currency_symbol($currency_id = null)
    {
        if (is_incevio_package_loaded('dynamic-currency')) {
            return get_dynamic_currency_attr('symbol', $currency_id);
        }

        return config('system_settings.currency.symbol', '$');
    }
}

if (! function_exists('get_gerder_list')) {
    function get_gerder_list()
    {
        return ListHelper::gerder_list();
    }
}

if (! function_exists('get_promotional_tagline')) {
    function get_promotional_tagline()
    {
        return Cache::rememberForever('promotional_tagline', function () {
            return get_from_option_table('promotional_tagline', []);
        });
    }
}

if (! function_exists('get_top_bar_banner_data')) {
    /**
     * Get top bar banner data
     */
    function get_top_bar_banner_data()
    {
        return Cache::rememberForever('top_bar_banner', function () {
            return get_from_option_table('top_bar_banner');
        });
    }
}

if (! function_exists('get_option_table_name')) {
    function get_option_table_name()
    {
        return 'options';
    }
}

if (! function_exists('get_social_media_links')) {
    /**
     * Return social_media_links
     */
    function get_social_media_links()
    {
        $media = ['facebook', 'twitter', 'google_plus', 'pinterest', 'instagram', 'youtube'];
        $links = [];
        foreach ($media as $value) {
            if ($link = config('system_settings.'.$value.'_link')) {
                $links[str_replace('_', '-', $value)] = $link;
            }
        }

        return $links;
    }
}

if (! function_exists('get_shop_url')) {
    /**
     * Return shop title or the application title
     */
    function get_shop_url($shop = '')
    {
        if ($shop instanceof Shop) {
            return url('/shop/'.$shop->slug);
        } elseif ($shop != '' && is_numeric($shop)) {
            $model = Shop::select('id', 'slug')->find($shop);

            return $model ? url('/shop/'.$model->slug) : url('/');
        } elseif ($shop != '' && is_string($shop)) {
            return url('/shop/'.$shop);
        }

        // When slug is not given and user is vendor stuff
        if (Auth::guard('web')->check() && Auth::user()->isFromMerchant()) {
            return url('/shop/'.Auth::user()->shop->slug);
        }

        return url('/');
    }
}

if (! function_exists('get_category_url')) {
    /**
     * Public category URL — store-scoped when the category belongs to a shop.
     */
    function get_category_url($category, $shop = null): string
    {
        if (is_string($category)) {
            return route('category.browse', $category);
        }

        $slug = $category->slug ?? null;
        if (! $slug) {
            return url('/');
        }

        $shopSlug = null;

        if ($shop instanceof Shop) {
            $shopSlug = $shop->slug;
        } elseif (is_string($shop) && $shop !== '') {
            $shopSlug = $shop;
        } elseif (! empty($category->shop_id)) {
            $shopSlug = optional(Shop::select('id', 'slug')->find($category->shop_id))->slug
                ?? (Auth::guard('web')->check()
                    && Auth::user()->isFromMerchant()
                    && Auth::user()->shop
                    ? Auth::user()->shop->slug
                    : null);
        }

        if ($shopSlug && (! empty($category->shop_id) || $shop)) {
            return route('shop.category.browse', ['slug' => $shopSlug, 'category' => $slug]);
        }

        return route('category.browse', $slug);
    }
}

if (! function_exists('get_csv_import_limit')) {
    /**
     * Return the csv_import_limit
     */
    function get_csv_import_limit()
    {
        return config('system_settings.csv_import_limit') ?? config('system.csv_import_limit', 500);
    }
}

if (! function_exists('get_page_url')) {
    /**
     * Return page url
     */
    function get_page_url($page = null)
    {
        if (is_null($page)) {
            return url('/');
        }

        return route('page.open', $page);
    }
}

if (! function_exists('get_verified_badge')) {
    function get_verified_badge()
    {
        return url('images/placeholders/verified_badge.png');
    }
}

if (! function_exists('get_invoice_stamp')) {
    /**
     * Return invoice stamp img
     */
    function get_invoice_stamp()
    {
        return public_path('images/placeholders/stamp.png');
    }
}

if (! function_exists('is_serialized')) {
    /**
     * Check if the given value is_serialized or not
     */
    function is_serialized($data)
    {
        // if it isn't a string, it isn't serialized
        if (! is_string($data)) {
            return false;
        }

        $data = trim($data);

        if ($data == 'N;') {
            return true;
        }

        if (! preg_match('/^([adObis]):/', $data, $badions)) {
            return false;
        }

        switch ($badions[1]) {
            case 'a':
            case 'O':
            case 's':
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data)) {
                    return true;
                }
                break;
            case 'b':
            case 'i':
            case 'd':
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data)) {
                    return true;
                }
                break;
        }

        return false;
    }
}

if (! function_exists('is_base64')) {
    /**
     * Checks if a string is a valid base64 encoded string.
     *
     * @param  string  $string  The string to check.
     * @return bool Returns true if the string is a valid base64 encoded string, false otherwise.
     */
    function is_base64(string $string): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $string);
    }
}

if (! function_exists('remove_url_parameter')) {
    /**
     * Remove given parameter from the given url str
     */
    function remove_url_parameter($url, $key = false)
    {
        return preg_replace('/'.($key ? '(\&|)'.$key.'(\=(.*?)((?=&(?!amp\;))|$)|(.*?)\b)' : '(\?.*)').'/i', '', $url);
    }
}

if (! function_exists('get_avatar_src')) {
    function get_avatar_src($model, $size = 'small')
    {
        if (
            $model instanceof User ||
            $model instanceof Customer ||
            $model instanceof DeliveryBoy
        ) {
            if ($model->avatarImage) {
                return get_storage_file_url($model->avatarImage->path, $size);
            }

            return get_gravatar_url($model->email, $size);
        }

        return get_gravatar_url('help@incevio.com', $size);
    }
}

if (! function_exists('get_gravatar_url')) {
    function get_gravatar_url($email, $size = 'small')
    {
        $sizes = config('image.sizes');

        $size = array_key_exists($size, $sizes) ? $sizes[$size] : $sizes['medium'];

        $email = md5(strtolower(trim($email)));

        return "https://www.gravatar.com/avatar/{$email}?s={$size['w']}&d=mm";
    }
}

if (! function_exists('get_sender_email')) {
    /**
     * Return shop title or the application title
     */
    function get_sender_email($shop = null)
    {
        if ($shop) {
            return config('shop_settings.default_sender_email_address') ??
                config('mail.from.address');
        }

        return config('mail.from.address');
    }
}

if (! function_exists('get_sender_name')) {
    /**
     * Return shop title or the application title
     */
    function get_sender_name($shop = null)
    {
        if ($shop) {
            return config('shop_settings.default_email_sender_name') ??
                config('mail.from.name');
        }

        return config('mail.from.name');
    }
}

if (! function_exists('get_address_str_from_request_data')) {
    function get_address_str_from_request_data($request)
    {
        $state = is_numeric($request->state_id) ? get_value_from($request->state_id, 'states', 'name') : $request->state_id;

        $str = [];
        $str[] = '<address>';
        $str[] = $request->address_title;
        $str[] = $request->address_line_1;
        $str[] = $request->address_line_2;
        $str[] = $request->city;
        $str[] = $state.' '.$request->zip_code;
        $str[] = is_numeric($request->country_id) ? get_value_from($request->country_id, 'countries', 'name') : $request->country_id;

        if ($request->phone) {
            $str[] = trans('app.phone').': '.e($request->phone);
        }
        $str[] = '</address>';

        return implode(',<br/>', array_filter($str));
    }
}

if (! function_exists('address_str_to_html')) {
    function address_str_to_html($address, $separator = '<br/>')
    {
        $addressStr = str_replace(',', $separator, $address);

        return '<address>'.$addressStr.'</address>';
    }
}

if (! function_exists('address_str_to_geocode_str')) {
    function address_str_to_geocode_str($address)
    {
        $t_arr = explode(',', $address);
        array_shift($t_arr); // Remove address time/name

        // Remove phone number from address
        if (preg_match('/^[0-9 +-]*$/', end($t_arr))) {
            array_pop($t_arr);
        }

        // build str string
        $str = trim(implode(',', array_filter($t_arr)));

        return str_replace(' ', '+', $str);
    }
}

/**
 * Get latitude and longitude of an address from Google API
 */
if (! function_exists('getGeocode')) {
    function getGeocode($address)
    {
        if (is_object($address)) {
            $address = $address->toGeocodeString();
        } elseif (is_numeric($address)) {
            $address = DB::table('addresses')->find($address);
            $address = $address->toGeocodeString();
        }

        $url = 'https://maps.google.com/maps/api/geocode/json?address='.$address.'&sensor=false';

        $result = [];

        // try to get geo codes
        if ($geocode = file_get_contents($url)) {
            $output = json_decode($geocode);

            if (count($output->results) && isset($output->results[0])) {
                if ($geo = $output->results[0]->geometry) {
                    $result['latitude'] = $geo->location->lat;
                    $result['longitude'] = $geo->location->lng;
                }
            }
        }

        return $result;
    }
}

if (! function_exists('getPaginationValue')) {
    function getPaginationValue()
    {
        $default = 10;

        if (Auth::check() && Auth::user()->isFromPlatform()) {
            $value = (int) (config('system_settings.pagination') ?? $default);
        } else {
            $value = (int) (config('shop_settings.pagination') ?? $default);
        }

        return $value > 0 ? $value : $default;
    }
}

if (! function_exists('getMinNumberOfRequiredImgsForInventory')) {
    /**
     * Return Min Number Of Required Imgs For Inventory to upload per item
     */
    function getMinNumberOfRequiredImgsForInventory()
    {
        return config('system_settings.min_number_of_inventory_imgs', 0);
    }
}

if (! function_exists('getMaxNumberOfImgsForInventory')) {
    /**
     * Return max_number_of_inventory_imgs allowed to upload per item
     */
    function getMaxNumberOfImgsForInventory()
    {
        return config('system_settings.max_number_of_inventory_imgs', 10);
    }
}

if (! function_exists('getAllowedMinImgSize')) {
    /**
     * Return min_img_size_limit_kb allowed to upload
     */
    function getAllowedMinImgSize()
    {
        return config('system_settings.min_img_size_limit_kb') ?? config('image.min_size', 0);
    }
}

if (! function_exists('getAllowedMaxImgSize')) {
    /**
     * Return max_img_size_limit_kb allowed to upload
     */
    function getAllowedMaxImgSize()
    {
        return config('system_settings.max_img_size_limit_kb') ?? config('image.max_size', 1024);
    }
}

if (! function_exists('storefront_panel_user')) {
    /**
     * Admin / merchant (web guard) session active while browsing the customer storefront.
     */
    function storefront_panel_user(): ?\App\Models\User
    {
        if (! Auth::guard('web')->check()) {
            return null;
        }

        $user = Auth::guard('web')->user();

        return $user instanceof \App\Models\User ? $user : null;
    }
}

if (! function_exists('is_panel_user_on_storefront')) {
    function is_panel_user_on_storefront(): bool
    {
        return storefront_panel_user() !== null;
    }
}

if (! function_exists('panel_user_storefront_role_label')) {
    function panel_user_storefront_role_label(): string
    {
        $user = storefront_panel_user();

        if (! $user) {
            return '';
        }

        if ($user->isFromMerchant()) {
            return 'Store';
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return 'Admin';
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return 'Admin';
        }

        if ($user->isFromPlatform()) {
            return 'Admin';
        }

        return 'Staff';
    }
}

if (! function_exists('panel_user_storefront_message')) {
    function panel_user_storefront_message(): string
    {
        $user = storefront_panel_user();

        if (! $user) {
            return '';
        }

        $role = panel_user_storefront_role_label();

        // Prefer shop name for store sessions — never expose numeric IDs.
        if ($user->isFromMerchant() && $user->shop) {
            $name = $user->shop->name ?: (method_exists($user, 'getName') ? $user->getName() : null);
        } else {
            $name = method_exists($user, 'getName') ? $user->getName() : null;
        }

        $name = $name ?: ($user->nice_name ?? $user->name ?? $user->email ?? $role);

        return trans('theme.notify.panel_user_logged_in', [
            'role' => $role,
            'name' => $name,
        ]);
    }
}

if (! function_exists('storefront_merchant_shop_url')) {
    /**
     * Storefront URL for the currently logged-in merchant's shop.
     */
    function storefront_merchant_shop_url(): ?string
    {
        $user = storefront_panel_user();

        if (! $user || ! $user->isFromMerchant() || ! $user->shop || blank($user->shop->slug)) {
            return null;
        }

        return get_shop_url($user->shop);
    }
}

if (! function_exists('resolve_attribute_type_id')) {
    /**
     * Map attribute name to an internal attribute_type_id (UI no longer asks for type).
     */
    function resolve_attribute_type_id(?string $name, ?int $fallback = null): int
    {
        $name = strtolower(trim((string) $name));

        if (str_contains($name, 'color') || str_contains($name, 'colour') || str_contains($name, 'pattern')) {
            return \App\Models\Attribute::TYPE_COLOR;
        }

        if (in_array($name, ['gender', 'condition', 'format'], true)) {
            return \App\Models\Attribute::TYPE_RADIO;
        }

        return $fallback ?: \App\Models\Attribute::TYPE_SELECT;
    }
}

if (! function_exists('ensure_shop_attribute_presets')) {
    /**
     * Ensure useful preset attributes exist for a shop (Colour, Size, Material, ...).
     */
    function ensure_shop_attribute_presets(?int $shopId = null): void
    {
        $shopId = $shopId ?: (Auth::check() ? Auth::user()->merchantId() : null);
        if (! $shopId) {
            return;
        }

        // Types must exist (internal FK).
        $types = [
            1 => 'Color/Pattern',
            2 => 'Radio',
            3 => 'Select',
        ];
        foreach ($types as $id => $type) {
            if (! \Illuminate\Support\Facades\DB::table('attribute_types')->where('id', $id)->exists()) {
                \Illuminate\Support\Facades\DB::table('attribute_types')->insert([
                    'id' => $id,
                    'type' => $type,
                ]);
            }
        }

        $presets = [
            'Colour' => [
                'type' => \App\Models\Attribute::TYPE_COLOR,
                'order' => 1,
                'values' => [
                    ['Black', '#000000'], ['White', '#ffffff'], ['Red', '#e53935'],
                    ['Blue', '#1e88e5'], ['Green', '#43a047'], ['Yellow', '#fdd835'],
                    ['Pink', '#ec407a'], ['Grey', '#9e9e9e'], ['Brown', '#6d4c41'], ['Orange', '#fb8c00'],
                ],
            ],
            'Size' => [
                'type' => \App\Models\Attribute::TYPE_SELECT,
                'order' => 2,
                'values' => [['XS'], ['S'], ['M'], ['L'], ['XL'], ['XXL'], ['3XL']],
            ],
            'Material' => [
                'type' => \App\Models\Attribute::TYPE_SELECT,
                'order' => 3,
                'values' => [['Cotton'], ['Polyester'], ['Leather'], ['Wool'], ['Silk'], ['Denim'], ['Metal'], ['Plastic']],
            ],
            'Style' => [
                'type' => \App\Models\Attribute::TYPE_SELECT,
                'order' => 4,
                'values' => [['Casual'], ['Formal'], ['Sport'], ['Classic'], ['Modern']],
            ],
            'Gender' => [
                'type' => \App\Models\Attribute::TYPE_RADIO,
                'order' => 5,
                'values' => [['Men'], ['Women'], ['Unisex'], ['Kids']],
            ],
            'Storage' => [
                'type' => \App\Models\Attribute::TYPE_SELECT,
                'order' => 6,
                'values' => [['32GB'], ['64GB'], ['128GB'], ['256GB'], ['512GB'], ['1TB']],
            ],
        ];

        foreach ($presets as $name => $meta) {
            $attribute = \App\Models\Attribute::withTrashed()
                ->where('shop_id', $shopId)
                ->where('name', $name)
                ->first();

            if (! $attribute) {
                $attribute = \App\Models\Attribute::create([
                    'shop_id' => $shopId,
                    'name' => $name,
                    'attribute_type_id' => $meta['type'],
                    'order' => $meta['order'],
                ]);
            } elseif (method_exists($attribute, 'trashed') && $attribute->trashed()) {
                $attribute->restore();
            }

            foreach ($meta['values'] as $i => $row) {
                $value = $row[0];
                $color = $row[1] ?? null;
                $exists = \App\Models\AttributeValue::withTrashed()
                    ->where('attribute_id', $attribute->id)
                    ->where('value', $value)
                    ->exists();
                if ($exists) {
                    continue;
                }
                \App\Models\AttributeValue::create([
                    'shop_id' => $shopId,
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                    'color' => $color,
                    'order' => $i + 1,
                ]);
            }
        }
    }
}

if (! function_exists('sync_product_category_attributes')) {
    /**
     * Attach selected attributes to the product's categories so variants can use them.
     *
     * @param  array<int>|null  $categoryIds
     * @param  array<int>|null  $attributeIds
     */
    function sync_product_category_attributes(?array $categoryIds, ?array $attributeIds): void
    {
        if (empty($categoryIds) || empty($attributeIds)) {
            return;
        }

        $attributeIds = array_values(array_unique(array_map('intval', $attributeIds)));

        foreach ($categoryIds as $categoryId) {
            $category = \App\Models\Category::find($categoryId);
            if ($category) {
                $category->attrsList()->syncWithoutDetaching($attributeIds);
            }
        }
    }
}

if (! function_exists('allow_checkout')) {
    function allow_checkout()
    {
        // Admin / Store panel sessions cannot place customer orders on the storefront.
        if (is_panel_user_on_storefront()) {
            return false;
        }

        if (\App\Models\SystemConfig::CustomerNeedsApproval() && Auth::guard('customer')->user() instanceof Customer) {
            return Auth::guard('customer')->user()->isApproved();
        }

        return config('system_settings.allow_guest_checkout') || Auth::guard('customer')->check();
    }
}

if (! function_exists('customerHasGroupPricing')) {
    /**
     * Check if the current user/customer is part of any buyer group
     *
     * @return bool
     */
    function customerHasGroupPricing()
    {
        return Auth::guard('customer')->check() &&
            Auth::guard('customer')->user()->buyer_group_id &&
            is_incevio_package_loaded('buyerGroup');
    }
}

if (! function_exists('highlightWords')) {
    function highlightWords($content = null, $words = null)
    {
        if (is_null($content) || is_null($words)) {
            return $content;
        }

        if (is_array($words)) {
            foreach ($words as $word) {
                $content = str_ireplace($word, '<mark>'.$word.'</mark>', $content);
            }

            return $content;
        }

        return str_ireplace($words, '<mark>'.$words.'</mark>', $content);
    }
}

if (! function_exists('clear_encoding_str')) {
    function clear_encoding_str($value)
    {
        if (is_array($value)) {
            $clean = [];

            foreach ($value as $key => $val) {
                $clean[$key] = mb_convert_encoding($val, 'UTF-8', 'UTF-8');
            }

            return $clean;
        }

        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}

if (! function_exists('get_qualified_model')) {
    function get_qualified_model($class_name = '')
    {
        return 'App\\Models\\'.Str::singular(Str::studly($class_name));
    }
}

if (! function_exists('should_seed_demo_images')) {
    // This function determines when the demo images should seeded
    function should_seed_demo_images()
    {
        return config('filesystems.default') != 'google' && File::isDirectory(public_path('images/demo'));
    }
}

if (! function_exists('temp_storage_dir')) {
    function temp_storage_dir($dir = '')
    {
        return Str::finish(public_path("temp/{$dir}"), '/');
    }
}

if (! function_exists('attachment_storage_dir')) {
    function attachment_storage_dir($dir = '')
    {
        return 'attachments';
    }
}

if (! function_exists('product_video_storage_dir')) {
    function product_video_storage_dir(): string
    {
        return 'videos/products';
    }
}

if (! function_exists('get_product_video_url')) {
    /**
     * Public URL for a product video path (served via image route as raw file).
     */
    function get_product_video_url(?string $path): ?string
    {
        if (! $path || ! \Illuminate\Support\Facades\Storage::exists($path)) {
            return null;
        }

        return url('image/'.$path);
    }
}

if (! function_exists('image_storage_dir')) {
    function image_storage_dir()
    {
        return config('image.dir');
    }
}

if (! function_exists('sys_image_path')) {
    function sys_image_path($dir = '')
    {
        return Str::finish("images/{$dir}", '/');
    }
}

if (! function_exists('image_storage_path')) {
    function image_storage_path($path = null)
    {
        $path = image_storage_dir().DIRECTORY_SEPARATOR.$path;

        return Str::finish($path, '/');
    }
}

if (! function_exists('image_cache_path')) {
    function image_cache_path($path = null)
    {
        $path = config('image.cache_dir').DIRECTORY_SEPARATOR.$path;

        return Str::finish($path, '/');
    }
}

if (! function_exists('default_brand_logo_url')) {
    function default_brand_logo_url($size = 'logo')
    {
        return asset('images/brand/logo.svg');
    }
}

if (! function_exists('default_brand_icon_url')) {
    function default_brand_icon_url($size = 'thumbnail')
    {
        return asset('images/brand/icon.svg');
    }
}

if (! function_exists('google_maps_api_key')) {
    function google_maps_api_key(): ?string
    {
        return config('services.google.place_api_key')
            ?: config('hyperlocal.google_maps_api_key')
            ?: null;
    }
}

if (! function_exists('get_storage_file_url')) {
    function get_storage_file_url($path = null, $size = '')
    {
        if (! $path || ! \Illuminate\Support\Facades\Storage::exists($path)) {
            return get_placeholder_img($size);
        }

        if (is_null($size) || Str::endsWith($path, 'svg')) {
            return url("image/{$path}");
        }

        return url("image/{$path}?p={$size}");
    }
}

if (! function_exists('php_gd_can_read_webp')) {
    /**
     * True only if GD can decode WEBP (DomPDF relies on GD for raster images).
     */
    function php_gd_can_read_webp(): bool
    {
        if (! function_exists('imagecreatefromwebp')) {
            return false;
        }

        $gd = @gd_info();

        return is_array($gd) && ! empty($gd['WebP Support']);
    }
}

if (! function_exists('pdf_dompdf_transparent_pixel_data_uri')) {
    /**
     * 1×1 transparent GIF — safe for DomPDF when WEBP cannot be decoded.
     */
    function pdf_dompdf_transparent_pixel_data_uri(): string
    {
        return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }
}

if (! function_exists('pdf_compatible_local_image_path')) {
    /**
     * Absolute filesystem path for DomPDF when GD can read the format (or Imagick converted WEBP → PNG).
     * For WEBP without GD/Imagick support, returns null — use pdf_dompdf_storage_image_src() in Blade.
     *
     * @param  string|null  $storageRelativePath  Relative to the default disk (e.g. public/images/foo.webp)
     */
    function pdf_compatible_local_image_path(?string $storageRelativePath): ?string
    {
        if ($storageRelativePath === null || $storageRelativePath === '') {
            return null;
        }

        if (! Storage::exists($storageRelativePath)) {
            return null;
        }

        $absolute = Storage::path($storageRelativePath);
        if (! is_readable($absolute)) {
            return null;
        }

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if ($ext !== 'webp') {
            return $absolute;
        }

        if (php_gd_can_read_webp()) {
            return $absolute;
        }

        if (extension_loaded('imagick')) {
            try {
                $imagick = new \Imagick($absolute);
                $imagick->setImageFormat('png');
                $pngPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cafremarket_pdf_img_'.uniqid('', true).'.png';
                $imagick->writeImage($pngPath);
                $imagick->clear();
                $imagick->destroy();

                return is_readable($pngPath) ? $pngPath : null;
            } catch (\Throwable $e) {
                report($e);

                return null;
            }
        }

        return null;
    }
}

if (! function_exists('pdf_dompdf_storage_image_src')) {
    /**
     * Value for &lt;img src="..."&gt; in PDF templates — never points DomPDF at unreadable WEBP.
     */
    function pdf_dompdf_storage_image_src(?string $storageRelativePath): string
    {
        $path = pdf_compatible_local_image_path($storageRelativePath);

        return $path !== null ? $path : pdf_dompdf_transparent_pixel_data_uri();
    }
}

if (! function_exists('pdf_invoice_qr_data_uri')) {
    /**
     * Build a PNG QR code (data URI) for DomPDF, with an optional centered logo.
     * Uses BaconQrCode + GD so Imagick is not required.
     *
     * Prefer pdf_invoice_qr_image_src() for DomPDF — data URIs are often blocked.
     */
    function pdf_invoice_qr_data_uri(string $payload, ?string $logoAbsolutePath = null, int $pixelSize = 160): string
    {
        $binary = pdf_invoice_qr_png_binary($payload, $logoAbsolutePath, $pixelSize);
        if ($binary === null) {
            return pdf_dompdf_transparent_pixel_data_uri();
        }

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}

if (! function_exists('pdf_invoice_qr_image_src')) {
    /**
     * Absolute filesystem path to a PNG QR for DomPDF &lt;img src&gt; (under storage/app).
     */
    function pdf_invoice_qr_image_src(string $payload, ?string $logoAbsolutePath = null, int $pixelSize = 160): string
    {
        $binary = pdf_invoice_qr_png_binary($payload, $logoAbsolutePath, $pixelSize);
        if ($binary === null) {
            return pdf_dompdf_transparent_pixel_data_uri();
        }

        $dir = storage_path('app'.DIRECTORY_SEPARATOR.'pdf_qr');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir.DIRECTORY_SEPARATOR.'qr_'.md5($payload.'|'.($logoAbsolutePath ?? '').'|'.$pixelSize).'.png';
        if (! is_file($file) || filesize($file) < 32) {
            @file_put_contents($file, $binary);
        }

        return is_readable($file) ? $file : pdf_dompdf_transparent_pixel_data_uri();
    }
}

if (! function_exists('pdf_invoice_qr_png_binary')) {
    /**
     * Raw PNG bytes for an invoice QR (optional centered logo).
     */
    function pdf_invoice_qr_png_binary(string $payload, ?string $logoAbsolutePath = null, int $pixelSize = 160): ?string
    {
        if ($payload === '') {
            return null;
        }

        try {
            $qr = \BaconQrCode\Encoder\Encoder::encode(
                $payload,
                \BaconQrCode\Common\ErrorCorrectionLevel::H()
            );
            $matrix = $qr->getMatrix();
            $modules = $matrix->getWidth();
            $quiet = 2;
            $scale = max(4, (int) floor($pixelSize / max(1, $modules + ($quiet * 2))));
            $dimension = ($modules + ($quiet * 2)) * $scale;

            $image = imagecreatetruecolor($dimension, $dimension);
            if ($image === false) {
                return null;
            }

            imagealphablending($image, true);
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            imagefilledrectangle($image, 0, 0, $dimension, $dimension, $white);

            for ($y = 0; $y < $modules; $y++) {
                for ($x = 0; $x < $modules; $x++) {
                    if ($matrix->get($x, $y) === 1) {
                        $x1 = ($x + $quiet) * $scale;
                        $y1 = ($y + $quiet) * $scale;
                        imagefilledrectangle(
                            $image,
                            $x1,
                            $y1,
                            $x1 + $scale - 1,
                            $y1 + $scale - 1,
                            $black
                        );
                    }
                }
            }

            if ($logoAbsolutePath && is_readable($logoAbsolutePath)) {
                $logo = pdf_gd_load_image($logoAbsolutePath);
                if ($logo) {
                    $logoBox = (int) round($dimension * 0.22);
                    $logoBox = max(28, min($logoBox, (int) ($dimension * 0.28)));
                    $pad = 3;
                    $box = $logoBox + ($pad * 2);
                    $ox = (int) (($dimension - $box) / 2);
                    $oy = $ox;
                    imagefilledrectangle($image, $ox, $oy, $ox + $box - 1, $oy + $box - 1, $white);

                    $lw = imagesx($logo);
                    $lh = imagesy($logo);
                    $ratio = min($logoBox / max(1, $lw), $logoBox / max(1, $lh));
                    $dw = max(1, (int) round($lw * $ratio));
                    $dh = max(1, (int) round($lh * $ratio));
                    $dx = $ox + (int) (($box - $dw) / 2);
                    $dy = $oy + (int) (($box - $dh) / 2);
                    imagecopyresampled($image, $logo, $dx, $dy, 0, 0, $dw, $dh, $lw, $lh);
                    imagedestroy($logo);
                }
            }

            ob_start();
            imagepng($image);
            $binary = ob_get_clean();
            imagedestroy($image);

            return (is_string($binary) && $binary !== '') ? $binary : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}

if (! function_exists('pdf_gd_load_image')) {
    /**
     * Load a local image into a GD resource for PDF compositing.
     *
     * @return \GdImage|resource|null
     */
    function pdf_gd_load_image(string $absolutePath)
    {
        if (! is_readable($absolutePath)) {
            return null;
        }

        $info = @getimagesize($absolutePath);
        if (! is_array($info) || empty($info[2])) {
            return null;
        }

        return match ((int) $info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($absolutePath) ?: null,
            IMAGETYPE_GIF => @imagecreatefromgif($absolutePath) ?: null,
            IMAGETYPE_WEBP => (function_exists('php_gd_can_read_webp') && php_gd_can_read_webp())
                ? (@imagecreatefromwebp($absolutePath) ?: null)
                : null,
            default => null,
        };
    }
}

if (! function_exists('pdf_platform_logo_path')) {
    /**
     * Absolute filesystem path to the platform logo for DomPDF (null if unavailable).
     */
    function pdf_platform_logo_path(): ?string
    {
        $system = \App\Models\System::orderBy('id', 'asc')->first();
        $path = optional($system?->logoImage)->path
            ?? optional($system?->iconImage)->path;

        return pdf_compatible_local_image_path($path);
    }
}

if (! function_exists('get_placeholder_img')) {
    function get_placeholder_img($size = 'small', $txt = null)
    {
        return asset('images/brand/placeholder.svg');
    }
}

if (! function_exists('get_product_img_src')) {
    /**
     * Retrieve inventory or product image
     *
     * @param  int|Inventory  $item
     * @param  string  $size
     * @param  string  $type
     * @return string
     */
    function get_product_img_src($item = null, $size = 'medium', $type = 'primary')
    {
        if (! $item) {
            return asset('images/brand/placeholder.svg');
        }

        if (is_numeric($item) && ! ($item instanceof Inventory)) {
            $item = Inventory::findOrFail($item);
        }

        $images_count = $item->images->count();

        // If the listing has no gallery images, use the product catalog images
        if (! $images_count && $item instanceof Inventory) {
            $product = $item->product;
            if ($product) {
                if ($type === 'alt') {
                    $gallery = $product->images;
                    if ($gallery->count() > 1) {
                        return url("image/{$gallery->get(1)->path}?p={$size}");
                    }
                }

                if ($product->featureImage?->path) {
                    return url("image/{$product->featureImage->path}?p={$size}");
                }

                if ($product->image?->path) {
                    return url("image/{$product->image->path}?p={$size}");
                }

                $item = $product;
                $images_count = $item->images->count();
            }
        }

        if ($images_count) {
            if ($type == 'alt' && $images_count > 1) {
                $imgs = $item->images->toArray();
                $path = $imgs[1]['path'];
            } else {
                $path = $item->images->first()->path;
            }

            return url("image/{$path}?p={$size}");
        }

        return asset('images/placeholders/no_img.png');
    }
}

if (! function_exists('get_inventory_img_src')) {
    function get_inventory_img_src($item, $size = 'medium')
    {
        if ($item->image?->path) {
            return get_storage_file_url($item->image->path, $size);
        }

        if ($item->images?->isNotEmpty()) {
            return get_storage_file_url($item->images->first()->path, $size);
        }

        if ($item->product_id) {
            $product = $item->relationLoaded('product') ? $item->product : $item->product()->first();
            if ($product) {
                if ($product->featureImage?->path) {
                    return get_storage_file_url($product->featureImage->path, $size);
                }

                if ($product->image?->path) {
                    return get_storage_file_url($product->image->path, $size);
                }

                if ($product->images?->isNotEmpty()) {
                    return get_storage_file_url($product->images->first()->path, $size);
                }
            }
        }

        return asset('images/placeholders/no_img.png');
    }
}

if (! function_exists('get_catalog_featured_img_src')) {
    function get_catalog_featured_img_src($product, $size = 'small')
    {
        if (is_int($product) && ! ($product instanceof Product)) {
            $product = Product::findOrFail($product);
        }

        if ($product->featureImage) {
            return get_storage_file_url($product->featureImage->path, $size);
        }

        if ($product->image) {
            return get_storage_file_url($product->image->path, $size);
        }

        return asset('images/placeholders/no_img.png');
    }
}

if (! function_exists('get_cover_img_src')) {
    function get_cover_img_src($model, $type = 'category', $size = 'cover')
    {
        if (isset($model->coverImage->path) && Storage::exists($model->coverImage->path)) {
            return get_storage_file_url($model->coverImage->path, $size);
        }

        return asset('images/placeholders/'.$type.'_cover.jpg');
    }
}

if (! function_exists('default_shop_logo_url')) {
    function default_shop_logo_url($size = 'small')
    {
        return asset('images/brand/default-store.svg');
    }
}

if (! function_exists('shop_has_custom_logo')) {
    function shop_has_custom_logo($model): bool
    {
        if (! is_object($model)) {
            return false;
        }

        $path = optional($model->logoImage)->path;

        return filled($path) && \Illuminate\Support\Facades\Storage::exists($path);
    }
}

if (! function_exists('system_has_custom_logo')) {
    function system_has_custom_logo(): bool
    {
        $system = System::orderBy('id', 'asc')->first();
        $path = optional($system?->logoImage)->path;

        return filled($path) && \Illuminate\Support\Facades\Storage::exists($path);
    }
}

if (! function_exists('get_platform_brand_label')) {
    function get_platform_brand_label(): string
    {
        $name = trim((string) (config('system_settings.name') ?? config('app.name') ?? ''));

        return $name !== '' ? $name : 'cafremarket';
    }
}

if (! function_exists('get_logo_url')) {
    function get_logo_url($model, $size = 'small')
    {
        if ($model == 'system') {
            return Cache::rememberForever('system_logo_img_'.$size, function () use ($size) {
                $system = System::orderBy('id', 'asc')->first();
                $path = optional($system?->logoImage)->path;

                if ($path && Storage::exists($path)) {
                    return url("image/{$path}?p={$size}");
                }

                return default_brand_logo_url($size);
            });
        }

        $path = is_object($model) ? optional($model->logoImage)->path : null;

        if (shop_has_custom_logo($model)) {
            return get_storage_file_url($path, $size);
        }

        return default_shop_logo_url($size);
    }
}

if (! function_exists('get_icon_url')) {
    function get_icon_url($model, $size = 'thumbnail')
    {
        if ($model == 'system') {
            return Cache::rememberForever('favicon_img', function () use ($size) {
                $system = System::orderBy('id', 'asc')->first();
                $path = optional($system?->iconImage)->path;

                if ($path && Storage::exists($path)) {
                    return url("image/{$path}?p={$size}");
                }

                return default_brand_icon_url($size);
            });
        }

        if (is_object($model) && $model->iconImage && Storage::exists($model->iconImage->path)) {
            return get_storage_file_url($model->iconImage->path, $size);
        }

        return default_brand_icon_url($size);
    }
}

if (! function_exists('verifyUniqueSlug')) {
    function verifyUniqueSlug($slug, $table, $field = 'slug', $json = true)
    {
        $query = DB::table($table)->select($field)->where($field, $slug);

        if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $taken = (bool) $query->first();

        if (! $taken && in_array($table, ['products', 'inventories'], true)) {
            $taken = listing_slug_is_taken($slug);
        }

        if ($taken) {
            return $json ? response()->json('false') : false;
        }

        return $json ? response()->json('true') : true;
    }
}

if (! function_exists('generate_unique_shop_slug')) {
    /**
     * Build a unique shop slug for the storefront URL (/shop/{slug}).
     */
    function generate_unique_shop_slug(string $name, ?int $exceptShopId = null): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;
        $counter = 1;

        while (true) {
            $query = DB::table('shops')->where('slug', $slug)->whereNull('deleted_at');

            if ($exceptShopId) {
                $query->where('id', '!=', $exceptShopId);
            }

            if (! $query->exists()) {
                return $slug;
            }

            $slug = $base.'-'.$counter;
            $counter++;
        }
    }
}

if (! function_exists('listing_slug_is_taken')) {
    /**
     * Product and inventory slugs share the same public URL namespace.
     */
    function listing_slug_is_taken(string $slug, ?int $exceptProductId = null, ?int $exceptInventoryId = null): bool
    {
        $products = DB::table('products')->where('slug', $slug);
        if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'deleted_at')) {
            $products->whereNull('deleted_at');
        }
        if ($exceptProductId) {
            $products->where('id', '!=', $exceptProductId);
        }
        if ($products->exists()) {
            return true;
        }

        $inventories = DB::table('inventories')->where('slug', $slug);
        if (\Illuminate\Support\Facades\Schema::hasColumn('inventories', 'deleted_at')) {
            $inventories->whereNull('deleted_at');
        }
        if ($exceptInventoryId) {
            $inventories->where('id', '!=', $exceptInventoryId);
        }

        return $inventories->exists();
    }
}

if (! function_exists('generate_unique_listing_slug')) {
    /**
     * Unique catalog slug. On collision append -2, -3, … so the product can still be saved.
     */
    function generate_unique_listing_slug(string $desired, ?int $exceptProductId = null, ?int $exceptInventoryId = null): string
    {
        $base = Str::slug($desired) ?: 'product';
        $reserved = ['products', 'reviews', 'category', 'product', 'offers', 'quickView'];
        if (in_array($base, $reserved, true)) {
            $base .= '-item';
        }

        $slug = $base;
        $counter = 2;

        while (listing_slug_is_taken($slug, $exceptProductId, $exceptInventoryId)) {
            $slug = $base.'-'.$counter;
            $counter++;

            if ($counter > 500) {
                $slug = $base.'-'.Str::lower(Str::random(5));
                break;
            }
        }

        return $slug;
    }
}

if (! function_exists('storefront_shop_slug')) {
    function storefront_shop_slug($item): ?string
    {
        if (is_object($item) && isset($item->shop) && $item->shop) {
            return $item->shop->slug;
        }

        $shopId = is_object($item) ? ($item->shop_id ?? optional($item->product)->shop_id) : null;
        if (! $shopId) {
            return null;
        }

        static $slugs = [];
        if (! array_key_exists($shopId, $slugs)) {
            $slugs[$shopId] = DB::table('shops')->where('id', $shopId)->value('slug');
        }

        return $slugs[$shopId];
    }
}

if (! function_exists('storefront_product_url')) {
    /**
     * Public product URL: /shop/{store-slug}/{product-slug}
     */
    function storefront_product_url($item, string $suffix = ''): string
    {
        if (is_string($item)) {
            $found = \App\Models\Inventory::query()->where('slug', $item)->first(['id', 'slug', 'shop_id']);
            if ($found) {
                return storefront_product_url($found, $suffix);
            }

            return url('product/'.$item.$suffix);
        }

        $shopSlug = storefront_shop_slug($item);
        $productSlug = $item->slug ?? '';

        if (! $shopSlug || $productSlug === '') {
            return url('product/'.$productSlug.$suffix);
        }

        return url('shop/'.$shopSlug.'/'.$productSlug.$suffix);
    }
}

if (! function_exists('storefront_product_quickview_url')) {
    function storefront_product_quickview_url($item): string
    {
        return storefront_product_url($item, '/quickView');
    }
}

if (! function_exists('storefront_product_offers_url')) {
    function storefront_product_offers_url($item): string
    {
        $shopSlug = storefront_shop_slug($item);
        $productSlug = is_object($item)
            ? (optional($item->product)->slug ?? $item->slug)
            : $item;

        if (! $shopSlug || ! $productSlug) {
            return url('product/'.$productSlug.'/offers');
        }

        return url('shop/'.$shopSlug.'/'.$productSlug.'/offers');
    }
}

if (! function_exists('convertToSlugString')) {
    function convertToSlugString($str, $salt = null, $separator = '-')
    {
        if ($salt) {
            return Str::slug($str, $separator).$separator.Str::slug($salt, $separator);
        }

        return Str::slug($str, $separator);
    }
}

if (! function_exists('generateCouponCode')) {
    function generateCouponCode()
    {
        $unique = true;
        $size = config('system_settings.coupon_code_size');

        do {
            $code = generateUniqueSrt($size);

            $check = DB::table('coupons')->where('code', $code)->first();

            if ($check) {
                $unique = false;
            }
        } while (! $unique);

        return $code;
    }
}

if (! function_exists('generatePinCode')) {
    function generatePinCode()
    {
        $unique = true;
        $size = config('system_settings.gift_card_pin_size');

        do {
            $code = generateUniqueSrt($size);

            $check = DB::table('gift_cards')->where('pin_code', $code)->first();

            if ($check) {
                $unique = false;
            }
        } while (! $unique);

        return $code;
    }
}

if (! function_exists('generateSerialNumber')) {
    function generateSerialNumber()
    {
        $unique = true;
        $size = config('system_settings.gift_card_serial_number_size');

        do {
            $code = generateUniqueSrt($size);

            $check = DB::table('gift_cards')->where('serial_number', $code)->first();

            if ($check) {
                $unique = false;
            }
        } while (! $unique);

        return $code;
    }
}

if (! function_exists('generateUniqueSrt')) {
    /**
     * Generate random alfa numeric str.
     *
     * @param  string  $dob  date of birth
     * @return string
     */
    function generateUniqueSrt($size = 8)
    {
        $characters = implode(range('A', 'Z')).implode(range(0, 9));
        $uniqueStr = '';
        for ($i = 0; $i < $size; $i++) {
            $uniqueStr .= $characters[mt_rand(0, strlen($characters) - 1)];
        }

        return $uniqueStr;
    }
}

if (! function_exists('get_age')) {
    /**
     * Get age of user/customer from date of birth.
     *
     * @param  string  $dob  date of birth
     * @return string
     */
    function get_age($dob)
    {
        return date_diff(date_create($dob), date_create('today'))->y.' years old';
    }
}

if (! function_exists('get_readable_time_from_seconds')) {
    function get_readable_time_from_seconds($seconds = null)
    {
        if (! $seconds) {
            return '';
        }

        $s = $seconds % 60;
        $m = floor(($seconds % 3600) / 60);
        $h = floor(($seconds % 86400) / 3600);
        $d = floor(($seconds % 2592000) / 86400);
        $M = floor($seconds / 2592000);

        $time = '';
        if ($M > 0) {
            $time .= $M.' '.trans_choice('app.months', $M).', ';
        }

        if ($d > 0) {
            $time .= $d.' '.trans_choice('app.days', $d).', ';
        }

        if ($h > 0) {
            $time .= $h.' '.trans_choice('app.hours', $h).', ';
        }

        if ($m > 0) {
            $time .= $m.' '.trans_choice('app.minutes', $m).', ';
        }

        if ($s > 0) {
            $time .= $s.' '.trans_choice('app.seconds', $s).', ';
        }

        return substr($time, 0, -2);
    }
}

if (! function_exists('get_formated_file_size')) {
    /**
     * Get the formated file size.
     *
     * @param  int  $bytes
     * @param  int  $precision
     * @return string formated size string
     */
    function get_formated_file_size($bytes = 0, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}

if (! function_exists('get_customer_email_from_order')) {
    function get_customer_email_from_order($order)
    {
        if (! $order instanceof Order) {
            $order = Order::find($order);
        }

        if ($order->customer->email) {
            return $order->customer->email;
        }

        return $order->email;
    }
}

if (! function_exists('get_formated_customer_str')) {
    /**
     * Get the formated customer string.
     *
     * @param  object|array  $customer
     * @return string formated customer string
     */
    function get_formated_customer_str($customer)
    {
        if (is_array($customer)) {
            return $customer['nice_name'].' | '.$customer['name'].' | '.$customer['email'];
        }

        return $customer->nice_name.' | '.$customer->name.' | '.$customer->email;
    }
}

if (! function_exists('get_formated_gender')) {
    /**
     * Get the formated gender string.
     *
     * @param  string  $sex
     * @return string formated gender to display
     */
    function get_formated_gender($sex, $show_icon = true)
    {
        if (! $show_icon) {
            return trans($sex);
        }

        $icon = '';
        if ($sex == 'Male' || $sex == 'app.male') {
            $icon = "<i class='fa fa-mars'></i> ";
        } elseif ($sex == 'Female' || $sex == 'app.female') {
            $icon = "<i class='fa fa-venus'></i> ";
        }

        return $icon.trans($sex);
    }
}

if (! function_exists('get_cent_from_dollar')) {
    /**
     * Get cent from decimal amount value.
     *
     * @param  float  $value
     * @return int
     */
    function get_cent_from_dollar($value = 0)
    {
        $decimals = 2;

        if (is_non_decimal_currency()) {
            $decimals = 0;
        }

        $value = number_format($value, $decimals, '.', '');

        return intval($value * 100);
    }
}

if (! function_exists('is_non_decimal_currency')) {
    /**
     * Check if the given currency is a non decimal.
     *
     * @param  string  $iso  value of currencies ISO code
     * @return bool
     */
    function is_non_decimal_currency($iso = null)
    {
        if (! $iso) {
            $iso = is_incevio_package_loaded('dynamic-currency') ? get_dynamic_currency_attr('iso_code') : get_system_currency();
        }

        return in_array($iso, config('system.non_decimal_currencies'));
    }
}

if (! function_exists('get_dollar_from_cent')) {
    /**
     * Get dollar from cent decimal value.
     *
     * @param  float  $value
     * @return string
     */
    function get_dollar_from_cent($value = 0)
    {
        if (! is_int($value)) {
            $value = intval($value);
        }

        $value = $value / 100;

        return number_format($value, 2, config('system_settings.currency.decimal_mark', '.'), '');
    }
}

if (! function_exists('format_to_number')) {
    /**
     * Format the input data with decimal places
     *
     * Defaults to 2 decimal places
     *
     * @param  int  $decimals
     * @return null|string
     */
    function format_price_for_paypal($value, $decimals = 2)
    {
        if (is_non_decimal_currency()) {
            $decimals = 0;
        }

        return number_format($value, $decimals, '.', '');
    }
}

if (! function_exists('get_formated_decimal')) {
    function get_formated_decimal($value = 0, $trim = true, $decimal = 0)
    {
        if (! $decimal) {
            // $decimal = $decimal == 0 ? 0 : config('system_settings.decimals', 2);
            $decimal = 0;
        }

        $decimal_mark = config('system_settings.currency.decimal_mark', '.');

        $value = number_format($value, $decimal, $decimal_mark, config('system_settings.currency.thousands_separator', ','));

        if ($trim) {
            $arr = explode($decimal_mark, $value);
            if (count($arr) == 2) {
                $temp = rtrim($arr[1], '0');
                $value = $temp ? $arr[0].$decimal_mark.$temp : $arr[0];
            }
        }

        return $value;
    }
}

if (! function_exists('remaining_days_until')) {
    /**
     * Whole days remaining until a date (rounds up partial days).
     */
    function remaining_days_until($date): int
    {
        if (! $date) {
            return 0;
        }

        $target = $date instanceof \Carbon\CarbonInterface
            ? $date
            : \Carbon\Carbon::parse($date);

        if ($target->isPast()) {
            return 0;
        }

        return (int) max(0, ceil(now()->diffInHours($target, false) / 24));
    }
}

if (! function_exists('format_distance_km')) {
    /**
     * Format a distance in kilometres for display (meters when very close).
     */
    function format_distance_km($km): string
    {
        $km = (float) $km;

        if ($km < 0.01) {
            if ($km <= 0) {
                return trans('theme.distance_at_location');
            }

            return max(1, (int) round($km * 1000)).' m';
        }

        return number_format($km, 2).' km';
    }
}

if (! function_exists('get_formated_price_value')) {
    function get_formated_price_value($value = 0)
    {
        if (is_incevio_package_loaded('dynamic-currency')) {
            $value = get_dynamic_currency_value($value);
        }

        return $value;
    }
}

if (! function_exists('get_formated_price_array')) {
    function get_formated_price_array($values = [], $decimal = null)
    {
        if (is_non_decimal_currency()) {
            $decimal = 0;
        }

        if ($decimal && is_non_decimal_currency()) {
            $decimal = null;
        }

        $formattedPrices = array_map(function ($value) use ($decimal) {
            if ($value < 0) {
                $value = get_formated_decimal($value * -1, $decimal ? false : true, $decimal);

                return '-'.get_currency_prefix().$value.get_currency_suffix();
            }

            $value = get_formated_decimal($value, $decimal ? false : true, $decimal);

            return $value;
        }, $values);

        return $formattedPrices;
    }
}

if (! function_exists('get_formated_currency')) {
    function get_formated_currency($value = 0, $decimal = null, $currency_id = null)
    {
        if (is_incevio_package_loaded('dynamic-currency')) {
            $iso_code = get_dynamic_currency_attr('iso_code', $currency_id);

            $value = get_dynamic_currency_value($value, $currency_id);
        } else {
            $iso_code = get_system_currency();
        }

        $prefix = get_currency_prefix($currency_id);
        $suffix = get_currency_suffix($currency_id);

        if (is_non_decimal_currency($iso_code)) {
            $decimal = 0;
        }

        if ($value < 0) {
            $value = get_formated_decimal($value * -1, $decimal ? false : true, $decimal);

            return '-'.$prefix.$value.$suffix;
        } else {
            $value = get_formated_decimal($value, $decimal ? false : true, $decimal);
        }

        return $prefix.$value.$suffix;
    }
}

if (! function_exists('get_formated_value')) {
    function get_formated_value($amount, $currency_id = null)
    {
        if (is_incevio_package_loaded('dynamic-currency')) {
            return get_dynamic_currency_value($amount, $currency_id);
        }

        return $amount;
    }
}

if (! function_exists('get_system_currency_value')) {
    function get_system_currency_value($amount, $currency_id = null)
    {
        if (is_incevio_package_loaded('dynamic-currency')) {
            return $amount / get_currency_details('exchange_rate', $currency_id);
        }

        return $amount;
    }
}

if (! function_exists('get_currency_prefix')) {
    function get_currency_prefix($currency_id = null)
    {
        $symbol = get_formated_currency_symbol($currency_id);

        if ($currency_id) {
            $currency = get_active_currencies()->find($currency_id);

            return $currency->symbol_first ? $symbol : '';
        }

        return config('system_settings.currency.symbol_first') ? $symbol : '';
    }
}

if (! function_exists('get_currency_suffix')) {
    function get_currency_suffix($currency_id = null)
    {
        $symbol = get_formated_currency_symbol($currency_id);

        if ($currency_id) {
            $currency = get_active_currencies()->find($currency_id);

            return $currency->symbol_first ? '' : $symbol;
        }

        return config('system_settings.currency.symbol_first') ? '' : $symbol;
    }
}

if (! function_exists('get_formated_currency_symbol')) {
    function get_formated_currency_symbol($currency_id = null)
    {
        if (config('system_settings.show_currency_symbol')) {
            $space = config('system_settings.show_space_after_symbol') ? ' ' : '';

            if (config('system_settings.currency.symbol_first')) {
                return get_currency_symbol($currency_id).($space);
            }

            return $space.get_currency_symbol($currency_id);
        }

        return '';
    }
}

if (! function_exists('get_currency_code')) {
    function get_currency_code()
    {
        return get_system_currency();
    }
}

if (! function_exists('get_formated_weight')) {
    function get_formated_weight($value = 0)
    {
        if (is_null($value)) {
            return null;
        }

        return get_formated_decimal($value, true, 0).' '.config('system_settings.weight_unit');
    }
}

if (! function_exists('get_formated_order_number')) {
    function get_formated_order_number($shop_id = null, $order_id = null)
    {
        $order_id = $order_id ?? str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

        if ($shop_id == null && Auth::guard('web')->check()) {
            $shop_id = Auth::user()->merchantId();
        }

        return getShopConfig($shop_id, 'order_number_prefix').$order_id.getShopConfig($shop_id, 'order_number_suffix');
    }
}

if (! function_exists('file_upload_max_size')) {
    // Returns a file size limit in bytes based on the PHP upload_max_filesize
    // and post_max_size
    function file_upload_max_size()
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = parse_size(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }

        return format_bytes($max_size);
    }
}

if (! function_exists('parse_size')) {
    function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }
}

if (! function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}

if (! function_exists('generate_ranges')) {
    /**
     * Return array of different ranges
     */
    function generate_ranges($min, $max, $number_of_ranges = 5)
    {
        $range = ($max - $min) / $number_of_ranges;
        $ranges = [];

        for ($i = 0; $i < $number_of_ranges; $i++) {
            $end = intval($min + $range);
            $ranges[$i]['lower'] = $min;
            $ranges[$i]['upper'] = $end;
            $min = $end;
        }

        return $ranges;
    }
}

if (! function_exists('get_percentage_of')) {
    function get_percentage_of($old_num, $new_num)
    {
        return get_formated_decimal((($old_num - $new_num) * 100) / $old_num);
    }
}

if (! function_exists('get_formated_shipping_range_of')) {
    /**
     * get_formated_shipping_range_of given shipping rate
     *
     * @param  $tax
     */
    function get_formated_shipping_range_of($rate)
    {
        if (! is_object($rate)) {
            $rate = DB::table('shipping_rates')->find($rate);
        }

        if ($rate->based_on == 'weight') {
            $lower = get_formated_weight($rate->minimum);
            $upper = get_formated_weight($rate->maximum);
        } else {
            $lower = get_formated_currency($rate->minimum);
            $upper = get_formated_currency($rate->maximum);
        }

        if (get_formated_decimal($rate->maximum) > 0) {
            return $lower.' - '.$upper;
        }

        return trans('app.and_up', ['value' => $lower]);
    }
}

// Shipping zone
if (! function_exists('get_countries_in_shipping_zone')) {
    function get_countries_in_shipping_zone($shipping_zone)
    {
        return Country::select('id', 'iso_code', 'name', 'active')
            ->whereIn('id', $shipping_zone->country_ids)
            ->withCount('states')->with('states:id,country_id')->get();
    }
}

// COUNTRY
/**
 * @deprecated there are no longer any calls to this function in the codebase. If confirmed that it is not needed this will be removed
 */
if (! function_exists('get_countries_name_with_states')) {
    function get_countries_name_with_states($ids)
    {
        if (is_array($ids)) {
            $countries = DB::table('countries')->select('iso_code', 'name', 'id')->whereIn('id', $ids)->get()->toArray();
            $all_states = DB::table('states')->whereIn('country_id', $ids)->pluck('country_id', 'id')->toArray();

            if (! empty($countries)) {
                $result = [];
                foreach ($countries as $country) {
                    $states = array_filter($all_states, function ($value) use ($country) {
                        return $value == $country->id;
                    });

                    $result[$country->id]['code'] = $country->iso_code;
                    $result[$country->id]['name'] = $country->name;
                    $result[$country->id]['states'] = array_keys($states);
                }

                return $result;
            }
        } else {
            $country_data = DB::table('countries')->select('iso_code', 'name')->find($ids);
        }
    }
}

if (! function_exists('get_flag_img_by_code')) {
    function get_flag_img_by_code($code, $plain = false)
    {
        $full_path = sys_image_path('flags').$code.'.png';

        if (! file_exists($full_path)) {
            $full_path = sys_image_path('flags').'default.gif';
        }

        if ($plain) {
            return asset($full_path);
        }

        return '<img src="'.asset($full_path).'" alt="'.$code.'"/>';
    }
}

if (! function_exists('get_formated_country_name')) {
    function get_formated_country_name($country, $code = null)
    {
        if (is_numeric($country)) {
            $country_data = DB::table('countries')->select('iso_code', 'name')->find($country);
            $country = $country_data->name;
            $code = $country_data->iso_code;
        }

        if ($code) {
            return get_flag_img_by_code($code).' <span class="indent5">'.$country.'</span>';
        }

        return $country;
    }
}

if (! function_exists('get_item_details_of')) {
    /**
     * Return the item details for the given inventory id
     *
     * @param  $tax
     */
    function get_item_details_of($id)
    {
        $item_details = DB::table('inventories')->select([
            'id',
            'sku',
            'description',
            'key_features',
            'condition',
            'condition_note',
            'shipping_weight',
            'min_order_quantity',
            'available_from',
        ])->where('id', $id)->first();

        return $item_details;
    }
}

if (! function_exists('get_shipping_zone_of')) {
    /**
     * Return the shipping zone id of given shop and country and state
     *
     * @param  $tax
     */
    function get_shipping_zone_of($shop, $country, $state = null)
    {
        $cant_ship = new stdClass; // A blank std class for null

        if (is_null($country)) {
            return $cant_ship;
        }

        // If the iso_2 code given instead of ID as country
        if (! is_numeric($country)) {
            $temp_country = DB::table('countries')->select('id', 'active')
                ->where('iso_code', $country)->first();

            $country = optional($temp_country)->id;
        }

        // If the iso_2 code given instead of ID as state
        if ($state && ! is_numeric($state)) {
            $temp_state = DB::table('states')->select('id', 'active')->where([
                ['iso_code', '=', $state],
                ['country_id', '=', $country],
            ])->first();

            $state = optional($temp_state)->id;
        }

        // Check if the marketplace is worldwide_business_area
        if (! config('system_settings.worldwide_business_area')) {
            // Need the country's active value to check the business area
            if (! isset($temp_country)) {
                $temp_country = DB::table('countries')->select('id', 'active')->where([
                    ['id', '=', $country],
                    ['active', '=', 1],
                ])->first();

                // Return back if the area is not in active business area
                if (! $temp_country || $temp_country->active != 1) {
                    return $cant_ship;
                }
            }

            // Need the state's active value to check the business area
            if ($state && ! isset($temp_state)) {
                $temp_state = DB::table('states')->select('id', 'active')->where([
                    ['id', '=', $state],
                    ['country_id', '=', $country],
                    ['active', '=', 1],
                ])->first();

                // Return back if the area is not in active business area
                if (! $temp_state || $temp_state->active != 1) {
                    return $cant_ship;
                }
            }
        }

        // Get number of states
        if ($state) {
            $state_counts = get_state_count_of($country);
        }

        $zones = DB::table('shipping_zones')
            ->select(['id', 'name', 'tax_id', 'country_ids', 'state_ids', 'rest_of_the_world'])
            ->where('shop_id', $shop)->where('active', 1)
            ->get();

        foreach ($zones as $zone) {
            // Check the the shop has a worldwide shipping zone
            if ($zone->rest_of_the_world == 1) {
                $worldwide = $zone;
            }

            $countries = unserialize($zone->country_ids);

            // Skip if the country is not found in this zone
            if (empty($countries) || ! in_array($country, $countries)) {
                continue;
            }

            // If the country has no state or the state is not given, then return the zone
            if (is_null($state) || $state_counts == 0) {
                return $zone;
            }

            $states = unserialize($zone->state_ids);

            // Skip if the country has states but the id not supplied
            if ($state_counts > 0 && is_null($state)) {
                continue;
            }

            if (in_array($state, $states)) {
                return $zone;
            }
        }

        return isset($worldwide) ? $worldwide : $cant_ship;
    }
}

if (! function_exists('get_state_count_of')) {
    /**
     * Return total number of states of given country
     *
     * @param  $tax
     */
    function get_state_count_of($country)
    {
        return DB::table('states')->where('country_id', $country)->count();
    }
}

if (! function_exists('get_states_of')) {
    /**
     * Get states ids of given countries.
     *
     * @param  int  $countries
     * @return array
     */
    function get_states_of($countries, $all = false)
    {
        $states = DB::table('states');

        if (is_array($countries)) {
            $states->whereIn('country_id', $countries);
        } else {
            $states->where('country_id', $countries);
        }

        if (! $all) {
            $states->where('active', 1);
        }

        return $states->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
    }
}

if (! function_exists('get_business_area_of')) {

    /**
     * Get states of given countries.
     *
     * @param  int|array  $countries
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function get_business_area_of($countries)
    {
        $states = State::select('id', 'name', 'iso_code', 'active')->orderBy('name', 'asc');

        if (is_array($countries)) {
            $states->whereIn('country_id', $countries);
        } else {
            $states->where('country_id', $countries);
        }

        return $states->get();
    }
}

if (! function_exists('get_id_of_model')) {
    /**
     * Return ID og the given table using where
     *
     * @param  string  $table  Name of the table
     * @param  string  $where  Name of the field
     * @param  string  $value  The value compare to
     * @return int
     */
    function get_id_of_model($table, $where, $value)
    {
        $temp = DB::table($table)->select('id')->where($where, $value)->first();

        return optional($temp)->id;
    }
}

if (! function_exists('get_default_geoip_country_iso')) {
    /**
     * Default country ISO code when GeoIP cannot resolve or is not in business areas (Mozambique).
     *
     * @return string
     */
    function get_default_geoip_country_iso()
    {
        return config('system_settings.default_geoip_country_iso', 'MZ');
    }
}

if (! function_exists('cart_item_count')) {
    /**
     * Get cart item count for customer.
     */
    function cart_item_count($customer_id = null)
    {
        // return Cache::rememberForever('cart_item_count', function() {
        if (! $customer_id) {
            $customer_id = Auth::guard('customer')->check() ? Auth::guard('customer')->user()->id : null;
        }

        $cart_list = DB::table('carts')
            ->join('cart_items', 'cart_items.cart_id', '=', 'carts.id')
            ->whereNull('customer_id')->whereNull('deleted_at')
            ->whereIn('id', cart_ids_from_cookie());

        if ($customer_id) {
            $cart_list = $cart_list->orWhere('customer_id', $customer_id);
        }

        return $cart_list->count();
        // });
    }
}

if (! function_exists('cart_ids_from_cookie')) {
    /**
     * Get cart ids from cookie.
     */
    function cart_ids_from_cookie()
    {
        return isset($_COOKIE['cart_ids']) ? explode(',', $_COOKIE['cart_ids']) : [];
    }
}

if (! function_exists('wishlist_item_count')) {
    /**
     * Get cart item count for customer.
     */
    function wishlist_item_count()
    {
        // return Cache::rememberForever('cart_item_count', function() {

        if (Auth::guard('customer')->check()) {

            $customer_id = Auth::guard('customer')->user()->id;

            return Wishlist::where('customer_id', $customer_id)->count();
        }
    }
}

if (! function_exists('getTaxRate')) {
    /**
     * Return tax rate for the given tax id
     *
     * @param  $tax
     */
    function getTaxRate($tax_id = null)
    {
        $tax_id = $tax_id ?? Tax::DEFAULT_TAX_ID;

        $rate = DB::table('taxes')->select('taxrate')->where('id', $tax_id)->first();

        return $rate ? $rate->taxrate : null;
    }
}

if (! function_exists('getShippingRates')) {
    /**
     * Get shipping rates list for the given zone or shop.
     */
    function getShippingRates($zone = null, $cart = null)
    {
        if ($zone) {
            $rates = ShippingRate::where('shipping_zone_id', $zone)
                ->with('carrier:id,name')->orderBy('rate', 'asc')->get();

            if (is_incevio_package_loaded('dynamic-currency')) {
                $rates = $rates->map(function ($item) {
                    $item->rate = get_dynamic_currency_value($item->rate);

                    return $item;
                });
            }

            if (! is_object($cart)) {
                $cart = Cart::find($cart); // When cart id is given instead of an cart object
            }

            // Insert shipping rate at live here
            if (isset($cart) && is_incevio_package_loaded('shippo')) {
                $shippo_service = new \Incevio\Package\Shippo\Services\ShippoShippingService($cart->shop_id);
                $rates = $shippo_service->insertLiveRatesToFrontend($cart, $rates);
            }

            return $rates;
        }

        // Return empty object if zone it is not given and not an user
        if (! Auth::guard('web')->check() || Auth::guard('web')->user()->merchantId()) {
            return new stdClass;
        }

        $rates = DB::table('shipping_zones')
            ->join('shipping_rates', 'shipping_zones.id', 'shipping_rates.shipping_zone_id')
            ->where('shipping_zones.shop_id', Auth::guard('web')->user()->merchantId())
            ->where('shipping_zones.active', 1)
            ->orderBy('shipping_rates.rate', 'asc')
            ->get();

        if (is_incevio_package_loaded('dynamic-currency')) {
            return $rates->map(function ($item) {
                $item->rate = get_dynamic_currency_value($item->rate);

                return $item;
            });
        }

        return $rates;
    }
}

if (! function_exists('getTrackingUrl')) {
    /**
     * Return tracking utl for the given carrier and tracking id
     */
    function getTrackingUrl($tracking_id = null, $carrier = null)
    {
        if (! $tracking_id || ! $carrier) {
            return '#';
        }

        $carrier = DB::table('carriers')->select('tracking_url')
            ->where('id', $carrier)
            ->first();

        if ($carrier) {
            return str_replace('@', $tracking_id, $carrier->tracking_url);
        }

        return '#';
    }
}

if (! function_exists('filterShippingOptions')) {
    /**
     * Return filtered shipping options for a given zone and price
     *
     * @param  $shop
     */
    function filterShippingOptions($zone, $price, $weight = null)
    {
        $results = DB::table('shipping_rates')
            ->where('shipping_zone_id', $zone)->orderBy('rate');

        $results->where(function ($query) use ($price, $weight) {
            $query->where('based_on', 'price')
                ->where('minimum', '<=', $price)
                ->where(function ($q) use ($price) {
                    $q->where('maximum', '>=', $price)
                        ->orWhereNull('maximum');
                });

            if ($weight) {
                $query->orWhere(function ($q) use ($weight) {
                    $q->where('based_on', 'weight')
                        ->where('minimum', '<=', $weight)
                        ->where('maximum', '>=', $weight);
                });
            }
        })
            ->select('shipping_rates.*', 'carriers.name as carrier_name')
            ->leftJoin('carriers', 'shipping_rates.carrier_id', '=', 'carriers.id');

        return $results->get();
    }
}

if (! function_exists('getFreeShippingObject')) {
    /**
     * Return free shipping options
     */
    function getFreeShippingObject($zone = null)
    {
        return [
            'id' => null,
            'name' => trans('api.free_shipping'),
            'shipping_zone_id' => $zone && ! is_numeric($zone) ? $zone->id : $zone,
            'carrier_id' => null,
            'carrier_name' => trans('theme.std_shipping_carrier'),
            'cost' => '$0.00',
            'cost_raw' => 0,
            'rate' => '0.00',
            'delivery_takes' => trans('api.std_delivery_time'),
        ];
    }
}

if (! function_exists('get_item_location_shipping_options')) {
    /**
     * PDP / listing shipping estimate using free|fixed|km (replaces zone rates).
     *
     * @param  \App\Models\Inventory  $item
     * @return \Illuminate\Support\Collection
     */
    function get_item_location_shipping_options($item, $lat = null, $lng = null)
    {
        if (is_numeric($item)) {
            $item = \App\Models\Inventory::with(['shop.config'])->find($item);
        }

        if (! $item) {
            return collect();
        }

        $calculator = app(\App\Services\Shipping\ShippingCalculator::class);
        $item->loadMissing(['shop.config']);

        // Prefer explicit coords, then the buyer's selected delivery location (header address).
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            $buyer = app(\App\Services\Hyperlocal\BuyerLocationService::class);
            $buyer->ensureDeliveryLocation();
            $lat = $buyer->latitude();
            $lng = $buyer->longitude();
        } else {
            $lat = (float) $lat;
            $lng = (float) $lng;
        }

        $distanceKm = $calculator->distanceFromShop($item->shop, $lat, $lng);
        $amount = $calculator->calculateForItem($item, optional($item->shop)->config, $distanceKm);
        $handling = (float) (getHandelingCostOf($item->shop_id) ?: 0);
        $total = $amount > 0 ? $amount + $handling : 0;

        $shopRadius = (float) (optional($item->shop)->service_radius_km
            ?: config('hyperlocal.default_shop_service_radius_km', 5));
        $outOfRange = false;
        if (hyperlocal_enabled() && $distanceKm !== null) {
            $outOfRange = $distanceKm > $shopRadius;
        }

        $label = $outOfRange
            ? (trans('theme.out_of_delivery_range') ?: 'Out of delivery range')
            : ($total <= 0
                ? (trans('theme.free_shipping') ?: 'Free shipping')
                : (trans('app.shipping') ?: 'Shipping'));

        if (! $outOfRange && $distanceKm !== null && $total > 0) {
            $label .= ' ('.round($distanceKm, 1).' km)';
        }

        $distanceLabel = null;
        if ($outOfRange) {
            $distanceLabel = trans('theme.notify.product_out_of_delivery_range', [
                'store' => optional($item->shop)->name ?? 'Store',
                'distance' => round($distanceKm, 1),
                'radius' => round($shopRadius, 1),
            ]);
        } elseif ($distanceKm !== null) {
            $distanceLabel = round($distanceKm, 1).' km';
        } elseif (! $lat || ! $lng) {
            $distanceLabel = trans('theme.notify.shipping_select_location') ?: 'Select your delivery location';
        } else {
            $distanceLabel = trans('theme.notify.shipping_based_on_location') ?: 'Based on delivery distance';
        }

        return collect([(object) [
            'id' => 'location',
            'name' => $label,
            'shipping_zone_id' => null,
            'carrier_id' => null,
            'carrier' => (object) ['name' => ' '],
            'carrier_name' => trans('app.shipping') ?? 'Shipping',
            'rate' => $outOfRange ? null : round($amount, 6),
            'based_on' => 'location',
            'minimum' => 0,
            'maximum' => $shopRadius,
            'delivery_takes' => $distanceLabel,
            'distance_km' => $distanceKm,
            'out_of_range' => $outOfRange,
            'service_radius_km' => $shopRadius,
        ]]);
    }
}

if (! function_exists('getShippingCost')) {
    /**
     * Return shipping Cost for the given id
     *
     * @param  $int  shipping
     */
    function getShippingCost($shipping = null)
    {
        if (! $shipping) {
            return null;
        }

        $shipping_rate = DB::table('shipping_rates')->select('rate')
            ->where('id', $shipping)
            ->first();

        return $shipping_rate ? $shipping_rate->rate : null;
    }
}

if (! function_exists('find_string_in_array')) {
    /**
     * find string or sub_string in array of string
     *
     * @param  array  $arr  haystack
     * @param  string  $string  needle
     * @return bool
     */
    function find_string_in_array($arr, $string)
    {
        return array_filter($arr, function ($value) use ($string) {
            return strpos($value, $string) !== false;
        });
    }
}

if (! function_exists('userLevelCompare')) {
    /**
     * Compare two user access level and
     * return true is $user can access the $comparable users information
     *
     * @param  mix  $compare
     * @param  $user  request user
     * @return bool
     */
    function userLevelCompare($compare, $user = null)
    {
        if (! $user) {
            $user = Auth::user();
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $compare instanceof User) {
            $compare = User::findOrFail($compare);
        }

        // If the comparable user is from a shop and the request user is the owner of the shop
        if (
            $compare->merchantId() && $user->isMerchant() &&
            $user->merchantId() === $compare->merchantId()
        ) {
            return true;
        }

        // Return if the user is from a shop and the compare user is not from the same shop
        if (! $user->isFromPlatform() && $user->merchantId() !== $compare->merchantId()) {
            return false;
        }

        // Return true, If comparable user role level not set
        // and request user from platform or same shop
        if (! $compare->role->level) {
            return $user->isFromPlatform() || $user->merchantId() == $compare->merchantId();
        }

        // If the comparable user role have level.
        // Then the request user must have role level set and have to be an user level user
        return $user->role->level && $compare->role->level > $user->role->level;
    }
}

if (! function_exists('get_value_from')) {
    /**
     * Get a value from a table for given ids
     *
     * @param  array|int  $ids  The primary keys
     * @param  string  $table
     * @param  string  $field
     * @return array|string|null
     */
    function get_value_from($ids, $table, $field)
    {
        if (is_array($ids)) {
            $values = DB::table($table)->select($field)->whereIn('id', $ids)->get()->toArray();

            if (! empty($values)) {
                $result = [];
                foreach ($values as $value) {
                    $result[] = $value->$field;
                }

                return $result;
            }
        } else {
            $value = DB::table($table)->select($field)->where('id', $ids)->first();

            if (! empty($value) && isset($value->$field)) {
                return $value->$field;
            }
        }

        return null;
    }
}

if (! function_exists('get_package_options_settings')) {
    function get_package_options_settings($prefix)
    {
        return DB::table('options')->where('option_name', 'like', $prefix.'_%')->get();
    }
}

if (! function_exists('get_from_option_table')) {
    function get_from_option_table($field, $default = null)
    {
        $cacheKey = 'option_table_'.$field;

        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return \Illuminate\Support\Facades\Cache::get($cacheKey);
        }

        $record = DB::table('options')->select('option_value')
            ->where('option_name', $field)->first();

        if ($record) {
            $value = $record->option_value;

            if (is_serialized($value)) {
                $value = unserialize($value);
                $value = $value ? $value : [];
            }

            \Illuminate\Support\Facades\Cache::forever($cacheKey, $value);

            return $value;
        }

        // Insert the option when the default value is given
        if (! is_null($default)) {
            $now = Carbon::now();

            DB::table('options')->insert([
                'option_name' => $field,
                'option_value' => is_array($default) ? serialize($default) : $default,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            \Illuminate\Support\Facades\Cache::forever($cacheKey, $default);
        }

        return $default;
    }
}

if (! function_exists('forget_option_table_cache')) {
    function forget_option_table_cache($option): void
    {
        \Illuminate\Support\Facades\Cache::forget('option_table_'.$option);
    }
}

if (! function_exists('update_option_table_record')) {
    function update_option_table_record($option, $data)
    {
        $data = is_array($data) ? serialize($data) : $data;

        $updated = DB::table('options')->where('option_name', $option)
            ->update([
                'option_value' => $data,
                'updated_at' => Carbon::now(),
            ]);

        forget_option_table_cache($option);

        return $updated;
    }
}

if (! function_exists('update_or_create_option_table_record')) {
    function update_or_create_option_table_record($option, $data)
    {
        // Try to update first
        $update = update_option_table_record($option, $data);

        // When the update failed, create
        if (! $update) {
            $now = Carbon::now();

            $update = DB::table('options')->insert([
                'option_name' => $option,
                'option_value' => is_array($data) ? serialize($data) : $data,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            forget_option_table_cache($option);
        }

        return $update;
    }
}

// if (! function_exists('get_from_given_collection')) {
//     function get_from_given_collection(Collection $collection, $field, $value)
//     {
//         return $collection->firstWhere($field, $value);
//     }
// }

if (! function_exists('get_yes_or_no')) {
    /**
     * Return YES or No string for views base on a given bool value
     *
     * @param  bool  $value
     * @return string
     */
    function get_yes_or_no($value = null)
    {
        return $value == 1 ? trans('app.yes') : trans('app.no');
    }
}

if (! function_exists('get_msg_folder_name_from_label')) {
    /**
     * get_msg_folder_name_from_label
     *
     * @param  int  $label
     * @return string
     */
    function get_msg_folder_name_from_label($label = 1)
    {
        switch ($label) {
            case Message::LABEL_INBOX:
                return trans('app.message_labels.inbox');
            case Message::LABEL_SENT:
                return trans('app.message_labels.sent');
            case Message::LABEL_DRAFT:
                return trans('app.message_labels.draft');
            case Message::LABEL_SPAM:
                return trans('app.message_labels.spam');
            case Message::LABEL_TRASH:
                return trans('app.message_labels.trash');
            default:
                return trans('app.message_labels.inbox');
        }
    }
}

if (! function_exists('get_payment_method_type')) {
    function get_payment_method_type($type)
    {
        switch ($type) {
            case PaymentMethod::TYPE_PAYPAL:
                return [
                    'name' => trans('app.payment_method_type.paypal.name'),
                    'description' => trans('app.payment_method_type.paypal.description'),
                    'admin_description' => trans('app.payment_method_type.paypal.admin_description'),
                ];

            case PaymentMethod::TYPE_CREDIT_CARD:
                return [
                    'name' => trans('app.payment_method_type.credit_card.name'),
                    'description' => trans('app.payment_method_type.credit_card.description'),
                    'admin_description' => trans('app.payment_method_type.credit_card.admin_description'),
                ];

            case PaymentMethod::TYPE_MANUAL:
                return [
                    'name' => trans('app.payment_method_type.manual.name'),
                    'description' => trans('app.payment_method_type.manual.description'),
                    'admin_description' => trans('app.payment_method_type.manual.admin_description'),
                ];

            case PaymentMethod::TYPE_OTHERS:
                return [
                    'name' => trans('app.payment_method_type.others.name'),
                    'description' => trans('app.payment_method_type.others.description'),
                    'admin_description' => trans('app.payment_method_type.others.admin_description'),
                ];

            case PaymentMethod::MOBILE_WALLET:
                return [
                    'name' => trans('app.payment_method_type.mobile_wallet.name'),
                    'description' => trans('app.payment_method_type.mobile_wallet.description'),
                    'admin_description' => trans('app.payment_method_type.mobile_wallet.admin_description'),
                ];

            default:
                return [
                    'name' => '',
                    'description' => '',
                    'admin_description' => '',
                ];
        }
    }
}

if (! function_exists('get_shipping_method_type')) {
    function get_shipping_method_type($type)
    {
        switch ($type) {
            case ShippingMethod::TYPE_MANUAL:
                return [
                    'name' => trans('app.shipping_method_type.manual.name'),
                    'description' => trans('app.shipping_method_type.manual.description'),
                    'admin_description' => trans('app.shipping_method_type.manual.admin_description'),
                ];

            case ShippingMethod::TYPE_ONLINE:
                return [
                    'name' => trans('app.shipping_method_type.online.name'),
                    'description' => trans('app.shipping_method_type.online.description'),
                    'admin_description' => trans('app.shipping_method_type.online.admin_description'),
                ];

            default:
                return [
                    'name' => '',
                    'description' => '',
                    'admin_description' => '',
                ];
        }
    }
}

if (! function_exists('get_order_status_name')) {
    /**
     * get_order_status_name
     *
     * @param  int  $label
     * @return string
     */
    function get_order_status_name($status = 1)
    {
        switch ($status) {
            case Order::STATUS_WAITING_FOR_PAYMENT:
                return trans('app.statuses.waiting_for_payment');
            case Order::STATUS_PAYMENT_ERROR:
                return trans('app.statuses.payment_error');
            case Order::STATUS_CONFIRMED:
                return trans('app.statuses.confirmed');
            case Order::STATUS_FULFILLED:
                return trans('app.statuses.fulfilled');
            case Order::STATUS_AWAITING_DELIVERY:
                return trans('app.statuses.awaiting_delivery');
            case Order::STATUS_DELIVERED:
                return trans('app.statuses.delivered');
            case Order::STATUS_RETURNED:
                return trans('app.statuses.refunded');
            case Order::STATUS_CANCELED:
                return trans('app.canceled');
            default:
                return '';
        }
    }
}

if (! function_exists('get_payment_status_name')) {
    /**
     * get_payment_status_name
     *
     * @param  int  $label
     * @return string
     */
    function get_payment_status_name($status = 1)
    {
        switch ($status) {
            case Order::PAYMENT_STATUS_UNPAID:
                return trans('app.statuses.unpaid');
            case Order::PAYMENT_STATUS_PENDING:
                return trans('app.statuses.pending');
            case Order::PAYMENT_STATUS_PAID:
                return trans('app.statuses.paid');
            case Order::PAYMENT_STATUS_INITIATED_REFUND:
                return trans('app.statuses.refund_initiated');
            case Order::PAYMENT_STATUS_PARTIALLY_REFUNDED:
                return trans('app.statuses.partially_refunded');
            case Order::PAYMENT_STATUS_REFUNDED:
                return trans('app.statuses.refunded');
            default:
                return trans('app.statuses.unpaid');
        }
    }
}

if (! function_exists('get_exception_message')) {
    /**
     * get_payment_status_name
     *
     * @param  int  $label
     * @return string
     */
    function get_exception_message($exception)
    {
        return $exception->getMessage().' | Line: '.$exception->getLine().' | File: '.$exception->getFile();
    }
}

if (! function_exists('get_dispute_status_name')) {
    /**
     * get_dispute_status_name
     *
     * @param  int  $label
     * @return string
     */
    function get_dispute_status_name($status = 1)
    {
        switch ($status) {
            case Dispute::STATUS_NEW:
                return trans('app.statuses.new');
            case Dispute::STATUS_OPEN:
                return trans('app.statuses.open');
            case Dispute::STATUS_WAITING:
                return trans('app.statuses.waiting');
            case Dispute::STATUS_APPEALED:
                return trans('app.statuses.appealed');
            case Dispute::STATUS_SOLVED:
                return trans('app.statuses.solved');
            case Dispute::STATUS_CLOSED:
                return trans('app.statuses.closed');
                // case Dispute::STATUS_REFUNDED:
                //     return trans('app.statuses.refunded');
            default:
                return '';
        }
    }
}

if (! function_exists('get_chat_status_name')) {
    /**
     * get_chat_status_name
     *
     * @param  int  $status
     * @return string
     */
    function get_chat_status_name($status = \Incevio\Package\LiveChat\Models\ChatConversation::STATUS_NEW)
    {
        switch ($status) {
            case \Incevio\Package\LiveChat\Models\ChatConversation::STATUS_NEW:
                return trans('app.statuses.new');
            case \Incevio\Package\LiveChat\Models\ChatConversation::STATUS_READ:
                return trans('app.statuses.read');
            case \Incevio\Package\LiveChat\Models\ChatConversation::STATUS_UNREAD:
                return trans('app.statuses.unread');
        }
    }
}

if (! function_exists('get_cancellation_reason_txt')) {
    /**
     * get_cancellation_reason_txt
     *
     * @param  int  $status
     * @return string
     */
    function get_cancellation_reason_txt($status = Cancellation::STATUS_NEW)
    {
        switch ($status) {
            case Cancellation::STATUS_NEW:
                return trans('app.statuses.new');
            case Cancellation::STATUS_OPEN:
                return trans('app.statuses.open');
            case Cancellation::STATUS_APPROVED:
                return trans('app.statuses.approved');
            case Cancellation::STATUS_DECLINED:
                return trans('app.statuses.declined');
        }
    }
}

if (! function_exists('get_activity_title')) {
    function get_activity_title($activity)
    {
        if (! $activity->causer) {
            return trans('app.system').' '.$activity->description.' '.trans('app.this').' '.$activity->log_name;
        }

        return Str::title($activity->description).' '.trans('app.by').' '.$activity->causer->getName();
    }
}

if (! function_exists('isActive')) {
    /**
     * Set the active class to the current opened menu.
     *
     * @param  string|array  $route
     * @param  string  $className
     * @return string
     */
    function isActive($route, $className = 'active')
    {
        if (is_array($route)) {
            return in_array(Route::currentRouteName(), $route) ? $className : '';
        }

        if (Route::currentRouteName() == $route) {
            return $className;
        }

        if (strpos(URL::current(), $route)) {
            return $className;
        }

        return '';
    }
}

if (! function_exists('verifyRequiredDataForBulkUpload')) {
    function verifyRequiredDataForBulkUpload($data, $type = 'inventory')
    {
        if (! is_array($data)) {
            $data = unserialize($data);
        }

        $required = array_flip(config('system.import_required.'.$type, []));

        $value = array_intersect_ukey($data, $required, 'checkAllValuesExistInAArray');

        return count($value) == count($required);
    }
}

if (! function_exists('checkAllValuesExistInAArray')) {
    /**
     * check all the Values Exist of $b exist is array $a
     *
     * @param  array  $a
     * @param  array  $b
     * @return mix
     */
    function checkAllValuesExistInAArray($a, $b)
    {
        if ($a === $b) {
            return 0;
        }

        return ($a > $b) ? 1 : -1;
    }
}

if (! function_exists('is_address_autocomplete_on')) {
    function is_address_autocomplete_on()
    {
        return config('services.google.place_api_key');
    }
}

if (! function_exists('is_chat_enabled')) {
    /**
     * Live chat is always enabled for storefront shops (no on/off toggle).
     */
    function is_chat_enabled(Shop $shop)
    {
        return (bool) optional($shop)->id;
    }
}

if (! function_exists('is_subscription_enabled')) {
    /**
     * Check if the subscription enabled
     */
    function is_subscription_enabled()
    {
        return config('system.subscription.enabled');
    }
}

if (! function_exists('get_subscription_billing')) {
    /**
     * Check if the subscription billing
     */
    function get_subscription_billing()
    {
        return config('system.subscription.billing');
    }
}

if (! function_exists('subscription_billing_type')) {
    /**
     * Get the subscription billing type
     */
    function subscription_billing_type()
    {
        return config('system.subscription.billing');
    }
}

if (! function_exists('is_stripe_configured')) {
    /**
     * Check if the stripe APIs configured
     */
    function is_stripe_configured()
    {
        return config('services.stripe.client_id') && config('services.stripe.key') &&
            config('services.stripe.secret') && config('services.stripe.webhook.secret');
    }
}

if (! function_exists('get_chat_room_name')) {
    /**
     * Return marketplace chat room name
     */
    function get_chat_room_name($room = '')
    {
        return "cafremarket-chat{$room}";
    }
}

if (! function_exists('get_vendor_chat_room_id')) {
    /**
     * Return vendor_chat_room_id
     */
    function get_vendor_chat_room_id($shop = null)
    {
        $shop = $shop ?? optional(Auth::user())->merchantId();

        if ($shop instanceof Shop) {
            return (string) $shop->slug;
        }

        if (! $shop) {
            return '';
        }

        $model = Shop::find($shop);

        return $model ? (string) $model->slug : '';
    }
}

if (! function_exists('get_private_chat_room_id')) {
    /**
     * Return unique private_chat_room_id
     */
    function get_private_chat_room_id(\Incevio\Package\LiveChat\Models\ChatConversation $conversation)
    {
        return get_chat_room_name($conversation->shop_id.$conversation->customer_id);
    }
}

if (! function_exists('multi_tag_explode')) {
    /**
     * extend php's explode functions
     */
    function multi_tag_explode($delimiters, $string)
    {
        return explode($delimiters[0], str_replace($delimiters, $delimiters[0], $string));
    }
}

if (! function_exists('get_featured_items')) {
    /**
     * Get featured Products
     *
     * @return array
     */
    function get_featured_items($shop_id = null)
    {
        $field = 'featured_items'.$shop_id;

        return Cache::rememberForever($field, function () use ($field) {
            $items = get_from_option_table($field, []);

            if (! empty($items)) {
                return Inventory::whereIn('id', $items)
                    ->where('active', 1)
                    ->where('available_from', '<=', Carbon::now())
                    ->with([
                        'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                        'image:path,imageable_id,imageable_type',
                    ])->get();
            }

            return $items;
        });
    }
}

if (! function_exists('hyperlocal_enabled')) {
    function hyperlocal_enabled(): bool
    {
        return (bool) config('hyperlocal.enabled', true);
    }
}

if (! function_exists('buyer_has_location')) {
    function buyer_has_location(): bool
    {
        return app(\App\Services\Hyperlocal\BuyerLocationService::class)->hasLocation();
    }
}

if (! function_exists('buyer_delivery_address_label')) {
    /**
     * Unified delivery address label for header, homepage, and store pages.
     */
    function buyer_delivery_address_label(): ?string
    {
        $service = app(\App\Services\Hyperlocal\BuyerLocationService::class);

        if (! $service->hasLocation()) {
            return null;
        }

        return $service->addressText();
    }
}

if (! function_exists('customer_needs_delivery_address')) {
    /**
     * Logged-in customer must add or sync a saved delivery address.
     */
    function customer_needs_delivery_address(): bool
    {
        if (! Auth::guard('customer')->check()) {
            return false;
        }

        if (function_exists('hyperlocal_enabled') && ! hyperlocal_enabled()) {
            return false;
        }

        $customer = Auth::guard('customer')->user();
        $service = app(\App\Services\Hyperlocal\BuyerLocationService::class);

        if ($customer->addresses()->count() === 0) {
            session()->forget([
                'buyer_latitude',
                'buyer_longitude',
                'buyer_address_text',
            ]);

            return true;
        }

        $service->ensureDeliveryLocation($customer);

        return ! $service->hasLocation();
    }
}

if (! function_exists('get_deliverable_shop_ids')) {
    function get_deliverable_shop_ids(): array
    {
        return app(\App\Services\Hyperlocal\HyperlocalCatalogService::class)->deliverableShopIds();
    }
}

if (! function_exists('scope_inventory_for_buyer')) {
    /**
     * Restrict inventory queries to approved shops within buyer delivery radius.
     */
    function scope_inventory_for_buyer($query, ?int $shop_id = null)
    {
        if ($shop_id) {
            return $query->where('shop_id', $shop_id);
        }

        $query = $query->whereHas('shop', function ($q) {
            $q->approved();
        });

        if (hyperlocal_enabled()) {
            return app(\App\Services\Hyperlocal\HyperlocalCatalogService::class)->scopeInventoryQuery($query);
        }

        return $query->zipcode();
    }
}

if (! function_exists('hyperlocal_location_cache_suffix')) {
    function hyperlocal_location_cache_suffix(): string
    {
        if (! hyperlocal_enabled()) {
            return '';
        }

        $location = app(\App\Services\Hyperlocal\BuyerLocationService::class);

        return '_hl_'.round((float) ($location->latitude() ?? 0), 3).'_'.round((float) ($location->longitude() ?? 0), 3);
    }
}

if (! function_exists('hyperlocal_browse_gate_view')) {
    /**
     * Return a location gate view when browse requires buyer location.
     */
    function hyperlocal_browse_gate_view()
    {
        $catalog = app(\App\Services\Hyperlocal\HyperlocalCatalogService::class);
        $buyerLocation = app(\App\Services\Hyperlocal\BuyerLocationService::class);
        $buyerLocation->syncFromCustomer();

        if (! $catalog->requiresLocationForBrowse() || $buyerLocation->hasLocation()) {
            return null;
        }

        return view('theme::search_results', [
            'products' => \App\Models\Inventory::whereRaw('1 = 0')->paginate(config('system.view_listing_per_page', 15)),
            'category' => null,
            'brands' => collect(),
            'priceRange' => ['min' => 0, 'max' => 0],
            'searchCountries' => collect(),
            'searchStates' => collect(),
            'require_location' => true,
        ]);
    }
}

if (! function_exists('get_nearby_featured_items')) {
    /**
     * Get up to N featured products from nearby shop IDs.
     */
    function get_nearby_featured_items(array $shopIds, int $limit = 5)
    {
        if (empty($shopIds)) {
            return collect();
        }

        $items = collect();

        foreach ($shopIds as $shopId) {
            $featured = get_featured_items($shopId);
            if ($featured && count($featured)) {
                $items = $items->merge($featured);
            }
        }

        if ($items->count() < $limit) {
            $existingIds = $items->pluck('id')->filter()->toArray();

            $fallback = Inventory::query()
                ->whereIn('shop_id', $shopIds)
                ->where('active', 1)
                ->where('available_from', '<=', Carbon::now())
                ->when(! empty($existingIds), fn ($q) => $q->whereNotIn('id', $existingIds))
                ->with([
                    'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                    'image:path,imageable_id,imageable_type',
                    'shop:id,name,slug',
                ])
                ->orderByDesc('sold_quantity')
                ->limit($limit - $items->count())
                ->get();

            $items = $items->merge($fallback);
        }

        return $items->unique('id')->take($limit)->values();
    }
}

if (! function_exists('get_featured_brand_ids')) {
    /**
     * Get featured brand ids
     *
     * @return array
     */
    function get_featured_brand_ids()
    {
        return Cache::rememberForever('featured_brand_ids', function () {
            return get_from_option_table('featured_brands', []);
        });
    }
}

if (! function_exists('get_featured_brands')) {
    /**
     * Get featured brands
     *
     * @return array
     */
    function get_featured_brands()
    {
        if (! $featured_brands = get_featured_brand_ids()) {
            return collect([]);
        }

        return Cache::rememberForever('featured_brands', function () use ($featured_brands) {
            return Manufacturer::select('id', 'name', 'slug', 'description')
                ->whereIn('id', $featured_brands)
                ->with('featureImage:path,imageable_id,imageable_type')
                ->get();
        });
    }
}

if (! function_exists('get_featured_vendor_ids')) {
    /**
     * Get featured vendor ids
     *
     * @return array
     */
    function get_featured_vendor_ids()
    {
        return Cache::rememberForever('featured_vendor_ids', function () {
            return get_from_option_table('featured_vendors', []);
        });
    }
}

if (! function_exists('get_featured_vendors')) {
    /**
     * Get featured vendors
     *
     * @return array
     */
    function get_featured_vendors()
    {
        return Cache::rememberForever('featured_vendors', function () {
            $featured_vendors = get_featured_vendor_ids();

            $baseQuery = Shop::select('id', 'name', 'slug', 'id_verified', 'phone_verified', 'address_verified')
                ->active()
                ->whereHas('inventories', function ($q) {
                    $q->available();
                })
                ->with([
                    'inventories' => function ($q) {
                        $q->select(ListHelper::common_select_attr('inventory'))
                            ->available()
                            ->with([
                                'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                                'image:path,imageable_id,imageable_type',
                            ])
                            ->where('parent_id', null)
                            ->inRandomOrder()->take(30);
                    },
                    'logoImage:path,imageable_id,imageable_type',
                ]);

            if ($featured_vendors) {
                $shops = (clone $baseQuery)->whereIn('id', $featured_vendors)->get();
                if ($shops->isNotEmpty()) {
                    return $shops->take(3);
                }
            }

            return $baseQuery->inRandomOrder()->take(3)->get();
        });
    }
}

if (! function_exists('get_featured_category')) {
    /**
     * Get featured category
     *
     * @return array
     */
    function get_featured_category()
    {
        return Cache::rememberForever('featured_categories', function () {
            return Category::select('id', 'name', 'slug')
                ->with('featureImage:path,imageable_id,imageable_type')
                ->withCount('listings')
                ->orderBy('order', 'asc')
                ->featured()->get();
        });
    }
}

if (! function_exists('get_main_nav_categories')) {
    /**
     * Get featured brands
     *
     * @return array
     */
    function get_main_nav_categories()
    {
        return Cache::rememberForever('main_nav_categories', function () {
            $ids = get_from_option_table('main_nav_categories', []);

            return Category::findMany($ids, ['id', 'slug', 'name']);
        });
    }
}

if (! function_exists('hidden_menu_items')) {
    /**
     * get hide menu item
     *
     * @return array|mixed|null
     */
    function hidden_menu_items()
    {
        return Cache::rememberForever('hidden_menu_items', function () {
            return get_from_option_table('hidden_menu_items', []);
        });
    }
}

if (! function_exists('get_trending_category_ids')) {
    /**
     * Get trending category ids
     *
     * @return array
     */
    function get_trending_category_ids()
    {
        return Cache::rememberForever('trending_category_ids', function () {
            return get_from_option_table('trending_categories', []);
        });
    }
}

if (! function_exists('get_trending_categories')) {
    /**
     * Get trending categories
     *
     * @return array
     */
    function get_trending_categories()
    {
        $trending_ids = get_trending_category_ids();

        if (! $trending_ids) {
            return [];
        }

        return Cache::rememberForever('trending_categories', function () use ($trending_ids) {
            return Category::whereIn('id', $trending_ids)->get();
        });
    }
}

if (! function_exists('get_trending_categories_with_items')) {
    /**
     * Get trending_categories
     *
     * @return array
     */
    function get_trending_categories_with_items()
    {
        if (! $trending_ids = get_trending_category_ids()) {
            return collect([]);
        }

        return Cache::remember('trending_categories_with_items', config('cache.remember.trending_category_items', 86400), function () use ($trending_ids) {
            return Category::select('id', 'name', 'slug', 'order')
                ->whereIn('id', $trending_ids)
                ->whereHas('listings')
                ->with([
                    'listings' => function ($q) {
                        $q->select(ListHelper::common_select_attr('inventory'))
                            ->available()
                            ->with([
                                'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                                'image:path,imageable_id,imageable_type',
                            ])
                            ->whereNull('parent_id')
                            // Prefer ordered listings over random (random scans are expensive).
                            ->orderByDesc('inventories.id')
                            ->limit(config('system.popular.take.trending', 20))
                            ->get();
                    },
                ])->get();
        });
    }
}

if (! function_exists('get_deal_of_the_day')) {
    /**
     * Get get_deal_of_the_day
     *
     * @return inventory
     */
    function get_deal_of_the_day($shop_id = null)
    {
        $field = 'deal_of_the_day'.$shop_id;

        return Cache::rememberForever($field, function () use ($field) {
            return Inventory::where('id', get_from_option_table($field))
                ->with([
                    'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                    'images:path,imageable_id,imageable_type',
                ])
                ->where('active', 1)
                ->where('available_from', '<=', Carbon::now())
                ->first();
        });
    }
}

if (! function_exists('create_file_from_base64')) {
    /**
     * Decode a data-URL or raw base64 payload into a temporary UploadedFile (chat, checkout, etc.).
     */
    function create_file_from_base64($base64File)
    {
        $raw = (string) $base64File;
        $mime = 'application/octet-stream';
        $ext = 'bin';

        if (preg_match('#^data:([^;]+);base64,(.+)$#s', $raw, $m)) {
            $mime = strtolower(trim($m[1]));
            $binary = base64_decode($m[2], true);
        } else {
            $stripped = preg_replace('#^data:[^;]+;base64,#i', '', $raw);
            $binary = base64_decode($stripped, true);
        }

        if ($binary === false || $binary === '') {
            $binary = base64_decode($raw, true) ?: '';
        }

        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if (isset($mimeMap[$mime])) {
            $ext = $mimeMap[$mime];
        } elseif (str_starts_with($mime, 'image/')) {
            $ext = 'png';
        }

        $tmpFilePath = sys_get_temp_dir().'/'.Str::uuid()->toString().'.'.$ext;

        file_put_contents($tmpFilePath, $binary);

        $tmpFile = new \Symfony\Component\HttpFoundation\File\File($tmpFilePath);

        return new UploadedFile(
            $tmpFile->getPathname(),
            $tmpFile->getFilename(),
            $tmpFile->getMimeType(),
            0,
            false
        );
    }
}

// STRIPE Helper
// if (! function_exists('getStripeAuthorizeUrl'))
// {
//     /**
//      * Return authorize_url to Stripe connect authorization
//      */
//     function getStripeAuthorizeUrl()
//     {
//         return "https://connect.stripe.com/oauth/authorize?response_type=code&client_id=" . config('services.stripe.client_id') . "&scope=read_write&state=" . csrf_token();
//     }

/**
 * This method will return unique random number
 *
 * @return code()
 */
if (! function_exists('generateUniqueNumber')) {
    function generateUniqueNumber()
    {
        do {
            $code = random_int(100000, 999999);
        } while (DB::table('password_resets')->where('token', $code)->first());

        return $code;
    }
}

/**
 * Update Env file
 *
 * @param  array  $data
 */
if (! function_exists('update_env')) {
    function update_env($data = []): void
    {
        // When the user is admin
        if (Auth::user()->isAdmin()) {
            if (! empty($data)) {
                $env = new \App\Services\EnvManager;
                foreach ($data as $key => $value) {
                    $env->setValue($key, $value, true);
                }
            }
        }

    }
}

if (! function_exists('is_social_login_configured')) {
    /**
     * Check if social login plugin configured
     */
    function is_social_login_configured()
    {
        return is_incevio_package_loaded('facebook-login') ||
            is_incevio_package_loaded('google-login') ||
            is_incevio_package_loaded('apple-login');
    }
}

if (! function_exists('get_flash_deals')) {
    /**
     * Get Flash Deals
     *
     * @return array | null
     */
    function get_flash_deals()
    {
        $flash_deals = Cache::rememberForever('flashdeals', function () {
            $deals = get_from_option_table('flashdeal_items', []);

            // Return null when the list is empty
            if (empty($deals)) {
                return null;
            }

            $items = [];

            // Get general deals
            if (! empty($deals['listings'])) {
                $items['listings'] = Inventory::available()
                    ->whereIn('id', $deals['listings'])
                    ->select(ListHelper::common_select_attr('inventory'))
                    ->with([
                        'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                        'image:path,imageable_id,imageable_type',
                    ])
                    ->get();
            }

            // Get featured deals
            if (! empty($deals['featured'])) {
                $items['featured'] = Inventory::available()
                    ->whereIn('id', $deals['featured'])
                    ->with([
                        'avgFeedback:rating,count,feedbackable_id,feedbackable_type',
                        'image:path,imageable_id,imageable_type',
                    ])
                    ->get();
            }

            return array_merge($deals, $items);
        });

        if (
            ! Request::is('admin/*') &&
            $flash_deals &&
            $flash_deals['start_time']->isPast() &&
            $flash_deals['end_time']->isFuture()
        ) {
            return $flash_deals;
        }

        return null;
    }
}

if (! function_exists('get_custom_css')) {
    /**
     * Get custom css
     *
     * @return string
     */
    function get_custom_css($shop_id = null)
    {
        $field = 'theme_custom_styling'.$shop_id;

        return Cache::rememberForever($field, function () use ($field) {
            return get_from_option_table($field) ?? '';
        });
    }
}

if (! function_exists('best_finds_under')) {
    /**
     * Get best finds under value
     *
     * @return string
     */
    function best_finds_under($shop_id = null)
    {
        $field = 'best_finds_under'.$shop_id;

        return Cache::rememberForever($field, function () use ($field) {
            return get_from_option_table($field, 99);
        });
    }
}

if (! function_exists('convert_img_to')) {
    /**
     * Convert img to the given extension
     *
     * @param  string  $file_path
     * @param  string  $ext
     * @return string
     */
    function convert_img_to($file_path, $ext = 'webp')
    {
        $manager = new ImageManager(config('image.driver'));

        return $manager->read($file_path)->toWebp()->toFilePointer();
    }
}

if (! function_exists('shorten')) {
    /**
     * Short format of large number
     *
     * @return string
     */
    function shorten($number)
    {
        $suffix = ['', 'K', 'M', 'B'];
        $precision = 2;
        for ($i = 0; $i < count($suffix); $i++) {
            $divide = $number / pow(1000, $i);
            if ($divide < 1000) {
                return round($divide, $precision).$suffix[$i];
            }
        }
    }
}

if (! function_exists('get_shortcode_replaced')) {
    /**
     * Replace short codes in a given string
     */
    function get_shortcode_replaced($str = '')
    {
        return str_replace(
            [
                '{platform_name}',
                '{platform_url}',
                '{platform_address}',
                '{CONTACT_US}',
                '{ABOUT_US}',
                '{PRIVACY_POLICY}',
                '{RETURN_AND_REFUND}',
                '{TERMS_AND_CONDITIONS_FOR_CUSTOMER}',
                '{TERMS_AND_CONDITIONS_FOR_MERCHANT}',
            ],
            [
                get_platform_title(),
                '<a href="'.url('/').'" target="_black">'.url('/').'</a>',
                get_platform_address(),
                get_page_url('contact-us'),
                get_page_url('about-us'),
                get_page_url('privacy-policy'),
                get_page_url('return-and-refund-policy'),
                get_page_url('terms-of-use-customer'),
                get_page_url('terms-of-use-merchant'),
            ],
            $str
        );
    }
}

/**
 * Return minimum and maximum price from the given listings
 *
 * @return array
 */
function get_price_ranges_from_listings($listings)
{
    $prices = $listings->pluck('sale_price');

    $priceRange['min'] = floor($prices->min());
    $priceRange['max'] = ceil($prices->max());

    return $priceRange;
}

if (! function_exists('get_smart_form_lists')) {
    function get_smart_form_lists()
    {
        return ListHelper::smart_forms();
    }
}

if (! function_exists('get_shipping_label_templates_list')) {
    function get_shipping_label_templates_list()
    {
        return PdfTemplate::where('type', PdfTemplate::TYPE_SHIPPING_LABEL)->pluck('name', 'id');
    }
}

if (! function_exists('get_customer_invoice_templates_list')) {
    function get_customer_invoice_templates_list()
    {
        return PdfTemplate::where('type', PdfTemplate::TYPE_ORDER_INVOICE)->pluck('name', 'id');
    }
}

if (! function_exists('shop_ships_to_state')) {
    /**
     * Whether the shop has an active shipping zone that delivers to the given state/region.
     */
    function shop_ships_to_state(Shop $shop, int $stateId): bool
    {
        if ($stateId < 1) {
            return false;
        }

        $state = State::find($stateId);
        if (! $state) {
            return false;
        }

        $countryId = (int) $state->country_id;

        $zones = $shop->relationLoaded('shippingZones')
            ? $shop->shippingZones->where('active', 1)->values()
            : $shop->shippingZones()->where('active', 1)->get();

        if ($zones->isEmpty()) {
            return false;
        }

        foreach ($zones as $zone) {
            if ($zone->rest_of_the_world) {
                return true;
            }

            $stateIds = is_array($zone->state_ids) ? $zone->state_ids : [];
            if (in_array($stateId, $stateIds, true)) {
                return true;
            }

            $countryIds = is_array($zone->country_ids) ? $zone->country_ids : [];
            if ($countryIds !== [] && in_array($countryId, $countryIds, true)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('shop_ships_to_country')) {
    /**
     * Whether the shop has an active shipping zone that delivers to the given country
     * (any region in that country: whole-country zone, rest-of-world, or specific states in the country).
     *
     * @param  array<int>|null  $stateIdsInCountry  Optional precomputed state ids for the country (avoids repeated queries).
     */
    function shop_ships_to_country(Shop $shop, int $countryId, ?array $stateIdsInCountry = null): bool
    {
        if ($countryId < 1) {
            return false;
        }

        $zones = $shop->relationLoaded('shippingZones')
            ? $shop->shippingZones->where('active', 1)->values()
            : $shop->shippingZones()->where('active', 1)->get();

        if ($zones->isEmpty()) {
            return false;
        }

        if ($stateIdsInCountry === null) {
            $stateIdsInCountry = State::where('country_id', $countryId)->pluck('id')->all();
        }

        $countryStateIds = array_map('intval', $stateIdsInCountry);

        foreach ($zones as $zone) {
            if ($zone->rest_of_the_world) {
                return true;
            }

            $countryIds = is_array($zone->country_ids) ? $zone->country_ids : [];
            if ($countryIds !== [] && in_array($countryId, $countryIds, true)) {
                return true;
            }

            $stateIds = is_array($zone->state_ids) ? $zone->state_ids : [];
            if ($stateIds === [] || $countryStateIds === []) {
                continue;
            }

            $zoneStateIds = array_map('intval', $stateIds);
            if (array_intersect($zoneStateIds, $countryStateIds) !== []) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('ensure_default_category_sub_group_id')) {
    /**
     * Categories still require a category_sub_group_id FK.
     * Sub-group UI was removed, so ensure a shared default exists and return its id.
     */
    function ensure_default_category_sub_group_id(): int
    {
        $group = CategoryGroup::withTrashed()->firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'description' => 'Default category group',
                'active' => 1,
                'order' => 100,
            ]
        );

        if (method_exists($group, 'trashed') && $group->trashed()) {
            $group->restore();
        }

        $subGroup = CategorySubGroup::withTrashed()->firstOrCreate(
            ['slug' => 'general'],
            [
                'name' => 'General',
                'category_group_id' => $group->id,
                'description' => 'Default category sub group',
                'active' => 1,
                'order' => 100,
            ]
        );

        if (method_exists($subGroup, 'trashed') && $subGroup->trashed()) {
            $subGroup->restore();
        }

        if ((int) $subGroup->category_group_id !== (int) $group->id) {
            $subGroup->category_group_id = $group->id;
            $subGroup->save();
        }

        return (int) $subGroup->id;
    }
}
