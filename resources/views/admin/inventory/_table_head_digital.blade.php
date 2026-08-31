@php
  $inventoryModel = \App\Models\Inventory::class;
  $massActions = [
    ['url' => route('admin.stock.inventory.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
    ['url' => route('admin.stock.inventory.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
  ];
  $qtyColumn = $qtyColumn ?? false;
@endphp

<thead>
  <tr>
    @include('admin.partials.ui.mass_checkbox_header', ['model' => $inventoryModel, 'massActions' => $massActions])
    @cannot('massDelete', $inventoryModel)
      {{-- no checkbox column when mass delete not allowed --}}
    @endcannot
    <th>{{ trans('app.image') }}</th>
    <th>{{ trans('app.sku') }}</th>
    <th>{{ trans('app.title') }}</th>
    <th>{{ trans('app.condition') }}</th>
    <th style="min-width: 10%;">{{ trans('app.sale_price') }}<br><small>( {{ trans('app.excl_tax') }} )</small></th>
    <th>{{ $qtyColumn ? trans('app.quantity') : trans('app.download_limit') }}</th>
    @if (is_incevio_package_loaded('pharmacy'))
      <th>{{ trans('app.expiry_date') }}</th>
    @endif
    <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
  </tr>
</thead>
