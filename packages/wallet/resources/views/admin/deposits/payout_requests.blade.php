@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.payout_requests') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.payout_requests'),
    'icon' => 'fa-clock-o',
    'actions' => view('wallet::admin._btn_payout')->render(),
  ])
      <table class="table table-hover admin-table table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.date') }}</th>
            <th>{{ trans('packages.wallet.shop') }}</th>
            <th>{{ trans('packages.wallet.description') }}</th>
            <th>{{ trans('packages.wallet.payout_amount') }}</th>
            <th>{{ trans('app.options') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($payout_requests as $transaction)
            @if ($transaction->isTypeOf(\Incevio\Package\Wallet\Models\Transaction::TYPE_PAYOUT))
              <tr>
                <td>
                  {{ $transaction->created_at->toFormattedDateString() }}
                </td>
                <td>
                  {!! $transaction->payable->getName() !!}
                </td>
                <td>
                  {!! $transaction->getFromMetaData('description') !!}
                </td>
                <td>
                  {{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}
                </td>
                <td class="row-options admin-row-actions">
                  @if (Auth::user()->isAdmin())
                    <a href="javascript:void(0)" data-link="{{ route('admin.payout.approval', $transaction) }}" class="ajax-modal-btn btn btn-new btn-sm">
                      <i class="fa fa-check"></i> {{ trans('packages.wallet.approve') }}
                    </a>

                    {!! Form::open(['route' => ['admin.payout.decline', $transaction], 'method' => 'post', 'class' => 'action-form confirm']) !!}
                    <button class="btn btn-flat btn-red" class="">
                      <i class="fa fa-close"></i> {{ trans('packages.wallet.decline') }}
                    </button>
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
