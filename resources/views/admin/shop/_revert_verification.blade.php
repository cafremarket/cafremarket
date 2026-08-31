<div class="modal-dialog modal-md">
  <div class="modal-content admin-verify-modal admin-verify-modal--revert">
    {!! Form::open(['method' => 'POST', 'route' => ['admin.vendor.shop.verify.revert', $shop->id], 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <h4 class="modal-title">
        <i class="fa fa-undo text-warning"></i>
        {{ trans('app.revert_verification') }}
      </h4>
      <p class="admin-verify-modal__subtitle">{{ $shop->name }}</p>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning admin-verify-modal__alert">
        <i class="fa fa-exclamation-triangle"></i> {{ trans('messages.verification_revert_help', ['shop' => $shop->name]) }}
      </div>
      <ul class="admin-verify-modal__checklist">
        <li class="admin-verify-modal__checklist-item is-pending">
          <span class="admin-verify-modal__checklist-icon"><i class="fa fa-user"></i></span>
          <span class="admin-verify-modal__checklist-label">{{ trans('app.person_verification') }}</span>
        </li>
        <li class="admin-verify-modal__checklist-item is-pending">
          <span class="admin-verify-modal__checklist-icon"><i class="fa fa-store"></i></span>
          <span class="admin-verify-modal__checklist-label">{{ trans('app.store_verification') }}</span>
        </li>
        <li class="admin-verify-modal__checklist-item is-pending">
          <span class="admin-verify-modal__checklist-icon"><i class="fa fa-envelope"></i></span>
          <span class="admin-verify-modal__checklist-label">{{ trans('app.phone_and_email_verification') }}</span>
        </li>
      </ul>
    </div>
    <div class="modal-footer admin-verify-modal__footer">
      <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">{{ trans('app.cancel') }}</button>
      {!! Form::submit(trans('app.revert_verification'), ['class' => 'btn btn-warning btn-flat']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
