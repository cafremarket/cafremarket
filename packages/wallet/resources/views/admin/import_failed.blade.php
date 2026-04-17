@extends('admin.layouts.master')

@section('content')
  <div class="alert alert-danger">
    <strong><i class="icon fa fa-info-circle"></i>{{ trans('app.notice') }}</strong>
    {{ trans('messages.import_ignored') }}
  </div>
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">
        {{ trans('app.import_failed') }} <small>({{ trans('app.total_number_of_rows', ['value' => count($failed_rows)]) }})</small>
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
              <th>{{ trans('app.reason') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($failed_rows as $row)
              <tr>
                <td> {{ $row['data']['coupon_code'] }} </td>
                <td>{{ $row['data']['email'] }}</td>
                <td>{{ $row['data']['amount'] }}</td>
                <td>{{ $row['data']['currency_code'] }}</td>
                <td>{{ $row['data']['user_type'] }}</td>
                <td>{{ $row['reason'] }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div> <!-- /.box-body -->

    <div class="box-footer">
      <a href="{{ route('admin.wallet.bulkupload.index') }}" class="btn btn-danger btn-flat">{{ trans('app.dismiss') }}</a>
      <small class="indent20">{{ trans('app.total_number_of_rows', ['value' => count($failed_rows)]) }}</small>
      <div class="box-tools pull-right">
        {!! Form::open(['route' => 'admin.wallet.bulkupload.downloadFailedRows', 'id' => 'form', 'class' => 'inline-form', 'data-toggle' => 'validator']) !!}
        @foreach ($failed_rows as $row)
          <input type="hidden" name="data[]" value="{{ serialize($row['data']) }}">
        @endforeach
        {!! Form::button(trans('app.download_failed_rows'), ['type' => 'submit', 'class' => 'btn btn-new btn-flat']) !!}
        {!! Form::close() !!}
      </div>
    </div> <!-- /.box-footer -->
  </div> <!-- /.box -->
@endsection
