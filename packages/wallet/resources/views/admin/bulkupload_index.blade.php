@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.wallet.wallet_bulk_upload') }}</h3>
      <div class="box-tools pull-right">
        <a href="javascript:void(0)" data-link="{{ route('admin.wallet.bulkupload') }}" class="ajax-modal-btn btn btn-new btn-flat"><i class="fa fa-plus"></i> {{ trans('packages.wallet.bulk_upload') }}</a>
      </div>
    </div> <!-- /.box-header -->
    <div class="box-body">
      <table class="table table-hover table-no-sort">
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
          @foreach ($bulkupload_deposits as $transaction)
            <tr>
              <td>
                {{ $transaction->created_at->toFormattedDateString() }}
              </td>
              <td>
                {{ optional($transaction->payable)->getName() }}
              </td>
              <td>
                {!! $transaction->getFromMetaData('email') !!}
              </td>
              <td>
                {!! $transaction->getFromMetaData('description') !!}
              </td>
              <td>
                {!! $transaction->getFromMetaData('coupon_code') !!}
              </td>
              <td>
                {{ get_formated_currency($transaction->amount, 2, config('system_settings.currency.id')) }}
              </td>
              <td>
                {!! $transaction->uuid !!}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
