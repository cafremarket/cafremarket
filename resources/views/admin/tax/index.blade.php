@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.taxes') }}
@endsection

@section('content')
  @php
    $taxModel = \App\Models\Tax::class;
    $massActions = [
      ['url' => route('admin.setting.tax.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.setting.tax.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.taxes'),
    'icon' => 'fa-percent',
    'actions' => view('admin.tax._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $taxModel, 'massActions' => $massActions])
        @cannot('massDelete', $taxModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.tax_rate') }}</th>
        <th>{{ trans('app.region') }}</th>
        <th>{{ trans('app.public') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($taxes as $tax)
        <tr>
          @can('massDelete', $taxModel)
            <td><input id="{{ $tax->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>{{ $tax->name }}</td>
          <td>{{ get_formated_decimal($tax->taxrate) . ' ' . trans('app.%') }}</td>
          <td>
            {{ $tax->state ? $tax->state->name . ' :: ' : '' }}
            {{ $tax->country ? $tax->country->name : '' }}
          </td>
          <td>{{ $tax->public ? trans('app.yes') : '-' }}</td>
          <td>{{ $tax->active ? trans('app.active') : trans('app.inactive') }}</td>
          <td class="row-options admin-row-actions">
            @can('update', $tax)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.tax.edit', $tax->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $tax)
              {!! Form::open(['route' => ['admin.setting.tax.trash', $tax->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.tax_rate') }}</th>
        <th>{{ trans('app.country') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->taxrate }} {{ trans('app.%') }}</td>
          <td>{{ $trash->country->name }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.setting.tax.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.setting.tax.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
