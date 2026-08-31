@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.states') . ': ' . $country->name }}
@endsection

@section('content')
  @include('admin.partials.notices.worldwide_business_area')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.states') . ': ' . $country->name,
    'icon' => 'fa-map',
    'headerExtra' => '<i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="' . e(trans('help.active_business_zone')) . '"></i>',
    'actions' => Gate::allows('update', $country)
      ? '<a href="javascript:void(0)" data-link="' . route('admin.setting.state.create', $country->id) . '" class="ajax-modal-btn btn btn-new btn-flat btn-sm"><i class="fa fa-plus"></i> ' . e(trans('app.add_state')) . '</a>'
      : '',
    'bodyClass' => 'responsive-table',
  ])
      <table class="table table-hover admin-table table-no-sort">
        <thead>
          <tr>
            @can('massDelete', \App\Models\Country::class)
              <th class="massActionWrapper">
                <!-- Check all button -->
                <div class="btn-group ">
                  <button type="button" class="btn btn-xs btn-default checkbox-toggle">
                    <i class="fa fa-square-o" data-toggle="tooltip" data-placement="top" title="{{ trans('app.select_all') }}"></i>
                  </button>
                  <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span class="caret"></span>
                    <span class="sr-only">{{ trans('app.toggle_dropdown') }}</span>
                  </button>
                  <ul class="dropdown-menu" role="menu">
                    <li><a href="javascript:void(0)" data-link="{{ route('admin.setting.state.massDestroy') }}" class="massAction" data-doafter="reload"><i class="fa fa-times"></i> {{ trans('app.delete_permanently') }}</a></li>
                  </ul>
                </div>
              </th>
            @endcan

            <th>{{ trans('app.iso_code') }}</th>
            <th>{{ trans('app.name') }}</th>
            <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
          </tr>
        </thead>
        <tbody id="massSelectArea">
          @foreach ($country->states as $state)
            <tr>
              @can('massDelete', \App\Models\Country::class)
                <td><input id="{{ $state->id }}" type="checkbox" class="massCheck"></td>
              @endcan
              <td>{{ $state->iso_code }}</td>
              <td>
                {{ $state->name }}

                @if ($state->active)
                  <span class="indent10 label label-primary pull-right">{{ trans('app.active') }}</span>
                @endif
              </td>
              <td class="row-options admin-row-actions">
                @can('update', $country)
                  <a href="javascript:void(0)" data-link="{{ route('admin.setting.state.edit', $state->id) }}" class="ajax-modal-btn"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.edit') }}" class="fa fa-edit"></i></a>&nbsp;
                @endcan

                @can('delete', $country)
                  {!! Form::open(['route' => ['admin.setting.state.destroy', $state->id], 'method' => 'delete', 'class' => 'data-form']) !!}
                  {!! Form::button('<i class="fa fa-trash-o"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.delete'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
                  {!! Form::close() !!}
                @endcan
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
  @include('admin.partials.ui.card_end')
@endsection
