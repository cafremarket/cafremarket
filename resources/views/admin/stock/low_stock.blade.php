@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.low_stock') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.low_stock'),
    'icon' => 'fa-warning',
  ])

  <table class="table table-hover admin-table">
    <thead>
      <tr>
        <th>{{ trans('app.inventory') }}</th>
        <th>{{ trans('app.sku') }}</th>
        <th>{{ trans('app.warehouse') }}</th>
        <th>{{ trans('app.on_hand') }}</th>
        <th>{{ trans('app.reserved') }}</th>
        <th>{{ trans('app.available') }}</th>
        <th>{{ trans('app.reorder_level') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($stocks as $stock)
        <tr>
          <td>{{ optional($stock->inventory)->title }}</td>
          <td>{{ optional($stock->inventory)->sku }}</td>
          <td>{{ optional($stock->warehouse)->name }}</td>
          <td>{{ $stock->quantity }}</td>
          <td>{{ $stock->reserved_quantity }}</td>
          <td>{{ $stock->availableQuantity() }}</td>
          <td>{{ $stock->reorder_level ?? (config('shop_settings.alert_quantity') ?? 0) }}</td>
          <td>
            @if ($stock->inventory)
              <a href="javascript:void(0)" data-link="{{ route('admin.stock.inventory.editQtt', $stock->inventory_id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.update') }}">
                <i class="fa fa-edit"></i>
              </a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-center">{{ trans('app.no_data_found') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  {{ $stocks->links() }}

  @include('admin.partials.ui.card_end')
@endsection
