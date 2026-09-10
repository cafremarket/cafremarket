@php
  $stockRows = isset($inventory) ? $inventory->stocks()->with('warehouse')->get()->keyBy('warehouse_id') : collect();
  $selectedWarehouseIds = $stockRows->keys()->all();
  if (empty($selectedWarehouseIds) && isset($inventory) && $inventory->warehouse_id) {
    $selectedWarehouseIds = [$inventory->warehouse_id];
  }
  if (empty($selectedWarehouseIds)) {
    $defaultWh = config('shop_settings.default_warehouse_id');
    $selectedWarehouseIds = $defaultWh ? [$defaultWh] : [];
  }
@endphp

<div class="form-group">
  {!! Form::label('warehouse_id[]', trans('app.form.warehouse'), ['class' => 'with-help']) !!}
  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.select_warehouse') }}"></i>
  {!! Form::select('warehouse_id[]', $warehouses, $selectedWarehouseIds, ['class' => 'form-control select2-normal', 'multiple' => 'multiple', 'id' => 'inventory_warehouse_ids']) !!}
</div>

<div class="form-group">
  <label>{{ trans('app.warehouse_stock_levels') }}</label>
  <div class="table-responsive">
    <table class="table table-bordered table-condensed" id="warehouse-stock-matrix">
      <thead>
        <tr>
          <th>{{ trans('app.warehouse') }}</th>
          <th>{{ trans('app.on_hand') }}</th>
          <th>{{ trans('app.reserved') }}</th>
          <th>{{ trans('app.reorder_level') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($warehouses as $warehouseId => $warehouseName)
          @php
            $row = $stockRows->get($warehouseId);
            $isSelected = in_array($warehouseId, $selectedWarehouseIds);
          @endphp
          <tr class="warehouse-stock-row" data-warehouse-id="{{ $warehouseId }}" style="{{ $isSelected ? '' : 'display:none;' }}">
            <td>{{ $warehouseName }}</td>
            <td>
              <input type="number" min="0" class="form-control"
                     name="warehouse_stocks[{{ $warehouseId }}]"
                     value="{{ $row->quantity ?? ($isSelected && !isset($inventory) ? old('stock_quantity', 0) : ($row->quantity ?? 0)) }}">
            </td>
            <td>{{ $row->reserved_quantity ?? 0 }}</td>
            <td>
              <input type="number" min="0" class="form-control"
                     name="reorder_levels[{{ $warehouseId }}]"
                     value="{{ $row->reorder_level ?? '' }}"
                     placeholder="{{ config('shop_settings.alert_quantity') ?? 0 }}">
            </td>
          </tr>
        @empty
          <tr><td colspan="4">{{ trans('app.no_warehouse_found') }}</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <p class="help-block">{{ trans('help.warehouse_stock_matrix') }}</p>
</div>

<script>
  (function () {
    var $select = $('#inventory_warehouse_ids');
    if (!$select.length) return;

    function syncRows() {
      var selected = $select.val() || [];
      $('#warehouse-stock-matrix .warehouse-stock-row').each(function () {
        var id = String($(this).data('warehouse-id'));
        var show = selected.map(String).indexOf(id) !== -1;
        $(this).toggle(show);
        $(this).find('input').prop('disabled', !show);
      });
    }

    $select.on('change', syncRows);
    syncRows();
  })();
</script>
