<div class="mp-verify-tab-panel" id="mp-tab-person" role="tabpanel" hidden>
  <section class="mp-verify-card">
    <header class="mp-verify-card__head">
      <div>
        <h2>{{ trans('app.person_verification') }}</h2>
        <p>{{ trans('messages.verification_tab_person_help') }}</p>
      </div>
      @if ($shop->id_verified)
        <span class="mp-chip mp-chip--success">{{ trans('app.verified') }}</span>
      @elseif ($hasPersonDocs)
        <span class="mp-chip mp-chip--info">{{ trans('app.submitted') }}</span>
      @endif
    </header>

    <p class="mp-verify-card__note">{!! trans('messages.verification_person_documents') !!}</p>

    @include('merchant.verify.partials.documents', [
      'config' => $config,
      'editable' => $canSubmit,
      'attachments' => $personAttachments,
      'documentType' => 'person',
    ])
  </section>
</div>
