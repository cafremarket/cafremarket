<?php

namespace Incevio\Package\Wallet\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Incevio\Package\Wallet\Interfaces\Confirmable;
use Incevio\Package\Wallet\Interfaces\Wallet;
use Incevio\Package\Wallet\Traits\CanConfirm;
use Incevio\Package\Wallet\Traits\HasWallet;

/**
 * Class UserConfirm.
 *
 * @property string $name
 * @property string $email
 */
class UserConfirm extends Model implements Confirmable, Wallet
{
    use CanConfirm, HasWallet;

    /**
     * @var array
     */
    protected $fillable = ['name', 'email'];

    public function getTable(): string
    {
        return 'users';
    }
}
