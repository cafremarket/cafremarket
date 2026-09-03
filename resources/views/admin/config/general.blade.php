@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.general_settings') }}
@endsection

@php
  $can_update = Gate::allows('update', $shop->config) ?? null;
  $translation_language = app()->getLocale();
  $isMerchant = Auth::user()->isFromMerchant();
  $storeAddress = $storeAddress ?? $shop->storeAddress();
  $hasStoreLocation = $shop->hasStoreLocation();
@endphp

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.general_settings'),
    'icon' => 'fa-cog',
    'actions' => $isMerchant ? '' : view('admin.config._general_header_actions', compact('can_update', 'shop', 'translation_language'))->render(),
    'bodyClass' => '',
  ])

      <div class="row mt-4">
        {!! Form::model($shop, ['method' => 'PUT', 'route' => [panel_route_name('admin.setting.basic.config.update'), $shop->id], 'files' => true, 'id' => 'form', 'class' => 'form-horizontal', 'data-toggle' => 'validator']) !!}
        <div class="col-sm-8">
          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('name', '*' . trans('app.shop_name') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_name') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::text('name', $shop->name, ['class' => 'form-control input-lg', 'placeholder' => trans('app.placeholder.shop_name'), 'required']) !!}
                  <div class="help-block with-errors"></div>
                @else
                  <span class="lead">{{ $shop->name }}</span>
                @endif
              </div>
            </div> <!-- /.row -->
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('slug', '*' . trans('app.slug') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_url') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  @if ($isMerchant && !empty($pendingSlugChangeRequest))
                    <div class="alert alert-info">
                      <i class="fa fa-clock-o"></i> {{ trans('messages.slug_change_request_pending') }}
                      <br><strong>{{ trans('app.requested_slug') }}:</strong> <code>{{ $pendingSlugChangeRequest->requested_slug }}</code>
                    </div>
                    {!! Form::text('slug', $shop->slug, ['class' => 'form-control slug', 'readonly' => true]) !!}
                  @elseif ($isMerchant && !empty($slugChangeRequiresApproval))
                    {!! Form::text('slug', null, ['class' => 'form-control slug', 'placeholder' => trans('app.placeholder.slug'), 'required']) !!}
                    <p class="help-block text-muted">{{ trans('messages.slug_change_requires_approval') }}</p>
                    <div class="help-block with-errors"></div>
                  @else
                    {!! Form::text('slug', null, ['class' => 'form-control slug', 'placeholder' => trans('app.placeholder.slug'), 'required']) !!}
                    <div class="help-block with-errors"></div>
                  @endif
                @else
                  {{ get_shop_url($shop->id) }}
                  <a href="{{ get_shop_url($shop->id) }}" target="_blank">
                    <i class="small indent10 fa fa-external-link"></i>
                  </a>
                @endif
              </div>
            </div> <!-- /.row -->
          </div>

          @if (!$isMerchant)
          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('legal_name', '*' . trans('app.shop_legal_name') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_legal_name') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::text('legal_name', $shop->legal_name, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.shop_name'), 'required']) !!}
                  <div class="help-block with-errors"></div>
                @else
                  <span>{{ $shop->legal_name }}</span>
                @endif
              </div>
            </div> <!-- /.row -->
          </div>
          @else
            {!! Form::hidden('legal_name', $shop->legal_name ?: $shop->name) !!}
          @endif

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('email', '*' . trans('app.form.email_address') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_email') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::email('email', $shop->email, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.valid_email'), 'required']) !!}
                  <div class="help-block with-errors"></div>
                @else
                  <span>{{ $shop->email }}</span>
                @endif
              </div>
            </div> <!-- /.row -->
          </div>

          @if (!$isMerchant && is_incevio_package_loaded('livechat'))
            @include('liveChat::facebook.fb_chat_config_form')
          @endif

          @if (!$isMerchant)
          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('external_url', trans('app.form.external_url') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_external_url') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::text('external_url', $shop->external_url, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.external_url')]) !!}
                @else
                  <span>{{ $shop->external_url }}</span>
                @endif
              </div>
            </div> <!-- /.row -->
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('timezone_id', '*' . trans('app.form.timezone') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_timezone') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::select('timezone_id', $timezones, $shop->timezone_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.timezone'), 'required']) !!}
                  <div class="help-block with-errors"></div>
                @else
                  <span>{{ $shop->timezone->text }}</span>
                @endif
              </div>
            </div> <!-- /.row -->
          </div>
          @else
            {!! Form::hidden('timezone_id', $shop->timezone_id) !!}
            {!! Form::hidden('external_url', $shop->external_url) !!}
          @endif

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('description', trans('app.form.description') . ':', ['class' => 'with-help control-label']) !!}
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::textarea('description', $shop->description, ['class' => 'form-control summernote-without-toolbar', 'placeholder' => trans('app.placeholder.description'), 'rows' => '3']) !!}
                @else
                  <span>{{ $shop->description }}</span>
                @endif
              </div>
            </div> <!-- /.row -->
          </div>

          @if ($can_update)
            <div class="form-group">
              <div class="row">
                <div class="col-sm-4 text-right">
                  <label for="exampleInputFile" class="with-help control-label"> {{ trans('app.form.logo') }}</label>
                </div>
                <div class="col-md-6 nopadding">
                  <input id="uploadFile" placeholder="{{ trans('app.placeholder.logo') }}" class="form-control" disabled="disabled" style="height: 28px;" />
                  <div class="help-block with-errors">{{ trans('help.logo_img_size') }}</div>
                </div>
                <div class="col-md-2 nopadding-left">
                  <div class="fileUpload btn btn-primary btn-block btn-flat">
                    <span>{{ trans('app.form.upload') }}</span>
                    <input type="file" name="logo" id="uploadBtn" class="upload" />
                  </div>
                </div>
              </div> <!-- /.row -->
            </div>

            <div class="form-group">
              <div class="row">
                <div class="col-sm-4 text-right">
                  {!! Form::label('exampleInputFile', trans('app.form.cover_img'), ['class' => 'with-help control-label']) !!}
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.cover_img', ['page' => trans('app.shop')]) }}"></i>
                </div>
                <div class="col-md-6 nopadding">
                  <input id="uploadFile1" placeholder="{{ trans('app.placeholder.cover_image') }}" class="form-control" disabled="disabled" style="height: 28px;" />
                  <div class="help-block with-errors">{{ trans('help.cover_img_size') }}</div>
                </div>
                <div class="col-md-2 nopadding-left">
                  <div class="fileUpload btn btn-primary btn-block btn-flat">
                    <span>{{ trans('app.form.upload') }} </span>
                    <input type="file" name="cover_image" id="uploadBtn1" class="upload" />
                  </div>
                </div>
              </div> <!-- /.row -->
            </div>

            @if (!$isMerchant)
            <div class="form-group">
              <div class="row">
                <div class="col-sm-4 text-right">
                  {!! Form::label('stamp_image', trans('app.form.stamp_image'), ['class' => 'with-help control-label']) !!}
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.stamp_img', ['page' => trans('app.shop')]) }}"></i>
                </div>
                <div class="col-md-6 nopadding">
                  <input id="uploadFile2" placeholder="{{ trans('app.placeholder.stamp_image') }}" class="form-control" disabled="disabled" style="height: 28px;" />
                  <div class="help-block with-errors">{{ trans('help.stamp_img_size') }}</div>
                </div>
                <div class="col-md-2 nopadding-left">
                  <div class="fileUpload btn btn-primary btn-block btn-flat">
                    <span>{{ trans('app.form.upload') }} </span>
                    <input type="file" name="stamp_image" id="uploadBtn2" class="upload" />
                  </div>
                </div>
              </div> <!-- /.row -->
            </div>
            @endif
          @endif

          @if (!$isMerchant)
          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('service_radius_km', trans('app.service_radius_km') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.service_radius_km') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::number('service_radius_km', $shop->service_radius_km ?? config('hyperlocal.default_shop_service_radius_km', 5), ['class' => 'form-control', 'min' => 1, 'max' => 100, 'step' => '0.5']) !!}
                @else
                  <span>{{ $shop->service_radius_km ?? config('hyperlocal.default_shop_service_radius_km', 5) }} km</span>
                @endif
              </div>
            </div>
          </div>
          @endif

          @if (Auth::user()->isFromPlatform())
          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 text-right">
                {!! Form::label('delivery_capability', trans('app.delivery_capability') . ':', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.delivery_capability') }}"></i>
              </div>
              <div class="col-sm-8 nopadding-left">
                @if ($can_update)
                  {!! Form::select('delivery_capability', [
                    'both' => trans('app.delivery_capability_both'),
                    'shop_only' => trans('app.delivery_capability_shop_only'),
                    'system_only' => trans('app.delivery_capability_system_only'),
                  ], $shop->delivery_capability ?? 'both', ['class' => 'form-control select2-normal']) !!}
                @else
                  <span>{{ trans('app.delivery_capability_' . ($shop->delivery_capability ?? 'both') . '_label') }}</span>
                @endif
              </div>
            </div>
          </div>
          @endif

          @if ($can_update)
            <div class="row">
              <div class="col-md-4 text-right">
                <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
              </div>
              <div class="col-md-8 text-right">
                {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
              </div>
            </div> <!-- /.row -->
          @endif

          <div class="clearfix spacer30"></div>
        </div>

        <div class="col-sm-4">
          @if ($can_update && !$isMerchant)
            <div class="form-group text-center">
              {!! Form::label('maintenance_mode', trans('app.form.maintenance_mode'), ['class' => 'control-label with-help']) !!}
              <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_maintenance_mode_handle') }}"></i>
              <div class="handle">
                <a href="javascript:void(0)" data-link="{{ panel_route('admin.setting.config.maintenanceMode.toggle', $shop) }}" type="button" class="toggle-confirm btn btn-lg btn-secondary btn-toggle {{ $shop->config->maintenance_mode == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $shop->config->maintenance_mode == 1 ? 'true' : 'false' }}" autocomplete="off">
                  <div class="btn-handle"></div>
                </a>
              </div>
            </div>
          @endif

          @if (!$isMerchant)
            <div class="text-center mb-4">
              <div class="form-group">
                {!! Form::label('shop_address', trans('app.shop_address'), ['class' => 'control-label with-help']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.shop_address') }}"></i>
              </div>

              @if ($shop->primaryAddress)
                {!! $shop->primaryAddress->toHtml() !!}
              @else
                <p class="text-muted">{{ trans('app.store_location_required') }}</p>
              @endif
            </div>
          @elseif ($hasStoreLocation && $storeAddress)
            <div class="text-center mb-4">
              <div class="form-group">
                {!! Form::label('shop_address', trans('app.shop_address'), ['class' => 'control-label with-help']) !!}
              </div>
              <div class="text-left">{!! $storeAddress->toHtml() !!}</div>
            </div>
          @endif

          @if (is_incevio_package_loaded('wallet') && !$isMerchant)
            <div class="form-group text-center mt-4">
              {!! Form::label('bank_info', trans('app.bank_info'), ['class' => 'control-label with-help']) !!}
              <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.payout_bank_info') }}"></i>
            </div>

            <div class="text-center py-0 text-dark">
              <p>
                {{ trans('app.bank_name') . ': ' }}
                {{ $shop_config->bank_name }}
              </p>
              <p>
                {{ trans('app.form.account_holder_name') . ': ' }}
                {{ $shop_config->ac_holder_name }}
              </p>
              <p>
                {{ trans('app.form.account_number') . ': ' }}
                {{ $shop_config->ac_number }}
              </p>

              @if ($shop_config->ac_type)
                <p>
                  {{ trans('app.form.account_type') . ': ' }}
                  {{ $shop_config->ac_type }}
                </p>
              @endif

              @if ($shop_config->ac_routing_number)
                <p>
                  {{ trans('app.form.account_routing_number') . ': ' }}
                  {{ $shop_config->ac_routing_number }}
                </p>
              @endif

              <p>
                {{ trans('app.form.ac_swift_bic_code') . ': ' }}
                {{ $shop_config->ac_swift_bic_code }}
              </p>

              @if ($shop_config->ac_iban)
                <p>
                  {{ trans('app.form.ac_iban') . ': ' }}
                  {{ $shop_config->ac_iban }}
                </p>
              @endif

              <p>
                {{ trans('app.form.ac_bank_address') . ': ' }}
                {{ $shop_config->ac_bank_address }}
              </p>
            </div>

            <div class="my-5 text-center">
              <a href="javascript:void(0)" data-link="{{ panel_route('admin.setting.bankInfo.edit', [$shop->id]) }}" class="btn btn-default ajax-modal-btn">
                <i class="fa fa-money"></i>
                @lang('app.update_bank_detail')
              </a>
            </div>
          @endif

          @if (isset($shop) && $shop->logoImage)
            <div class="form-group text-center">
              <label class="with-help control-label"> {{ trans('app.logo') }}</label>

              <img src="{{ get_storage_file_url(optional($shop->logoImage)->path, 'medium') }}" alt="{{ trans('app.logo') }}">

              <label class="mt-3">
                {!! Form::checkbox('delete_logo', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_logo') }}
              </label>
            </div>
          @endif

          @if (isset($shop) && $shop->coverImage)
            <div class="form-group text-center">
              <label class="with-help control-label"> {{ trans('app.cover_image') }}</label>

              <img src="{{ get_storage_file_url(optional($shop->coverImage)->path, 'medium') }}" width="" alt="{{ trans('app.cover_image') }}">

              <label class="mt-3">
                {!! Form::checkbox('delete_cover_image', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_image') }}
              </label>
            </div>
          @endif

          @if (isset($shop) && $shop->stampImage)
            <div class="form-group text-center">
              <label class="with-help control-label"> {{ trans('app.stamp_image') }}</label>

              <img src="{{ get_storage_file_url(optional($shop->stampImage)->path, 'medium') }}" width="" alt="{{ trans('app.stamp_image') }}">

              <label class="mt-3">
                {!! Form::checkbox('delete_stamp_image', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_image') }}
              </label>
            </div>
          @endif

        </div>
        {!! Form::close() !!}
      </div>

      @if ($isMerchant && $can_update)
        <div class="row mt-4">
          <div class="col-sm-8 col-sm-offset-2">
            <div class="box box-solid">
              <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-map-marker"></i> {{ trans('app.shop_address') }}</h3>
              </div>
              <div class="box-body">
                @if (!empty($pendingAddressChangeRequest))
                  <div class="alert alert-info">
                    <i class="fa fa-clock-o"></i> {{ trans('messages.address_change_request_pending') }}
                  </div>
                @elseif (!$hasStoreLocation)
                  <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> {{ trans('app.store_location_required') }}
                  </div>
                @endif

                @if (empty($pendingAddressChangeRequest))
                  {!! Form::open(['route' => 'merchant.verify.location', 'id' => 'store-location-form', 'class' => 'mp-address-wizard-form']) !!}
                    {!! Form::hidden('_from', 'general') !!}
                    @include('merchant.verify.partials.address_wizard', [
                      'wizardId' => 'shop-settings-address-wizard',
                      'address' => $storeAddress,
                      'countries' => $countries ?? [],
                      'states' => $states ?? [],
                      'defaultAddressTitle' => $shop->name,
                      'defaultPhone' => optional($shop->config)->support_phone ?? Auth::user()->phone,
                      'submitLabel' => trans('app.update_address'),
                    ])
                  {!! Form::close() !!}
                @endif
              </div>
            </div>
          </div>
        </div>
      @endif

  @include('admin.partials.ui.card_end')
@endsection
