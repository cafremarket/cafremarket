<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Customer;
use App\Models\DeliveryBoy;
use App\Models\Shop;
use App\Models\System;
use App\Services\Geo\GeocodeService;
use App\Services\Hyperlocal\BuyerLocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HyperlocalCleanupCommand extends Command
{
    protected $signature = 'hyperlocal:cleanup
                            {--skip-geocode : Do not call Google geocoding API}
                            {--clear-carts : Remove cart rows older than 30 days}';

    protected $description = 'Normalize hyperlocal data: geocode addresses, link shop locations, sync customer delivery prefs, set defaults';

    public function handle(GeocodeService $geocoder, BuyerLocationService $buyerLocation): int
    {
        $this->info('Starting hyperlocal database cleanup...');

        $stats = [
            'addresses_geocoded' => 0,
            'shops_linked' => 0,
            'shops_defaults' => 0,
            'customers_synced' => 0,
            'riders_normalized' => 0,
            'carts_removed' => 0,
        ];

        $stats = array_merge($stats, $this->applySystemDefaults());

        if (! $this->option('skip-geocode')) {
            $stats['addresses_geocoded'] = $this->geocodeMissingAddresses($geocoder);
        } else {
            $this->warn('Skipping geocoding (--skip-geocode).');
        }

        $stats['shops_linked'] = $this->linkShopPrimaryAddresses();
        $stats['shops_defaults'] = $this->applyShopDefaults();
        $stats['customers_synced'] = $this->syncCustomerPreferredLocations($buyerLocation);
        $stats['riders_normalized'] = $this->normalizeDeliveryBoyTypes();

        if ($this->option('clear-carts') && Schema::hasTable('carts')) {
            $stats['carts_removed'] = DB::table('carts')
                ->where('created_at', '<', Carbon::now()->subDays(30))
                ->delete();
        }

        $this->newLine();
        $this->table(
            ['Task', 'Count'],
            collect($stats)->map(fn ($count, $task) => [str_replace('_', ' ', $task), $count])->values()->all()
        );

        $shopsReady = Shop::query()
            ->whereHas('addresses', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'))
            ->count();

        $this->info("Shops with geocoded store location: {$shopsReady} / ".Shop::count());
        $this->info('Hyperlocal cleanup complete.');

        if (! config('services.google.place_api_key') && ! config('hyperlocal.google_maps_api_key')) {
            $this->warn('GOOGLE_PLACE_KEY is not set — geocoding may have been skipped for some addresses.');
        }

        return self::SUCCESS;
    }

    protected function applySystemDefaults(): array
    {
        $updated = 0;
        $system = System::first();

        if (! $system) {
            $this->warn('No system row found — skipping system defaults.');

            return ['system_defaults' => 0];
        }

        $defaults = [
            'default_buyer_search_radius_km' => config('hyperlocal.default_buyer_search_radius_km', 10),
            'max_delivery_assignment_radius_km' => config('hyperlocal.max_delivery_assignment_radius_km', 15),
            'rider_accept_timeout_min' => config('hyperlocal.rider_accept_timeout_min', 5),
        ];

        foreach ($defaults as $column => $value) {
            if (! Schema::hasColumn('systems', $column)) {
                continue;
            }

            if ($system->{$column} === null || $system->{$column} === '') {
                $system->{$column} = $value;
                $updated++;
            }
        }

        if ($updated) {
            $system->save();
        }

        return ['system_defaults' => $updated];
    }

    protected function geocodeMissingAddresses(GeocodeService $geocoder): int
    {
        $count = 0;

        Address::query()
            ->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            })
            ->orderBy('id')
            ->chunkById(50, function ($addresses) use ($geocoder, &$count) {
                foreach ($addresses as $address) {
                    $hadCoords = $address->latitude && $address->longitude;
                    $geocoder->applyToAddress($address);
                    $address->refresh();

                    if (! $hadCoords && $address->latitude && $address->longitude) {
                        $count++;
                        $this->line("  Geocoded address #{$address->id}");
                    }
                }
            });

        return $count;
    }

    protected function linkShopPrimaryAddresses(): int
    {
        $linked = 0;

        Shop::query()->with('addresses')->chunkById(50, function ($shops) use (&$linked) {
            foreach ($shops as $shop) {
                $address = $shop->addresses
                    ->firstWhere('address_type', 'Primary')
                    ?? $shop->addresses->firstWhere(fn ($a) => $a->latitude && $a->longitude)
                    ?? $shop->addresses->first();

                if (! $address) {
                    continue;
                }

                if ((int) $shop->primary_address_id !== (int) $address->id) {
                    $shop->primary_address_id = $address->id;
                    $shop->save();
                    $linked++;
                    $this->line("  Shop #{$shop->id} → address #{$address->id}");
                }
            }
        });

        return $linked;
    }

    protected function applyShopDefaults(): int
    {
        $updated = 0;
        $defaultRadius = config('hyperlocal.default_shop_service_radius_km', 5);

        Shop::query()->chunkById(50, function ($shops) use ($defaultRadius, &$updated) {
            foreach ($shops as $shop) {
                $dirty = false;

                if (! $shop->service_radius_km || (float) $shop->service_radius_km <= 0) {
                    $shop->service_radius_km = $defaultRadius;
                    $dirty = true;
                }

                if (! $shop->delivery_capability) {
                    $shop->delivery_capability = 'both';
                    $dirty = true;
                }

                if ($dirty) {
                    $shop->save();
                    $updated++;
                }
            }
        });

        return $updated;
    }

    protected function syncCustomerPreferredLocations(BuyerLocationService $buyerLocation): int
    {
        $synced = 0;

        Customer::query()->with('addresses')->chunkById(50, function ($customers) use ($buyerLocation, &$synced) {
            foreach ($customers as $customer) {
                if ($customer->preferred_latitude && $customer->preferred_longitude) {
                    continue;
                }

                $address = $customer->addresses
                    ->firstWhere('address_type', 'Primary')
                    ?? $customer->addresses->firstWhere('address_type', 'Shipping')
                    ?? $customer->addresses->firstWhere(fn ($a) => $a->latitude && $a->longitude)
                    ?? $customer->addresses->first();

                if (! $address) {
                    continue;
                }

                if ($buyerLocation->applyAddressAsLocation($address, $customer)) {
                    $synced++;
                    $this->line("  Customer #{$customer->id} ← address #{$address->id}");
                }
            }
        });

        return $synced;
    }

    protected function normalizeDeliveryBoyTypes(): int
    {
        $count = 0;

        DeliveryBoy::query()->chunkById(50, function ($riders) use (&$count) {
            foreach ($riders as $rider) {
                $expected = $rider->shop_id ? DeliveryBoy::TYPE_SHOP : DeliveryBoy::TYPE_PLATFORM;

                if ($rider->type !== $expected) {
                    $rider->type = $expected;
                    $rider->save();
                    $count++;
                }
            }
        });

        return $count;
    }
}
