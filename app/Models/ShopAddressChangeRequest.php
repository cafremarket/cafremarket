<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopAddressChangeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'shop_id',
        'address_id',
        'requested_by',
        'previous_address',
        'requested_address',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'previous_address' => 'array',
        'requested_address' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function previousAddressModel(): ?Address
    {
        return $this->addressFromSnapshot($this->previous_address ?? []);
    }

    public function requestedAddressModel(): ?Address
    {
        return $this->addressFromSnapshot($this->requested_address ?? []);
    }

    protected function addressFromSnapshot(array $data): ?Address
    {
        if ($data === []) {
            return null;
        }

        $address = new Address($data);
        $address->exists = true;

        if (! empty($data['country_id'])) {
            $address->setRelation('country', Country::find($data['country_id']));
        }

        if (! empty($data['state_id'])) {
            $address->setRelation('state', State::find($data['state_id']));
        }

        return $address;
    }
}
