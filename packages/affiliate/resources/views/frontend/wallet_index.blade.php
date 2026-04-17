@extends('affiliate::backend.master_layout')
@section('content')
  <!-- Info boxes -->
  <div class="row dashboard-total vendor-wallet">
    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-danger card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.wallet.balance') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency(auth()->guard('affiliate')->user()->balance, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-info card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.affiliate.pending_commission') }}
          </h4>
          <h2 class="mb-5">{{ get_formated_currency($pending_commissions, 2, config('system_settings.currency.id')) }}</h2>
        </div>
      </div>
    </div>

    <div class="col-md-3 stretch-card grid-margin">
      <div class="card bg-gradient-primary card-img-holder text-white">
        <div class="card-body">
          <img src="/images/circle.svg" class="card-img-absolute" alt="circle-image">
          <h4 class="font-weight-normal mb-3">{{ trans('packages.affiliate.last_commission') }}
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
        <a href="javascript:void(0)" data-link="{{ route('affiliate.wallet.withdrawal') }}" class="ajax-modal-btn btn btn-new btn-flat">
          <i class="fa fa-plus"></i> {{ trans('packages.wallet.payout_request') }}
        </a>
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
