{!! Form::model($system, ['method' => 'PUT', 'route' => ['admin.setting.system.update'], 'files' => true, 'id' => 'form-system-basic', 'class' => 'form-horizontal ajax-form', 'data-toggle' => 'validator']) !!}
<div class="row">
  <div class="col-sm-6">
    @if (is_subscription_enabled())
      <fieldset>
        <legend>{{ trans('app.config_subscription_section') }}</legend>
        <div class="form-group">
          {!! Form::label('trial_days', trans('app.config_trial_days') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
          <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.config_trial_days') }}"></i>
          <div class="col-sm-5 nopadding-left">
            @if ($can_update)
              <div class="input-group">
                {!! Form::number('trial_days', $system->trial_days, ['class' => 'form-control', 'max' => '730', 'placeholder' => trans('app.placeholder.trial_days')]) !!}
                <span class="input-group-addon">{{ trans('app.form.days') }}</span>
              </div>
            @else
              <span>{{ $system->trial_days }}</span>
            @endif
          </div>
        </div>
        @include('admin.system.config._sections._toggle', [
          'field' => 'required_card_upfront',
          'label' => trans('app.required_card_upfront'),
          'help' => trans('help.required_card_upfront'),
          'active' => (bool) $system->required_card_upfront,
        ])
      </fieldset>
    @endif

    <fieldset>
      <legend>{{ trans('app.vendors') }}</legend>
      @include('admin.system.config._sections._toggle', [
        'field' => 'vendor_needs_approval',
        'label' => trans('app.vendor_needs_approval'),
        'help' => trans('help.vendor_needs_approval'),
        'active' => (bool) $system->vendor_needs_approval,
      ])
      @include('admin.system.config._sections._toggle', [
        'field' => 'catalog_system_enable',
        'label' => trans('app.catalog_system_enable_disable'),
        'help' => trans('help.catalog_system_enable_disable'),
        'active' => (bool) $system->catalog_system_enable,
        'reload' => true,
      ])
      @if ($system->catalog_system_enable)
        @include('admin.system.config._sections._toggle', [
          'field' => 'can_use_own_catalog_only',
          'label' => trans('app.can_use_own_catalog_only'),
          'help' => trans('help.can_use_own_catalog_only'),
          'active' => (bool) $system->can_use_own_catalog_only,
        ])
      @endif
      @include('admin.system.config._sections._toggle', [
        'field' => 'vendor_can_view_customer_info',
        'label' => trans('app.vendor_can_view_customer_info'),
        'help' => trans('help.vendor_can_view_customer_info'),
        'active' => (bool) $system->vendor_can_view_customer_info,
      ])
      @include('admin.system.config._sections._toggle', [
        'field' => 'show_vendor_terms_and_conditions',
        'label' => trans('app.show_vendor_terms_and_conditions'),
        'help' => trans('help.show_vendor_terms_and_conditions'),
        'active' => (bool) $system->show_vendor_terms_and_conditions,
      ])
      @if (is_incevio_package_loaded('wallet'))
        <div class="form-group">
          {!! Form::label('vendor_order_cancellation_fee', trans('app.vendor_order_cancellation_fee') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
          <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.vendor_order_cancellation_fee') }}"></i>
          <div class="col-sm-5 nopadding-left">
            @if ($can_update)
              {!! Form::number('vendor_order_cancellation_fee', $system->vendor_order_cancellation_fee, ['class' => 'form-control', 'min' => 0, 'step' => '0.01']) !!}
            @else
              <span>{{ $system->vendor_order_cancellation_fee }}</span>
            @endif
          </div>
        </div>
      @endif
    </fieldset>

    <fieldset>
      <legend>{{ trans('app.customers') }}</legend>
      <div class="form-group">
        {!! Form::label('can_cancel_order_within', trans('app.can_cancel_order_within') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.can_cancel_order_within') }}"></i>
        <div class="col-sm-5 nopadding-left">
          @if ($can_update)
            <div class="input-group">
              {!! Form::number('can_cancel_order_within', $system->can_cancel_order_within, ['class' => 'form-control', 'min' => 0]) !!}
              <span class="input-group-addon">{{ trans('app.hours') }}</span>
            </div>
          @else
            <span>{{ $system->can_cancel_order_within }}</span>
          @endif
        </div>
      </div>
      @include('admin.system.config._sections._toggle', [
        'field' => 'customer_needs_approval',
        'label' => trans('app.customer_needs_approval'),
        'help' => trans('help.customer_needs_approval'),
        'active' => (bool) $system->customer_needs_approval,
      ])
      @include('admin.system.config._sections._toggle', [
        'field' => 'show_customer_terms_and_conditions',
        'label' => trans('app.show_customer_terms_and_conditions'),
        'help' => trans('help.show_customer_terms_and_conditions'),
        'active' => (bool) $system->show_customer_terms_and_conditions,
      ])
      @include('admin.system.config._sections._toggle', [
        'field' => 'ask_customer_for_email_subscription',
        'label' => trans('app.ask_customer_for_email_subscription'),
        'help' => trans('help.ask_customer_for_email_subscription'),
        'active' => (bool) $system->ask_customer_for_email_subscription,
      ])
      @include('admin.system.config._sections._toggle', [
        'field' => 'social_auth',
        'label' => trans('app.show_social_auth'),
        'help' => trans('help.show_social_auth'),
        'active' => (bool) $system->social_auth,
      ])
    </fieldset>

    @if (is_incevio_package_loaded('affiliate'))
      <fieldset>
        <legend>{{ trans('nav.affiliate') ?? 'Affiliate' }}</legend>
        <div class="form-group">
          {!! Form::label('affiliate_commission_release_in_days', trans('packages.affiliate.affiliate_commission_release_in_days') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
          <div class="col-sm-5 nopadding-left">
            @if ($can_update)
              {!! Form::number('affiliate_commission_release_in_days', $system->affiliate_commission_release_in_days, ['class' => 'form-control', 'min' => 0]) !!}
            @else
              <span>{{ $system->affiliate_commission_release_in_days }}</span>
            @endif
          </div>
        </div>
        @include('admin.system.config._sections._toggle', [
          'field' => 'publicly_show_affiliate_commission',
          'label' => trans('packages.affiliate.publicly_show_affiliate_commission'),
          'help' => trans('packages.affiliate.publicly_show_affiliate_commission'),
          'active' => (bool) $system->publicly_show_affiliate_commission,
        ])
      </fieldset>
    @endif
  </div>

  <div class="col-sm-6">
    <fieldset>
      <legend>{{ trans('app.inventory') }}</legend>
      @include('admin.system.config._sections._toggle', [
        'field' => 'hide_out_of_stock_items',
        'label' => trans('app.hide_out_of_stock_items'),
        'help' => trans('help.hide_out_of_stock_items'),
        'active' => (bool) $system->hide_out_of_stock_items,
      ])
      @include('admin.system.config._sections._toggle', [
        'field' => 'show_item_conditions',
        'label' => trans('app.show_item_conditions'),
        'help' => trans('help.show_item_conditions'),
        'active' => (bool) $system->show_item_conditions,
      ])
      <div class="form-group">
        {!! Form::label('max_img_size_limit_kb', trans('app.max_img_size_limit_kb') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.max_img_size_limit_kb') }}"></i>
        <div class="col-sm-5 nopadding-left">
          @if ($can_update)
            {!! Form::number('max_img_size_limit_kb', $system->max_img_size_limit_kb, ['class' => 'form-control', 'min' => 1]) !!}
          @else
            <span>{{ $system->max_img_size_limit_kb }}</span>
          @endif
        </div>
      </div>
      <div class="form-group">
        {!! Form::label('max_number_of_inventory_imgs', trans('app.max_number_of_inventory_imgs') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
        <div class="col-sm-5 nopadding-left">
          @if ($can_update)
            {!! Form::number('max_number_of_inventory_imgs', $system->max_number_of_inventory_imgs, ['class' => 'form-control', 'min' => 1]) !!}
          @else
            <span>{{ $system->max_number_of_inventory_imgs }}</span>
          @endif
        </div>
      </div>
      <div class="form-group">
        {!! Form::label('weight_unit', '*' . trans('app.weight_unit') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
        <div class="col-sm-5 nopadding-left">
          @if ($can_update)
            {!! Form::select('weight_unit', ['g' => 'g', 'kg' => 'kg', 'oz' => 'oz', 'lb' => 'lb'], $system->weight_unit, ['class' => 'form-control select2-normal', 'required']) !!}
          @else
            <span>{{ $system->weight_unit }}</span>
          @endif
        </div>
      </div>
      <div class="form-group">
        {!! Form::label('pagination', trans('app.pagination') . ':', ['class' => 'with-help col-sm-6 control-label']) !!}
        <div class="col-sm-5 nopadding-left">
          @if ($can_update)
            {!! Form::number('pagination', $system->pagination, ['class' => 'form-control', 'min' => 1]) !!}
          @else
            <span>{{ $system->pagination }}</span>
          @endif
        </div>
      </div>
    </fieldset>

    <fieldset>
      <legend>{{ trans('app.address') }}</legend>
      <div class="form-group">
        {!! Form::label('address_default_country', trans('app.config_address_default_country') . ':', ['class' => 'with-help col-sm-5 control-label']) !!}
        <div class="col-sm-6 nopadding-left">
          @if ($can_update)
            {!! Form::select('address_default_country', $countries, $system->address_default_country, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
          @else
            <span>{{ $system->address_default_country }}</span>
          @endif
        </div>
      </div>
      <div class="form-group">
        {!! Form::label('address_default_state', trans('app.config_address_default_state') . ':', ['class' => 'with-help col-sm-5 control-label']) !!}
        <div class="col-sm-6 nopadding-left">
          @if ($can_update)
            {!! Form::select('address_default_state', $states, $system->address_default_state, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.select')]) !!}
          @else
            <span>{{ $system->address_default_state }}</span>
          @endif
        </div>
      </div>
      @include('admin.system.config._sections._toggle', [
        'field' => 'address_show_map',
        'label' => trans('app.address_show_map'),
        'help' => trans('help.address_show_map'),
        'active' => (bool) $system->address_show_map,
      ])
      @include('admin.system.config._sections._toggle', [
        'field' => 'address_show_country',
        'label' => trans('app.address_show_country'),
        'help' => trans('help.address_show_country'),
        'active' => (bool) $system->address_show_country,
      ])
    </fieldset>
  </div>
</div>

@if ($can_update)
  <div class="row">
    <div class="col-sm-12 text-right">
      {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new']) !!}
    </div>
  </div>
@endif
{!! Form::close() !!}
