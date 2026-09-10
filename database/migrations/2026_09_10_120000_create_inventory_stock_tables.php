<?php

use App\Models\Inventory;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up a partial failed run (FK type mismatch).
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_stocks');

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->index();
            $table->unsignedBigInteger('inventory_id')->index();
            $table->unsignedInteger('warehouse_id')->index();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('damaged_quantity')->default(0);
            $table->integer('reorder_level')->nullable();
            $table->timestamps();

            $table->unique(['inventory_id', 'warehouse_id']);
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->index();
            $table->unsignedBigInteger('inventory_id')->index();
            $table->unsignedInteger('warehouse_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 32)->index();
            $table->integer('quantity');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->nullableMorphs('reference');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('shop_id')->index();
            $table->unsignedInteger('from_warehouse_id')->index();
            $table->unsignedInteger('to_warehouse_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('status', 20)->default('completed')->index();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('stock_transfer_id')->index();
            $table->unsignedBigInteger('inventory_id')->index();
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
        });

        $this->migrateLegacyStock();
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_stocks');
    }

    private function migrateLegacyStock(): void
    {
        if (! Schema::hasTable('inventories') || ! Schema::hasTable('inventory_stocks')) {
            return;
        }

        if (DB::table('inventory_stocks')->exists()) {
            return;
        }

        Inventory::withTrashed()->select([
            'id', 'shop_id', 'warehouse_id', 'stock_quantity', 'damaged_quantity',
        ])->orderBy('id')->chunkById(200, function ($inventories) {
            foreach ($inventories as $inventory) {
                $warehouseIds = $this->resolveWarehouseIds($inventory);
                if (empty($warehouseIds)) {
                    continue;
                }

                $primary = (int) $warehouseIds[0];
                $qty = (int) ($inventory->stock_quantity ?? 0);
                $damaged = (int) ($inventory->damaged_quantity ?? 0);

                foreach ($warehouseIds as $index => $warehouseId) {
                    DB::table('inventory_stocks')->updateOrInsert(
                        [
                            'inventory_id' => $inventory->id,
                            'warehouse_id' => (int) $warehouseId,
                        ],
                        [
                            'shop_id' => $inventory->shop_id,
                            'quantity' => $index === 0 ? $qty : 0,
                            'reserved_quantity' => 0,
                            'damaged_quantity' => $index === 0 ? $damaged : 0,
                            'reorder_level' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                DB::table('inventories')
                    ->where('id', $inventory->id)
                    ->update(['warehouse_id' => $primary]);
            }
        });
    }

    private function resolveWarehouseIds($inventory): array
    {
        $raw = $inventory->getAttributes()['warehouse_id'] ?? null;
        $ids = [];

        if ($raw !== null && $raw !== '') {
            if (is_string($raw) && function_exists('is_serialized') && is_serialized($raw)) {
                $decoded = @unserialize($raw);
                if (is_array($decoded)) {
                    $ids = $decoded;
                } elseif (is_numeric($decoded)) {
                    $ids = [(int) $decoded];
                }
            } elseif (is_array($raw)) {
                $ids = $raw;
            } elseif (is_numeric($raw)) {
                $ids = [(int) $raw];
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if (! empty($ids)) {
            return $ids;
        }

        if (! $inventory->shop_id) {
            return [];
        }

        $default = DB::table('configs')
            ->where('shop_id', $inventory->shop_id)
            ->value('default_warehouse_id');

        if ($default) {
            return [(int) $default];
        }

        $fallback = Warehouse::query()
            ->where('shop_id', $inventory->shop_id)
            ->orderBy('id')
            ->value('id');

        return $fallback ? [(int) $fallback] : [];
    }
};
