@extends('theme::layouts.main')

@section('content')
  <section class="sf-order-confirm__header">
    <div class="container">
      <ol class="breadcrumb nav-breadcrumb mb-0">
        <li><a href="{{ url('/') }}">@lang('theme.home')</a></li>
        <li class="active">@lang('account_deletion.title')</li>
      </ol>
    </div>
  </section>

  <section class="sf-account-delete py-4">
    <div class="container" style="max-width: 760px;">
      <div class="sf-account-settings">
        <section class="sf-account-section">
          <div class="sf-account-section__head">
            <span class="sf-account-section__icon sf-account-section__icon--danger"><i class="fas fa-user-slash" aria-hidden="true"></i></span>
            <div>
              <h1 class="sf-account-section__title">@lang('account_deletion.title')</h1>
              <p class="mb-0">@lang('account_deletion.intro', ['platform' => get_platform_title()])</p>
            </div>
          </div>

          @if (session('account_deleted'))
            <div class="sf-form-panel">
              <div class="sf-alert sf-alert--info">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <div>
                  <strong>@lang('account_deletion.done_title')</strong>
                  @lang('account_deletion.done_body')
                </div>
              </div>
              <p class="text-center mb-0"><a href="{{ url('/') }}" class="btn btn-default">@lang('theme.home')</a></p>
            </div>
          @else
            <div class="sf-form-panel">
              <h2 class="h4">@lang('account_deletion.what_deleted_title')</h2>
              <ul>
                <li>@lang('account_deletion.deleted_profile')</li>
                <li>@lang('account_deletion.deleted_addresses')</li>
                <li>@lang('account_deletion.deleted_login')</li>
                <li>@lang('account_deletion.deleted_store')</li>
              </ul>

              <h2 class="h4">@lang('account_deletion.what_kept_title')</h2>
              <ul>
                <li>@lang('account_deletion.kept_orders')</li>
                <li>@lang('account_deletion.kept_reviews')</li>
              </ul>

              <div class="sf-alert sf-alert--danger">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <div>
                  <strong>{{ trans('app.notice') }}</strong>
                  @lang('account_deletion.warning')
                </div>
              </div>

              <p class="mb-0">@lang('account_deletion.data_link', ['url' => route('account.data_deletion.form', ['type' => $type])])</p>
            </div>

            <div class="sf-form-panel sf-form-panel--danger">
              <form method="POST" action="{{ route('account.deletion.destroy') }}" autocomplete="off">
                @csrf

                @if ($errors->any())
                  <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                      <div>{{ $error }}</div>
                    @endforeach
                  </div>
                @endif

                @php($selected = old('type', $type))
                <div class="form-group">
                  <label class="d-block">@lang('account_deletion.account_type')</label>
                  @foreach (['customer', 'seller', 'delivery'] as $option)
                    <label class="radio-inline mr-3">
                      <input type="radio" name="type" value="{{ $option }}" {{ $selected === $option ? 'checked' : '' }} required>
                      @lang('account_deletion.type_'.$option)
                    </label>
                  @endforeach
                </div>

                <div class="form-group">
                  <label for="delete-email">@lang('account_deletion.email')</label>
                  <input type="email" id="delete-email" name="email" value="{{ old('email') }}" class="form-control" maxlength="191" required>
                </div>

                <div class="form-group">
                  <label for="delete-password">@lang('account_deletion.password')</label>
                  <input type="password" id="delete-password" name="password" class="form-control" maxlength="191" autocomplete="current-password" required>
                  <small class="text-muted">@lang('account_deletion.password_help')</small>
                </div>

                <div class="form-group">
                  <label>
                    <input type="checkbox" name="confirm" value="1" required>
                    @lang('account_deletion.confirm_label')
                  </label>
                </div>

                @if (config('services.recaptcha.key'))
                  <div class="form-group">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
                  </div>
                @endif

                <button type="submit" class="btn btn-danger px-5 py-2">
                  <i class="fas fa-trash mr-2" aria-hidden="true"></i>@lang('account_deletion.submit')
                </button>
              </form>

              <p class="small text-muted mt-3 mb-0">
                @lang('account_deletion.help', ['contact' => get_page_url(\App\Models\Page::PAGE_CONTACT_US)])
              </p>
            </div>
          @endif
        </section>
      </div>
    </div>
  </section>
@endsection

@section('scripts')
  @if (config('services.recaptcha.key') && ! session('account_deleted'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  @endif
@endsection
