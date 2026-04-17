@extends('admin.layouts.master')

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
            <th>{{ trans('app.order') }}</th>
            <th>{{ trans('packages.affiliate.affiliate') }}</th>
            <th>{{ trans('packages.affiliate.amount') }}</th>
            <th>{{ trans('packages.affiliate.status') }}</th>
            <th>{{ trans('packages.affiliate.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($commissions as $commission)
            <tr>
              <td>
                {{ $commission->created_at->toFormattedDateString() }}
              </td>
              <td>
                @can('view', $commission->order)
                  <a href="{{ route('admin.order.order.show', $commission->order_id) }}" data-toggle="tooltip" data-placement="top" title="{{ trans('app.view_detail') }}">
                    {!! $commission->order->order_number !!}
                  </a>
                @else
                  {!! $commission->order->order_number !!}
                @endcan
              </td>
              <td>
                {!! $commission->affiliate->getName() !!}
              </td>
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
              <td>
                @unless ($commission->isPaid())
                  {!! Form::open(['route' => ['admin.affiliate.commission.release', $commission], 'method' => 'put', 'class' => 'action-form confirm']) !!}
                  <button class="btn btn-flat btn-primary">
                    <i class="fa fa-check"></i> {{ trans('packages.affiliate.release') }}
                  </button>
                  {!! Form::close() !!}
                @endunless
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
