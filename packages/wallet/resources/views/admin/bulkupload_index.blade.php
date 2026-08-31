@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.wallet_bulk_upload') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.wallet_bulk_upload'),
    'icon' => 'fa-upload',
    'actions' => '<a href="javascript:void(0)" data-link="' . route('admin.wallet.bulkupload') . '" class="ajax-modal-btn btn btn-new btn-flat btn-sm"><i class="fa fa-plus"></i> ' . e(trans('packages.wallet.bulk_upload')) . '</a>',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('packages.wallet.date') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('packages.wallet.description') }}</th>
        <th>{{ trans('packages.wallet.coupon_code') }}</th>
        <th>{{ trans('packages.wallet.amount') }}</th>
        <th>{{ trans('packages.wallet.transactions') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($bulkupload_deposits as $transaction)
        <tr>
          <td>{{ $transaction->created_at->toFormattedDateString() }}</td>
          <td>{{ optional($transaction->payable)->getName() }}</td>
          <td>{!! $transaction->getFromMetaData('email') !!}</td>
          <td>{!! $transaction->getFromMetaData('description') !!}</td>
          <td>{!! $transaction->getFromMetaData('coupon_code') !!}</td>
          <td>{{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}</td>
          <td>{!! $transaction->uuid !!}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center text-muted">{{ trans('app.no_records_found') }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
