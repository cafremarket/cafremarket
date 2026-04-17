<?php

namespace Incevio\Package\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Incevio\Package\Wallet\Interfaces\Confirmable;
use Incevio\Package\Wallet\Interfaces\Customer;
use Incevio\Package\Wallet\Interfaces\Exchangeable;
use Incevio\Package\Wallet\Interfaces\WalletFloat;
use Incevio\Package\Wallet\Models\Transaction as TransactionModel;
use Incevio\Package\Wallet\Services\WalletService;
use Incevio\Package\Wallet\Traits\CanConfirm;
use Incevio\Package\Wallet\Traits\CanExchange;
use Incevio\Package\Wallet\Traits\CanPayFloat;
use Incevio\Package\Wallet\Traits\HasGift;

use function app;
use function array_key_exists;
use function array_merge;
use function config;

/**
 * Class Wallet.
 *
 * @property string $holder_type
 * @property int $holder_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property int $balance
 * @property \Incevio\Package\Wallet\Interfaces\Wallet $holder
 * @property-read string $currency
 */
class Wallet extends Model implements Confirmable, Customer, Exchangeable, WalletFloat
{
    use CanConfirm;
    use CanExchange;
    use CanPayFloat;
    use HasGift;

    /**
     * @var array
     */
    protected $fillable = [
        'holder_type',
        'holder_id',
        'name',
        'slug',
        'description',
        'balance',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'blocked' => 'bool',
    ];

    /**
     * {@inheritdoc}
     */
    public function getCasts(): array
    {
        return array_merge(
            parent::getCasts(),
            config('wallet.wallet.casts', [])
        );
    }

    public function getTable(): string
    {
        if (! $this->table) {
            $this->table = config('wallet.wallet.table', 'wallets');
        }

        return parent::getTable();
    }

    public function setNameAttribute(string $name): void
    {
        $this->attributes['name'] = $name;

        /**
         * Must be updated only if the model does not exist
         *  or the slug is empty.
         */
        if (! $this->exists && ! array_key_exists('slug', $this->attributes)) {
            $this->attributes['slug'] = Str::slug($name);
        }
    }

    public function refreshBalance(): bool
    {
        return app(WalletService::class)->refresh($this);
    }

    /**
     * @return float|int
     */
    public function getAvailableBalance()
    {
        return $this->transactions()
            ->where('wallet_id', $this->getKey())
            ->where('confirmed', true)
            ->sum('amount');
    }

    /**
     * @return int|mixed
     */
    public function getPendingBalance()
    {
        return $this->transactions()
            ->where([
                ['wallet_id', '=', $this->getKey()],
                ['confirmed', '=', false],
                ['amount', '>', 0],
            ])
            // ->where('confirmed', false)
            // ->where(function ($query) {
            //     $query->where('meta->order_id', '!=', null)
            //         ->orWhere('meta', 'like', '%"order_id"%');
            // })
            ->sum('amount');
    }

    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany
     */
    // public function transactions(): HasMany
    // {
    //     return $this->hasMany(config('wallet.transaction.model', TransactionModel::class));
    // }

    public function getCurrencyAttribute(): string
    {
        $currencies = config('wallet.currencies', []);

        return $currencies[$this->slug] ?? Str::upper($this->slug);
    }
}
