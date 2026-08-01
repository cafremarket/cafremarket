@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">
        {{ trans('packages.wallet.wallet_logs') }}
        @if ($wallet && $wallet->holder)
          — {{ method_exists($wallet->holder, 'getName') ? $wallet->holder->getName() : ($wallet->holder->name ?? '') }}
          ({{ $wallet->holder->email ?? '' }})
        @endif
      </h3>
      <div class="box-tools pull-right">
        <a href="{{ route('admin.wallet.list') }}" class="btn btn-default btn-flat">
          <i class="fa fa-arrow-left"></i> {{ trans('packages.wallet.active_wallets') }}
        </a>
        <a href="javascript:void(0)" data-link="{{ route('admin.wallet.topup', $wallet ? ['wallet_id' => $wallet->id] : []) }}" class="ajax-modal-btn btn btn-new btn-flat">
          <i class="fa fa-plus"></i> {{ trans('packages.wallet.topup_wallet') }}
        </a>
      </div>
    </div>

    <div class="box-body">
      {!! Form::open(['route' => 'admin.wallet.transactions', 'method' => 'GET', 'class' => 'form-inline', 'style' => 'margin-bottom:15px;']) !!}
        @if (request('wallet_id'))
          {!! Form::hidden('wallet_id', request('wallet_id')) !!}
        @endif
        <div class="form-group" style="margin-right:8px;">
          {!! Form::text('q', request('q'), ['class' => 'form-control', 'placeholder' => trans('packages.wallet.search_wallet')]) !!}
        </div>
        <div class="form-group" style="margin-right:8px;">
          {!! Form::select('type', [
            '' => trans('packages.wallet.all_types'),
            'deposit' => trans('packages.wallet.deposit'),
            'withdraw' => trans('packages.wallet.withdraw'),
            'refund' => trans('packages.wallet.refund'),
            'payout' => trans('packages.wallet.payout'),
          ], request('type'), ['class' => 'form-control']) !!}
        </div>
        <button type="submit" class="btn btn-flat btn-default">
          <i class="fa fa-search"></i> {{ trans('app.search') }}
        </button>
        @if (request()->hasAny(['q', 'type']) || request('wallet_id'))
          <a href="{{ route('admin.wallet.transactions') }}" class="btn btn-flat btn-default">
            {{ trans('app.clear') }}
          </a>
        @endif
      {!! Form::close() !!}

      <table class="table table-hover table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.date') }}</th>
            <th>{{ trans('packages.wallet.wallet_owner') }}</th>
            <th>{{ trans('packages.wallet.type') }}</th>
            <th>{{ trans('packages.wallet.description') }}</th>
            <th>{{ trans('packages.wallet.amount') }}</th>
            <th>{{ trans('packages.wallet.remaining_balance') }}</th>
            <th>{{ trans('packages.wallet.status') }}</th>
            <th>{{ trans('packages.wallet.transactions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($transactions as $transaction)
            <tr>
              <td>{{ optional($transaction->created_at)->toDayDateTimeString() }}</td>
              <td>
                @php
                  $payable = $transaction->payable;
                  $payableName = '-';
                  if ($payable) {
                    $payableName = method_exists($payable, 'getName')
                      ? $payable->getName()
                      : ($payable->name ?? '-');
                  }
                @endphp
                {{ $payableName }}
                @if (optional($payable)->email)
                  <br><small class="text-muted">{{ $payable->email }}</small>
                @endif
              </td>
              <td>
                <span class="label label-default">{{ ucfirst($transaction->type) }}</span>
              </td>
              <td>
                {!! $transaction->getFromMetaData('description') ?: '-' !!}
                @if ($transaction->getFromMetaData('admin_manual'))
                  <br><small class="text-muted">
                    {{ trans('packages.wallet.by_admin') }}
                    @if ($transaction->getFromMetaData('admin_name'))
                      — {{ $transaction->getFromMetaData('admin_name') }}
                    @endif
                  </small>
                @endif
              </td>
              <td>{{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}</td>
              <td>{{ get_formated_currency($transaction->balance, 2, config('system_settings.currency.id')) }}</td>
              <td>{!! $transaction->statusName() !!}</td>
              <td><small>{{ $transaction->uuid }}</small></td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted">
                {{ trans('packages.wallet.no_transaction_found') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="text-center">
        {{ $transactions->links() }}
      </div>
    </div>
  </div>
@endsection
