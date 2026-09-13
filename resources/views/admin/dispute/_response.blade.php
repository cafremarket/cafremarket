<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::model($dispute, ['method' => 'POST', 'route' => ['admin.support.dispute.storeResponse', $dispute->id], 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.response') }} — {{ $dispute->ticketRef() }}
    </div>
    <div class="modal-body">

      <div class="form-group">
        {!! Form::label('status', trans('app.form.status') . '*') !!}
        {!! Form::select('status', $statuses, $dispute->status, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.status'), 'required']) !!}
        <p class="help-block">{{ trans('app.only_admin_can_set_status_closed') }}</p>
        <div class="help-block with-errors"></div>
      </div>

      @include('admin.partials._reply')

      <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.reply'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
