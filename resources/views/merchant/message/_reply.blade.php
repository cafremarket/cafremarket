<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::model($message, ['method' => 'POST', 'route' => [panel_route_name('admin.support.message.storeReply'), $message->id], 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.reply') }}
    </div>
    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('email', trans('app.reply_to')) !!}
        {!! Form::text('email', $message->getSenderEmail(), ['class' => 'form-control', 'placeholder' => trans('app.reply_to'), 'disabled']) !!}
        <div class="help-block with-errors"></div>
      </div>

      @include('admin.partials._reply')
      <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.reply'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
