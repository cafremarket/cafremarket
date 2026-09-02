<div class="sf-account-settings">
  <section class="sf-account-section">
    <div class="sf-account-section__head">
      <span class="sf-account-section__icon sf-account-section__icon--danger"><i class="fas fa-trash-alt" aria-hidden="true"></i></span>
      <div>
        <h2 class="sf-account-section__title">@lang('theme.button.delete')</h2>
      </div>
    </div>

    <div class="sf-form-panel sf-form-panel--danger">
      <div class="sf-alert sf-alert--danger">
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        <div>
          <strong>{{ trans('app.notice') }}</strong>
          {{ trans('messages.account_delete') }}
        </div>
      </div>

      {!! Form::model($account_delete, ['method' => 'DELETE', 'route' => 'my.account.remove', 'class' => 'text-center', 'data-toggle' => 'validator']) !!}
      <button class="btn btn-danger px-5 py-2 confirm" data-confirm="{{ trans('theme.confirm_action.delete') }}" type="submit">
        <i class="fas fa-trash mr-2" aria-hidden="true"></i>
        {{ trans('theme.button.delete') }}
      </button>
      {!! Form::close() !!}
    </div>
  </section>
</div>
