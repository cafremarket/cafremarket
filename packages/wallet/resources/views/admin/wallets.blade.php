@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.active_wallets') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.active_wallets'),
    'icon' => 'fa-wallet',
    'actions' => view('wallet::admin._header_actions')->render(),
  ])

  <div class="admin-filters">
    {!! Form::open(['route' => 'admin.wallet.list', 'method' => 'GET', 'class' => 'form-inline admin-filters__form']) !!}
      <div class="form-group">
        {!! Form::text('q', request('q'), ['class' => 'form-control input-sm', 'placeholder' => trans('packages.wallet.search_wallet')]) !!}
      </div>
      <div class="form-group">
        {!! Form::select('type', [
          '' => trans('packages.wallet.all_types'),
          'customer' => trans('app.customer'),
          'merchant' => trans('packages.wallet.shop'),
        ], request('type'), ['class' => 'form-control input-sm']) !!}
      </div>
      <div class="form-group">
        {!! Form::select('has_balance', [
          '1' => trans('packages.wallet.with_balance'),
          '0' => trans('packages.wallet.all_active_wallets'),
        ], request('has_balance', '1'), ['class' => 'form-control input-sm']) !!}
      </div>
      <button type="submit" class="btn btn-default btn-sm btn-flat">
        <i class="fa fa-search"></i> {{ trans('app.search') }}
      </button>
    {!! Form::close() !!}
  </div>

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('packages.wallet.wallet_owner') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('packages.wallet.type') }}</th>
        <th>{{ trans('packages.wallet.balance') }}</th>
        <th>{{ trans('packages.wallet.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('packages.wallet.option') }}</th>
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
          <td>{{ get_formated_currency($wallet->balance, 2, config('system_settings.currency.id')) }}</td>
          <td>
            @if ($wallet->blocked)
              <span class="label label-danger">{{ trans('packages.wallet.blocked') }}</span>
            @else
              <span class="label label-success">{{ trans('packages.wallet.active') }}</span>
            @endif
          </td>
          <td class="row-options admin-row-actions">
            <a href="javascript:void(0)" data-link="{{ route('admin.wallet.topup', ['wallet_id' => $wallet->id]) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('packages.wallet.topup_wallet') }}" data-toggle="tooltip"><i class="fa fa-plus"></i></a>
            <a href="{{ route('admin.wallet.transactions', ['wallet_id' => $wallet->id]) }}" class="admin-action-btn" title="{{ trans('packages.wallet.wallet_logs') }}" data-toggle="tooltip"><i class="fa fa-history"></i></a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center text-muted">{{ trans('packages.wallet.no_wallets_found') }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="text-center">{{ $wallets->links() }}</div>

  @include('admin.partials.ui.card_end')
@endsection
