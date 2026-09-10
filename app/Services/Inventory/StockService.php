<?php

namespace App\Services\Inventory;

use App\Models\Inventory;
use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    /**
     * Sync warehouse stock rows from admin form input and refresh listing cache.
     *
     * @param  array<int|string, int|string>  $warehouseQuantities  warehouse_id => qty
     * @param  array<int|string, int|string|null>  $reorderLevels
     * @param  array<int|string, int|string>  $damagedQuantities
     */
    public function syncWarehouseStocks(
        Inventory $inventory,
        array $warehouseQuantities,
        array $reorderLevels = [],
        array $damagedQuantities = [],
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($inventory, $warehouseQuantities, $reorderLevels, $damagedQuantities, $notes) {
            $keep = [];

            foreach ($warehouseQuantities as $warehouseId => $qty) {
                $warehouseId = (int) $warehouseId;
                if ($warehouseId <= 0) {
                    continue;
                }

                $keep[] = $warehouseId;
                $this->setStock(
                    $inventory,
                    $warehouseId,
                    (int) $qty,
                    StockMovement::TYPE_ADJUST,
                    $notes ?? 'Synced warehouse stock',
                    null,
                    array_key_exists($warehouseId, $reorderLevels) ? (int) $reorderLevels[$warehouseId] : null,
                    array_key_exists($warehouseId, $damagedQuantities) ? (int) $damagedQuantities[$warehouseId] : null
                );
            }

            if (! empty($keep)) {
                InventoryStock::query()
                    ->where('inventory_id', $inventory->id)
                    ->whereNotIn('warehouse_id', $keep)
                    ->get()
                    ->each(function (InventoryStock $stock) use ($inventory, $notes) {
                        $this->recordMovement(
                            $inventory,
                            $stock->warehouse_id,
                            StockMovement::TYPE_OUT,
                            -(int) $stock->quantity,
                            (int) $stock->quantity,
                            0,
                            $notes ?? 'Removed warehouse stock',
                            null
                        );
                        $stock->delete();
                    });

                $inventory->warehouse_id = $keep[0];
                $inventory->saveQuietly();
            }

            $this->syncStockCache($inventory);
        });
    }

    /**
     * Ensure at least one stock row exists (e.g. after create with flat stock_quantity).
     */
    public function ensurePrimaryStock(Inventory $inventory, ?int $warehouseId = null, ?int $quantity = null): InventoryStock
    {
        $warehouseId = $warehouseId ?: $this->resolvePrimaryWarehouseId($inventory);
        if (! $warehouseId) {
            throw new InvalidArgumentException('No warehouse available for inventory stock.');
        }

        $stock = InventoryStock::firstOrNew([
            'inventory_id' => $inventory->id,
            'warehouse_id' => $warehouseId,
        ]);

        if (! $stock->exists) {
            $stock->shop_id = $inventory->shop_id;
            $stock->quantity = $quantity !== null ? $quantity : (int) $inventory->stock_quantity;
            $stock->reserved_quantity = 0;
            $stock->damaged_quantity = (int) ($inventory->damaged_quantity ?? 0);
            $stock->save();

            $this->recordMovement(
                $inventory,
                $warehouseId,
                StockMovement::TYPE_IN,
                (int) $stock->quantity,
                0,
                (int) $stock->quantity,
                'Initial stock',
                null
            );
        } elseif ($quantity !== null && (int) $stock->quantity !== $quantity) {
            $this->setStock($inventory, $warehouseId, $quantity, StockMovement::TYPE_ADJUST, 'Stock quantity update');
            $stock->refresh();
        }

        if (! $inventory->warehouse_id) {
            $inventory->warehouse_id = $warehouseId;
            $inventory->saveQuietly();
        }

        $this->syncStockCache($inventory);

        return $stock;
    }

    public function setStock(
        Inventory $inventory,
        int $warehouseId,
        int $quantity,
        string $type = StockMovement::TYPE_ADJUST,
        ?string $notes = null,
        ?Model $reference = null,
        ?int $reorderLevel = null,
        ?int $damagedQuantity = null
    ): InventoryStock {
        $quantity = max(0, $quantity);

        $stock = InventoryStock::firstOrNew([
            'inventory_id' => $inventory->id,
            'warehouse_id' => $warehouseId,
        ]);

        $before = (int) ($stock->quantity ?? 0);
        $stock->shop_id = $inventory->shop_id;
        $stock->quantity = $quantity;
        if ($reorderLevel !== null) {
            $stock->reorder_level = $reorderLevel;
        }
        if ($damagedQuantity !== null) {
            $stock->damaged_quantity = max(0, $damagedQuantity);
        }
        $stock->reserved_quantity = (int) ($stock->reserved_quantity ?? 0);
        $stock->save();

        $delta = $quantity - $before;
        if ($delta !== 0) {
            $this->recordMovement(
                $inventory,
                $warehouseId,
                $type,
                $delta,
                $before,
                $quantity,
                $notes,
                $reference
            );
        }

        $this->syncStockCache($inventory);

        return $stock;
    }

    public function adjust(
        Inventory $inventory,
        int $warehouseId,
        int $delta,
        string $type = StockMovement::TYPE_ADJUST,
        ?string $notes = null,
        ?Model $reference = null
    ): InventoryStock {
        $stock = InventoryStock::firstOrCreate(
            [
                'inventory_id' => $inventory->id,
                'warehouse_id' => $warehouseId,
            ],
            [
                'shop_id' => $inventory->shop_id,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
            ]
        );

        $before = (int) $stock->quantity;
        $after = max(0, $before + $delta);
        $stock->quantity = $after;
        $stock->save();

        $this->recordMovement(
            $inventory,
            $warehouseId,
            $type,
            $after - $before,
            $before,
            $after,
            $notes,
            $reference
        );

        $this->syncStockCache($inventory);

        return $stock;
    }

    public function transfer(
        int $shopId,
        int $fromWarehouseId,
        int $toWarehouseId,
        array $items,
        ?string $notes = null
    ): StockTransfer {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new InvalidArgumentException('Source and destination warehouses must differ.');
        }

        return DB::transaction(function () use ($shopId, $fromWarehouseId, $toWarehouseId, $items, $notes) {
            $transfer = StockTransfer::create([
                'shop_id' => $shopId,
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'user_id' => Auth::id(),
                'status' => StockTransfer::STATUS_COMPLETED,
                'notes' => $notes,
                'completed_at' => now(),
            ]);

            foreach ($items as $item) {
                $inventoryId = (int) ($item['inventory_id'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 0);
                if ($inventoryId <= 0 || $qty <= 0) {
                    continue;
                }

                $inventory = Inventory::findOrFail($inventoryId);
                $fromStock = InventoryStock::where('inventory_id', $inventoryId)
                    ->where('warehouse_id', $fromWarehouseId)
                    ->lockForUpdate()
                    ->first();

                if (! $fromStock || $fromStock->availableQuantity() < $qty) {
                    throw new InvalidArgumentException(
                        trans('app.stock_transfer_insufficient', ['sku' => $inventory->sku])
                    );
                }

                $this->adjust(
                    $inventory,
                    $fromWarehouseId,
                    -$qty,
                    StockMovement::TYPE_TRANSFER_OUT,
                    $notes ?? 'Stock transfer out',
                    $transfer
                );

                $this->adjust(
                    $inventory,
                    $toWarehouseId,
                    $qty,
                    StockMovement::TYPE_TRANSFER_IN,
                    $notes ?? 'Stock transfer in',
                    $transfer
                );

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'inventory_id' => $inventoryId,
                    'quantity' => $qty,
                ]);
            }

            return $transfer->load('items');
        });
    }

    /**
     * Deduct stock for a sale (order create). Prefer preferred warehouse when possible.
     */
    public function sell(Inventory $inventory, int $quantity, ?int $preferredWarehouseId = null, ?Model $reference = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        $warehouseId = $this->pickWarehouseForQuantity($inventory, $quantity, $preferredWarehouseId);
        if (! $warehouseId) {
            // Fall back to primary / create row so checkout never hard-fails like legacy decrement.
            $warehouseId = $this->resolvePrimaryWarehouseId($inventory);
            if (! $warehouseId) {
                $inventory->decrement('stock_quantity', $quantity);

                return;
            }
            $this->ensurePrimaryStock($inventory, $warehouseId, (int) $inventory->stock_quantity);
        }

        $this->adjust(
            $inventory,
            $warehouseId,
            -$quantity,
            StockMovement::TYPE_SALE,
            'Order sale',
            $reference
        );
    }

    public function restock(Inventory $inventory, int $quantity, ?int $preferredWarehouseId = null, ?Model $reference = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        $warehouseId = $preferredWarehouseId
            ?: $this->resolvePrimaryWarehouseId($inventory)
            ?: InventoryStock::where('inventory_id', $inventory->id)->value('warehouse_id');

        if (! $warehouseId) {
            $inventory->increment('stock_quantity', $quantity);

            return;
        }

        $this->adjust(
            $inventory,
            (int) $warehouseId,
            $quantity,
            StockMovement::TYPE_REFUND,
            'Stock restored',
            $reference
        );
    }

    public function reserve(Inventory $inventory, int $quantity, ?int $preferredWarehouseId = null, ?Model $reference = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        $warehouseId = $this->pickWarehouseForQuantity($inventory, $quantity, $preferredWarehouseId)
            ?: $this->resolvePrimaryWarehouseId($inventory);

        if (! $warehouseId) {
            return;
        }

        $stock = InventoryStock::firstOrCreate(
            ['inventory_id' => $inventory->id, 'warehouse_id' => $warehouseId],
            ['shop_id' => $inventory->shop_id, 'quantity' => (int) $inventory->stock_quantity]
        );

        $before = (int) $stock->reserved_quantity;
        $stock->reserved_quantity = $before + $quantity;
        $stock->save();

        $this->recordMovement(
            $inventory,
            $warehouseId,
            StockMovement::TYPE_RESERVE,
            $quantity,
            $before,
            (int) $stock->reserved_quantity,
            'Stock reserved',
            $reference
        );

        $this->syncStockCache($inventory);
    }

    public function release(Inventory $inventory, int $quantity, ?int $preferredWarehouseId = null, ?Model $reference = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        $stock = null;
        if ($preferredWarehouseId) {
            $stock = InventoryStock::where('inventory_id', $inventory->id)
                ->where('warehouse_id', $preferredWarehouseId)
                ->first();
        }
        if (! $stock) {
            $stock = InventoryStock::where('inventory_id', $inventory->id)
                ->where('reserved_quantity', '>', 0)
                ->orderByDesc('reserved_quantity')
                ->first();
        }
        if (! $stock) {
            return;
        }

        $before = (int) $stock->reserved_quantity;
        $stock->reserved_quantity = max(0, $before - $quantity);
        $stock->save();

        $this->recordMovement(
            $inventory,
            (int) $stock->warehouse_id,
            StockMovement::TYPE_RELEASE,
            -min($quantity, $before),
            $before,
            (int) $stock->reserved_quantity,
            'Stock reservation released',
            $reference
        );

        $this->syncStockCache($inventory);
    }

    public function syncStockCache(Inventory $inventory): void
    {
        $totals = InventoryStock::query()
            ->where('inventory_id', $inventory->id)
            ->selectRaw('COALESCE(SUM(quantity), 0) as qty, COALESCE(SUM(damaged_quantity), 0) as damaged')
            ->first();

        $inventory->stock_quantity = (int) ($totals->qty ?? 0);
        $inventory->damaged_quantity = (int) ($totals->damaged ?? 0);
        $inventory->saveQuietly();
    }

    public function pickWarehouseForQuantity(Inventory $inventory, int $quantity, ?int $preferredWarehouseId = null): ?int
    {
        $stocks = InventoryStock::query()
            ->where('inventory_id', $inventory->id)
            ->orderBy('id')
            ->get();

        if ($stocks->isEmpty()) {
            return null;
        }

        if ($preferredWarehouseId) {
            $preferred = $stocks->firstWhere('warehouse_id', $preferredWarehouseId);
            if ($preferred && $preferred->availableQuantity() >= $quantity) {
                return (int) $preferred->warehouse_id;
            }
        }

        $primaryId = $this->normalizeWarehouseId($inventory->warehouse_id);
        if ($primaryId) {
            $primary = $stocks->firstWhere('warehouse_id', $primaryId);
            if ($primary && $primary->availableQuantity() >= $quantity) {
                return (int) $primary->warehouse_id;
            }
        }

        $fit = $stocks->first(fn (InventoryStock $s) => $s->availableQuantity() >= $quantity);

        return $fit ? (int) $fit->warehouse_id : (int) $stocks->first()->warehouse_id;
    }

    public function resolvePrimaryWarehouseId(Inventory $inventory): ?int
    {
        $id = $this->normalizeWarehouseId($inventory->warehouse_id);
        if ($id) {
            return $id;
        }

        $fromStock = InventoryStock::where('inventory_id', $inventory->id)->value('warehouse_id');
        if ($fromStock) {
            return (int) $fromStock;
        }

        if (! $inventory->shop_id) {
            return null;
        }

        $default = DB::table('configs')
            ->where('shop_id', $inventory->shop_id)
            ->value('default_warehouse_id');
        if ($default) {
            return (int) $default;
        }

        $existing = Warehouse::where('shop_id', $inventory->shop_id)->orderBy('id')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        // Auto-create a primary warehouse so stock can be tracked immediately.
        $warehouse = Warehouse::create([
            'shop_id' => $inventory->shop_id,
            'name' => 'Main',
            'active' => 1,
        ]);

        DB::table('configs')
            ->where('shop_id', $inventory->shop_id)
            ->whereNull('default_warehouse_id')
            ->update(['default_warehouse_id' => $warehouse->id]);

        return (int) $warehouse->id;
    }

    public function normalizeWarehouseId($value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        if (is_array($value)) {
            $first = reset($value);

            return $first ? (int) $first : null;
        }

        if (is_string($value) && function_exists('is_serialized') && is_serialized($value)) {
            $decoded = @unserialize($value);
            if (is_array($decoded)) {
                $first = reset($decoded);

                return $first ? (int) $first : null;
            }
            if (is_numeric($decoded)) {
                return (int) $decoded;
            }
        }

        return is_numeric($value) ? (int) $value : null;
    }

    protected function recordMovement(
        Inventory $inventory,
        ?int $warehouseId,
        string $type,
        int $quantity,
        int $before,
        int $after,
        ?string $notes = null,
        ?Model $reference = null
    ): void {
        if ($quantity === 0) {
            return;
        }

        StockMovement::create([
            'shop_id' => $inventory->shop_id,
            'inventory_id' => $inventory->id,
            'warehouse_id' => $warehouseId,
            'user_id' => Auth::id(),
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
        ]);
    }
}
