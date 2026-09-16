@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.refunds') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_tabbed_start', [
    'title' => trans('app.refunds'),
    'icon' => 'fa-undo',
    'actions' => view('admin.refunds._header_actions')->render(),
  ])

  <ul class="nav nav-tabs nav-justified admin-tabs">
    <li class="{{ $tab === 'pending' ? 'active' : '' }}">
      <a href="{{ route('admin.refunds.index', ['tab' => 'pending', 'q' => $search ?: null]) }}">
        {{ trans('app.refund_status.pending') }}
        <span class="badge">{{ $counts['pending'] }}</span>
      </a>
    </li>
    <li class="{{ $tab === 'completed' ? 'active' : '' }}">
      <a href="{{ route('admin.refunds.index', ['tab' => 'completed', 'q' => $search ?: null]) }}">
        {{ trans('app.refund_status.completed') }}
        <span class="badge">{{ $counts['completed'] }}</span>
      </a>
    </li>
    <li class="{{ $tab === 'issue' ? 'active' : '' }}">
      <a href="{{ route('admin.refunds.index', ['tab' => 'issue', 'q' => $search ?: null]) }}">
        {{ trans('app.refund_status.issue') }}
        <span class="badge">{{ $counts['issue'] }}</span>
      </a>
    </li>
  </ul>

  <div class="admin-filters admin-filters--inset" style="padding:12px 15px;">
    <form method="get" action="{{ route('admin.refunds.index') }}" class="form-inline">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <div class="input-group" style="max-width:360px;width:100%;">
        <input type="text" name="q" value="{{ $search }}" class="form-control input-sm" placeholder="{{ trans('app.search_by_order_number') }}">
        <span class="input-group-btn">
          <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-search"></i></button>
        </span>
      </div>
    </form>
  </div>

  <div class="responsive-table">
    <table class="table table-hover admin-table table-option">
      <thead>
        <tr>
          <th>{{ trans('app.order_number') }}</th>
          @if (Auth::user()->isFromPlatform())
            <th>{{ trans('app.shop') }}</th>
          @endif
          <th>{{ trans('app.customer') }}</th>
          <th>{{ trans('app.refund_amount') }}</th>
          <th>{{ trans('app.status') }}</th>
          <th>{{ trans('app.created_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($refunds as $refund)
          <tr>
            <td>
              @can('index', \App\Models\Order::class)
                <a href="{{ route('admin.order.order.show', $refund->order_id) }}">
                  {{ optional($refund->order)->order_number ?? ('#'.$refund->order_id) }}
                </a>
              @else
                {{ optional($refund->order)->order_number ?? ('#'.$refund->order_id) }}
              @endcan
            </td>
            @if (Auth::user()->isFromPlatform())
              <td>{{ optional($refund->shop)->name ?? '—' }}</td>
            @endif
            <td>{{ optional(optional($refund->order)->customer)->getName() ?? '—' }}</td>
            <td>{{ get_formated_currency($refund->amount, 2, optional($refund->order)->currency_id) }}</td>
            <td>
              {!! $refund->statusName() !!}
              @if ($refund->failure_reason)
                <br><small class="text-danger">{{ \Illuminate\Support\Str::limit($refund->failure_reason, 80) }}</small>
              @elseif ($refund->admin_note)
                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($refund->admin_note), 80) }}</small>
              @endif
            </td>
            <td>{{ optional($refund->created_at)->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @can('index', \App\Models\Order::class)
                <a href="{{ route('admin.order.order.show', $refund->order_id) }}" class="admin-action-btn" title="{{ trans('app.order') }}" data-toggle="tooltip">
                  <i class="fa fa-shopping-cart"></i>
                </a>
              @endcan
              @if ($refund->isOpen())
                @can('approve', $refund)
                  <a href="javascript:void(0)" data-link="{{ route('admin.refunds.response', $refund) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.response') }}" data-toggle="tooltip">
                    <i class="fa fa-random"></i>
                  </a>
                @endcan
              @else
                <a href="javascript:void(0)" data-link="{{ route('admin.refunds.response', $refund) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip">
                  <i class="fa fa-eye"></i>
                </a>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @include('admin.partials.ui.card_tabbed_end')
@endsection
