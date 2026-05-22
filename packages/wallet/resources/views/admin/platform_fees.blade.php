@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.wallet.platform_fees_page_title') }}</h3>
    </div>
    <div class="box-body">
      <div class="spacer20"></div>
      <div class="row">
        {!! Form::open([
          'route' => 'admin.wallet.platform_fees.update',
          'method' => 'post',
          'class' => 'form-horizontal',
          'id' => 'form',
          'data-toggle' => 'validator',
        ]) !!}
        <div class="col-sm-10">
          <p class="text-muted">{{ trans('packages.wallet.platform_fees_section_help') }}</p>

          @include('wallet::admin.partials.platform_fee_fields', [
            'prefix' => 'platform_fee_mpesa',
            'title' => trans('packages.wallet.platform_fee_mpesa_customer'),
          ])
          @include('wallet::admin.partials.platform_fee_fields', [
            'prefix' => 'platform_fee_emola',
            'title' => trans('packages.wallet.platform_fee_emola_customer'),
          ])
          @include('wallet::admin.partials.platform_fee_fields', [
            'prefix' => 'platform_fee_payout',
            'title' => trans('packages.wallet.platform_fee_payout_vendor'),
          ])

          {!! Form::submit(trans('app.update'), ['class' => 'btn btn-lg btn-flat btn-new pull-right']) !!}
        </div>
        {!! Form::close() !!}
      </div>
      <div class="spacer20"></div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    (function () {
      function syncPlatformFeeAddon(select) {
        var panel = select.closest('.panel');
        if (!panel) return;
        var addon = panel.querySelector('.platform-fee-value-addon');
        if (!addon) return;
        var isPercent = select.value === 'percent';
        addon.textContent = isPercent
          ? addon.getAttribute('data-percent-symbol')
          : addon.getAttribute('data-flat-symbol');
      }

      document.querySelectorAll('.platform-fee-type-select').forEach(function (select) {
        syncPlatformFeeAddon(select);
        select.addEventListener('change', function () {
          syncPlatformFeeAddon(select);
        });
      });
    })();
  </script>
@endsection
