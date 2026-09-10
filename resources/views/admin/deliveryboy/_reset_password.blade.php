<div class="modal-dialog modal-sm">
  <div class="modal-content">
    {!! Form::open(['route' => ['admin.admin.deliveryboy.resetPassword', $deliveryboy->id], 'method' => 'put', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.reset_password') }} — {{ $deliveryboy->getName() }}
    </div>
    <div class="modal-body">
      @include('admin.partials._password_fields')
      <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.reset_password'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
