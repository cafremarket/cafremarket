<div class="modal-dialog modal-md">
  <div class="modal-content admin-verify-modal admin-verify-modal--reject">
    {!! Form::open(['method' => 'POST', 'route' => ['admin.vendor.shop.verify.reject', $shop->id], 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <h4 class="modal-title">
        <i class="fa fa-times-circle text-danger"></i>
        {{ trans('app.reject_verification') }}
      </h4>
      <p class="admin-verify-modal__subtitle">{{ $shop->name }}</p>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning admin-verify-modal__alert">
        <i class="fa fa-exclamation-triangle"></i> {{ trans('messages.verification_rejection_help', ['shop' => $shop->name]) }}
      </div>
      <div class="form-group">
        {!! Form::label('verification_rejection_reason', trans('app.rejection_reason') . '*') !!}
        {!! Form::textarea('verification_rejection_reason', null, ['class' => 'form-control', 'rows' => 4, 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>
    </div>
    <div class="modal-footer admin-verify-modal__footer">
      <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">{{ trans('app.cancel') }}</button>
      {!! Form::submit(trans('app.reject_verification'), ['class' => 'btn btn-danger btn-flat']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
