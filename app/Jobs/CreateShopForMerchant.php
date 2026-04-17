<?php

namespace App\Jobs;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CreateShopForMerchant
{
    use Dispatchable;

    protected $merchant;

    protected $request;

    /**
     * Create a new job instance.
     *
     * @param  string  $request
     * @return void
     */
    public function __construct(User $merchant, $request)
    {
        $this->merchant = $merchant;
        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (isset($this->request['active'])) {
            $status = $this->request['active'];
        } else {
            $status = config('system_settings.vendor_needs_approval') ? null : 1;
        }

        if (is_subscription_enabled() && (bool) config('system_settings.trial_days')) {
            $trial_ends_at = now()->addDays(config('system_settings.trial_days'));
        }

        if (is_incevio_package_loaded('smartForm') && isset($this->request['extra_info']['file_paths'])) {
            // Store files and add filepaths to extra_info
            foreach ($this->request['extra_info'] as $key => $value) {
                if (is_base64($value)) {
                    $value = create_file_from_base64($key);
                }

                if ($value instanceof UploadedFile) {
                    $directory = 'shop_extra_info/'.$this->merchant->id.'/'.$this->request['shop_name'];
                    $this->request['extra_info']['file_paths'][$key] = $value->storeAs($directory, $value->getClientOriginalName());
                }
            }
        }

        $shopData = array_merge($this->request, [
            'name' => $this->request['shop_name'],
            'description' => $this->request['description'] ?? trans('app.welcome'),
            'legal_name' => $this->request['legal_name'] ?? null,
            'owner_id' => $this->merchant->id,
            'email' => $this->merchant->email,
            'slug' => $this->request['slug'] ?? Str::slug($this->request['shop_name']),
            'external_url' => $this->request['external_url'] ?? null,
            'timezone_id' => config('system_settings.timezone_id'),
            'card_holder_name' => $this->request['name'] ?? null,
            'current_billing_plan' => $this->request['current_billing_plan'] ?? $this->request['plan'] ?? null,
            'trial_ends_at' => $this->request['trial_ends_at'] ?? $trial_ends_at ?? null,
            'active' => $status,
            'extra_info' => isset($this->request['extra_info']) ? json_encode($this->request['extra_info']) : null,
        ]);

        // Remove commission_rate when the Dynamic Commission plugin is not present
        if (isset($this->request['commission_rate']) && ! is_incevio_package_loaded('dynamicCommission')) {
            unset($this->request['commission_rate']);
        }

        $shop = Shop::create($shopData);

        // Configuring The Shop
        $supportInfo = [
            'return_refund' => $this->request['return_refund_policy'] ?? '',
            'support_phone' => $this->request['support_phone'] ?? $this->request['phone'] ?? null,
            'support_email' => $this->request['support_email'] ?? $this->merchant->email,
            'default_sender_email_address' => $this->request['default_sender_email_address'] ?? $this->request['support_email'] ?? $this->merchant->email,
            'default_email_sender_name' => $this->request['default_email_sender_name'] ?? $this->request['shop_name'],
            'maintenance_mode' => isset($this->request['maintenance_mode']) ? $this->request['maintenance_mode'] : 1,
        ];
        $shop->config()->create(array_merge($this->request, $supportInfo));

        // Updating shop_id field in user table
        $this->merchant->shop_id = $shop->id;
        $this->merchant->save();

        // Creating WordWide shippingZones for the Shop
        $shop->shippingZones()->create([
            'name' => trans('app.worldwide'),
            'tax_id' => 1,
            'country_ids' => [],
            'state_ids' => [],
            'rest_of_the_world' => true,
        ]);

        // Create address
        $shop->addresses()->create(array_merge($this->request, [
            'address_title' => $this->request['shop_name'],
            'phone' => $supportInfo['support_phone'],
        ]));

        // Save Brand logo from url
        if (isset($this->request['brand_logo']) && filter_var($this->request['brand_logo'], FILTER_VALIDATE_URL)) {
            $shop->saveImageFromUrl($this->request['brand_logo'], 'logo');
        }

        // Save cover image from url
        if (isset($this->request['cover_image']) && filter_var($this->request['cover_image'], FILTER_VALIDATE_URL)) {
            $shop->saveImageFromUrl($this->request['cover_image'], 'cover');
        }
    }
}
