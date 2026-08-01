@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.wallet.active_wallets') }}</h3>
      <div class="box-tools pull-right">
        <a href="javascript:void(0)" data-link="{{ route('admin.wallet.topup') }}" class="ajax-modal-btn btn btn-new btn-flat">
          <i class="fa fa-plus"></i> {{ trans('packages.wallet.topup_wallet') }}
        </a>
        <a href="{{ route('admin.wallet.transactions') }}" class="btn btn-default btn-flat">
          <i class="fa fa-list"></i> {{ trans('packages.wallet.wallet_logs') }}
        </a>
      </div>
    </div>

    <div class="box-body">
      {!! Form::open(['route' => 'admin.wallet.list', 'method' => 'GET', 'class' => 'form-inline', 'style' => 'margin-bottom:15px;']) !!}
        <div class="form-group" style="margin-right:8px;">
          {!! Form::text('q', request('q'), ['class' => 'form-control', 'placeholder' => trans('packages.wallet.search_wallet')]) !!}
        </div>
        <div class="form-group" style="margin-right:8px;">
          {!! Form::select('type', [
            '' => trans('packages.wallet.all_types'),
            'customer' => trans('app.customer'),
            'merchant' => trans('packages.wallet.shop'),
          ], request('type'), ['class' => 'form-control']) !!}
        </div>
        <div class="form-group" style="margin-right:8px;">
          {!! Form::select('has_balance', [
            '1' => trans('packages.wallet.with_balance'),
            '0' => trans('packages.wallet.all_active_wallets'),
          ], request('has_balance', '1'), ['class' => 'form-control']) !!}
        </div>
        <button type="submit" class="btn btn-flat btn-default">
          <i class="fa fa-search"></i> {{ trans('app.search') }}
        </button>
      {!! Form::close() !!}

      <table class="table table-hover table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.wallet_owner') }}</th>
            <th>{{ trans('app.email') }}</th>
            <th>{{ trans('packages.wallet.type') }}</th>
            <th>{{ trans('packages.wallet.balance') }}</th>
            <th>{{ trans('packages.wallet.status') }}</th>
            <th>{{ trans('packages.wallet.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($wallets as $wallet)
            @php
              $holder = $wallet->holder;
              $isShop = $holder instanceof \App\Models\Shop;
              $ownerName = $holder
                ? (method_exists($holder, 'getName') ? $holder->getName() : ($holder->name ?? '-'))
                : '-';
              $ownerEmail = $holder->email ?? '-';
            @endphp
            <tr>
              <td>{{ $ownerName }}</td>
              <td>{{ $ownerEmail }}</td>
              <td>
                @if ($isShop)
                  <span class="label label-info">{{ trans('packages.wallet.shop') }}</span>
                @else
                  <span class="label label-primary">{{ trans('app.customer') }}</span>
                @endif
              </td>
              <td>
                {{ get_formated_currency($wallet->balance, 2, config('system_settings.currency.id')) }}
              </td>
              <td>
                @if ($wallet->blocked)
                  <span class="label label-danger">{{ trans('packages.wallet.blocked') }}</span>
                @else
                  <span class="label label-success">{{ trans('packages.wallet.active') }}</span>
                @endif
              </td>
              <td class="text-nowrap">
                <a href="javascript:void(0)"
                   data-link="{{ route('admin.wallet.topup', ['wallet_id' => $wallet->id]) }}"
                   class="ajax-modal-btn btn btn-primary btn-sm btn-flat">
                  <i class="fa fa-plus"></i> {{ trans('packages.wallet.topup_wallet') }}
                </a>
                <a href="{{ route('admin.wallet.transactions', ['wallet_id' => $wallet->id]) }}"
                   class="btn btn-default btn-sm btn-flat">
                  <i class="fa fa-history"></i> {{ trans('packages.wallet.wallet_logs') }}
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted">
                {{ trans('packages.wallet.no_wallets_found') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="text-center">
        {{ $wallets->links() }}
      </div>
    </div>
  </div>
@endsection
