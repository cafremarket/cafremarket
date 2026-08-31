@php
  $storeAddress = $shop->storeAddress();
  $hasLocation = $shop->hasStoreLocation();
  $isVerified = $shop->isVerified();
  $isPending = optional($shop->config)->pending_verification;
  $config = $shop->config;
  $personAttachments = $config ? $config->personVerificationAttachments() : collect();
  $storeAttachments = $config ? $config->storeVerificationAttachments() : collect();
  $shopPhone = optional($config)->support_phone ?: optional($shop->owner)->phone;
  $shopEmail = optional($config)->support_email ?: ($shop->email ?: optional($shop->owner)->email);
@endphp

<div class="modal-dialog modal-lg">
  <div class="modal-content admin-verify-modal">
    @if ($isPending)
      {!! Form::open(['method' => 'POST', 'route' => ['admin.vendor.shop.verify.approve', $shop->id], 'id' => 'form', 'data-toggle' => 'validator']) !!}
    @endif

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <h4 class="modal-title">
        <i class="fa fa-shield text-muted"></i>
        {{ trans('app.review_verification_request') }}
      </h4>
      <p class="admin-verify-modal__subtitle">{{ $shop->name }}</p>
    </div>

    <div class="modal-body">
      @if ($isPending)
        <div class="alert alert-info admin-verify-modal__alert">
          <i class="fa fa-info-circle"></i> {{ trans('messages.verification_request_pending_review') }}
        </div>
      @elseif ($isVerified)
        <div class="alert alert-success admin-verify-modal__alert">
          <i class="fa fa-check-circle"></i> {{ trans('messages.store_already_verified') }}
        </div>
      @elseif (optional($shop->config)->verification_rejected_at)
        <div class="alert alert-warning admin-verify-modal__alert">
          <i class="fa fa-times-circle"></i> {{ trans('app.verification_rejected') }}
          @if ($shop->config->verification_rejection_reason)
            <p class="admin-verify-modal__alert-note"><strong>{{ trans('app.rejection_reason') }}:</strong> {{ $shop->config->verification_rejection_reason }}</p>
          @endif
        </div>
      @endif

      <div class="row">
        <div class="col-sm-4">
          <div class="admin-verify-modal__meta">
            <span class="admin-verify-modal__meta-label">{{ trans('app.owner') }}</span>
            <span class="admin-verify-modal__meta-value">{{ optional($shop->owner)->getName() ?? trans('app.not_available') }}</span>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="admin-verify-modal__meta">
            <span class="admin-verify-modal__meta-label">{{ trans('app.verification') }}</span>
            <span class="admin-verify-modal__meta-value">
              <span class="label label-{{ $isVerified ? 'success' : ($isPending ? 'warning' : 'default') }}">{{ $shop->getVerificationStatus() }}</span>
            </span>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="admin-verify-modal__meta">
            <span class="admin-verify-modal__meta-label">{{ trans('app.requested_at') }}</span>
            <span class="admin-verify-modal__meta-value">{{ optional($config?->updated_at)->diffForHumans() ?? trans('app.not_available') }}</span>
          </div>
        </div>
      </div>

      <ul class="nav nav-tabs admin-verify-tabs" role="tablist">
        <li class="active" role="presentation">
          <a href="#admin-verify-tab-person" aria-controls="admin-verify-tab-person" role="tab" data-toggle="tab">
            <i class="fa fa-user"></i> {{ trans('app.person_verification') }}
            @if ($shop->id_verified)<span class="label label-success label-xs">{{ trans('app.verified') }}</span>@endif
          </a>
        </li>
        <li role="presentation">
          <a href="#admin-verify-tab-store" aria-controls="admin-verify-tab-store" role="tab" data-toggle="tab">
            <i class="fa fa-store"></i> {{ trans('app.store_verification') }}
            @if ($shop->address_verified)<span class="label label-success label-xs">{{ trans('app.verified') }}</span>@endif
          </a>
        </li>
        <li role="presentation">
          <a href="#admin-verify-tab-contact" aria-controls="admin-verify-tab-contact" role="tab" data-toggle="tab">
            <i class="fa fa-envelope"></i> {{ trans('app.phone_and_email_verification') }}
            @if ($shop->phone_verified)<span class="label label-success label-xs">{{ trans('app.verified') }}</span>@endif
          </a>
        </li>
      </ul>

      <div class="tab-content admin-verify-tabs__content">
        {{-- Tab 1: Person --}}
        <div role="tabpanel" class="tab-pane active" id="admin-verify-tab-person">
          <div class="admin-verify-modal__section">
            <h5 class="admin-verify-modal__section-title"><i class="fa fa-user"></i> {{ trans('app.person_verification') }}</h5>
            <p class="text-muted admin-verify-modal__help">{{ trans('messages.verification_tab_person_help') }}</p>

            <ul class="admin-verify-modal__checklist">
              <li class="admin-verify-modal__checklist-item {{ $shop->id_verified ? 'is-done' : 'is-pending' }}">
                <span class="admin-verify-modal__checklist-icon"><i class="fa fa-user"></i></span>
                <span class="admin-verify-modal__checklist-label">{{ trans('app.person_verification') }}</span>
                <span class="admin-verify-modal__checklist-status">
                  @if ($shop->id_verified)
                    <i class="fa fa-check-circle"></i> {{ trans('app.verified') }}
                  @else
                    <i class="fa fa-circle-o"></i> {{ trans('app.not_verified') }}
                  @endif
                </span>
              </li>
            </ul>
          </div>

          <div class="admin-verify-modal__section">
            <h5 class="admin-verify-modal__section-title"><i class="fa fa-file-text-o"></i> {{ trans('app.person_verification_documents') }}</h5>
            @if ($personAttachments->count())
              <ul class="admin-verify-modal__files">
                @foreach ($personAttachments as $attachment)
                  <li>
                    <a href="{{ route('attachment.download', $attachment) }}" class="admin-verify-modal__file">
                      <span class="admin-verify-modal__file-icon"><i class="fa fa-file-o"></i></span>
                      <span class="admin-verify-modal__file-name">{{ $attachment->name }}</span>
                      <span class="admin-verify-modal__file-size">{{ get_formated_file_size($attachment->size) }}</span>
                      <span class="admin-verify-modal__file-action"><i class="fa fa-download"></i></span>
                    </a>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="text-muted admin-verify-modal__empty">{{ trans('messages.no_verification_documents_uploaded') }}</p>
            @endif
          </div>
        </div>

        {{-- Tab 2: Store --}}
        <div role="tabpanel" class="tab-pane" id="admin-verify-tab-store">
          <div class="admin-verify-modal__section">
            <h5 class="admin-verify-modal__section-title"><i class="fa fa-map-marker"></i> {{ trans('app.address_verification') }}</h5>

            @if ($hasLocation)
              <p class="admin-verify-modal__location">
                {{ $storeAddress->address_line_1 }}{{ $storeAddress->city ? ', '.$storeAddress->city : '' }}
                @if ($storeAddress->latitude && $storeAddress->longitude)
                  <br><small class="text-muted">{{ number_format($storeAddress->latitude, 6) }}, {{ number_format($storeAddress->longitude, 6) }}</small>
                @endif
              </p>
            @else
              <div class="alert alert-warning admin-verify-modal__alert">
                <i class="fa fa-exclamation-triangle"></i> {{ trans('app.store_location_required') }}
              </div>
            @endif

            <ul class="admin-verify-modal__checklist">
              <li class="admin-verify-modal__checklist-item {{ $shop->address_verified ? 'is-done' : 'is-pending' }}">
                <span class="admin-verify-modal__checklist-icon"><i class="fa fa-map-marker"></i></span>
                <span class="admin-verify-modal__checklist-label">{{ trans('app.address_verification') }}</span>
                <span class="admin-verify-modal__checklist-status">
                  @if ($shop->address_verified)
                    <i class="fa fa-check-circle"></i> {{ trans('app.verified') }}
                  @else
                    <i class="fa fa-circle-o"></i> {{ trans('app.not_verified') }}
                  @endif
                </span>
              </li>
            </ul>
          </div>

          <div class="admin-verify-modal__section">
            <h5 class="admin-verify-modal__section-title"><i class="fa fa-file-text-o"></i> {{ trans('app.store_document_verification') }}</h5>
            @if ($storeAttachments->count())
              <ul class="admin-verify-modal__files">
                @foreach ($storeAttachments as $attachment)
                  <li>
                    <a href="{{ route('attachment.download', $attachment) }}" class="admin-verify-modal__file">
                      <span class="admin-verify-modal__file-icon"><i class="fa fa-file-o"></i></span>
                      <span class="admin-verify-modal__file-name">{{ $attachment->name }}</span>
                      <span class="admin-verify-modal__file-size">{{ get_formated_file_size($attachment->size) }}</span>
                      <span class="admin-verify-modal__file-action"><i class="fa fa-download"></i></span>
                    </a>
                  </li>
                @endforeach
              </ul>
            @else
              <p class="text-muted admin-verify-modal__empty">{{ trans('messages.no_verification_documents_uploaded') }}</p>
            @endif
          </div>
        </div>

        {{-- Tab 3: Contact --}}
        <div role="tabpanel" class="tab-pane" id="admin-verify-tab-contact">
          <div class="admin-verify-modal__section">
            <h5 class="admin-verify-modal__section-title"><i class="fa fa-phone"></i> {{ trans('app.phone_and_email_verification') }}</h5>
            <p class="text-muted admin-verify-modal__help">{{ trans('messages.verification_tab_contact_help') }}</p>

            <div class="row">
              <div class="col-sm-6">
                <div class="admin-verify-modal__meta">
                  <span class="admin-verify-modal__meta-label">{{ trans('app.form.phone') }}</span>
                  <span class="admin-verify-modal__meta-value">{{ $shopPhone ?: trans('app.not_available') }}</span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="admin-verify-modal__meta">
                  <span class="admin-verify-modal__meta-label">{{ trans('app.form.email_address') }}</span>
                  <span class="admin-verify-modal__meta-value">{{ $shopEmail ?: trans('app.not_available') }}</span>
                </div>
              </div>
            </div>

            <ul class="admin-verify-modal__checklist admin-verify-modal__checklist--spaced">
              <li class="admin-verify-modal__checklist-item {{ $shop->phone_verified ? 'is-done' : 'is-pending' }}">
                <span class="admin-verify-modal__checklist-icon"><i class="fa fa-phone"></i></span>
                <span class="admin-verify-modal__checklist-label">{{ trans('app.phone_and_email_verification') }}</span>
                <span class="admin-verify-modal__checklist-status">
                  @if ($shop->phone_verified)
                    <i class="fa fa-check-circle"></i> {{ trans('app.verified') }}
                  @else
                    <i class="fa fa-circle-o"></i> {{ trans('app.not_verified') }}
                  @endif
                </span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      @if ($isPending)
        <p class="admin-verify-modal__approve-note">
          <i class="fa fa-info-circle"></i> {{ trans('messages.verification_approve_sets_all') }}
        </p>
      @endif
    </div>

    <div class="modal-footer admin-verify-modal__footer">
      @if ($isPending)
        <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify.reject.form', $shop) }}" class="ajax-modal-btn btn btn-default btn-flat">
          <i class="fa fa-times"></i> {{ trans('app.reject_verification') }}
        </a>
        {!! Form::submit(trans('app.approve_verification'), ['class' => 'btn btn-success btn-flat']) !!}
      @elseif ($isVerified)
        @can('update', $shop)
          <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">{{ trans('app.cancel') }}</button>
          <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify.revert.form', $shop) }}" class="ajax-modal-btn btn btn-warning btn-flat">
            <i class="fa fa-undo"></i> {{ trans('app.revert_verification') }}
          </a>
        @endcan
      @else
        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">{{ trans('app.cancel') }}</button>
      @endif
    </div>

    @if ($isPending)
      {!! Form::close() !!}
    @endif
  </div>
</div>
