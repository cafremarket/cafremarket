@extends('theme::layouts.main')

@section('content')
  <section class="sf-order-confirm__header">
    <div class="container">
      <ol class="breadcrumb nav-breadcrumb mb-0">
        <li><a href="{{ url('/') }}">@lang('theme.home')</a></li>
        <li class="active">@lang('account_deletion.data_title')</li>
      </ol>
    </div>
  </section>

  <section class="sf-account-delete py-4">
    <div class="container" style="max-width: 760px;">
      <div class="sf-account-settings">
        <section class="sf-account-section">
          <div class="sf-account-section__head">
            <span class="sf-account-section__icon sf-account-section__icon--danger"><i class="fas fa-eraser" aria-hidden="true"></i></span>
            <div>
              <h1 class="sf-account-section__title">@lang('account_deletion.data_title')</h1>
              <p class="mb-0">@lang('account_deletion.data_intro', ['platform' => get_platform_title()])</p>
            </div>
          </div>

          @if (session('data_deleted'))
            <div class="sf-form-panel">
              <div class="sf-alert sf-alert--info">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <div>
                  <strong>@lang('account_deletion.data_done_title')</strong>
                  <ul class="mb-0">
                    @foreach (session('data_deleted') as $dataItem)
                      <li>@lang('account_deletion.data_'.$dataItem)</li>
                    @endforeach
                  </ul>
                </div>
              </div>
            </div>
          @endif

          <div class="sf-form-panel">
            <h2 class="h4">@lang('account_deletion.data_kept_title')</h2>
            <ul>
              <li>@lang('account_deletion.kept_orders')</li>
              <li>@lang('account_deletion.data_kept_profile')</li>
            </ul>
            <p class="mb-0">
              @lang('account_deletion.data_full_delete', ['url' => route('account.deletion.form', ['type' => $type])])
            </p>
          </div>

          <div class="sf-form-panel sf-form-panel--danger">
            <form method="POST" action="{{ route('account.data_deletion.destroy') }}" autocomplete="off" id="sf-data-delete-form">
              @csrf

              @if ($errors->any())
                <div class="alert alert-danger">
                  @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                  @endforeach
                </div>
              @endif

              @php($selected = old('type', $type))
              @php($checked = (array) old('data', []))
              <div class="form-group">
                <label class="d-block">@lang('account_deletion.account_type')</label>
                @foreach (array_keys($options) as $option)
                  <label class="radio-inline mr-3">
                    <input type="radio" name="type" value="{{ $option }}" {{ $selected === $option ? 'checked' : '' }} required>
                    @lang('account_deletion.type_'.$option)
                  </label>
                @endforeach
              </div>

              <div class="form-group">
                <label class="d-block">@lang('account_deletion.data_choose')</label>
                @foreach (array_unique(array_merge(...array_values($options))) as $dataItem)
                  <div class="checkbox" data-data-item="{{ $dataItem }}"
                    data-types="{{ implode(',', array_keys(array_filter($options, fn ($typeItems) => in_array($dataItem, $typeItems)))) }}">
                    <label>
                      <input type="checkbox" name="data[]" value="{{ $dataItem }}" {{ in_array($dataItem, $checked) ? 'checked' : '' }}>
                      @lang('account_deletion.data_'.$dataItem)
                    </label>
                  </div>
                @endforeach
              </div>

              <div class="form-group">
                <label for="data-email">@lang('account_deletion.email')</label>
                <input type="email" id="data-email" name="email" value="{{ old('email') }}" class="form-control" maxlength="191" required>
              </div>

              <div class="form-group">
                <label for="data-password">@lang('account_deletion.password')</label>
                <input type="password" id="data-password" name="password" class="form-control" maxlength="191" autocomplete="current-password" required>
                <small class="text-muted">@lang('account_deletion.password_help')</small>
              </div>

              @if (config('services.recaptcha.key'))
                <div class="form-group">
                  <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.key') }}"></div>
                </div>
              @endif

              <button type="submit" class="btn btn-danger px-5 py-2">
                <i class="fas fa-eraser mr-2" aria-hidden="true"></i>@lang('account_deletion.data_submit')
              </button>
            </form>

            <p class="small text-muted mt-3 mb-0">
              @lang('account_deletion.data_help', ['contact' => get_page_url(\App\Models\Page::PAGE_CONTACT_US)])
            </p>
          </div>
        </section>
      </div>
    </div>
  </section>
@endsection

@section('scripts')
  @if (config('services.recaptcha.key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  @endif
  <script>
    // Only show the data choices that apply to the selected account type.
    (function () {
      var form = document.getElementById('sf-data-delete-form');
      if (!form) return;
      function sync() {
        var type = (form.querySelector('input[name="type"]:checked') || {}).value;
        form.querySelectorAll('[data-data-item]').forEach(function (row) {
          var show = row.getAttribute('data-types').split(',').indexOf(type) !== -1;
          row.style.display = show ? '' : 'none';
          if (!show) row.querySelector('input').checked = false;
        });
      }
      form.querySelectorAll('input[name="type"]').forEach(function (r) { r.addEventListener('change', sync); });
      sync();
    })();
  </script>
@endsection
