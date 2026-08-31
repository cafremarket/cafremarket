@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.dispute') }}
@endsection

@section('content')
  <div class="row admin-dispute-detail">
    <div class="col-md-3 admin-dispute-detail__sidebar">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.merchant'),
        'icon' => 'fa-store',
        'bodyClass' => 'admin-order-sidebar-panel',
      ])
        @if (Gate::allows('view', $dispute->shop))
          <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $dispute->shop_id) }}" class="ajax-modal-btn admin-order-sidebar-panel__name"><strong>{{ $dispute->shop->name }}</strong></a>
        @else
          <strong>{{ $dispute->shop->name }}</strong>
        @endif
        <img src="{{ get_logo_url($dispute->shop, 'small') }}" class="admin-detail-panel__thumb" alt="">
        <dl class="admin-order-sidebar-panel__meta">
          <dt>{{ trans('app.total_disputes') }}</dt>
          <dd><span class="label label-outline">{{ \App\Helpers\Statistics::dispute_count($dispute->shop_id) }}</span></dd>
          <dt>{{ trans('app.latest_days', ['days' => 30]) }}</dt>
          <dd><span class="label label-info">{{ \App\Helpers\Statistics::dispute_count($dispute->shop_id, 30) }}</span></dd>
        </dl>
        @if ($dispute->shop->owner)
          <label class="admin-detail-panel__label">{{ trans('app.owner') }}</label>
          <div class="admin-order-sidebar-panel__user">
            <img src="{{ get_avatar_src($dispute->shop->owner, 'tiny') }}" class="img-circle img-sm" alt="">
            @if (Gate::allows('view', $dispute->shop->owner))
              <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.show', $dispute->shop->owner_id) }}" class="ajax-modal-btn">{{ $dispute->shop->owner->getName() }}</a>
            @else
              {{ $dispute->shop->owner->getName() }}
            @endif
          </div>
        @endif
      @include('admin.partials.ui.card_end')
    </div>

    <div class="col-md-6 admin-dispute-detail__main">
      @php
        $disputeActions = '';
        if (Gate::allows('view', $dispute->order)) {
          $disputeActions .= '<a href="' . route('admin.order.order.show', $dispute->order->id) . '" class="btn btn-default btn-flat btn-sm"><i class="fa fa-shopping-cart"></i> ' . e(trans('app.order_details')) . '</a> ';
        }
        if (!$dispute->order->refunds->count() && Gate::allows('initiate', \App\Models\Refund::class)) {
          $disputeActions .= '<a href="javascript:void(0)" data-link="' . route('admin.support.refund.form', $dispute->order->id) . '" class="ajax-modal-btn btn btn-new btn-flat btn-sm">' . e(trans('app.initiate_refund')) . '</a> ';
        }
        if (Gate::allows('response', $dispute)) {
          $disputeActions .= '<a href="javascript:void(0)" data-link="' . route('admin.support.dispute.response', $dispute) . '" class="ajax-modal-btn btn btn-info btn-flat btn-sm"><i class="fa fa-reply"></i> ' . e(trans('app.response')) . '</a>';
        }
      @endphp

      @include('admin.partials.ui.card_start', [
        'title' => trans('app.dispute'),
        'icon' => 'fa-gavel',
        'headerExtra' => $dispute->statusName(),
        'actions' => $disputeActions,
        'bodyClass' => 'admin-detail-view',
      ])
        <div class="admin-detail-view__badges">
          <span class="label label-outline">
            @can('view', $dispute->order)
              <a href="{{ route('admin.order.order.show', $dispute->order->id) }}">
                {{ trans('app.order_number') . ': ' }}{{ $dispute->order->order_number }}
              </a>
            @else
              {{ trans('app.order_number') . ': ' }}{{ $dispute->order->order_number }}
            @endcan
          </span>
        </div>

        <p class="admin-detail-view__title">{{ $dispute->dispute_type->detail }}</p>

        @if (count($dispute->attachments))
          <div class="admin-detail-view__attachments">
            {{ trans('app.attachments') }}:
            @foreach ($dispute->attachments as $attachment)
              <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-default btn-xs btn-flat"><i class="fa fa-file"></i></a>
            @endforeach
          </div>
        @endif

        @if ($dispute->description)
          <div class="admin-detail-view__message well">{!! $dispute->description !!}</div>
        @endif

        @if ($dispute->replies->count() > 0)
          <div class="admin-detail-view__replies">
            <strong>{{ trans('app.conversations') }}</strong>
            @foreach ($dispute->replies as $reply)
              @include('admin.partials._reply_conversations')
            @endforeach
          </div>
        @endif
      @include('admin.partials.ui.card_end')

      @if ($dispute->order->refunds->count())
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.refunds'),
          'icon' => 'fa-undo',
        ])
          <table class="table table-hover admin-table admin-table--compact">
            <thead>
              <tr>
                <th>{{ trans('app.refund_amount') }}</th>
                <th>{{ trans('app.status') }}</th>
                <th>{{ trans('app.created_at') }}</th>
                <th>{{ trans('app.updated_at') }}</th>
                <th class="admin-table__actions-col">&nbsp;</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($dispute->order->refunds as $refund)
                <tr>
                  <td>{{ get_formated_currency($refund->amount, 2, $dispute->order->currency_id) }}</td>
                  <td>{!! $refund->statusName() !!}</td>
                  <td>{{ $refund->created_at->diffForHumans() }}</td>
                  <td>{{ $refund->updated_at->diffForHumans() }}</td>
                  <td class="row-options admin-row-actions">
                    @if ($refund->isOpen())
                      @can('approve', $refund)
                        <a href="javascript:void(0)" data-link="{{ route('admin.support.refund.response', $refund) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.response') }}" data-toggle="tooltip"><i class="fa fa-random"></i></a>
                      @endcan
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @include('admin.partials.ui.card_end')
      @endif

      @include('admin.partials._activity_logs', ['logger' => $dispute])
    </div>

    <div class="col-md-3 admin-dispute-detail__aside">
      @if ($dispute->product_id)
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.product'),
          'icon' => 'fa-cube',
          'bodyClass' => 'admin-order-sidebar-panel',
        ])
          <img src="{{ get_storage_file_url(optional($dispute->product->image)->path, 'medium') }}" class="admin-detail-panel__thumb admin-detail-panel__thumb--full" alt="">
          @if (Gate::allows('view', $dispute->product))
            <a href="javascript:void(0)" data-link="{{ route('admin.catalog.product.show', $dispute->product_id) }}" class="ajax-modal-btn">{{ $dispute->product->name }}</a>
          @else
            {{ $dispute->product->name }}
          @endif
        @include('admin.partials.ui.card_end')
      @endif

      @if ($dispute->refund_amount)
        @include('admin.partials.ui.card_start', [
          'title' => trans('app.refund_requested'),
          'icon' => 'fa-money',
          'bodyClass' => 'admin-order-sidebar-panel',
        ])
          <strong>{{ get_formated_currency($dispute->refund_amount, 2, $dispute->order->currency_id) }}</strong>
        @include('admin.partials.ui.card_end')
      @endif

      @include('admin.partials.ui.card_start', [
        'title' => trans('app.customer'),
        'icon' => 'fa-user',
        'bodyClass' => 'admin-order-sidebar-panel',
      ])
        <div class="admin-order-sidebar-panel__user">
          <img src="{{ get_avatar_src($dispute->customer, 'tiny') }}" class="img-circle img-sm" alt="">
          <div>
            @if (Gate::allows('view', $dispute->customer))
              <a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $dispute->customer_id) }}" class="ajax-modal-btn"><strong>{{ $dispute->customer->getName() }}</strong></a>
            @else
              <strong>{{ $dispute->customer->getName() }}</strong>
            @endif
          </div>
        </div>
        <dl class="admin-order-sidebar-panel__meta">
          <dt>{{ trans('app.total_disputes') }}</dt>
          <dd><span class="label label-outline">{{ \App\Helpers\Statistics::disputes_by_customer_count($dispute->customer_id) }}</span></dd>
          <dt>{{ trans('app.latest_days', ['days' => 30]) }}</dt>
          <dd><span class="label label-info">{{ \App\Helpers\Statistics::disputes_by_customer_count($dispute->customer_id, 30) }}</span></dd>
          <dt>{{ trans('app.created_at') }}</dt>
          <dd>{{ $dispute->created_at->diffForHumans() }}</dd>
          <dt>{{ trans('app.updated_at') }}</dt>
          <dd>{{ $dispute->updated_at->diffForHumans() }}</dd>
        </dl>
      @include('admin.partials.ui.card_end')
    </div>
  </div>
@endsection
