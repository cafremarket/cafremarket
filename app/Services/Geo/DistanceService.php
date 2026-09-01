<?php

namespace App\Services\Geo;

class DistanceService
{
    private const EARTH_RADIUS_KM = 6371;

    /**
     * Haversine distance in kilometres between two points.
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_KM * $c, 4);
    }

    public function withinRadius(float $lat1, float $lng1, float $lat2, float $lng2, float $radiusKm): bool
    {
        return $this->distanceKm($lat1, $lng1, $lat2, $lng2) <= $radiusKm;
    }

    /**
     * SQL Haversine expression for Eloquent (MySQL).
     */
    public function haversineSql(string $latColumn, string $lngColumn, float $lat, float $lng): string
    {
        $lat = (float) $lat;
        $lng = (float) $lng;

        return "({$this->earthRadius()} * acos(cos(radians({$lat})) * cos(radians({$latColumn})) * cos(radians({$lngColumn}) - radians({$lng})) + sin(radians({$lat})) * sin(radians({$latColumn}))))";
    }

    public function earthRadius(): int
    {
        return self::EARTH_RADIUS_KM;
    }
}
