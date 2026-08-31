@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.payouts') }}
@endsection

@section('content')
  <div class="row">
    <div class="col-sm-12">
      <div class="report-filter-panel">
        <div class="row">
          <div class="col-md-2 nopadding-right">
            <div class="form-group">
              <label>{{ trans('packages.wallet.type') }}</label>
              <select id="payoutType" onchange="fireEventOnFilter(this.value)" class="form-control" name="payout_type">
                <option value="" @if (request()->get('payout_type') == '') selected @endif>{{ trans('app.all') }}</option>
                <option value="deposit" @if (request()->get('payout_type') == 'deposit') selected @endif>{{ trans('packages.wallet.deposit') }}</option>
                <option value="withdraw" @if (request()->get('payout_type') == 'withdraw') selected @endif>{{ trans('packages.wallet.withdraw') }}</option>
              </select>
            </div>
          </div>
          <div class="col-md-2 nopadding-right">
            <div class="form-group">
              <label>{{ trans('packages.wallet.status') }}</label>
              <select id="status" onchange="fireEventOnFilter(this.value)" class="form-control" name="status">
                <option value="" @if (request()->get('status') == '') selected @endif>{{ trans('app.all') }}</option>
                <option value="0" @if (request()->get('status') == '0') selected @endif>{{ trans('packages.wallet.pending') }}</option>
                <option value="1" @if (request()->get('status') == '1') selected @endif>{{ trans('packages.wallet.approve') }}</option>
              </select>
            </div>
          </div>
          <div class="col-md-8">
            <div class="form-group">
              <label>&nbsp;</label>
              <button onclick="clearAllFilter()" type="button" class="btn btn-default pull-right" name="search" value="1"><i class="fa fa-caret-left"></i> {{ trans('app.clear') }}</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.payouts'),
    'icon' => 'fa-money',
    'class' => 'margin-top-2',
    'bodyClass' => '',
    'actions' => view('admin.partials.reports.timeframe')->render(),
  ])
      <div class="rg-card-simple equal-height">
        <canvas id="payoutReport" style="height: 300px; min-height: 300px; max-height: 300px; width: 100%"></canvas>
      </div>

      <span class="spacer30"></span>

      <table class="table table-hover admin-table table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.date') }}</th>
            <th>{{ trans('packages.wallet.shop') }}</th>
            <th>{{ trans('packages.wallet.type') }}</th>
            <th>{{ trans('packages.wallet.description') }}</th>
            <th>{{ trans('packages.wallet.remaining_balance') }}</th>
            <th>{{ trans('packages.wallet.amount') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($data as $payout)
            <tr>
              <td>
                {{ $payout->created_at->toFormattedDateString() }}
              </td>
              <td>
                {{ $payout->payable->name }}
              </td>
              <td>
                {{ $payout->type }}
              </td>
              <td>
                {{ $payout->meta['description'] }}
              </td>
              <td>
                {{ get_formated_currency($payout->balance, 2, config('system_settings.currency.id')) }}
              </td>
              <td>
                {{ get_formated_currency($payout->amount, 2, config('system_settings.currency.id')) }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  @include('wallet::scripts.report')
@endsection
