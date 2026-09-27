@extends('affiliate::backend.master_layout')

@section('content')
  <div class="box admin-card">
    <div class="box-header with-border admin-card__header">
      <h3 class="box-title admin-card__title">{{ trans('packages.affiliate.affiliate_commissions') }}</h3>
      <div class="box-tools pull-right admin-card__actions">
      </div>
    </div> <!-- /.box-header -->

    <div class="box-body admin-card__body">
      <table class="table table-hover admin-table table-option">
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
                @include('affiliate::partials._commission_status', ['commission' => $commission, 'paidLabel' => trans('packages.affiliate.released')])
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
