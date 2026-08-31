@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.wallet_logs') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.wallet_logs') . ($wallet && $wallet->holder ? ' — ' . (method_exists($wallet->holder, 'getName') ? $wallet->holder->getName() : ($wallet->holder->name ?? '')) : ''),
    'icon' => 'fa-list-alt',
    'actions' => view('wallet::admin._transactions_header_actions', compact('wallet'))->render(),
  ])

  <div class="admin-filters">
    {!! Form::open(['route' => 'admin.wallet.transactions', 'method' => 'GET', 'class' => 'form-inline admin-filters__form']) !!}
      @if (request('wallet_id'))
        {!! Form::hidden('wallet_id', request('wallet_id')) !!}
      @endif
      <div class="form-group">
        {!! Form::text('q', request('q'), ['class' => 'form-control input-sm', 'placeholder' => trans('packages.wallet.search_wallet')]) !!}
      </div>
      <div class="form-group">
        {!! Form::select('type', [
          '' => trans('packages.wallet.all_types'),
          'deposit' => trans('packages.wallet.deposit'),
          'withdraw' => trans('packages.wallet.withdraw'),
          'refund' => trans('packages.wallet.refund'),
          'payout' => trans('packages.wallet.payout'),
        ], request('type'), ['class' => 'form-control input-sm']) !!}
      </div>
      <button type="submit" class="btn btn-default btn-sm btn-flat">
        <i class="fa fa-search"></i> {{ trans('app.search') }}
      </button>
      @if (request()->hasAny(['q', 'type']) || request('wallet_id'))
        <a href="{{ route('admin.wallet.transactions') }}" class="btn btn-default btn-sm btn-flat">{{ trans('app.clear') }}</a>
      @endif
    {!! Form::close() !!}
  </div>

  <table class="table table-hover admin-table table-no-sort">
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
        @php
          $payable = $transaction->payable;
          $payableName = '-';
          if ($payable) {
            $payableName = method_exists($payable, 'getName') ? $payable->getName() : ($payable->name ?? '-');
          }
        @endphp
        <tr>
          <td class="small">{{ optional($transaction->created_at)->toDayDateTimeString() }}</td>
          <td>
            {{ $payableName }}
            @if (optional($payable)->email)
              <br><small class="text-muted">{{ $payable->email }}</small>
            @endif
          </td>
          <td><span class="label label-default">{{ ucfirst($transaction->type) }}</span></td>
          <td class="small">
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
          <td><small class="text-muted">{{ $transaction->uuid }}</small></td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center text-muted">{{ trans('packages.wallet.no_transaction_found') }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="text-center">{{ $transactions->links() }}</div>

  @include('admin.partials.ui.card_end')
@endsection
