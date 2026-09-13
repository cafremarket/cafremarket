@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.popups') }}
@endsection

@section('content')
  @php
    $popupModel = \App\Models\Popup::class;
    $massActions = [
      ['url' => route('admin.popup.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('nav.popups'),
    'icon' => 'fa-window-restore',
    'actions' => view('admin.popup._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $popupModel, 'massActions' => $massActions])
        <th>{{ trans('app.form.title') }}</th>
        <th>{{ trans('app.platform') }}</th>
        <th>{{ trans('app.form.page') }}</th>
        <th>{{ trans('app.form.user_type') }}</th>
        <th>{{ trans('app.form.frequency') }}</th>
        <th>{{ trans('app.priority') }}</th>
        <th>{{ trans('app.active') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($popups as $popup)
        <tr>
          @can('massDelete', $popupModel)
            <td><input id="{{ $popup->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <strong>{{ $popup->title }}</strong>
            @if ($popup->headline)
              <br><small class="text-muted">{{ $popup->headline }}</small>
            @endif
          </td>
          <td>
            @if ($popup->platform === \App\Models\Popup::PLATFORM_ALL)
              {{ trans('app.all') }}
            @elseif ($popup->platform === \App\Models\Popup::PLATFORM_WEB)
              {{ trans('app.website') }}
            @else
              {{ trans('app.mobile_app') }}
            @endif
          </td>
          <td>{{ $popup->page === \App\Models\Popup::PAGE_ALL ? trans('app.all') : trans('app.popup_page_'.$popup->page) }}</td>
          <td>
            @if ($popup->user_type === \App\Models\Popup::USER_TYPE_ALL)
              {{ trans('app.all') }}
            @elseif ($popup->user_type === \App\Models\Popup::USER_TYPE_GUEST)
              {{ trans('app.guest') }}
            @else
              {{ trans('app.customer') }}
            @endif
          </td>
          <td>{{ trans('app.popup_frequency_'.$popup->frequency) }}</td>
          <td>{{ $popup->priority }}</td>
          <td>
            @if ($popup->active)
              <span class="label label-success">{{ trans('app.active') }}</span>
            @else
              <span class="label label-default">{{ trans('app.inactive') }}</span>
            @endif
          </td>
          <td class="row-options admin-row-actions">
            @can('update', $popup)
              <a href="javascript:void(0)" data-link="{{ route('admin.popup.edit', $popup->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $popup)
              {!! Form::open(['route' => ['admin.popup.destroy', $popup->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
