@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.wallet.credit_rewards') }}</h3>
      <div class="box-tools pull-right">
      </div>
    </div> <!-- /.box-header -->

    <div class="box-body">
      <table class="table table-hover table-option">
        <thead>
          <tr>
            <th>{{ trans('packages.wallet.initiated_at') }}</th>
            <th>{{ trans('app.order') }}</th>
            <th>{{ trans('app.customer') }}</th>
            <th>{{ trans('packages.wallet.amount') }}</th>
            <th>{{ trans('packages.wallet.status') }}</th>
            <th>{{ trans('packages.wallet.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($creditRewards as $credit)
            <tr>
              <td>
                {{ $credit->created_at->toFormattedDateString() }}
              </td>
              <td>
               @if($credit->order_id && $credit->order)
    @can('view', $credit->order)
        <a href="{{ route('admin.order.order.show', ['order' => $credit->order->id]) }}"
           data-toggle="tooltip"
           title="{{ trans('app.view_detail') }}">
            {!! $credit->order->order_number !!}
        </a>
    @else
        {!! $credit->order->order_number !!}
    @endcan
@else
    <span class="text-muted">No Order</span>
@endif
              </td>
              <td>
                @if ($credit->customer_id)
                  @can('view', $credit->customer)
                    <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $credit->customer_id) }}" class="ajax-modal-btn modal-btn" data-toggle="tooltip" data-placement="top" title="{{ trans('app.profile') }}">
                      {!! $credit->customer->getName() !!}
                    </a>
                  @endcan
                @else
                  {!! $credit->customer->getName() !!}
                @endif
              </td>
              <td>
                {{ get_formated_currency($credit->amount, 2, config('system_settings.currency.id')) }}
              </td>
              <td>
                {!! $credit->status_badge !!}
              </td>
              <td>
                @unless ($credit->isReleased())
                  {!! Form::open(['route' => ['admin.wallet.reward.release', $credit], 'method' => 'post', 'class' => 'action-form confirm']) !!}
                  <button class="btn btn-flat btn-primary">
                    <i class="fa fa-check"></i> {{ trans('packages.wallet.release') }}
                  </button>
                  {!! Form::close() !!}
                @endunless

                {!! Form::open(['route' => ['admin.wallet.reward.delete', $credit], 'method' => 'delete', 'class' => 'data-form confirm']) !!}
                <button class="btn btn-flat btn-danger">
                  <i class="fa fa-trash-o"></i> {{ trans('app.delete') }}
                </button>
                {!! Form::close() !!}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->
@endsection
