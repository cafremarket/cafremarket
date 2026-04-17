@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">
        {{ trans('packages.wallet.wallet_bulk_upload') }} {{ trans('app.preview') }} <small>({{ trans('app.total_number_of_rows', ['value' => count($rows)]) }})</small>
      </h3>
    </div> <!-- /.box-header -->

    <div class="box-body responsive-table">
      <table class="table table-striped">
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
              <td> {{ $row['coupon_code'] }} </td>
              <td>{{ $row['email'] }}</td>
              <td>{{ $row['amount'] }}</td>
              <td>{{ $row['currency_code'] }}</td>
              <td>{{ $row['user_type'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->

    <div class="box-footer">
      <a href="{{ route('admin.wallet.bulkupload.index') }}" class="btn btn-default btn-flat">{{ trans('app.cancel') }}</a>
      <small class="indent20">{{ trans('app.total_number_of_rows', ['value' => count($rows)]) }}</small>
      <div class="box-tools pull-right">
        {!! Form::open(['route' => 'admin.wallet.bulkupload.import', 'id' => 'form', 'class' => 'inline-form', 'data-toggle' => 'validator']) !!}
        @foreach ($rows as $row)
          {{ Form::hidden('data[]', serialize($row)) }}
        @endforeach
        {!! Form::button(trans('app.looks_good'), ['type' => 'submit', 'class' => 'confirm btn btn-new btn-flat']) !!}
        {!! Form::close() !!}
      </div>
    </div> <!-- /.box-footer -->
  </div> <!-- /.box -->
@endsection
