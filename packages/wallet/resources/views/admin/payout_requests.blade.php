@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.payout_requests') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.payout_requests'),
    'icon' => 'fa-hand-paper-o',
    'actions' => view('wallet::admin._btn_payout')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('packages.wallet.date') }}</th>
        <th>{{ trans('packages.wallet.wallet_owner') }}</th>
        <th>{{ trans('packages.wallet.description') }}</th>
        <th>{{ trans('packages.wallet.payout_amount') }}</th>
        <th>{{ trans('packages.wallet.payout_method') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.options') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($payout_requests as $transaction)
        @if ($transaction->isTypeOf(\Incevio\Package\Wallet\Models\Transaction::TYPE_PAYOUT))
          @php $pm = $transaction->getFromMetaData('payout_method'); @endphp
          <tr>
            <td class="small">{{ $transaction->created_at->toFormattedDateString() }}</td>
            <td>{!! $transaction->payable->getName() !!}</td>
            <td class="small">{!! $transaction->getFromMetaData('description') !!}</td>
            <td>{{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}</td>
            <td>
              @if ($pm === 'mpesa')
                {{ trans('packages.wallet.payout_method_mpesa') }}
              @elseif ($pm === 'emola')
                {{ trans('packages.wallet.payout_method_emola') }}
              @elseif ($pm === 'bank_transfer')
                {{ trans('packages.wallet.payout_method_bank_transfer') }}
              @else
                —
              @endif
            </td>
            <td class="row-options admin-row-actions">
              @if (Auth::user()->isAdmin())
                <a href="javascript:void(0)" data-link="{{ route('admin.payout.approval', $transaction) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('packages.wallet.approve') }}" data-toggle="tooltip"><i class="fa fa-check"></i></a>
                {!! Form::open(['route' => ['admin.payout.decline', $transaction], 'method' => 'post', 'class' => 'admin-inline-form confirm']) !!}
                <button type="submit" class="admin-action-btn" title="{{ trans('packages.wallet.decline') }}" data-toggle="tooltip"><i class="fa fa-times"></i></button>
                {!! Form::close() !!}
              @endif
            </td>
          </tr>
        @endif
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
