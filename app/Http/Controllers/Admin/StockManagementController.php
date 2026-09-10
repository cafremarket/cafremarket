<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Services\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StockManagementController extends Controller
{
    public function __construct(private StockService $stockService)
    {
        parent::__construct();
    }

    public function overview(Request $request)
    {
        Gate::authorize('index', Inventory::class);

        $shopId = Auth::user()->isFromPlatform() ? null : Auth::user()->merchantId();
        $alertQty = (int) (config('shop_settings.alert_quantity') ?? 0);

        $baseInventory = Inventory::query()
            ->whereNull('parent_id')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId));

        $stockBase = InventoryStock::query()
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->whereHas('inventory', fn ($q) => $q->whereNull('deleted_at')->whereNull('parent_id'));

        $stats = [
            'skus' => (clone $baseInventory)->count(),
            'on_hand' => (int) (clone $stockBase)->sum('quantity'),
            'reserved' => (int) (clone $stockBase)->sum('reserved_quantity'),
            'low_stock' => (int) (clone $stockBase)->lowStock()->count(),
            'out_of_stock' => (clone $baseInventory)->where('stock_quantity', '<=', 0)->count(),
            'warehouses' => \App\Models\Warehouse::query()
                ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
                ->count(),
            'inactive' => (clone $baseInventory)->where(function ($q) {
                $q->where('active', '!=', Inventory::ACTIVE)->orWhereNull('active');
            })->count(),
        ];
        $stats['available'] = max(0, $stats['on_hand'] - $stats['reserved']);

        $query = Inventory::query()
            ->with([
                'image',
                'product:id,name,downloadable',
                'product.image',
                'warehouse:id,name',
                'stocks.warehouse:id,name',
            ])
            ->withCount('variants')
            ->whereNull('parent_id')
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId));

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('warehouse_id')) {
            $warehouseId = (int) $request->warehouse_id;
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhereHas('stocks', fn ($s) => $s->where('warehouse_id', $warehouseId));
            });
        }

        $status = $request->input('status', 'all');
        if ($status === 'in_stock') {
            $query->where('stock_quantity', '>', 0)->where('active', Inventory::ACTIVE);
        } elseif ($status === 'low') {
            $query->where(function ($q) use ($alertQty) {
                $q->whereHas('stocks', fn ($s) => $s->lowStock())
                    ->orWhere(function ($q2) use ($alertQty) {
                        $q2->whereDoesntHave('stocks')
                            ->where('stock_quantity', '>', 0)
                            ->where('stock_quantity', '<=', $alertQty);
                    });
            });
        } elseif ($status === 'out') {
            $query->where('stock_quantity', '<=', 0);
        } elseif ($status === 'inactive') {
            $query->where(function ($q) {
                $q->where('active', '!=', Inventory::ACTIVE)->orWhereNull('active');
            });
        }

        if ($request->input('type') === 'digital') {
            $query->whereHas('product', fn ($p) => $p->where('downloadable', 1));
        } elseif ($request->input('type') === 'physical') {
            $query->where(function ($q) {
                $q->whereHas('product', fn ($p) => $p->where('downloadable', 0))
                    ->orWhereDoesntHave('product');
            });
        }

        $inventories = $query->latest()->paginate(24)->withQueryString();

        $warehouses = \App\Helpers\ListHelper::warehouses($shopId);

        $recentMovements = StockMovement::with(['inventory:id,title,sku', 'warehouse:id,name'])
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->latest()
            ->limit(8)
            ->get();

        $lowStockItems = InventoryStock::with(['inventory:id,title,sku', 'warehouse:id,name'])
            ->lowStock()
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->whereHas('inventory', fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('quantity')
            ->limit(8)
            ->get();

        return view('admin.stock.overview', compact(
            'stats',
            'inventories',
            'warehouses',
            'recentMovements',
            'lowStockItems',
            'alertQty',
            'status'
        ));
    }

    public function movements(Request $request)
    {
        Gate::authorize('index', Inventory::class);

        $query = StockMovement::with(['inventory:id,title,sku', 'warehouse:id,name', 'user:id,name'])
            ->latest();

        if (! Auth::user()->isFromPlatform()) {
            $query->where('shop_id', Auth::user()->merchantId());
        }

        if ($request->filled('inventory_id')) {
            $query->where('inventory_id', $request->inventory_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->paginate(50)->withQueryString();

        return view('admin.stock.movements', compact('movements'));
    }

    public function lowStock(Request $request)
    {
        Gate::authorize('index', Inventory::class);

        $query = InventoryStock::with(['inventory:id,title,sku,shop_id', 'warehouse:id,name'])
            ->lowStock()
            ->whereHas('inventory', function ($q) {
                $q->whereNull('deleted_at');
            });

        if (! Auth::user()->isFromPlatform()) {
            $query->where('shop_id', Auth::user()->merchantId());
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $stocks = $query->orderBy('quantity')->paginate(50)->withQueryString();

        return view('admin.stock.low_stock', compact('stocks'));
    }

    public function transferForm()
    {
        Gate::authorize('index', Inventory::class);

        $shopId = Auth::user()->isFromPlatform() ? null : Auth::user()->merchantId();
        $warehouses = \App\Helpers\ListHelper::warehouses($shopId);
        $inventories = Inventory::query()
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->whereNull('parent_id')
            ->orderBy('title')
            ->limit(500)
            ->pluck('title', 'id');

        return view('admin.stock._transfer', compact('warehouses', 'inventories'));
    }

    public function transfer(Request $request)
    {
        Gate::authorize('index', Inventory::class);

        $request->validate([
            'from_warehouse_id' => 'required|integer|different:to_warehouse_id',
            'to_warehouse_id' => 'required|integer',
            'inventory_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shopId = Auth::user()->merchantId() ?: Inventory::findOrFail($request->inventory_id)->shop_id;

        try {
            $this->stockService->transfer(
                (int) $shopId,
                (int) $request->from_warehouse_id,
                (int) $request->to_warehouse_id,
                [[
                    'inventory_id' => (int) $request->inventory_id,
                    'quantity' => (int) $request->quantity,
                ]],
                $request->notes
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', trans('messages.created', ['model' => trans('app.stock_transfer')]));
    }

    public function transfers()
    {
        Gate::authorize('index', Inventory::class);

        $query = StockTransfer::with(['fromWarehouse:id,name', 'toWarehouse:id,name', 'user:id,name', 'items.inventory:id,sku,title'])
            ->latest();

        if (! Auth::user()->isFromPlatform()) {
            $query->where('shop_id', Auth::user()->merchantId());
        }

        $transfers = $query->paginate(40);

        return view('admin.stock.transfers', compact('transfers'));
    }

    public function adjust(Request $request, Inventory $inventory)
    {
        $this->authorize('update', $inventory);

        $request->validate([
            'warehouse_id' => 'required|integer',
            'quantity' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->stockService->setStock(
            $inventory,
            (int) $request->warehouse_id,
            (int) $request->quantity,
            \App\Models\StockMovement::TYPE_ADJUST,
            $request->notes ?? 'Manual stock adjustment'
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'stock_quantity' => $inventory->fresh()->stock_quantity,
            ]);
        }

        return back()->with('success', trans('messages.updated', ['model' => trans('app.model.inventory')]));
    }
}
