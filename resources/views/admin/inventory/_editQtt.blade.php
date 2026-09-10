<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::model($inventory, ['method' => 'PUT', 'route' => ['admin.stock.inventory.updateQtt', $inventory->id], 'class' => 'ajax-form', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.form.update') }} — {{ $inventory->sku }}
    </div>
    <div class="modal-body">
      @php
        $stocks = $inventory->stocks()->with('warehouse')->get();
        $warehouses = $warehouses ?? \App\Helpers\ListHelper::warehouses($inventory->shop_id);
      @endphp

      @if ($stocks->isNotEmpty())
        <table class="table table-condensed">
          <thead>
            <tr>
              <th>{{ trans('app.warehouse') }}</th>
              <th>{{ trans('app.on_hand') }}</th>
              <th>{{ trans('app.reserved') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($stocks as $stock)
              <tr>
                <td>{{ optional($stock->warehouse)->name }}</td>
                <td>
                  <input type="number" min="0" class="form-control"
                         name="warehouse_stocks[{{ $stock->warehouse_id }}]"
                         value="{{ $stock->quantity }}">
                </td>
                <td>{{ $stock->reserved_quantity }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <div class="form-group">
          {!! Form::label('warehouse_id', trans('app.warehouse')) !!}
          {!! Form::select('warehouse_id', $warehouses, $inventory->warehouse_id ?: config('shop_settings.default_warehouse_id'), ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.select')]) !!}
        </div>
        <div class="form-group">
          {!! Form::label('stock_quantity', trans('app.form.stock_quantity') . '*') !!}
          {!! Form::number('stock_quantity', $inventory->stock_quantity, ['id' => 'stock_quantity', 'class' => 'form-control', 'min' => 0, 'required']) !!}
        </div>
      @endif

      <div class="form-group">
        {!! Form::label('notes', trans('app.notes')) !!}
        {!! Form::text('notes', null, ['class' => 'form-control', 'placeholder' => trans('app.stock_adjustment_note')]) !!}
      </div>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.update'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
