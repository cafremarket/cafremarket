<?php

namespace Incevio\Package\Wallet\Test\Models;

use Illuminate\Database\Eloquent\Model;
use Incevio\Package\Wallet\Interfaces\Customer;
use Incevio\Package\Wallet\Interfaces\Product;
use Incevio\Package\Wallet\Services\WalletService;
use Incevio\Package\Wallet\Test\Common\Models\Wallet;
use Incevio\Package\Wallet\Traits\HasWallet;

/**
 * Class Item.
 *
 * @property string $name
 * @property int $quantity
 * @property int $price
 */
class Item extends Model implements Product
{
    use HasWallet;

    /**
     * @var array
     */
    protected $fillable = ['name', 'quantity', 'price'];

    public function canBuy(Customer $customer, int $quantity = 1, ?bool $force = null): bool
    {
        $result = $this->quantity >= $quantity;

        if ($force) {
            return $result;
        }

        return $result && ! $customer->paid($this);
    }

    /**
     * @return float|int
     */
    public function getAmountProduct(Customer $customer)
    {
        /**
         * @var Wallet $wallet
         */
        $wallet = app(WalletService::class)->getWallet($customer);

        return $this->price + $wallet->holder_id;
    }

    public function getMetaProduct(): ?array
    {
        return null;
    }

    public function getUniqueId(): string
    {
        return $this->getKey();
    }
}
