@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.verification') }}
@endsection

@section('content')
  @php
    $storeAddress = $config->shop->storeAddress();
    $hasLocation = $config->shop->hasStoreLocation();
    $hasDocuments = count($config->attachments) > 0;
    $stepRegister = true;
    $stepLocation = $hasLocation;
    $stepDocuments = $hasDocuments;
    $stepSubmitted = (bool) $config->pending_verification;
    $stepApproved = $config->shop->isVerified();
  @endphp

  <div class="row admin-verify">
    <div class="col-md-12">
      <div class="box admin-verify__onboarding">
        <div class="box-body">
          <h3 class="admin-verify__onboarding-title">{{ trans('messages.seller_onboarding_title') }}</h3>
          <p class="text-muted">{{ trans('messages.seller_onboarding_subtitle') }}</p>

          <ol class="admin-verify__steps list-unstyled">
            <li class="admin-verify__step {{ $stepRegister ? 'is-done' : '' }}">
              <span class="admin-verify__step-icon"><i class="fa fa-user-plus"></i></span>
              <span class="admin-verify__step-label">{{ trans('messages.seller_onboarding_step_register') }}</span>
            </li>
            <li class="admin-verify__step {{ $stepLocation ? 'is-done' : ($stepRegister ? 'is-active' : '') }}">
              <span class="admin-verify__step-icon"><i class="fa fa-map-marker"></i></span>
              <span class="admin-verify__step-label">{{ trans('messages.seller_onboarding_step_location') }}</span>
            </li>
            <li class="admin-verify__step {{ $stepDocuments ? 'is-done' : ($stepLocation ? 'is-active' : '') }}">
              <span class="admin-verify__step-icon"><i class="fa fa-file-text-o"></i></span>
              <span class="admin-verify__step-label">{{ trans('messages.seller_onboarding_step_documents') }}</span>
            </li>
            <li class="admin-verify__step {{ $stepSubmitted || $stepApproved ? 'is-done' : ($stepDocuments && $stepLocation ? 'is-active' : '') }}">
              <span class="admin-verify__step-icon"><i class="fa fa-paper-plane"></i></span>
              <span class="admin-verify__step-label">{{ trans('messages.seller_onboarding_step_submit') }}</span>
            </li>
          </ol>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      @if ($config->shop->isVerified())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.verification'),
          'icon' => 'fa-shield',
        ])
          <div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ trans('messages.store_verification_approved_notice') }}</div>
        @include('admin.partials.ui.card_end')
      @elseif ($config->pending_verification)
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.verification'),
          'icon' => 'fa-shield',
        ])
          <div class="alert alert-warning"><i class="fa fa-clock-o"></i> {{ trans('messages.verification_request_pending_notice') }}</div>
          <p class="text-muted">{{ trans('messages.seller_onboarding_pending_help') }}</p>
        @include('admin.partials.ui.card_end')
      @elseif ($config->verification_rejected_at)
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.verification'),
          'icon' => 'fa-shield',
        ])
          <div class="alert alert-danger">
            <i class="fa fa-times-circle"></i> {{ trans('messages.verification_request_rejected_notice') }}
            @if ($config->verification_rejection_reason)
              <p class="mt-2"><strong>{{ trans('app.rejection_reason') }}:</strong> {{ $config->verification_rejection_reason }}</p>
            @endif
          </div>
        @include('admin.partials.ui.card_end')
      @endif

      @if ($config->canSubmitVerificationRequest())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.store_location'),
          'icon' => 'fa-map-marker',
        ])
          <p>{{ trans('messages.seller_onboarding_location_help') }}</p>

          @if ($hasLocation)
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ trans('app.store_location_set') }}</div>
            <p class="small text-muted">{{ $storeAddress->address_line_1 }}{{ $storeAddress->city ? ', '.$storeAddress->city : '' }}</p>
          @else
            <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> {{ trans('app.store_location_required') }}</div>
          @endif

          {!! Form::open(['route' => 'admin.setting.verify.location', 'id' => 'store-location-form', 'data-toggle' => 'validator']) !!}
            <div class="row">
              <div class="col-md-8">
                <div class="form-group">
                  {!! Form::label('address_line_1', trans('app.form.address_line_1')) !!}
                  {!! Form::text('address_line_1', old('address_line_1', optional($storeAddress)->address_line_1), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.address_line_1')]) !!}
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  {!! Form::label('city', trans('app.form.city')) !!}
                  {!! Form::text('city', old('city', optional($storeAddress)->city), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.city')]) !!}
                </div>
              </div>
            </div>

            @if (config('services.google.place_api_key'))
              @include('partials.map_pin_picker', [
                'latitude' => old('latitude', optional($storeAddress)->latitude),
                'longitude' => old('longitude', optional($storeAddress)->longitude),
              ])
            @else
              <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> {{ trans('messages.seller_onboarding_map_unavailable') }}
                @if ($storeAddress)
                  <a href="javascript:void(0)" data-link="{{ route('address.edit', $storeAddress->id) }}" class="ajax-modal-btn">{{ trans('app.set_store_location') }}</a>
                @else
                  <a href="javascript:void(0)" data-link="{{ route('address.create', ['addressable_type' => 'App\\Models\\Shop', 'addressable_id' => $config->shop->id]) }}" class="ajax-modal-btn">{{ trans('app.set_store_location') }}</a>
                @endif
              </div>
            @endif

            {!! Form::submit(trans('app.save_store_location'), ['class' => 'btn btn-flat btn-new']) !!}
          {!! Form::close() !!}
        @include('admin.partials.ui.card_end')

        @include('admin.partials.ui.card_start', [
          'title' => trans('app.upload_documents'),
          'icon' => 'fa-upload',
        ])
          <p>{!! trans('messages.what_the_verification_documents_need') !!}</p>
          <p class="text-muted small">{!! trans('messages.verification_documents') !!}</p>

          @if (count($config->attachments))
            <ul class="list-group admin-verify__files mb-3">
              @foreach ($config->attachments as $attachment)
                <li class="list-group-item small">
                  <a href="{{ route('attachment.download', $attachment) }}"><i class="fa fa-cloud-download"></i> {{ $attachment->name }}</a>
                  <small>({{ get_formated_file_size($attachment->size) }})</small>
                </li>
              @endforeach
            </ul>
          @endif

          {!! Form::open(['route' => 'admin.setting.verify.submit', 'files' => true, 'id' => 'verification-form', 'data-toggle' => 'validator']) !!}
            <div class="row">
              <div class="col-xs-8">
                <input id="uploadFile" placeholder="{{ trans('app.upload_documents') }}" class="form-control" disabled="disabled" />
                <div class="help-block with-errors small"><i class="fa fa-info"></i> {{ trans('help.select_all_verification_documents') }}</div>
              </div>
              <div class="col-xs-4">
                <div class="fileUpload btn btn-primary btn-block btn-flat">
                  <span>{{ trans('app.form.upload') }}</span>
                  <input type="file" name="documents[]" multiple="true" id="uploadBtn" class="upload" required />
                </div>
              </div>
            </div>

            @if (config('hyperlocal.require_store_location_for_verification', true) && ! $hasLocation)
              <p class="text-warning small"><i class="fa fa-exclamation-triangle"></i> {{ trans('app.store_location_required') }}</p>
            @endif

            {!! Form::submit(trans('app.submit_verification_request'), ['class' => 'btn btn-flat btn-block btn-new mt-2']) !!}
          {!! Form::close() !!}
        @include('admin.partials.ui.card_end')
      @endif

      @include('admin.partials.ui.card_start', [
        'title' => trans('messages.how_the_verification_process_works'),
        'icon' => 'fa-info-circle',
      ])
        <p>{!! trans('messages.verification_intro') !!}</p>
        <p>{!! trans('messages.verification_process') !!}</p>
      @include('admin.partials.ui.card_end')
    </div>

    <div class="col-md-4 admin-verify__sidebar">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.verification_status'),
        'icon' => 'fa-check-circle',
        'bodyClass' => 'admin-order-sidebar-panel',
      ])
        <ul class="list-unstyled admin-verify__checklist">
          <li class="{{ $config->shop->id_verified ? 'text-success' : 'text-muted' }}">
            <i class="fa fa-{{ $config->shop->id_verified ? 'check' : 'times' }}-circle-o"></i> {{ trans('app.id_verified') }}
          </li>
          <li class="{{ $config->shop->phone_verified ? 'text-success' : 'text-muted' }}">
            <i class="fa fa-{{ $config->shop->phone_verified ? 'check' : 'times' }}-circle-o"></i> {{ trans('app.phone_verified') }}
          </li>
          <li class="{{ $config->shop->address_verified ? 'text-success' : 'text-muted' }}">
            <i class="fa fa-{{ $config->shop->address_verified ? 'check' : 'times' }}-circle-o"></i> {{ trans('app.address_verified') }}
          </li>
          <li class="{{ $hasLocation ? 'text-success' : 'text-muted' }}">
            <i class="fa fa-{{ $hasLocation ? 'check' : 'times' }}-circle-o"></i> {{ trans('app.store_location') }}
          </li>
          <li class="{{ $hasDocuments ? 'text-success' : 'text-muted' }}">
            <i class="fa fa-{{ $hasDocuments ? 'check' : 'times' }}-circle-o"></i> {{ trans('app.upload_documents') }}
          </li>
        </ul>
      @include('admin.partials.ui.card_end')

      <div class="admin-verify__example-box">
        <h4 class="small text-muted">{{ trans('messages.verified_business_name_like') }}</h4>
        <p class="admin-verify__example lead">
          <img src="{{ get_storage_file_url(optional($config->shop->logo)->path, 'tiny') }}" class="img-circle img-sm" alt="">
          <strong>{{ get_site_title() }}</strong>
          <img src="{{ get_verified_badge() }}" class="verified-badge img-xs" data-toggle="tooltip" title="{{ trans('help.verified_seller') }}" alt="">
        </p>
      </div>
    </div>
  </div>
@endsection

@section('page-style')
  <style>
    .admin-verify__onboarding { margin-bottom: 20px; }
    .admin-verify__onboarding-title { margin-top: 0; }
    .admin-verify__steps {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin: 20px 0 0;
      padding: 0;
    }
    .admin-verify__step {
      flex: 1 1 180px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 14px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      background: #fafafa;
      color: #6b7280;
    }
    .admin-verify__step.is-active {
      border-color: #3b82f6;
      background: #eff6ff;
      color: #1d4ed8;
    }
    .admin-verify__step.is-done {
      border-color: #10b981;
      background: #ecfdf5;
      color: #047857;
    }
    .admin-verify__step-icon {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,.06);
    }
    .admin-verify__step-label { font-weight: 600; font-size: 13px; }
    .admin-verify__files { margin-bottom: 0; }
    .admin-verify__example-box {
      margin-top: 16px;
      padding: 16px;
      border: 1px dashed #e5e7eb;
      border-radius: 8px;
      background: #fff;
    }
  </style>
@endsection
