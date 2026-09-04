@extends('admin.layouts.master')

@php
  $can_update = Gate::allows('update', $config) ?? null;
  $configUpdateRoute = panel_route_name('admin.setting.config.update');
  $toggleRoute = panel_route_name('admin.setting.config.notification.toggle');
@endphp

@section('page_title')
  {{ trans('nav.configurations') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_tabbed_start', [
    'title' => trans('nav.configurations'),
    'icon' => 'fa-cog',
  ])

      <ul class="nav nav-tabs nav-justified admin-tabs">
        <li class="active"><a href="#order-tab" data-toggle="tab">
            <i class="fa fa-shopping-cart hidden-sm"></i>
            {{ trans('app.order') }}
          </a></li>
        <li><a href="#storefront-tab" data-toggle="tab">
            <i class="fa fa-laptop hidden-sm"></i>
            {{ trans('app.store_front') }}
          </a></li>
        <li><a href="#support-tab" data-toggle="tab">
            <i class="fa fa-phone hidden-sm"></i>
            {{ trans('app.support') }}
          </a></li>
        <li><a href="#notifications-tab" data-toggle="tab">
            <i class="fa fa-bell-o hidden-sm"></i>
            {{ trans('app.notifications') }}
          </a></li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane active" id="order-tab">
          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => [$configUpdateRoute, $config], 'files' => true, 'id' => 'merchant-config-order', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-8 col-sm-offset-1">
              <div class="form-group">
                {!! Form::label('order_number_prefix', trans('app.order_number_prefix') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.order_number_prefix_suffix') }}"></i>
                <div class="col-sm-2 nopadding-left">
                  @if ($can_update)
                    {!! Form::text('order_number_prefix', $config->order_number_prefix, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.order_number_prefix')]) !!}
                  @else
                    <span>{{ $config->order_number_prefix }}</span>
                  @endif
                </div>

                {!! Form::label('order_number_suffix', trans('app.and') . ' ' . trans('app.suffix') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <div class="col-sm-2 nopadding-left">
                  @if ($can_update)
                    {!! Form::text('order_number_suffix', $config->order_number_suffix, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.order_number_suffix')]) !!}
                  @else
                    <span>{{ $config->order_number_suffix }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('default_tax_id', trans('app.default_tax') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.default_tax_id') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    {!! Form::select('default_tax_id', $taxes, $config->default_tax_id, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
                  @else
                    <span>{{ optional($config->tax)->name }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('order_handling_cost', trans('app.order_handling_cost') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_order_handling_cost') }}"></i>
                <div class="col-sm-7 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      @if (get_currency_prefix())
                        <span class="input-group-addon">{{ get_currency_prefix() }}</span>
                      @endif
                      {!! Form::number('order_handling_cost', get_formated_decimal($config->order_handling_cost), ['class' => 'form-control', 'placeholder' => trans('app.placeholder.order_handling_cost'), 'min' => 0]) !!}
                      @if (get_currency_suffix())
                        <span class="input-group-addon">{{ get_currency_suffix() }}</span>
                      @endif
                    </div>
                  @else
                    <span>{{ get_formated_decimal($config->order_handling_cost) }}</span>
                  @endif
                </div>
              </div>

              @include('merchant.config.partials._shipping_settings')

              @include('merchant.config.partials._toggle_row', [
                'field' => 'auto_archive_order',
                'label' => trans('app.auto_archive_order'),
                'help' => trans('help.config_auto_archive_order'),
                'active' => $config->auto_archive_order == 1,
              ])

              @include('merchant.config.partials._toggle_row', [
                'field' => 'pay_online',
                'label' => trans('app.pay_online'),
                'help' => trans('help.pay_online'),
                'active' => $config->pay_online == 1,
              ])

              @include('merchant.config.partials._toggle_row', [
                'field' => 'pickup_enabled',
                'label' => trans('theme.pickup'),
                'help' => trans('help.config_enable_pickup_order'),
                'active' => $config->isPickupEnabled(),
              ])

              @if ($can_update)
                <div class="col-md-offset-4">
                  {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
                </div>
              @endif
            </div>
            {!! Form::close() !!}
          </div>
        </div>

        <div class="tab-pane" id="storefront-tab">
          <div class="row">
            <div class="col-sm-8 col-sm-offset-2">
              @include('merchant.config.partials._toggle_row', [
                'field' => 'active_ecommerce',
                'label' => trans('app.active_ecommerce'),
                'help' => trans('help.active_ecommerce'),
                'active' => $config->active_ecommerce == 1,
              ])

              @include('merchant.config.partials._toggle_row', [
                'field' => 'show_shop_desc_with_listing',
                'label' => trans('app.show_shop_desc_with_listing'),
                'help' => trans('help.show_shop_desc_with_listing'),
                'active' => $config->show_shop_desc_with_listing == 1,
              ])

              @include('merchant.config.partials._toggle_row', [
                'field' => 'show_refund_policy_with_listing',
                'label' => trans('app.show_refund_policy_with_listing'),
                'help' => trans('help.show_refund_policy_with_listing'),
                'active' => $config->show_refund_policy_with_listing == 1,
              ])
            </div>
          </div>
        </div>

        <div class="tab-pane" id="support-tab">
          <div class="row">
            {!! Form::model($config, ['method' => 'PUT', 'route' => [$configUpdateRoute, $config], 'files' => true, 'id' => 'merchant-config-support', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
            <div class="col-sm-12">
              <div class="form-group">
                {!! Form::label('support_phone', trans('app.support_phone') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_phone') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                      {!! Form::text('support_phone', $config->support_phone, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_phone')]) !!}
                    </div>
                  @else
                    <span>{{ $config->support_phone }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('support_email', '*' . trans('app.support_email') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.support_email') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    <div class="input-group">
                      <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                      {!! Form::email('support_email', $config->support_email, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.support_email'), 'required']) !!}
                    </div>
                  @else
                    <span>{{ $config->support_email }}</span>
                  @endif
                </div>
              </div>

              <div class="form-group">
                {!! Form::label('return_refund', '*' . trans('app.form.config_return_refund') . ':', ['class' => 'with-help col-sm-3 control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_return_refund') }}"></i>
                <div class="col-sm-6 nopadding-left">
                  @if ($can_update)
                    {!! Form::textarea('return_refund', $config->return_refund, ['class' => 'form-control summernote', 'placeholder' => trans('app.placeholder.config_return_refund'), 'required']) !!}
                  @else
                    <span>{!! $config->return_refund !!}</span>
                  @endif
                </div>
              </div>

              @if ($can_update)
                <div class="col-md-offset-3">
                  {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
                </div>
              @endif
            </div>
            {!! Form::close() !!}
          </div>
        </div>

        <div class="tab-pane" id="notifications-tab">
          <div class="row mb-5">
            <div class="col-sm-8 col-sm-offset-2">
              <fieldset>
                <legend>{{ trans('app.order') }}</legend>

                @include('merchant.config.partials._toggle_row', [
                  'field' => 'notify_new_order',
                  'label' => trans('app.notify_new_order'),
                  'help' => trans('help.notify_new_order'),
                  'active' => $config->notify_new_order == 1,
                ])

                @include('merchant.config.partials._toggle_row', [
                  'field' => 'notify_abandoned_checkout',
                  'label' => trans('app.notify_abandoned_checkout'),
                  'help' => trans('help.notify_abandoned_checkout'),
                  'active' => $config->notify_abandoned_checkout == 1,
                ])

                @include('merchant.config.partials._toggle_row', [
                  'field' => 'notify_new_disput',
                  'label' => trans('app.notify_new_dispute'),
                  'help' => trans('help.notify_new_dispute'),
                  'active' => $config->notify_new_disput == 1,
                ])
              </fieldset>

              @if (is_catalog_enabled())
                <fieldset>
                  <legend>{{ trans('app.inventory') }}</legend>

                  @include('merchant.config.partials._toggle_row', [
                    'field' => 'notify_alert_quantity',
                    'label' => trans('app.notify_alert_quantity'),
                    'help' => trans('help.notify_alert_quantity'),
                    'active' => $config->notify_alert_quantity == 1,
                  ])

                  @include('merchant.config.partials._toggle_row', [
                    'field' => 'notify_inventory_out',
                    'label' => trans('app.notify_inventory_out'),
                    'help' => trans('help.notify_inventory_out'),
                    'active' => $config->notify_inventory_out == 1,
                  ])
                </fieldset>
              @endif
            </div>
          </div>
        </div>
      </div>

  @include('admin.partials.ui.card_end')
@endsection
