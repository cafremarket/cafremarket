<div class="form-group">
  <div class="row" style="line-height: 20px;">
    <div class="col-md-4">
      <label for="email">{{ trans('packages.wallet.transfer_to') }}</label>
    </div>
    <div class="col-md-4">
      <label class="radio-inline">
        <input type="radio" name="recipient_type" value="customer" {{ Auth::guard('customer')->check() ? 'checked' : '' }}> Customer
      </label>
    </div>
    <div class="col-md-4">
      <label class="radio-inline">
        <input type="radio" name="recipient_type" value="vendor" {{ !Auth::guard('customer')->check() ? 'checked' : '' }}> Vendor
      </label>
    </div>
  </div>
</div>

<div id="transfer_input_form" class="form-group" style="margin:3px 0px;">
  <div id="transfer_to_vendor" class="input-group">
    <select type="email" name="email" id="email" class="form-control" required>
      <option class="text-muted" selected disabled value="">{{ trans('packages.wallet.select_vendor_to_transfer_balance') }}</option>
      @foreach ($shops_email_list as $shop_data)
        <option value="{{ $shop_data->email }}"> {{ $shop_data->name }} </option>
      @endforeach
    </select>
    <span class="input-group-addon">
      <i class="fa fa-info-circle" data-toggle="tooltip" title="{!! trans('packages.wallet.transfer_to_vendor_help_text') !!}" data-placement="left"></i>
    </span>
  </div>

  <div id="transfer_to_customer" class="input-group">
    {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => trans('packages.wallet.transfer_to_wallet'), 'required']) !!}
    <span class="input-group-addon">
      <i class="fa fa-info-circle" data-toggle="tooltip" title="{!! trans('packages.wallet.transfer_to_help_text') !!}" data-placement="left"></i>
    </span>
  </div>
  <div class="help-block with-errors"></div>
</div>

<div class="form-group space30">
  {!! Form::label('order', trans('packages.wallet.amount')) !!}
  <div class="input-group">
    @if (get_currency_prefix())
      <span class="input-group-addon">
        {{ get_currency_prefix() }}
      </span>
    @endif

    {!! Form::number('amount', null, ['class' => 'form-control', 'step' => 'any', 'placeholder' => trans('packages.wallet.amount'), 'max' => $wallet->balance, 'required']) !!}

    @if (get_currency_suffix())
      <span class="input-group-addon">
        {{ get_currency_suffix() }}
      </span>
    @endif
  </div>
  <div class="help-block with-errors">{{ trans('packages.wallet.max_transfer_amount', ['amount' => get_formated_currency($wallet->balance, 2)]) }}</div>
</div>
