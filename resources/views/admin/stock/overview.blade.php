@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.stock_overview') }}
@endsection

@section('page-style')
  <link href="{{ asset('css/stock-overview.css') }}?v={{ @filemtime(public_path('css/stock-overview.css')) ?: time() }}" rel="stylesheet">
@endsection

@section('content')
@php
  $status = $status ?? request('status', 'all');
  $filters = request()->only(['q', 'warehouse_id', 'type', 'status']);
@endphp

<div class="so-page">
  <div class="so-hero">
    <div class="so-hero__copy">
      <p class="so-hero__eyebrow">{{ trans('app.stock') }}</p>
      <h1 class="so-hero__title">{{ trans('nav.stock_overview') }}</h1>
      <p class="so-hero__sub">{{ trans('help.stock_overview_intro') }}</p>
    </div>
    <div class="so-hero__actions">
      @can('create', \App\Models\Inventory::class)
          <a href="{{ route('admin.stock.product.create') }}" class="so-btn so-btn--primary">
            <i class="fa fa-plus"></i> {{ trans('app.add_inventory') }}
          </a>
        @endcan
        <a href="javascript:void(0)" data-link="{{ route('admin.stock.transfer.create') }}" class="so-btn so-btn--ghost ajax-modal-btn">
          <i class="fa fa-exchange"></i> {{ trans('app.transfer_stock') }}
        </a>
        <a href="{{ route('admin.stock.movements') }}" class="so-btn so-btn--ghost">
          <i class="fa fa-history"></i> {{ trans('app.stock_movements') }}
        </a>
      </div>
    </div>

  <div class="so-kpi-grid">
    <a href="{{ route('admin.stock.overview', array_merge($filters, ['status' => 'all'])) }}" class="so-kpi {{ $status === 'all' ? 'is-active' : '' }}">
      <span class="so-kpi__label">{{ trans('app.skus') ?? 'SKUs' }}</span>
      <span class="so-kpi__value">{{ number_format($stats['skus']) }}</span>
    </a>
    <div class="so-kpi so-kpi--green">
      <span class="so-kpi__label">{{ trans('app.on_hand') }}</span>
      <span class="so-kpi__value">{{ number_format($stats['on_hand']) }}</span>
      <span class="so-kpi__meta">{{ trans('app.available') }}: {{ number_format($stats['available']) }}</span>
    </div>
    <div class="so-kpi so-kpi--blue">
      <span class="so-kpi__label">{{ trans('app.reserved') }}</span>
      <span class="so-kpi__value">{{ number_format($stats['reserved']) }}</span>
    </div>
    <a href="{{ route('admin.stock.overview', array_merge($filters, ['status' => 'low'])) }}" class="so-kpi so-kpi--amber {{ $status === 'low' ? 'is-active' : '' }}">
      <span class="so-kpi__label">{{ trans('app.low_stock') }}</span>
      <span class="so-kpi__value">{{ number_format($stats['low_stock']) }}</span>
    </a>
    <a href="{{ route('admin.stock.overview', array_merge($filters, ['status' => 'out'])) }}" class="so-kpi so-kpi--red {{ $status === 'out' ? 'is-active' : '' }}">
      <span class="so-kpi__label">{{ trans('app.out_of_stock') }}</span>
      <span class="so-kpi__value">{{ number_format($stats['out_of_stock']) }}</span>
    </a>
    <a href="{{ route('admin.stock.warehouse.index') }}" class="so-kpi">
      <span class="so-kpi__label">{{ trans('app.warehouses') }}</span>
      <span class="so-kpi__value">{{ number_format($stats['warehouses']) }}</span>
    </a>
  </div>

  <div class="so-layout">
    <div class="so-main">
      <form method="get" action="{{ route('admin.stock.overview') }}" class="so-filters">
        <div class="so-filters__search">
          <i class="fa fa-search"></i>
          <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ trans('app.search') }} SKU / {{ trans('app.title') }}" autocomplete="off">
        </div>
        <select name="warehouse_id" class="so-select">
          <option value="">{{ trans('app.all') }} {{ trans('app.warehouses') }}</option>
          @foreach ($warehouses as $id => $name)
            <option value="{{ $id }}" {{ (string) request('warehouse_id') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
          @endforeach
        </select>
        <select name="status" class="so-select">
          <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ trans('app.all') }}</option>
          <option value="in_stock" {{ $status === 'in_stock' ? 'selected' : '' }}>{{ trans('app.active_stocks') }}</option>
          <option value="low" {{ $status === 'low' ? 'selected' : '' }}>{{ trans('app.low_stock') }}</option>
          <option value="out" {{ $status === 'out' ? 'selected' : '' }}>{{ trans('app.out_of_stock') }}</option>
          <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>{{ trans('app.inactive_stocks') }}</option>
        </select>
        <select name="type" class="so-select">
          <option value="">{{ trans('app.all') }} {{ trans('app.type') }}</option>
          <option value="physical" {{ request('type') === 'physical' ? 'selected' : '' }}>{{ trans('nav.physical_products') }}</option>
          <option value="digital" {{ request('type') === 'digital' ? 'selected' : '' }}>{{ trans('nav.digital_products') }}</option>
        </select>
        <button type="submit" class="so-btn so-btn--primary">{{ trans('app.filter') }}</button>
        @if (request()->hasAny(['q', 'warehouse_id', 'status', 'type']) && (request('status') !== 'all' || request('q') || request('warehouse_id') || request('type')))
          <a href="{{ route('admin.stock.overview') }}" class="so-btn so-btn--ghost">{{ trans('app.reset') ?? 'Reset' }}</a>
        @endif
      </form>

      <div class="so-list">
        @forelse ($inventories as $inventory)
          @php
            $stocks = $inventory->stocks;
            $available = $stocks->sum(fn ($s) => $s->availableQuantity());
            if ($stocks->isEmpty()) {
              $available = (int) $inventory->stock_quantity;
            }
            $isLow = $stocks->contains(fn ($s) => $s->isLowStock())
              || ($stocks->isEmpty() && $inventory->stock_quantity > 0 && $inventory->stock_quantity <= $alertQty);
            $isOut = (int) $inventory->stock_quantity <= 0;
            $badge = $isOut ? 'out' : ($isLow ? 'low' : (($inventory->active ?? 1) ? 'ok' : 'off'));
            $badgeLabel = $isOut
              ? trans('app.out_of_stock')
              : ($isLow ? trans('app.low_stock') : (($inventory->active ?? 1) ? trans('app.in_stock') : trans('app.inactive')));
          @endphp
          <article class="so-card">
            <div class="so-card__media">
              @if ($inventory->image)
                <img src="{{ get_storage_file_url($inventory->image->path, 'small') }}" alt="">
              @elseif (optional($inventory->product)->image)
                <img src="{{ get_storage_file_url($inventory->product->image->path, 'small') }}" alt="">
              @else
                <div class="so-card__placeholder"><i class="fa fa-cube"></i></div>
              @endif
            </div>
            <div class="so-card__body">
              <div class="so-card__top">
                <div>
                  <div class="so-card__sku">{{ $inventory->sku }}</div>
                  <h3 class="so-card__title">{{ $inventory->title }}</h3>
                  @if ($inventory->product)
                    <div class="so-card__product">{{ $inventory->product->name }}</div>
                  @endif
                </div>
                <span class="so-badge so-badge--{{ $badge }}">{{ $badgeLabel }}</span>
              </div>

              <div class="so-card__warehouses">
                @forelse ($stocks as $stock)
                  <span class="so-chip" title="{{ trans('app.reserved') }}: {{ $stock->reserved_quantity }}">
                    <strong>{{ optional($stock->warehouse)->name ?? '—' }}</strong>
                    {{ $stock->quantity }}
                  </span>
                @empty
                  @if ($inventory->warehouse)
                    <span class="so-chip">
                      <strong>{{ $inventory->warehouse->name }}</strong>
                      {{ $inventory->stock_quantity }}
                    </span>
                  @else
                    <span class="so-chip so-chip--muted">{{ trans('app.no_warehouse_found') }}</span>
                  @endif
                @endforelse
              </div>

              <div class="so-card__metrics">
                <div><span>{{ trans('app.on_hand') }}</span><strong>{{ $inventory->stock_quantity }}</strong></div>
                <div><span>{{ trans('app.available') }}</span><strong>{{ $available }}</strong></div>
                <div><span>{{ trans('app.reserved') }}</span><strong>{{ $stocks->sum('reserved_quantity') }}</strong></div>
                @if ($inventory->variants_count)
                  <div><span>{{ trans('app.skus') ?? 'Variants' }}</span><strong>{{ $inventory->variants_count }}</strong></div>
                @endif
              </div>
            </div>
            <div class="so-card__actions">
              @can('update', $inventory)
                <a href="javascript:void(0)" data-link="{{ route('admin.stock.inventory.editQtt', $inventory->id) }}" class="so-icon-btn ajax-modal-btn" title="{{ trans('app.update') }} {{ trans('app.quantity') }}">
                  <i class="fa fa-sliders"></i>
                </a>
                <a href="{{ route('admin.stock.inventory.edit', $inventory->id) }}" class="so-icon-btn" title="{{ trans('app.edit') }}">
                  <i class="fa fa-pencil"></i>
                </a>
              @endcan
              <a href="javascript:void(0)" data-link="{{ route('admin.stock.inventory.show', $inventory->id) }}" class="so-icon-btn ajax-modal-btn" title="{{ trans('app.detail') }}">
                <i class="fa fa-expand"></i>
              </a>
            </div>
          </article>
        @empty
          <div class="so-empty">
            <i class="fa fa-cubes"></i>
            <h3>{{ trans('app.no_data_found') }}</h3>
            <p>{{ trans('help.stock_overview_empty') }}</p>
            @can('create', \App\Models\Inventory::class)
              <a href="{{ route('admin.stock.product.create') }}" class="so-btn so-btn--primary">
                {{ trans('app.add_inventory') }}
              </a>
            @endcan
          </div>
        @endforelse
      </div>

      @if ($inventories->hasPages())
        <div class="so-pagination">
          {{ $inventories->links() }}
        </div>
      @endif
    </div>

    <aside class="so-aside">
      <section class="so-aside-card">
        <header>
          <h2>{{ trans('app.low_stock') }}</h2>
          <a href="{{ route('admin.stock.low') }}">{{ trans('app.view_all') ?? 'View all' }}</a>
        </header>
        <ul class="so-aside-list">
          @forelse ($lowStockItems as $row)
            <li>
              <div>
                <strong>{{ optional($row->inventory)->sku }}</strong>
                <span>{{ optional($row->warehouse)->name }}</span>
              </div>
              <em>{{ $row->availableQuantity() }}</em>
            </li>
          @empty
            <li class="is-empty">{{ trans('app.no_data_found') }}</li>
          @endforelse
        </ul>
      </section>

      <section class="so-aside-card">
        <header>
          <h2>{{ trans('app.stock_movements') }}</h2>
          <a href="{{ route('admin.stock.movements') }}">{{ trans('app.view_all') ?? 'View all' }}</a>
        </header>
        <ul class="so-aside-list">
          @forelse ($recentMovements as $movement)
            <li>
              <div>
                <strong>{{ optional($movement->inventory)->sku }}</strong>
                <span>{{ trans('app.stock_type_'.$movement->type) }} · {{ optional($movement->warehouse)->name }}</span>
              </div>
              <em class="{{ $movement->quantity >= 0 ? 'is-up' : 'is-down' }}">
                {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
              </em>
            </li>
          @empty
            <li class="is-empty">{{ trans('app.no_data_found') }}</li>
          @endforelse
        </ul>
      </section>

      <section class="so-aside-card so-aside-card--links">
        <a href="{{ route('admin.stock.warehouse.index') }}"><i class="fa fa-building"></i> {{ trans('nav.warehouses') }}</a>
        <a href="{{ route('admin.stock.transfers') }}"><i class="fa fa-random"></i> {{ trans('nav.stock_transfers') }}</a>
        <a href="{{ route('admin.stock.low') }}"><i class="fa fa-warning"></i> {{ trans('nav.low_stock') }}</a>
        <a href="{{ route('admin.stock.inventory.index', ['type' => 'digital']) }}"><i class="fa fa-cloud-download"></i> {{ trans('nav.digital_products') }}</a>
      </section>
    </aside>
  </div>
</div>
@endsection
