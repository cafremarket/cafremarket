{{-- General fields for one variant: image, SKU, stock, price, offer pricing.
     Rendered once per variant row inside a hidden `.variant-fields` wrapper; JS relocates
     this block into the shared manage-modal when "Manage" is clicked, and back on close. --}}
@php
  $hasOffer = ! empty($offerPriceValue);
@endphp
<div class="variant-fields">
  <div class="variant-fields__image">
    <label class="img-btn-with-preview">
      {{ Form::file($imageName, ['class' => 'variant-img']) }}
      <img src="{{ $imageUrl ?: url('images/placeholders/no_img.png') }}" class="img-md variant-img-preview" alt="">
    </label>
    <span class="text-muted small">{{ trans('help.variant_image') }}</span>
  </div>

  <div class="form-group">
    <label class="control-label">{{ trans('app.form.sku') }}</label>
    {!! Form::text($skuName, $skuValue, ['class' => 'form-control variant-sku', 'placeholder' => trans('app.placeholder.sku'), 'required']) !!}
  </div>

  <div class="row">
    <div class="col-sm-6">
      <div class="form-group">
        <label class="control-label">{{ trans('app.form.stock_quantity') }}</label>
        {!! Form::number($qtyName, $qtyValue, ['class' => 'form-control variant-qtt', 'min' => 0, 'step' => '1', 'required']) !!}
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label class="control-label">{{ trans('app.form.sale_price') }}</label>
        <div class="input-group">
          @if (get_currency_prefix())
            <span class="input-group-addon">{{ get_currency_prefix() }}</span>
          @endif
          {!! Form::number($priceName, $priceValue, ['class' => 'form-control variant-price', 'min' => 0, 'step' => 'any', 'required']) !!}
        </div>
      </div>
    </div>
  </div>

  <div class="variant-offer-section">
    <div class="variant-offer-section__title">
      <button type="button"
              class="variant-offer-toggle btn btn-xs {{ $hasOffer ? 'btn-primary' : 'btn-default' }}"
              data-enabled="{{ $hasOffer ? '1' : '0' }}">
        <i class="fa {{ $hasOffer ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i> {{ trans('app.offer_pricing') }}
      </button>
      <small class="text-muted">{{ trans('app.enable_offer_pricing') }}</small>
    </div>

    <div class="variant-offer-fields {{ $hasOffer ? '' : 'hide' }}">
      <div class="form-group">
        <label class="control-label">{{ trans('app.form.offer_price') }}</label>
        <div class="input-group">
          @if (get_currency_prefix())
            <span class="input-group-addon">{{ get_currency_prefix() }}</span>
          @endif
          {!! Form::number($offerPriceName, $offerPriceValue, ['class' => 'form-control variant-offer-price', 'min' => 0, 'step' => 'any']) !!}
        </div>
      </div>
    </div>
  </div>
</div>
