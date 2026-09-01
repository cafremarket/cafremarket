@extends('merchant.layouts.onboarding')

@section('title', trans('app.verification'))

@section('content')
  @php
    $shop = $config->shop;
    $storeAddress = $shop->storeAddress();
    $hasLocation = $shop->hasStoreLocation();
    $personAttachments = $config->personVerificationAttachments();
    $storeAttachments = $config->storeVerificationAttachments();
    $hasPersonDocs = $config->hasPersonVerificationDocuments();
    $hasStoreDocs = $config->hasStoreVerificationDocuments();
    $shopPhone = $config->support_phone ?: Auth::user()->phone;
    $shopEmail = $config->support_email ?: Auth::user()->email;
    $hasPhone = filled(trim((string) $shopPhone));
    $hasEmail = filled(trim((string) $shopEmail));
    $canSubmit = $config->canSubmitVerificationRequest();
    $wasRejected = $config->wasVerificationRejected();
    $canSubmitRequest = $hasPersonDocs && $hasStoreDocs && $hasLocation && $hasPhone && $hasEmail;
    $activeTab = old('verification_tab', request('tab', 'person'));
    if (! in_array($activeTab, ['person', 'store', 'contact'], true)) {
      $activeTab = 'person';
    }

    $tabs = [
      'person' => [
        'label' => trans('app.person_verification'),
        'icon' => 'fa-user',
        'done' => $hasPersonDocs,
        'verified' => $shop->id_verified,
      ],
      'store' => [
        'label' => trans('app.store_verification'),
        'icon' => 'fa-store',
        'done' => $hasLocation && $hasStoreDocs,
        'verified' => $shop->address_verified,
      ],
      'contact' => [
        'label' => trans('app.phone_and_email_verification'),
        'icon' => 'fa-envelope',
        'done' => $hasPhone && $hasEmail,
        'verified' => $shop->phone_verified,
      ],
    ];
  @endphp

  <div class="mp-onboarding__hero">
    <h1>{{ trans('messages.seller_onboarding_title') }}</h1>
    <p>{{ trans('messages.verification_tabs_intro') }}</p>
  </div>

  @if ($shop->isVerified())
    <div class="mp-panel">
      <div class="mp-panel__body">
        <div class="mp-alert mp-alert--success">
          <i class="fa fa-check-circle"></i> {{ trans('messages.store_verification_approved_notice') }}
        </div>
        <a href="{{ route('merchant.dashboard') }}" class="mp-btn mp-btn--primary">{{ trans('nav.dashboard') }}</a>
      </div>
    </div>
  @elseif ($config->pending_verification)
    <div class="mp-panel">
      <div class="mp-panel__body">
        <div class="mp-alert mp-alert--warning">
          <i class="fa fa-clock-o"></i> {{ trans('messages.verification_request_pending_notice') }}
        </div>
        <p class="mp-text-muted mp-text-muted--spaced">{{ trans('messages.seller_onboarding_pending_help') }}</p>
        <p class="mp-text-muted">{{ trans('messages.verification_pending_until_approved') }}</p>
      </div>
    </div>
  @elseif ($wasRejected)
    <div class="mp-panel mp-panel--rejected">
      <div class="mp-panel__body">
        <div class="mp-rejection-box">
          <div class="mp-rejection-box__head">
            <i class="fa fa-times-circle"></i>
            <div class="mp-panel__head-text">
              <h3>{{ trans('messages.verification_request_rejected_notice') }}</h3>
              @if ($config->verification_rejected_at)
                <p class="mp-rejection-box__date">{{ trans('app.rejected_at') }}: {{ $config->verification_rejected_at->format('M d, Y h:i A') }}</p>
              @endif
            </div>
          </div>

          @if ($config->verification_rejection_reason)
            <div class="mp-rejection-box__reason">
              <span class="mp-rejection-box__reason-label">{{ trans('app.rejection_reason') }}</span>
              <p>{{ $config->verification_rejection_reason }}</p>
            </div>
          @endif

          <p class="mp-rejection-box__help">{{ trans('messages.verification_reapply_help') }}</p>
        </div>
      </div>
    </div>
  @endif

  <div class="mp-verify-tabs" data-active-tab="{{ $activeTab }}">
    <div class="mp-verify-tabs__nav" role="tablist">
      @foreach ($tabs as $key => $tab)
        <button type="button"
          class="mp-verify-tabs__btn {{ $activeTab === $key ? 'is-active' : '' }}"
          data-tab-target="{{ $key }}"
          role="tab"
          aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
          aria-controls="mp-tab-{{ $key }}">
          <span class="mp-verify-tabs__btn-icon"><i class="fa {{ $tab['icon'] }}"></i></span>
          <span class="mp-verify-tabs__btn-text">{{ $tab['label'] }}</span>
          <span class="mp-verify-tabs__btn-status">
            @if ($tab['verified'])
              <i class="fa fa-check-circle"></i>
            @elseif ($tab['done'])
              <i class="fa fa-dot-circle-o"></i>
            @else
              <i class="fa fa-circle-o"></i>
            @endif
          </span>
        </button>
      @endforeach
    </div>

    <div class="mp-verify-tabs__content">
      @include('merchant.verify.partials.tab_person', compact('config', 'shop', 'canSubmit', 'hasPersonDocs', 'personAttachments'))
      @include('merchant.verify.partials.tab_store', compact('config', 'shop', 'canSubmit', 'hasLocation', 'hasStoreDocs', 'storeAddress', 'storeAttachments', 'countries', 'states', 'shopPhone'))
      @include('merchant.verify.partials.tab_contact', compact('config', 'shop', 'canSubmit', 'hasPhone', 'hasEmail', 'shopPhone', 'shopEmail'))
    </div>
  </div>

  @if ($canSubmit)
    <div class="mp-panel" id="mp-resubmit-section">
      <div class="mp-panel__head">
        <div class="mp-panel__head-icon"><i class="fa fa-paper-plane"></i></div>
        <div class="mp-panel__head-text">
          <h2>{{ $wasRejected ? trans('app.resubmit_verification_request') : trans('app.submit_verification_request') }}</h2>
          <p>{{ trans('messages.verification_submit_help') }}</p>
        </div>
      </div>
      <div class="mp-panel__body">
        <ul class="mp-checklist mp-checklist--compact mp-checklist--inset">
          <li class="{{ $hasPersonDocs ? 'is-done' : 'is-pending' }}">
            <i class="fa fa-{{ $hasPersonDocs ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.person_verification') }}
          </li>
          <li class="{{ $hasLocation ? 'is-done' : 'is-pending' }}">
            <i class="fa fa-{{ $hasLocation ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.address_verification') }}
          </li>
          <li class="{{ $hasStoreDocs ? 'is-done' : 'is-pending' }}">
            <i class="fa fa-{{ $hasStoreDocs ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.store_document_verification') }}
          </li>
          <li class="{{ $hasPhone ? 'is-done' : 'is-pending' }}">
            <i class="fa fa-{{ $hasPhone ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.form.phone') }}
          </li>
          <li class="{{ $hasEmail ? 'is-done' : 'is-pending' }}">
            <i class="fa fa-{{ $hasEmail ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.form.email_address') }}
          </li>
        </ul>

        {!! Form::open(['route' => 'merchant.verify.submit', 'id' => 'verification-form']) !!}
          @if (! $canSubmitRequest)
            <div class="mp-alert mp-alert--warning">
              <i class="fa fa-exclamation-triangle"></i> {{ trans('messages.verification_complete_all_tabs') }}
            </div>
          @endif

          <button type="submit" class="mp-btn mp-btn--primary mp-btn--block" {{ ! $canSubmitRequest ? 'disabled' : '' }}>
            <i class="fa fa-paper-plane"></i>
            {{ $wasRejected ? trans('app.resubmit_verification_request') : trans('app.submit_verification_request') }}
          </button>
        {!! Form::close() !!}
      </div>
    </div>
  @endif

  <div class="mp-panel">
    <div class="mp-panel__head">
      <div class="mp-panel__head-icon"><i class="fa fa-shield"></i></div>
      <div class="mp-panel__head-text">
        <h2>{{ trans('messages.verification_admin_review_status') }}</h2>
        <p>{{ trans('messages.verification_admin_review_help') }}</p>
      </div>
    </div>
    <div class="mp-panel__body">
      <ul class="mp-checklist">
        <li class="{{ $shop->id_verified ? 'is-done' : 'is-pending' }}">
          <i class="fa fa-{{ $shop->id_verified ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.person_verification') }}
        </li>
        <li class="{{ $shop->address_verified ? 'is-done' : 'is-pending' }}">
          <i class="fa fa-{{ $shop->address_verified ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.store_verification') }}
        </li>
        <li class="{{ $shop->phone_verified ? 'is-done' : 'is-pending' }}">
          <i class="fa fa-{{ $shop->phone_verified ? 'check-circle' : 'circle-o' }}"></i> {{ trans('app.phone_and_email_verification') }}
        </li>
      </ul>
    </div>
  </div>
