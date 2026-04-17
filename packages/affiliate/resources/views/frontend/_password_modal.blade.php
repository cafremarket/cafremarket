<div class="modal-dialog modal-sm">
  <div class="modal-content">
    {!! Form::open(['method' => 'PUT', 'route' => auth()->guard('affiliate')->check() ? ['affiliate.profile.password.update', $affiliate] : ['admin.affiliate.password.update',$affiliate], 'id' => 'change-password-form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.change_password') }}
    </div>
    <div class="modal-body">
      @include('affiliate::partials._password_form')
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.update'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
  