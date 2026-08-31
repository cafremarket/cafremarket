@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.coupons') }}
@endsection

@section('content')
  @php
    $couponModel = \App\Models\Coupon::class;
    $massActions = [
      ['url' => route('admin.promotion.coupon.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.promotion.coupon.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.coupons'),
    'icon' => 'fa-ticket',
    'actions' => view('admin.coupon._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $couponModel, 'massActions' => $massActions])
        @cannot('massDelete', $couponModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.code') }}</th>
        <th>{{ trans('app.restricted') }}</th>
        <th>{{ trans('app.value') }}</th>
        <th>{{ trans('app.starting_time') }}</th>
        <th>{{ trans('app.ending_time') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($coupons as $coupon)
        <tr>
          @can('massDelete', $couponModel)
            <td><input id="{{ $coupon->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            {{ $coupon->name }}
            @if ($coupon->ending_time < \Carbon\Carbon::now())
              <span class="label label-default">{{ strtoupper(trans('app.expired')) }}</span>
            @elseif(!$coupon->isActive())
              <span class="label label-info">{{ strtoupper(trans('app.inactive')) }}</span>
            @endif
          </td>
          <td>{{ $coupon->code }}</td>
          <td>{{ get_yes_or_no($coupon->customers_count || $coupon->promotion_zones_count) }}</td>
          <td>
            <strong>
              {{ $coupon->type == 'amount' ? get_formated_currency($coupon->value, 2, config('system_settings.currency.id')) : get_formated_decimal($coupon->value) . ' ' . trans('app.percent') }}
            </strong>
          </td>
          <td>{{ $coupon->starting_time ? $coupon->starting_time->toDayDateTimeString() : '' }}</td>
          <td>{{ $coupon->ending_time ? $coupon->ending_time->toDayDateTimeString() : '' }}</td>
          <td class="row-options admin-row-actions">
            @can('view', $coupon)
              <a href="javascript:void(0)" data-link="{{ route('admin.promotion.coupon.show', $coupon->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $coupon)
              <a href="javascript:void(0)" data-link="{{ route('admin.promotion.coupon.edit', $coupon->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $coupon)
              {!! Form::open(['route' => ['admin.promotion.coupon.trash', $coupon->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
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
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.code') }}</th>
        <th>{{ trans('app.value') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>{{ $trash->name }}</td>
          <td>
            {{ $trash->code }}
            @if ($trash->ending_time < \Carbon\Carbon::now())
              ({{ trans('app.expired') }})
            @endif
          </td>
          <td>
            {{ $trash->type == 'amount' ? get_formated_currency($trash->value, 2, config('system_settings.currency.id')) : get_formated_decimal($trash->value) . ' ' . trans('app.percent') }}
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.promotion.coupon.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.promotion.coupon.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
