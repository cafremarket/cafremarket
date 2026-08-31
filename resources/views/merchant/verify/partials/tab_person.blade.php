<div class="mp-verify-tab-panel" id="mp-tab-person" role="tabpanel" hidden>
  <div class="mp-panel mp-panel--flat">
    <div class="mp-panel__head">
      <div class="mp-panel__head-icon"><i class="fa fa-user"></i></div>
      <div class="mp-panel__head-text">
        <h2>{{ trans('app.person_verification') }}</h2>
        <p>{{ trans('messages.verification_tab_person_help') }}</p>
      </div>
      @if ($shop->id_verified)
        <span class="mp-verify-tab-badge mp-verify-tab-badge--success">{{ trans('app.verified') }}</span>
      @elseif ($hasPersonDocs)
        <span class="mp-verify-tab-badge mp-verify-tab-badge--info">{{ trans('app.submitted') }}</span>
      @else
        <span class="mp-verify-tab-badge mp-verify-tab-badge--warning">{{ trans('app.action_required') }}</span>
      @endif
    </div>
    <div class="mp-panel__body">
      <div class="mp-alert mp-alert--info">
        <i class="fa fa-info-circle"></i> {!! trans('messages.verification_person_documents') !!}
      </div>

      @if ($canSubmit)
        @include('merchant.verify.partials.documents', [
          'config' => $config,
          'editable' => true,
          'attachments' => $personAttachments,
          'documentType' => 'person',
        ])
      @else
        @include('merchant.verify.partials.documents', [
          'config' => $config,
          'editable' => false,
          'attachments' => $personAttachments,
          'documentType' => 'person',
        ])
      @endif
    </div>
  </div>
</div>
