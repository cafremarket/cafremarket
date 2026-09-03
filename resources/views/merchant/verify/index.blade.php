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
    $requiresStoreDocs = $shop->requiresBusinessDocuments();
    $canSubmitRequest = $hasPersonDocs && $hasLocation && $hasPhone && $hasEmail && (! $requiresStoreDocs || $hasStoreDocs);
    $activeTab = old('verification_tab', request('tab', 'person'));
    if (! in_array($activeTab, ['person', 'store', 'contact'], true)) {
      $activeTab = 'person';
    }

    $steps = [
      'person' => [
        'label' => trans('app.person_verification'),
        'short' => trans('app.verification_step_person'),
        'done' => $hasPersonDocs,
        'verified' => (bool) $shop->id_verified,
      ],
      'store' => [
        'label' => $requiresStoreDocs ? trans('app.store_verification') : trans('app.address_verification'),
        'short' => $requiresStoreDocs ? trans('app.verification_step_store') : trans('app.verification_step_address'),
        'done' => $hasLocation && (! $requiresStoreDocs || $hasStoreDocs),
        'verified' => (bool) $shop->address_verified,
      ],
      'contact' => [
        'label' => trans('app.phone_and_email_verification'),
        'short' => trans('app.verification_step_contact'),
        'done' => $hasPhone && $hasEmail,
        'verified' => (bool) $shop->phone_verified,
      ],
    ];

    $completedCount = collect($steps)->filter(fn ($s) => $s['done'] || $s['verified'])->count();
    $totalSteps = count($steps);
    $progressPct = (int) round(($completedCount / max($totalSteps, 1)) * 100);
  @endphp

  <div class="mp-verify">
    <header class="mp-verify__header">
      <p class="mp-verify__eyebrow">{{ trans('app.verification') }}</p>
      <h1>{{ trans('messages.seller_onboarding_title') }}</h1>
      <p class="mp-verify__lead">
        {{ $requiresStoreDocs ? trans('messages.verification_tabs_intro') : trans('messages.verification_tabs_intro_individual') }}
      </p>
      <p class="mp-verify__meta">
        <span class="mp-chip">{{ $shop->sellerTypeLabel() }}</span>
        @if ($shop->nuit)
          <span class="mp-chip">{{ trans('app.nuit') }}: {{ $shop->nuit }}</span>
        @endif
      </p>
    </header>

    @if ($shop->isVerified())
      <div class="mp-verify-status mp-verify-status--success">
        <div class="mp-verify-status__icon"><i class="fa fa-check"></i></div>
        <div>
          <strong>{{ trans('messages.store_verification_approved_notice') }}</strong>
          <a href="{{ route('merchant.dashboard') }}" class="mp-btn mp-btn--primary mp-btn--sm">{{ trans('nav.dashboard') }}</a>
        </div>
      </div>
    @elseif ($config->pending_verification)
      <div class="mp-verify-status mp-verify-status--pending">
        <div class="mp-verify-status__icon"><i class="fa fa-clock-o"></i></div>
        <div>
          <strong>{{ trans('messages.verification_request_pending_notice') }}</strong>
          <p>{{ trans('messages.verification_pending_until_approved') }}</p>
        </div>
      </div>
    @elseif ($wasRejected)
      <div class="mp-verify-status mp-verify-status--rejected">
        <div class="mp-verify-status__icon"><i class="fa fa-times"></i></div>
        <div>
          <strong>{{ trans('messages.verification_request_rejected_notice') }}</strong>
          @if ($config->verification_rejected_at)
            <p class="mp-verify-status__meta">{{ trans('app.rejected_at') }}: {{ $config->verification_rejected_at->format('M d, Y') }}</p>
          @endif
          @if ($config->verification_rejection_reason)
            <p class="mp-verify-status__reason">{{ $config->verification_rejection_reason }}</p>
          @endif
          <p>{{ trans('messages.verification_reapply_help') }}</p>
        </div>
      </div>
    @endif

    @unless ($shop->isVerified() || $config->pending_verification)
      <div class="mp-verify-progress" aria-hidden="true">
        <div class="mp-verify-progress__bar">
          <span style="width: {{ $progressPct }}%"></span>
        </div>
        <div class="mp-verify-progress__label">{{ $completedCount }}/{{ $totalSteps }}</div>
      </div>
    @endunless

    <nav class="mp-verify-steps" role="tablist" aria-label="{{ trans('app.verification') }}">
      @foreach ($steps as $key => $step)
        @php
          $stepState = $step['verified'] ? 'is-verified' : ($step['done'] ? 'is-done' : 'is-todo');
        @endphp
        <button type="button"
          class="mp-verify-steps__item {{ $activeTab === $key ? 'is-active' : '' }} {{ $stepState }}"
          data-tab-target="{{ $key }}"
          role="tab"
          aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
          aria-controls="mp-tab-{{ $key }}">
          <span class="mp-verify-steps__num">
            @if ($step['verified'] || $step['done'])
              <i class="fa fa-check"></i>
            @else
              {{ $loop->iteration }}
            @endif
          </span>
          <span class="mp-verify-steps__text">
            <span class="mp-verify-steps__title">{{ $step['short'] }}</span>
            <span class="mp-verify-steps__hint">{{ $step['label'] }}</span>
          </span>
        </button>
      @endforeach
    </nav>

    <div class="mp-verify-tabs" data-active-tab="{{ $activeTab }}">
      <div class="mp-verify-tabs__content">
        @include('merchant.verify.partials.tab_person', compact('config', 'shop', 'canSubmit', 'hasPersonDocs', 'personAttachments'))
        @include('merchant.verify.partials.tab_store', compact('config', 'shop', 'canSubmit', 'hasLocation', 'hasStoreDocs', 'storeAddress', 'storeAttachments', 'countries', 'states', 'shopPhone'))
        @include('merchant.verify.partials.tab_contact', compact('config', 'shop', 'canSubmit', 'hasPhone', 'hasEmail', 'shopPhone', 'shopEmail'))
      </div>
    </div>

    @if ($canSubmit)
      <section class="mp-verify-submit" id="mp-resubmit-section">
        <div class="mp-verify-submit__summary">
          <h2>{{ $wasRejected ? trans('app.resubmit_verification_request') : trans('app.submit_verification_request') }}</h2>
          <ul class="mp-verify-submit__checks">
            <li class="{{ $hasPersonDocs ? 'is-done' : '' }}"><i class="fa fa-{{ $hasPersonDocs ? 'check' : 'circle-o' }}"></i> {{ trans('app.person_verification') }}</li>
            <li class="{{ $hasLocation ? 'is-done' : '' }}"><i class="fa fa-{{ $hasLocation ? 'check' : 'circle-o' }}"></i> {{ trans('app.address_verification') }}</li>
            @if ($requiresStoreDocs)
              <li class="{{ $hasStoreDocs ? 'is-done' : '' }}"><i class="fa fa-{{ $hasStoreDocs ? 'check' : 'circle-o' }}"></i> {{ trans('app.store_document_verification') }}</li>
            @endif
            <li class="{{ $hasPhone && $hasEmail ? 'is-done' : '' }}"><i class="fa fa-{{ ($hasPhone && $hasEmail) ? 'check' : 'circle-o' }}"></i> {{ trans('app.phone_and_email_verification') }}</li>
          </ul>
        </div>

        {!! Form::open(['route' => 'merchant.verify.submit', 'id' => 'verification-form', 'class' => 'mp-verify-submit__form']) !!}
          @if (! $canSubmitRequest)
            <p class="mp-verify-submit__hint">{{ $requiresStoreDocs ? trans('messages.verification_complete_all_tabs') : trans('messages.verification_complete_all_tabs_individual') }}</p>
          @else
            <p class="mp-verify-submit__hint">{{ trans('messages.verification_submit_help') }}</p>
          @endif
          <button type="submit" class="mp-btn mp-btn--primary mp-btn--lg" {{ ! $canSubmitRequest ? 'disabled' : '' }}>
            {{ $wasRejected ? trans('app.resubmit_verification_request') : trans('app.submit_verification_request') }}
          </button>
        {!! Form::close() !!}
      </section>
    @elseif (! $shop->isVerified())
      <section class="mp-verify-review">
        <h2>{{ trans('messages.verification_admin_review_status') }}</h2>
        <ul class="mp-verify-submit__checks">
          <li class="{{ $shop->id_verified ? 'is-done' : '' }}"><i class="fa fa-{{ $shop->id_verified ? 'check' : 'circle-o' }}"></i> {{ trans('app.person_verification') }}</li>
          <li class="{{ $shop->address_verified ? 'is-done' : '' }}"><i class="fa fa-{{ $shop->address_verified ? 'check' : 'circle-o' }}"></i> {{ trans('app.store_verification') }}</li>
          <li class="{{ $shop->phone_verified ? 'is-done' : '' }}"><i class="fa fa-{{ $shop->phone_verified ? 'check' : 'circle-o' }}"></i> {{ trans('app.phone_and_email_verification') }}</li>
        </ul>
      </section>
    @endif
  </div>
@endsection

@section('scripts')
<script>
(function() {
  var tabsRoot = document.querySelector('.mp-verify-tabs');
  var stepsNav = document.querySelector('.mp-verify-steps');
  if (!tabsRoot || !stepsNav) return;

  var activeTab = tabsRoot.getAttribute('data-active-tab') || 'person';

  function showTab(key) {
    stepsNav.querySelectorAll('.mp-verify-steps__item').forEach(function(btn) {
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

  stepsNav.querySelectorAll('.mp-verify-steps__item').forEach(function(btn) {
    btn.addEventListener('click', function() {
      showTab(btn.getAttribute('data-tab-target'));
    });
  });

  showTab(activeTab);

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
