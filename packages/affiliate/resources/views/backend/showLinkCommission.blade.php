@extends('affiliate::backend.master_layout')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.affiliate.affiliate_commissions') }}</h3>
      <div class="box-tools pull-right">
      </div>
    </div> <!-- /.box-header -->

    <div class="box-body">
      <table class="table table-hover table-option">
        <thead>
          <tr>
            <th>{{ trans('packages.affiliate.created_at') }}</th>
            <th>{{ trans('packages.affiliate.order') }}</th>
            @unless (auth()->guard('affiliate')->check())
              <th>{{ trans('packages.affiliate.affiliate') }}</th>
            @endunless
            <th>{{ trans('packages.affiliate.amount') }}</th>
            <th>{{ trans('packages.affiliate.status') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($commissions as $commission)
            <tr>
              <td>
                {{ $commission->created_at->toFormattedDateString() }}
              </td>
              <td>
                  {!! $commission->order->order_number !!}
              </td>
              @unless(auth()->guard('affiliate')->check())
                <td>
                    {!! $commission->affiliate->getName() !!}
                </td>
              @endunless
              <td>
                {{ get_formated_currency($commission->total_commission, 2, config('system_settings.currency.id')) }}
              </td>
              <td>
                @if ($commission->isPaid())
                  <i class="fa fa-check text-success"></i> {{ trans('packages.affiliate.released') }}
                @else
                    <i class="fa fa-hourglass text-info"></i> {{ trans('packages.affiliate.pending_commission') }}
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
