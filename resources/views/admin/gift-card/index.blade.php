@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.gift_cards') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.gift_cards'),
    'icon' => 'fa-gift',
    'actions' => view('admin.gift-card._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-option">
    <thead>
      <tr>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.value') }}</th>
        <th>{{ trans('app.activation_time') }}</th>
        <th>{{ trans('app.expiry_time') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($valid_cards as $card)
        <tr>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_storage_file_url(optional($card->featureImage)->path, 'tiny') }}" class="img-sm admin-table__banner-thumb" alt="">
              <div>
                {{ $card->name }}
                @if ($card->isInUse())
                  <span class="label label-primary">{{ trans('app.in_use') }}</span>
                @endif
              </div>
            </div>
          </td>
          <td>{{ get_formated_currency($card->value, 2, config('system_settings.currency.id')) }}</td>
          <td>{{ $card->activation_time ? $card->activation_time->toDayDateTimeString() : '' }}</td>
          <td>{{ $card->expiry_time ? $card->expiry_time->toDayDateTimeString() : '' }}</td>
          <td class="row-options admin-row-actions">
            @can('view', $card)
              <a href="javascript:void(0)" data-link="{{ route('admin.promotion.giftCard.show', $card->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $card)
              <a href="javascript:void(0)" data-link="{{ route('admin.promotion.giftCard.edit', $card->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $card)
              {!! Form::open(['route' => ['admin.promotion.giftCard.trash', $card->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.invalid') . ' / ' . trans('app.used'),
    'icon' => 'fa-ban',
    'class' => 'admin-card--muted',
  ])

  <table class="table table-hover admin-table table-option">
    <thead>
      <tr>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.pin_code') }}</th>
        <th>{{ trans('app.serial_number') }}</th>
        <th>{{ trans('app.value') }}</th>
        <th>{{ trans('app.expiry_time') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($invalid_cards as $card)
        <tr>
          <td>
            {{ $card->name }}
            @if (!$card->hasRemaining())
              <span class="label label-info">{{ trans('app.used') }}</span>
            @endif
          </td>
          <td>{{ $card->pin_code }}</td>
          <td>{{ $card->serial_number }}</td>
          <td>{{ get_formated_currency($card->value, 2, config('system_settings.currency.id')) }}</td>
          <td>{{ $card->expiry_time->toDayDateTimeString() }}</td>
          <td class="row-options admin-row-actions">
            @can('view', $card)
              <a href="javascript:void(0)" data-link="{{ route('admin.promotion.giftCard.show', $card->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $card)
              <a href="javascript:void(0)" data-link="{{ route('admin.promotion.giftCard.edit', $card->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $card)
              {!! Form::open(['route' => ['admin.promotion.giftCard.trash', $card->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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

  <table class="table table-hover admin-table table-2nd-sort">
    <thead>
      <tr>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.serial_number') }}</th>
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
            {{ $trash->serial_number }}
            @if ($trash->expiry_time < \Carbon\Carbon::now())
              ({{ trans('app.invalid') }})
            @endif
          </td>
          <td>{{ get_formated_currency($trash->value, 2, config('system_settings.currency.id')) }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.promotion.giftCard.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.promotion.giftCard.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
