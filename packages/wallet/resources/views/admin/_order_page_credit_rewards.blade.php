@include('admin.partials.ui.card_start', [
  'title' => trans('packages.wallet.credit_back_rewards'),
  'icon' => 'fa-star',
])

<table class="table table-hover admin-table admin-table--compact">
  <thead>
    <tr>
      <th>{{ trans('packages.wallet.initiated_at') }}</th>
      <th>{{ trans('packages.wallet.amount') }}</th>
      <th>{{ trans('packages.wallet.status') }}</th>
      <th class="admin-table__actions-col">{{ trans('packages.wallet.option') }}</th>
    </tr>
  </thead>
  <tbody>
    @if (!$order->customer_id)
      <tr><td colspan="4" class="text-muted">{{ trans('packages.wallet.guest_customer_cant_get_reward') }}</td></tr>
    @elseif ($order->isPaid())
      @foreach ($order->creditRewards as $credit)
        <tr>
          <td>{{ $credit->created_at->toFormattedDateString() }}</td>
          <td>{{ get_formated_currency($credit->amount, 2, config('system_settings.currency.id')) }}</td>
          <td>{!! $credit->status_badge !!}</td>
          <td class="row-options admin-row-actions">
            @unless ($credit->isReleased())
              {!! Form::open(['route' => ['admin.wallet.reward.release', $credit], 'method' => 'post', 'class' => 'action-form confirm admin-inline-form']) !!}
              <button class="btn btn-flat btn-primary btn-sm"><i class="fa fa-check"></i> {{ trans('packages.wallet.release') }}</button>
              {!! Form::close() !!}
            @endunless
            {!! Form::open(['route' => ['admin.wallet.reward.delete', $credit], 'method' => 'delete', 'class' => 'data-form confirm admin-inline-form']) !!}
            <button class="btn btn-flat btn-danger btn-sm"><i class="fa fa-trash-o"></i> {{ trans('app.delete') }}</button>
            {!! Form::close() !!}
          </td>
        </tr>
      @endforeach
    @else
      <tr><td colspan="4" class="text-muted">{{ trans('packages.wallet.order_needs_to_be_paid') }}</td></tr>
    @endif
  </tbody>
</table>

@include('admin.partials.ui.card_end')
