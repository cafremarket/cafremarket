@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.disputes') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.disputes'),
    'icon' => 'fa-gavel',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.refund_requested') }}</th>
        <th>{{ trans('app.response') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($disputes as $dispute)
        <tr>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_avatar_src($dispute->customer, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
              <div>
                <strong>{{ $dispute->customer->getName() }}</strong>
                @if (Auth::user()->isFromPlatform() && $dispute->shop)
                  <br><span class="text-muted">{{ trans('app.vendor') . ': ' . optional($dispute->shop)->name }}</span>
                @endif
              </div>
            </div>
          </td>
          <td>
            @if (!Auth::user()->isFromPlatform())
              {!! $dispute->statusName() !!}
            @endif
            <a href="{{ route('admin.support.dispute.show', $dispute->id) }}">{{ $dispute->dispute_type->detail }}</a>
          </td>
          <td>{{ get_formated_currency($dispute->refund_amount, 2, $dispute->order->currency_id) }}</td>
          <td><span class="label label-default">{{ $dispute->replies_count }}</span></td>
          <td>{{ $dispute->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            <a href="{{ route('admin.support.dispute.show', $dispute->id) }}" class="admin-action-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @can('response', $dispute)
              <a href="javascript:void(0)" data-link="{{ route('admin.support.dispute.response', $dispute) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.response') }}" data-toggle="tooltip"><i class="fa fa-reply"></i></a>
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.response') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($closed as $dispute)
        <tr>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_avatar_src($dispute->customer, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
              <div>
                <strong>{{ $dispute->customer->getName() }}</strong>
                @if (Auth::user()->isFromPlatform() && $dispute->shop)
                  <br><span class="text-muted">{{ trans('app.vendor') . ': ' . optional($dispute->shop)->name }}</span>
                @endif
              </div>
            </div>
          </td>
          <td>
            @if (!Auth::user()->isFromPlatform())
              {!! $dispute->statusName() !!}
            @endif
            <a href="{{ route('admin.support.dispute.show', $dispute->id) }}">{{ $dispute->dispute_type->detail }}</a>
          </td>
          <td><span class="label label-default">{{ $dispute->replies_count }}</span></td>
          <td>{{ $dispute->updated_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('response', $dispute)
              <a href="javascript:void(0)" data-link="{{ route('admin.support.dispute.response', $dispute) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.response') }}" data-toggle="tooltip"><i class="fa fa-reply"></i></a>
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
