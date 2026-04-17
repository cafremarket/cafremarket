<?php

namespace Incevio\Package\Wallet\Interfaces;

interface Mathable
{
    /**
     * @param  int|float|string  $first
     * @param  int|float|string  $second
     */
    public function add($first, $second, ?int $scale = null): string;

    /**
     * @param  int|float|string  $first
     * @param  int|float|string  $second
     */
    public function sub($first, $second, ?int $scale = null): string;

    /**
     * @param  int|float|string  $first
     * @param  int|float|string  $second
     */
    public function div($first, $second, ?int $scale = null): string;

    /**
     * @param  int|float|string  $first
     * @param  int|float|string  $second
     */
    public function mul($first, $second, ?int $scale = null): string;

    /**
     * @param  int|float|string  $first
     * @param  int|string  $second
     */
    public function pow($first, $second, ?int $scale = null): string;

    /**
     * @param  int|float|string  $number
     */
    public function round($number, ?int $precision = null): string;

    /**
     * @param  int|float|string  $number
     */
    public function floor($number): string;

    /**
     * @param  int|float|string  $number
     */
    public function ceil($number): string;

    /**
     * @param  int|float|string  $number
     */
    public function abs($number): string;

    /**
     * @param  int|float|string  $first
     * @param  int|float|string  $second
     */
    public function compare($first, $second): int;
}
