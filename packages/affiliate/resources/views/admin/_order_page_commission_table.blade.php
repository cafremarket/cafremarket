@include('admin.partials.ui.card_start', [
  'title' => trans('packages.affiliate.affiliate_commissions'),
  'icon' => 'fa-handshake-o',
])

<table class="table table-hover admin-table admin-table--compact">
  <thead>
    <tr>
      <th>{{ trans('packages.affiliate.created_at') }}</th>
      <th>{{ trans('packages.affiliate.amount') }}</th>
      <th>{{ trans('packages.affiliate.status') }}</th>
      @if (auth()->user()->isSuperAdmin())
        <th class="admin-table__actions-col">{{ trans('packages.affiliate.option') }}</th>
      @endif
    </tr>
  </thead>
  <tbody>
    @if (is_null($commissions) || $commissions->isEmpty())
      <tr>
        <td colspan="4" class="text-muted">{{ trans('packages.affiliate.order_has_no_affiliate_commission') }}</td>
      </tr>
    @else
      @foreach ($commissions as $commission)
        <tr>
          <td>{{ $commission->created_at->toFormattedDateString() }}</td>
          <td>{{ get_formated_currency($commission->total_commission, 2, config('system_settings.currency.id')) }}</td>
          <td>
            @if ($commission->isPaid())
              <i class="fa fa-check text-success"></i> {{ trans('packages.affiliate.released') }}
            @else
              <i class="fa fa-hourglass text-info"></i> {{ trans('packages.affiliate.pending') }}
            @endif
          </td>
          @if (auth()->user()->isSuperAdmin())
            <td class="row-options admin-row-actions">
              @unless ($commission->isPaid())
                {!! Form::open(['route' => ['admin.affiliate.commission.release', $commission], 'method' => 'put', 'class' => 'action-form confirm admin-inline-form']) !!}
                <button class="btn btn-flat btn-primary btn-sm"><i class="fa fa-check"></i> {{ trans('packages.affiliate.release') }}</button>
                {!! Form::close() !!}
              @endunless
            </td>
          @endif
        </tr>
      @endforeach
    @endif
  </tbody>
</table>

@include('admin.partials.ui.card_end')
