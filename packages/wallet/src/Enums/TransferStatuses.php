<?php

namespace Incevio\Package\Wallet\Enums;

use InvalidArgumentException;

class TransferStatuses
{
    public const STATUS_EXCHANGE = 'exchange';

    public const STATUS_TRANSFER = 'transfer';

    public const STATUS_PAID = 'paid';

    public const STATUS_REFUND = 'refund';

    public const STATUS_GIFT = 'gift';

    public const STATUS_REWARD = 'reward';

    /**
     * Get the constant value of the given key
     *
     * @throws InvalidArgumentException
     */
    public static function getValue(string $key): ?int
    {
        $constants = self::getConstants();

        if (! array_key_exists($key, $constants)) {
            throw new InvalidArgumentException("Invalid key: $key");
        }

        return $constants[$key];
    }

    /**
     * Get an associative array of all constants
     */
    public static function list(): array
    {
        return [
            self::STATUS_EXCHANGE => 'Exchange',
            self::STATUS_TRANSFER => 'Transfer',
            self::STATUS_PAID => 'Paid',
            self::STATUS_REFUND => 'Refund',
            self::STATUS_GIFT => 'Gift',
        ];
    }

    /**
     * Get all class constants
     */
    private static function getConstants(): array
    {
        $reflectionClass = new \ReflectionClass(__CLASS__);

        return $reflectionClass->getConstants();
    }
}
