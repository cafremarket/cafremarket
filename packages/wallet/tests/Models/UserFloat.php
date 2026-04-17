<?php

namespace Incevio\Package\Wallet\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Incevio\Package\Wallet\Interfaces\Wallet;
use Incevio\Package\Wallet\Interfaces\WalletFloat;
use Incevio\Package\Wallet\Traits\HasWalletFloat;

/**
 * Class UserFloat.
 *
 * @property string $name
 * @property string $email
 */
class UserFloat extends Model implements Wallet, WalletFloat
{
    use HasWalletFloat;

    /**
     * @var array
     */
    protected $fillable = ['name', 'email'];

    public function getTable(): string
    {
        return 'users';
    }
}
