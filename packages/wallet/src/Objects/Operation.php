<?php

namespace Incevio\Package\Wallet\Objects;

use Incevio\Package\Wallet\Interfaces\Mathable;
// use Incevio\Package\Wallet\Interfaces\Wallet;
use Incevio\Package\Wallet\Models\Transaction;
use Ramsey\Uuid\Uuid;

class Operation
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var int
     */
    protected $amount;

    /**
     * @var int
     */
    protected $balance;

    /**
     * @var null|array
     */
    protected $meta;

    /**
     * @var bool
     */
    protected $confirmed;

    /**
     * @var bool
     */
    protected $approved;

    /**
     * @var Wallet
     */
    protected $wallet;

    /**
     * @var int
     */
    protected $order_id;

    /**
     * Transaction constructor.
     *
     * @throws
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4()->toString();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return float|int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return float|int
     */
    public function getBalance()
    {
        return $this->balance;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    /**
     * @return static
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param  int  $amount
     * @return static
     */
    public function setAmount($amount): self
    {
        $this->amount = app(Mathable::class)->round($amount, 2);

        return $this;
    }

    /**
     * @return static
     */
    public function setMeta(?array $meta): self
    {
        $this->meta = $meta;

        if (isset($meta['order_id'])) {
            $this->order_id = $meta['order_id'];
        }

        return $this;
    }

    /**
     * @return static
     */
    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;

        return $this;
    }

    public function setBalance($remainingBalance): self
    {
        $this->balance = $remainingBalance;

        return $this;
    }

    /**
     * @return static
     */
    public function setApproved(bool $approve): self
    {
        $this->approved = $approve;

        return $this;
    }

    /**
     * @return Wallet Model
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * @param  Wallet  $wallet
     * @return static
     */
    public function setWallet($wallet): self
    {
        $this->wallet = $wallet;

        return $this;
    }

    /**
     * @return Transaction Model
     */
    public function create(): Transaction
    {
        return $this->getWallet()->transactions()->create($this->toArray());
    }

    /**
     * @throws
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'wallet_id' => $this->getWallet()->getKey(),
            'uuid' => $this->getUuid(),
            'confirmed' => $this->isConfirmed(),
            'approved' => $this->isApproved(),
            'amount' => $this->getAmount(),
            'balance' => $this->getBalance(),
            'meta' => $this->getMeta(),
        ];
    }
}
