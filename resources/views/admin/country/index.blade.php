@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.countries') }}
@endsection

@section('content')
  @include('admin.partials.notices.worldwide_business_area')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.countries'),
    'icon' => 'fa-globe',
    'actions' => view('admin.country._header_actions')->render(),
    'headerExtra' => '<i class="fa fa-question-circle admin-card__help-icon" data-toggle="tooltip" title="' . e(trans('help.active_business_zone')) . '"></i>',
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.flag') }}</th>
        <th>{{ trans('app.iso_code') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th class="text-center">{{ trans('app.number_of_states') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($countries as $country)
        <tr>
          <td>{!! get_flag_img_by_code($country->iso_code) !!}</td>
          <td>{{ $country->iso_code }}</td>
          <td>
            <a href="{{ route('admin.setting.country.states', $country->id) }}">{{ $country->name }}</a>
            @if ($country->eea)
              <span class="label label-outline" data-toggle="tooltip" title="{{ trans('help.eea') }}">{{ trans('app.eea') }}</span>
            @endif
            @if ($country->active)
              <span class="label label-primary">{{ trans('app.active') }}</span>
            @endif
          </td>
          <td class="text-center">{{ $country->states_count }}</td>
          <td class="row-options admin-row-actions">
            <a href="{{ route('admin.setting.country.states', $country->id) }}" class="admin-action-btn" title="{{ trans('app.state') }}" data-toggle="tooltip"><i class="fa fa-plus"></i></a>
            @can('update', $country)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.country.edit', $country->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
