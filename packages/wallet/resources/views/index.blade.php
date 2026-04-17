@extends('admin.layouts.master')

@section('content')
  <!-- Info boxes -->
  {{-- <div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right">
      <div class="info-box">

        <span class="info-box-icon bg-yellow">
          <i class="fa fa-exchange"></i>
        </span>

        <div class="info-box-content">
          <span class="info-box-text">{{ trans('packages.wallet.pending_balance') }}</span>
          <span class="info-box-number">
            {{ get_formated_currency(Auth::user()->shop->balance, config('system_settings.decimals', 2)) }}
          </span>
          <span class="progress-description text-muted">
            <i class="icon ion-md-hourglass"></i>
            {{ trans('messages.no_sale', ['date' => trans('packages.wallet.today')]) }}
          </span>
        </div> <!-- /.info-box-content -->
      </div> <!-- /.info-box -->
    </div> <!-- /.col -->

    <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right nopadding-left">
      <div class="info-box">
        <span class="info-box-icon bg-aqua">
          <i class="fa fa-calculator"></i>
        </span>

        <div class="info-box-content">
          <span class="info-box-text">{{ trans('packages.wallet.refunded_amount') }}</span>
          <span class="info-box-number">
            <a href="{{ url('admin/order/order?tab=unfulfilled') }}" class="pull-right small" data-toggle="tooltip" data-placement="left" title="{{ trans('packages.wallet.detail') }}">
              <i class="fa fa-send-o"></i>
            </a>
          </span>
          <div class="progress" style="background: transparent;"></div>
          <span class="progress-description text-muted">
            <i class="fa fa-calendar"></i> {{ trans('packages.wallet.in_last_30_days') }}
          </span>
        </div> <!-- /.info-box-content -->
      </div> <!-- /.info-box -->
    </div> <!-- /.col -->

    <!-- fix for small devices only -->
    <div class="clearfix visible-sm-block"></div>

    <div class="col-md-3 col-sm-6 col-xs-12 nopadding-right nopadding-left">
      <div class="info-box">
        <span class="info-box-icon bg-red">
          <i class="fa fa-bullhorn"></i>
        </span>

        <div class="info-box-content">
          <span class="info-box-text">{{ trans('packages.wallet.desputed_amount') }}</span>
          <span class="info-box-number">0
            <a href="{{ url('admin/stock/inventory?tab=out_of_stock') }}" class="pull-right small" data-toggle="tooltip" data-placement="left" title="{{ trans('packages.wallet.detail') }}">
              <i class="fa fa-send-o"></i>
            </a>
          </span>

          @php
            // $stock_out_percents = $stock_count > 0 ?
            // round(($stock_out_count / $stock_count) * 100) :
            // ($stock_out_count * 100);
          @endphp
          <div class="progress">
            <div class="progress-bar progress-bar-danger" style="width: 70%"></div>
          </div>
          <span class="progress-description text-muted">
          </span>
        </div> <!-- /.info-box-content -->
      </div> <!-- /.info-box -->
    </div> <!-- /.col -->
    <div class="col-md-3 col-sm-6 col-xs-12 nopadding-left">
      <div class="info-box">
        <span class="info-box-icon bg-green">
          <i class="fa fa-bank"></i>
        </span>

        <div class="info-box-content">
          <span class="info-box-text">{{ trans('packages.wallet.balance') }}</span>
          <span class="info-box-number">
            {{ get_formated_currency(Auth::user()->shop->balance, config('system_settings.decimals', 2)) }}
          </span>
          <span class="progress-description text-muted">
            <i class="fa fa-clock-o"></i> {{ trans('packages.wallet.next_payout_date') }}
          </span>
        </div> <!-- /.info-box-content -->
      </div> <!-- /.info-box -->
    </div> <!-- /.col -->
  </div> <!-- /.row --> --}}

  <div class="row dashboard-total vendor-wallet">
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-danger card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.wallet.balance') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency(Auth::user()->shop->balance, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-info card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.wallet.pending_balance') }}
          </h4>
          <h2 class="mb-5"> {{ get_formated_currency(Auth::user()->shop->wallet->getPendingBalance(), 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-primary card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.wallet.last_deposit') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency($wallet->transactions->where('type', 'deposit')->where('approved', 1)->first()->amount ?? 0, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-success card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.wallet.last_payout') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency($wallet->transactions->where('type', 'withdraw')->where('approved', 1)->first()->amount ?? 0, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>
  </div>

  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.wallet.transactions') }}</h3>
      <div class="box-tools pull-right">
        @if (auth()->user()->isMerchant())
          <a href="javascript:void(0)" data-link="{{ route('merchant.wallet.withdrawal') }}" class="ajax-modal-btn btn btn-new btn-flat">
            <i class="fa fa-plus"></i>
            {{ trans('packages.wallet.payout_request') }}
          </a>

          <a href="{{ route('merchant.wallet.deposit.form') }}" class="btn btn-primary btn-flat">
            {{ get_currency_symbol() . ' ' . trans('packages.wallet.deposit_fund') }}
          </a>

          <a href="javascript:void(0)" data-link="{{ route('merchant.wallet.transfer_form') }}" class="ajax-modal-btn btn btn-warning btn-flat">
            <i class="fa fa-exchange"></i>
            {{ trans('packages.wallet.transfer') }}
          </a>

          @if (Auth::user()->isMerchant())
            <a href="javascript:void(0)" data-link="{{ route('admin.account.shop.editPayoutInstruction') }}" class="ajax-modal-btn btn btn-default">
              <i class="fa fa-money"></i> {{ trans('app.update_payout_instructions') }}
            </a>
          @endif

          @if (!customer_can_register())
            <a href="javascript:void(0)" data-link="{{ route('merchant.wallet.self_transfer_form') }}" class="ajax-modal-btn btn btn-warning btn-flat">
              <i class="fa fa-exchange"></i>
              {{ trans('packages.wallet.transfer_self_customer') }}
            </a>
          @endif
        @endif
      </div>
    </div> <!-- /.box-header -->
    <div class="box-body">
      <table class="table table-hover table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.date') }}</th>
            <th>{{ trans('packages.wallet.transaction_type') }}</th>
            <th>{{ trans('packages.wallet.description') }}</th>
            <th>{{ trans('packages.wallet.amount') }}</th>
            <th>{{ trans('packages.wallet.status') }}</th>
            <th>{{ trans('packages.wallet.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @if ($wallet->transactions)
            @foreach ($wallet->transactions as $transaction)
              <tr>
                <td>
                  {{ $transaction->updated_at->toFormattedDateString() }}
                </td>
                <td>
                  {{ $transaction->type }}
                </td>
                <td>
                  {!! $transaction->getFromMetaData('description') !!}
                </td>
                <td>
                  {{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}
                </td>
                <td>
                  {!! $transaction->statusName() !!}
                </td>
                <td>
                  @if ($transaction->approved)
                    <a href="{{ route('wallet.transaction.invoice', $transaction) }}" class="btn btn-default btn-sm btn-flat">
                      <i class="fa fa-file-o"></i> {{ trans('app.invoice') }}
                    </a>
                  @endif
                </td>
              </tr>
            @endforeach
          @endif
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
