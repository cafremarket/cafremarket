<div class="modal-dialog modal-lg">
  <div class="modal-content">
    {!! Form::model($popup, ['method' => 'PUT', 'route' => ['admin.popup.update', $popup->id], 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.edit_popup') }}
    </div>
    <div class="modal-body">
      @include('admin.popup._form')
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.update'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
