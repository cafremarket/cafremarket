<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::open(['route' => panel_route_name('admin.support.message.store'), 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.form.form') }}
    </div>

    <div class="modal-body">
      {!! Form::hidden('order_id', $order->id) !!}
      <h4>{{ trans('app.to') }}: {{ $order->customer_id ? $order->customer->email : $order->email }}</h4>
      <div class="spacer10"></div>

      @include('admin.partials._compose_a_message')
      @include('admin.partials._attachment_upload_field')

      <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.form.send'), ['name' => 'sned', 'class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
