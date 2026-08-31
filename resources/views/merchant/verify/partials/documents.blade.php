@php
  $editable = $editable ?? false;
  $attachments = $attachments ?? $config->attachments;
  $documentType = $documentType ?? 'person';
@endphp

<div class="mp-doc-manager">
  @if ($attachments->count())
    <div class="mp-doc-list">
      @foreach ($attachments as $attachment)
        @php
          $isImage = in_array(strtolower($attachment->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
          $isPdf = strtolower($attachment->extension) === 'pdf';
          $icon = $isPdf ? 'fa-file-pdf-o' : ($isImage ? 'fa-file-image-o' : 'fa-file-o');
        @endphp
        <div class="mp-doc-card" id="mp-doc-{{ $attachment->id }}">
          <div class="mp-doc-card__icon">
            <i class="fa {{ $icon }}"></i>
          </div>
          <div class="mp-doc-card__body">
            <span class="mp-doc-card__name" title="{{ $attachment->name }}">{{ $attachment->name }}</span>
            <span class="mp-doc-card__meta">{{ get_formated_file_size($attachment->size) }} · {{ strtoupper($attachment->extension) }}</span>
          </div>
          <div class="mp-doc-card__actions">
            <a href="{{ route('attachment.view', $attachment) }}" class="mp-doc-card__btn" target="_blank" rel="noopener" title="{{ trans('app.view') }}">
              <i class="fa fa-eye"></i>
            </a>
            <a href="{{ route('attachment.download', $attachment) }}" class="mp-doc-card__btn" title="{{ trans('app.download') }}">
              <i class="fa fa-download"></i>
            </a>
            @if ($editable)
              <form action="{{ route('merchant.verify.documents.replace', $attachment) }}"
                method="POST"
                enctype="multipart/form-data"
                class="mp-doc-replace-form">
                @csrf
                <label class="mp-doc-card__btn mp-doc-card__btn--edit" title="{{ trans('app.replace_document') }}">
                  <i class="fa fa-pencil"></i>
                  <input type="file"
                    name="document"
                    class="mp-doc-replace-input"
                    accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                </label>
              </form>
              <form action="{{ route('merchant.verify.documents.delete', $attachment) }}"
                method="POST"
                class="mp-doc-delete-form"
                onsubmit="return confirm(@json(trans('messages.confirm_delete_verification_document')));">
                @csrf
                @method('DELETE')
                <button type="submit" class="mp-doc-card__btn mp-doc-card__btn--danger" title="{{ trans('app.delete_document') }}">
                  <i class="fa fa-trash"></i>
                </button>
              </form>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @elseif ($editable)
    <div class="mp-doc-empty">
      <i class="fa fa-folder-open-o"></i>
      <p>{{ trans('messages.no_verification_documents_uploaded') }}</p>
    </div>
  @endif

  @if ($editable)
    <form action="{{ route('merchant.verify.documents.store') }}" method="POST" enctype="multipart/form-data" class="mp-doc-upload-form" data-doc-type="{{ $documentType }}">
      @csrf
      <input type="hidden" name="document_type" value="{{ $documentType }}">
      <div class="mp-upload-zone mp-upload-zone--{{ $documentType }}">
        <input type="file" name="documents[]" multiple class="mp-documents-input" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" />
        <label class="mp-upload-zone__trigger">
          <div class="mp-upload-zone__icon"><i class="fa fa-cloud-upload"></i></div>
          <div class="mp-upload-zone__title">{{ trans('app.add_documents') }}</div>
          <div class="mp-upload-zone__text">
            {{ $documentType === 'store' ? trans('messages.verification_store_document_upload_help') : trans('messages.verification_person_document_upload_help') }}
          </div>
          <div class="mp-upload-zone__hint">{{ trans('messages.verification_document_types') }}</div>
        </label>
        <div class="mp-upload-zone__footer mp-upload-footer" hidden>
          <div class="mp-upload-zone__filename mp-upload-filename"></div>
          <button type="submit" class="mp-btn mp-btn--secondary">
            <i class="fa fa-upload"></i> {{ trans('app.upload_selected_documents') }}
          </button>
        </div>
      </div>
    </form>
  @endif
</div>
