<div class="modal-dialog modal-sm">
  <div class="modal-content">
    {!! Form::open(['method' => 'POST', 'route' => ['admin.vendor.shop.verify.reject', $shop->id], 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.reject_verification') }}
    </div>
    <div class="modal-body">
      <p>{{ trans('messages.verification_rejection_help', ['shop' => $shop->name]) }}</p>
      <div class="form-group">
        {!! Form::label('verification_rejection_reason', trans('app.rejection_reason') . '*') !!}
        {!! Form::textarea('verification_rejection_reason', null, ['class' => 'form-control', 'rows' => 4, 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.reject_verification'), ['class' => 'btn btn-danger btn-flat']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
