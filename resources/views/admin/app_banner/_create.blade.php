<div class="modal-dialog modal-lg">
  <div class="modal-content">
    {!! Form::open(['route' => 'admin.app_banner.store', 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <strong>{{ trans('app.add_app_banner') }}</strong>
    </div>
    <div class="modal-body">
      @include('admin.web_banner._form', ['formNote' => trans('help.app_banner_form_note')])
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">{{ trans('app.cancel') }}</button>
      {!! Form::submit(trans('app.form.save'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
