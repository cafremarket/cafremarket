<?php

namespace Incevio\Package\Wallet\Objects;

use Countable;
use Incevio\Package\Wallet\Enums\TransferStatuses;
use Incevio\Package\Wallet\Interfaces\Customer;
use Incevio\Package\Wallet\Interfaces\Mathable;
use Incevio\Package\Wallet\Interfaces\Product;
use Incevio\Package\Wallet\Models\Transfer;

use function array_unique;
use function count;
use function get_class;

class Cart implements Countable
{
    /**
     * @var Product[]
     */
    protected $items = [];

    /**
     * @var int[]
     */
    protected $quantity = [];

    /**
     * @return static
     */
    public function addItem(Product $product, int $quantity = 1): self
    {
        $this->addQuantity($product, $quantity);
        for ($i = 0; $i < $quantity; $i++) {
            $this->items[] = $product;
        }

        return $this;
    }

    /**
     * @return static
     */
    public function addItems(iterable $products): self
    {
        foreach ($products as $product) {
            $this->addItem($product);
        }

        return $this;
    }

    /**
     * @return Product[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return Product[]
     */
    public function getUniqueItems(): array
    {
        return array_unique($this->items);
    }

    /**
     * The method returns the transfers already paid for the goods.
     *
     * @return Transfer[]
     */
    public function alreadyBuy(Customer $customer, ?bool $gifts = null): array
    {
        $status = [TransferStatuses::STATUS_PAID];
        if ($gifts) {
            $status[] = TransferStatuses::STATUS_GIFT;
        }

        /**
         * @var Transfer $query
         */
        $result = [];
        $query = $customer->transfers();
        foreach ($this->getUniqueItems() as $product) {
            $collect = (clone $query)
                ->where('to_type', $product->getMorphClass())
                ->where('to_id', $product->getKey())
                ->whereIn('status', $status)
                ->orderBy('id', 'desc')
                ->limit($this->getQuantity($product))
                ->get();

            foreach ($collect as $datum) {
                $result[] = $datum;
            }
        }

        return $result;
    }

    public function canBuy(Customer $customer, ?bool $force = null): bool
    {
        foreach ($this->items as $item) {
            if (! $item->canBuy($customer, $this->getQuantity($item), $force)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int
     */
    public function getTotal(Customer $customer): string
    {
        $result = 0;
        $math = app(Mathable::class);
        foreach ($this->items as $item) {
            $result = $math->add($result, $item->getAmountProduct($customer));
        }

        return $result;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getQuantity(Product $product): int
    {
        $class = get_class($product);
        $uniq = $product->getUniqueId();

        return $this->quantity[$class][$uniq] ?? 0;
    }

    protected function addQuantity(Product $product, int $quantity): void
    {
        $class = get_class($product);
        $uniq = $product->getUniqueId();
        $math = app(Mathable::class);
        $this->quantity[$class][$uniq] = $math->add($this->getQuantity($product), $quantity);
    }
}
