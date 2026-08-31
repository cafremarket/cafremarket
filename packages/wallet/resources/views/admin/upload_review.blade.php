@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.wallet_bulk_upload') }} — {{ trans('app.preview') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.wallet_bulk_upload') . ' — ' . trans('app.preview'),
    'icon' => 'fa-eye',
    'headerExtra' => '<small class="text-muted">' . e(trans('app.total_number_of_rows', ['value' => count($rows)])) . '</small>',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-striped admin-table">
    <thead>
      <tr>
        <th>{{ trans('packages.wallet.coupon_code') }}</th>
        <th width="20%">{{ trans('app.email') }}</th>
        <th width="25%">{{ trans('app.amount') }}</th>
        <th width="20%">{{ trans('app.currency') }}</th>
        <th>{{ trans('app.user') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $row)
        <tr>
          <td>{{ $row['coupon_code'] }}</td>
          <td>{{ $row['email'] }}</td>
          <td>{{ $row['amount'] }}</td>
          <td>{{ $row['currency_code'] }}</td>
          <td>{{ $row['user_type'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  <div class="admin-card admin-card--footer-only">
    <div class="admin-card__footer">
      <a href="{{ route('admin.wallet.bulkupload.index') }}" class="btn btn-default btn-flat">{{ trans('app.cancel') }}</a>
      <small class="text-muted indent20">{{ trans('app.total_number_of_rows', ['value' => count($rows)]) }}</small>
      <div class="pull-right">
        {!! Form::open(['route' => 'admin.wallet.bulkupload.import', 'id' => 'form', 'class' => 'inline-form', 'data-toggle' => 'validator']) !!}
        @foreach ($rows as $row)
          {{ Form::hidden('data[]', serialize($row)) }}
        @endforeach
        {!! Form::button(trans('app.looks_good'), ['type' => 'submit', 'class' => 'confirm btn btn-new btn-flat']) !!}
        {!! Form::close() !!}
      </div>
    </div>
  </div>
@endsection
