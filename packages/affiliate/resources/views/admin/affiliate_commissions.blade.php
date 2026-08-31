@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.affiliate.affiliate_commissions') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.affiliate.affiliate_commissions'),
    'icon' => 'fa-handshake-o',
  ])

  <table class="table table-hover admin-table">
    <thead>
      <tr>
        <th>{{ trans('packages.affiliate.created_at') }}</th>
        <th>{{ trans('app.order') }}</th>
        <th>{{ trans('packages.affiliate.affiliate') }}</th>
        <th>{{ trans('packages.affiliate.amount') }}</th>
        <th>{{ trans('packages.affiliate.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('packages.affiliate.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($commissions as $commission)
        <tr>
          <td>{{ $commission->created_at->toFormattedDateString() }}</td>
          <td>
            @if ($commission->order)
              @can('view', $commission->order)
                <a href="{{ route('admin.order.order.show', $commission->order_id) }}">{!! $commission->order->order_number !!}</a>
              @else
                {!! $commission->order->order_number !!}
              @endcan
            @else
              —
            @endif
          </td>
          <td>{!! $commission->affiliate ? $commission->affiliate->getName() : '—' !!}</td>
          <td>{{ get_formated_currency($commission->total_commission, 2, config('system_settings.currency.id')) }}</td>
          <td>
            @if ($commission->isPaid())
              <i class="fa fa-check text-success"></i> {{ trans('packages.affiliate.released') }}
            @else
              <i class="fa fa-hourglass text-info"></i> {{ trans('packages.affiliate.pending_commission') }}
            @endif
          </td>
          <td class="row-options admin-row-actions">
            @unless ($commission->isPaid())
              {!! Form::open(['route' => ['admin.affiliate.commission.release', $commission], 'method' => 'put', 'class' => 'action-form confirm admin-inline-form']) !!}
              <button class="btn btn-flat btn-primary btn-sm"><i class="fa fa-check"></i> {{ trans('packages.affiliate.release') }}</button>
              {!! Form::close() !!}
            @endunless
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
