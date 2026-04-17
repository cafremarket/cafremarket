<?php

namespace Incevio\Package\Wallet\Simple;

use Incevio\Package\Wallet\Interfaces\Mathable;

/**
 * Class MathService.
 *
 * @deprecated Will be removed in 6.x.
 */
class Math implements Mathable
{
    /**
     * @var int
     */
    protected $scale;

    /**
     * @param  string|int|float  $first
     * @param  string|int|float  $second
     */
    public function add($first, $second, ?int $scale = null): string
    {
        return $this->round($first + $second, $this->scale($scale));
    }

    /**
     * @param  string|int|float  $first
     * @param  string|int|float  $second
     */
    public function sub($first, $second, ?int $scale = null): string
    {
        return $this->round($first - $second, $this->scale($scale));
    }

    /**
     * @param  string|int|float  $first
     * @param  string|int|float  $second
     * @return float|int|string|null
     */
    public function div($first, $second, ?int $scale = null): string
    {
        return $this->round($first / $second, $this->scale($scale));
    }

    /**
     * @param  string|int|float  $first
     * @param  string|int|float  $second
     * @return float|int|string
     */
    public function mul($first, $second, ?int $scale = null): string
    {
        return $this->round($first * $second, $this->scale($scale));
    }

    /**
     * @param  string|int|float  $first
     * @param  string|int|float  $second
     */
    public function pow($first, $second, ?int $scale = null): string
    {
        return $this->round($first ** $second, $this->scale($scale));
    }

    /**
     * @param  string|int|float  $number
     */
    public function ceil($number): string
    {
        return ceil($number);
    }

    /**
     * @param  string|int|float  $number
     */
    public function floor($number): string
    {
        return floor($number);
    }

    /**
     * @param  float|int|string  $number
     */
    public function abs($number): string
    {
        return abs($number);
    }

    /**
     * @param  string|int|float  $number
     */
    public function round($number, ?int $precision = null): string
    {
        if ($precision == null) {
            $precision = config('system_settings.decimals', 2);
        }

        return round($number, $precision);
    }

    public function compare($first, $second): int
    {
        return $first <=> $second;
    }

    protected function scale(?int $scale = null): int
    {
        if ($scale !== null) {
            return $scale;
        }

        if ($this->scale === null) {
            $this->scale = (int) config('wallet.math.scale', 64);
        }

        return $this->scale;
    }
}