@endsection

@section('scripts')
<script>
(function() {
  var tabsRoot = document.querySelector('.mp-verify-tabs');
  if (tabsRoot) {
    var activeTab = tabsRoot.getAttribute('data-active-tab') || 'person';

    function showTab(key) {
      tabsRoot.querySelectorAll('.mp-verify-tabs__btn').forEach(function(btn) {
        var isActive = btn.getAttribute('data-tab-target') === key;
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });

      tabsRoot.querySelectorAll('.mp-verify-tab-panel').forEach(function(panel) {
        panel.hidden = panel.id !== 'mp-tab-' + key;
      });

      if (key === 'store') {
        setTimeout(function() {
          if (typeof window.initAddressWizard === 'function') {
            window.initAddressWizard('merchant-store-wizard');
          }
          if (typeof window.refreshAddressWizardMap === 'function') {
            window.refreshAddressWizardMap('merchant-store-wizard');
          }
        }, 120);
      }

      if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        window.history.replaceState({}, '', url.toString());
      }
    }

    tabsRoot.querySelectorAll('.mp-verify-tabs__btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        showTab(btn.getAttribute('data-tab-target'));
      });
    });

    showTab(activeTab);
  }

  document.querySelectorAll('.mp-doc-upload-form').forEach(function(form) {
    var uploadInput = form.querySelector('.mp-documents-input');
    var uploadFooter = form.querySelector('.mp-upload-footer');
    var uploadFilename = form.querySelector('.mp-upload-filename');
    var uploadZone = form.querySelector('.mp-upload-zone');
    var trigger = form.querySelector('.mp-upload-zone__trigger');

    if (trigger && uploadInput) {
      trigger.addEventListener('click', function(e) {
        if (e.target !== uploadInput) {
          e.preventDefault();
          uploadInput.click();
        }
      });
    }

    if (uploadInput && uploadFooter && uploadFilename) {
      uploadInput.addEventListener('change', function() {
        if (this.files.length) {
          var names = Array.prototype.map.call(this.files, function(f) { return f.name; });
          uploadFilename.textContent = names.join(', ');
          uploadFooter.hidden = false;
        } else {
          uploadFilename.textContent = '';
          uploadFooter.hidden = true;
        }
      });
    }

    if (uploadZone && uploadInput) {
      ['dragenter', 'dragover'].forEach(function(eventName) {
        uploadZone.addEventListener(eventName, function(e) {
          e.preventDefault();
          uploadZone.classList.add('is-dragover');
        });
      });

      ['dragleave', 'drop'].forEach(function(eventName) {
        uploadZone.addEventListener(eventName, function(e) {
          e.preventDefault();
          uploadZone.classList.remove('is-dragover');
        });
      });

      uploadZone.addEventListener('drop', function(e) {
        if (e.dataTransfer && e.dataTransfer.files.length) {
          uploadInput.files = e.dataTransfer.files;
          uploadInput.dispatchEvent(new Event('change'));
        }
      });
    }
  });

  document.querySelectorAll('.mp-doc-replace-input').forEach(function(input) {
    input.addEventListener('change', function() {
      if (this.files.length) {
        this.closest('form').submit();
      }
    });
  });
})();
</script>
@endsection
