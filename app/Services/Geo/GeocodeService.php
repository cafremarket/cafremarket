<?php

namespace App\Services\Geo;

use App\Models\Address;
use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeService
{
    protected function httpClient()
    {
        $client = Http::timeout(10);

        if (! $this->shouldVerifySsl()) {
            $client = $client->withOptions(['verify' => false]);
        }

        return $client;
    }

    protected function shouldVerifySsl(): bool
    {
        if (env('GEOCODE_VERIFY_SSL') !== null) {
            return filter_var(env('GEOCODE_VERIFY_SSL'), FILTER_VALIDATE_BOOLEAN);
        }

        return ! app()->environment('local', 'development');
    }

    public function geocode($address): array
    {
        if ($address instanceof Address) {
            if ($address->latitude && $address->longitude) {
                return [
                    'latitude' => (float) $address->latitude,
                    'longitude' => (float) $address->longitude,
                ];
            }

            $query = $address->toGeocodeString();
        } else {
            $query = (string) $address;
        }

        if (trim($query) === '') {
            return [];
        }

        $apiKey = config('hyperlocal.google_maps_api_key') ?: config('services.google.place_api_key');

        if ($apiKey) {
            try {
                $response = $this->httpClient()->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'key' => $apiKey,
                ]);

                $data = $response->json();

                if (($data['status'] ?? '') === 'OK' && ! empty($data['results'][0]['geometry']['location'])) {
                    $loc = $data['results'][0]['geometry']['location'];

                    return [
                        'latitude' => (float) $loc['lat'],
                        'longitude' => (float) $loc['lng'],
                    ];
                }

                if (! empty($data['error_message'])) {
                    Log::warning('GeocodeService forward geocode: '.$data['error_message']);
                }
            } catch (\Throwable $e) {
                Log::warning('GeocodeService API failed: '.$e->getMessage());
            }
        }

        if (function_exists('getGeocode')) {
            return getGeocode($query);
        }

        return [];
    }

    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        $apiKey = config('hyperlocal.google_maps_api_key') ?: config('services.google.place_api_key');

        if (! $apiKey) {
            return $this->reverseGeocodeWithNominatim($latitude, $longitude);
        }

        try {
            $response = $this->httpClient()->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $latitude.','.$longitude,
                'key' => $apiKey,
            ]);

            $data = $response->json();

            if (($data['status'] ?? '') === 'OK' && ! empty($data['results'][0]['formatted_address'])) {
                return $data['results'][0]['formatted_address'];
            }

            if (! empty($data['error_message'])) {
                Log::warning('GeocodeService reverse geocode: '.$data['error_message']);
            }
        } catch (\Throwable $e) {
            Log::warning('GeocodeService reverse geocode failed: '.$e->getMessage());
        }

        return $this->reverseGeocodeWithNominatim($latitude, $longitude);
    }

    /**
     * Reverse geocode coordinates into structured address fields for forms.
     */
    public function reverseGeocodeDetails(float $latitude, float $longitude): array
    {
        $empty = [
            'formatted_address' => null,
            'address_line_1' => '',
            'city' => '',
            'state' => '',
            'country' => '',
            'zip_code' => '',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        $apiKey = config('hyperlocal.google_maps_api_key') ?: config('services.google.place_api_key');

        if ($apiKey) {
            try {
                $response = $this->httpClient()->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => $latitude.','.$longitude,
                    'key' => $apiKey,
                ]);

                $data = $response->json();

                if (($data['status'] ?? '') === 'OK' && ! empty($data['results'][0])) {
                    return array_merge($empty, $this->parseGoogleGeocodeResult($data['results'][0]));
                }
            } catch (\Throwable $e) {
                Log::warning('GeocodeService reverseGeocodeDetails failed: '.$e->getMessage());
            }
        }

        $formatted = $this->reverseGeocodeWithNominatim($latitude, $longitude);

        if ($formatted) {
            return array_merge($empty, [
                'formatted_address' => $formatted,
                'address_line_1' => explode(',', $formatted)[0] ?? $formatted,
            ]);
        }

        return $empty;
    }

    protected function parseGoogleGeocodeResult(array $result): array
    {
        $components = collect($result['address_components'] ?? []);

        $get = function (array $types, bool $short = false) use ($components) {
            $match = $components->first(function ($component) use ($types) {
                return count(array_intersect($types, $component['types'] ?? [])) > 0;
            });

            if (! $match) {
                return '';
            }

            return $short ? ($match['short_name'] ?? '') : ($match['long_name'] ?? '');
        };

        $streetNumber = $get(['street_number']);
        $route = $get(['route']);
        $addressLine1 = trim($streetNumber.' '.$route);

        if ($addressLine1 === '' && ! empty($result['formatted_address'])) {
            $addressLine1 = explode(',', $result['formatted_address'])[0] ?? $result['formatted_address'];
        }

        return [
            'formatted_address' => $result['formatted_address'] ?? null,
            'address_line_1' => $addressLine1,
            'city' => $get(['locality', 'postal_town', 'administrative_area_level_2']),
            'state' => $get(['administrative_area_level_1']),
            'country' => $get(['country']),
            'zip_code' => $get(['postal_code']),
        ];
    }

    protected function reverseGeocodeWithNominatim(float $latitude, float $longitude): ?string
    {
        try {
            $response = $this->httpClient()->withHeaders([
                'User-Agent' => config('app.name', 'Cafrepay').'/1.0 ('.config('app.url').')',
            ])->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'format' => 'json',
            ]);

            $data = $response->json();

            return $data['display_name'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('GeocodeService Nominatim reverse geocode failed: '.$e->getMessage());
        }

        return null;
    }

    public function searchAddresses(string $query, int $limit = 5): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $limit = max(1, min($limit, 10));
        $results = [];
        $apiKey = config('hyperlocal.google_maps_api_key') ?: config('services.google.place_api_key');

        if ($apiKey) {
            try {
                $response = $this->httpClient()->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'key' => $apiKey,
                ]);

                $data = $response->json();

                if (($data['status'] ?? '') === 'OK' && ! empty($data['results'])) {
                    foreach ($data['results'] as $item) {
                        if (empty($item['geometry']['location']['lat']) || empty($item['formatted_address'])) {
                            continue;
                        }

                        $results[] = [
                            'label' => $item['formatted_address'],
                            'latitude' => (float) $item['geometry']['location']['lat'],
                            'longitude' => (float) $item['geometry']['location']['lng'],
                        ];
                    }
                } elseif (! empty($data['error_message'])) {
                    Log::warning('GeocodeService address search: '.$data['error_message']);
                }
            } catch (\Throwable $e) {
                Log::warning('GeocodeService address search failed: '.$e->getMessage());
            }
        }

        if (empty($results)) {
            $results = $this->searchAddressesWithNominatim($query, $limit);
        }

        return array_slice($results, 0, $limit);
    }

    protected function searchAddressesWithNominatim(string $query, int $limit): array
    {
        try {
            $response = $this->httpClient()->withHeaders([
                'User-Agent' => config('app.name', 'Cafrepay').'/1.0 ('.config('app.url').')',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => $limit,
                'addressdetails' => 0,
            ]);

            $data = $response->json();

            if (! is_array($data)) {
                return [];
            }

            return array_values(array_filter(array_map(function ($item) {
                if (empty($item['lat']) || empty($item['lon']) || empty($item['display_name'])) {
                    return null;
                }

                return [
                    'label' => $item['display_name'],
                    'latitude' => (float) $item['lat'],
                    'longitude' => (float) $item['lon'],
                ];
            }, $data)));
        } catch (\Throwable $e) {
            Log::warning('GeocodeService Nominatim search failed: '.$e->getMessage());
        }

        return [];
    }

    public function applyToAddress(Address $address): Address
    {
        $coords = $this->geocode($address);

        if (! empty($coords)) {
            $address->latitude = $coords['latitude'];
            $address->longitude = $coords['longitude'];
            $address->save();

            if ($address->addressable_type === Shop::class && $address->addressable_id) {
                Shop::where('id', $address->addressable_id)
                    ->update(['primary_address_id' => $address->id]);
            }
        }

        return $address;
    }
}
