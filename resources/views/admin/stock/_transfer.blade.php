<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::open(['route' => 'admin.stock.transfer.store', 'method' => 'POST', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.transfer_stock') }}
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('from_warehouse_id', trans('app.from_warehouse').'*') !!}
        {!! Form::select('from_warehouse_id', $warehouses, null, ['class' => 'form-control select2-normal', 'required', 'placeholder' => trans('app.placeholder.select')]) !!}
      </div>
      <div class="form-group">
        {!! Form::label('to_warehouse_id', trans('app.to_warehouse').'*') !!}
        {!! Form::select('to_warehouse_id', $warehouses, null, ['class' => 'form-control select2-normal', 'required', 'placeholder' => trans('app.placeholder.select')]) !!}
      </div>
      <div class="form-group">
        {!! Form::label('inventory_id', trans('app.inventory').'*') !!}
        {!! Form::select('inventory_id', $inventories, null, ['class' => 'form-control select2-normal', 'required', 'placeholder' => trans('app.placeholder.select')]) !!}
      </div>
      <div class="form-group">
        {!! Form::label('quantity', trans('app.quantity').'*') !!}
        {!! Form::number('quantity', 1, ['class' => 'form-control', 'min' => 1, 'required']) !!}
      </div>
      <div class="form-group">
        {!! Form::label('notes', trans('app.notes')) !!}
        {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2]) !!}
      </div>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.transfer_stock'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
