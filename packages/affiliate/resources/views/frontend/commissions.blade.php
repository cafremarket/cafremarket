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
            <th>{{ trans('packages.affiliate.item') }}</th>
            <th>{{ trans('packages.affiliate.commission') .' (%)' }}</th>
            <th>{{ trans('packages.affiliate.quantity') }}</th>
            <th>{{ trans('packages.affiliate.commission') }}</th>
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
                @isset($commission->order_id)
                  {!! $commission->order->order_number !!}
                @endisset
              </td>
              <td>
                @isset($commission->inventory_id)
                  {{ $commission->inventory->title }}
                @endisset
              </td>
              <td>
                {{ $commission->commission_rate }}
              </td>
              <td>
                {{ $commission->quantity }}
              </td>
              <td>
                {{ get_formated_currency($commission->total_commission, 2, config('system_settings.currency.id')) }}
              </td>
              <td>
                <span class="label label-outline text-uppercase">
                  @if ($commission->isPaid())
                    <i class="fa fa-check text-success"></i> {{ trans('packages.affiliate.received') }}
                  @else
                    <i class="fa fa-hourglass text-info"></i> {{ trans('packages.affiliate.pending') }}
                  @endif
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
