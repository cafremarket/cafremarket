@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.wallet.credit_rewards') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.wallet.credit_rewards'),
    'icon' => 'fa-gift',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('packages.wallet.initiated_at') }}</th>
        <th>{{ trans('app.order') }}</th>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('packages.wallet.amount') }}</th>
        <th>{{ trans('packages.wallet.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('packages.wallet.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($creditRewards as $credit)
        <tr>
          <td class="small">{{ $credit->created_at->toFormattedDateString() }}</td>
          <td>
            @if ($credit->order_id && $credit->order)
              @can('view', $credit->order)
                <a href="{{ route('admin.order.order.show', ['order' => $credit->order->id) }}" title="{{ trans('app.view_detail') }}" data-toggle="tooltip">{!! $credit->order->order_number !!}</a>
              @else
                {!! $credit->order->order_number !!}
              @endcan
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td>
            @if ($credit->customer_id)
              @can('view', $credit->customer)
                <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $credit->customer_id) }}" class="ajax-modal-btn">{!! $credit->customer->getName() !!}</a>
              @else
                {!! $credit->customer->getName() !!}
              @endcan
            @endif
          </td>
          <td>{{ get_formated_currency($credit->amount, 2, config('system_settings.currency.id')) }}</td>
          <td>{!! $credit->status_badge !!}</td>
          <td class="row-options admin-row-actions">
            @unless ($credit->isReleased())
              {!! Form::open(['route' => ['admin.wallet.reward.release', $credit], 'method' => 'post', 'class' => 'admin-inline-form confirm']) !!}
              <button type="submit" class="admin-action-btn" title="{{ trans('packages.wallet.release') }}" data-toggle="tooltip"><i class="fa fa-check"></i></button>
              {!! Form::close() !!}
            @endunless
            {!! Form::open(['route' => ['admin.wallet.reward.delete', $credit], 'method' => 'delete', 'class' => 'admin-inline-form confirm']) !!}
            <button type="submit" class="admin-action-btn" title="{{ trans('app.delete') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
            {!! Form::close() !!}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
