<?php

namespace Incevio\Package\Wallet\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Incevio\Package\Wallet\Interfaces\Customer;
use Incevio\Package\Wallet\Traits\CanPay;

/**
 * Class User.
 *
 * @property string $name
 * @property string $email
 */
class Buyer extends Model implements Customer
{
    use CanPay;

    public function getTable(): string
    {
        return 'users';
    }
}
