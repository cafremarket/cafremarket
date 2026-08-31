@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.digital_products') }}
@endsection

@section('content')
  @can('create', \App\Models\Inventory::class)
    @include('admin.inventory._add')
  @endcan

  @include('admin.partials.ui.card_tabbed_start', [
    'title' => trans('app.digital_products'),
    'icon' => 'fa-cloud-download',
  ])

    <ul class="nav nav-tabs nav-justified admin-tabs">
      <li class="{{ Request::has('tab') ? '' : 'active' }}">
        <a href="#active_inventory_tab" data-toggle="tab">
          <i class="fa fa-superpowers hidden-sm"></i>
          {{ trans('app.active_stocks') }}
        </a>
      </li>
      <li class="{{ Request::input('tab') == 'inactive_listings' ? 'active' : '' }}">
        <a href="#inactive_listings_tab" data-toggle="tab">
          <i class="fa fa-bell-o hidden-sm"></i>
          {{ trans('app.inactive_stocks') }}
        </a>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane {{ Request::has('tab') ? '' : 'active' }} responsive-table" id="active_inventory_tab">
        <table class="table table-hover admin-table" id="active_inventory">
          @include('admin.inventory._table_head_digital')
          <tbody id="massSelectArea"></tbody>
        </table>
      </div>

      <div class="tab-pane {{ Request::input('tab') == 'inactive_listings' ? 'active' : '' }} responsive-table" id="inactive_listings_tab">
        <table class="table table-hover admin-table" id="inactive_inventory">
          @include('admin.inventory._table_head_digital', ['qtyColumn' => true])
          <tbody id="massSelectArea2"></tbody>
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
        <th>{{ trans('app.sale_price') }} <small>( {{ trans('app.excl_tax') }} )</small></th>
        <th>{{ trans('app.download_limit') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            @if ($trash->image)
              <img src="{{ get_storage_file_url($trash->image->path, 'tiny') }}" class="img-sm admin-table__thumb" alt="">
            @else
              <img src="{{ get_storage_file_url(optional($trash->product->image)->path, 'tiny') }}" class="img-sm admin-table__thumb" alt="">
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
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip">
                <i class="fa fa-trash-o"></i>
              </button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
