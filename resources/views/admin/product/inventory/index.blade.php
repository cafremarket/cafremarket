@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.products') }}
@endsection

@section('content')
  @can('create', \App\Models\Product::class)
    @if (Auth::user()->isFromMerchant())
      {{-- Combined Product + Inventory: direct Add Product button --}}
      @unless (Auth::user()->shop && Auth::user()->shop->canAddMoreInventory())
        @include('admin.partials._max_inventory_limit_notice')
      @endunless
    @else
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.add_inventory'),
        'icon' => 'fa-plus',
        'bodyClass' => '',
        'class' => 'admin-card--collapsible collapsed-box',
      ])
        @if (Auth::user()->shop->canAddMoreInventory())
          <div class="form-group">
            <div class="input-group input-group-lg">
              <span class="input-group-addon"><i class="fa fa-search text-muted"></i></span>
              {!! Form::text('searchProduct', null, ['id' => 'searchProduct', 'class' => 'form-control', 'placeholder' => trans('app.placeholder.search_product')]) !!}
            </div>
          </div>
          <div id="productFounds"></div>
        @else
          @include('admin.partials._max_inventory_limit_notice')
        @endif
      @include('admin.partials.ui.card_end')
    @endif
  @endcan

  @include('admin.partials.ui.card_tabbed_start', [
    'title' => trans('app.products'),
    'icon' => 'fa-cube',
    'actions' => view('admin.product.inventory._header_actions')->render(),
  ])

    <ul class="nav nav-tabs nav-justified admin-tabs">
      <li class="{{ Request::has('tab') ? '' : 'active' }}">
        <a href="#active_inventory_tab" data-toggle="tab">
          <i class="fa fa-superpowers hidden-sm"></i>
          {{ trans('app.active_products') }}
        </a>
      </li>
      <li class="{{ Request::input('tab') == 'inactive_listings' ? 'active' : '' }}">
        <a href="#inactive_listings_tab" data-toggle="tab">
          <i class="fa fa-bell-o hidden-sm"></i>
          {{ trans('app.inactive_products') }}
        </a>
      </li>
      <li class="{{ Request::input('tab') == 'out_of_stock' ? 'active' : '' }}">
        <a href="#stock_out_tab" data-toggle="tab">
          <i class="fa fa-bullhorn hidden-sm"></i>
          {{ trans('app.out_of_stock') }}
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane {{ Request::has('tab') ? '' : 'active' }} responsive-table" id="active_inventory_tab">
        <table class="table table-hover admin-table" id="active_inventory">
          @include('admin.product.inventory._table_head')
          <tbody id="massSelectArea"></tbody>
        </table>
      </div>
      <div class="tab-pane {{ Request::input('tab') == 'inactive_listings' ? 'active' : '' }} responsive-table" id="inactive_listings_tab">
        <table class="table table-hover admin-table" id="inactive_inventory">
          @include('admin.product.inventory._table_head')
          <tbody id="massSelectArea2"></tbody>
        </table>
      </div>
      <div class="tab-pane {{ Request::input('tab') == 'out_of_stock' ? 'active' : '' }} responsive-table" id="stock_out_tab">
        <table class="table table-hover admin-table" id="outOfStock_inventory">
          @include('admin.product.inventory._table_head')
          <tbody id="massSelectArea3"></tbody>
        </table>
      </div>
    </div>

  @include('admin.partials.ui.card_tabbed_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.sku') }}</th>
        <th>{{ trans('app.title') }}</th>
        <th>{{ trans('app.condition') }}</th>
        <th>{{ trans('app.price') }}</th>
        <th>{{ trans('app.quantity') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            @if ($trash->image)
              <img src="{{ get_storage_file_url($trash->image->path, 'tiny') }}" class="img-sm admin-table__banner-thumb" alt="">
            @else
              <img src="{{ get_storage_file_url(optional($trash->product->image)->path, 'tiny') }}" class="img-sm admin-table__banner-thumb" alt="">
            @endif
          </td>
          <td>{{ $trash->sku }}</td>
          <td>{{ $trash->title }}</td>
          <td>{{ $trash->condition }}</td>
          <td>{{ get_formated_currency($trash->sale_price, 2, config('system_settings.currency.id')) }}</td>
          <td>{{ $trash->stock_quantity }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.stock.inventory.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.stock.inventory.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
