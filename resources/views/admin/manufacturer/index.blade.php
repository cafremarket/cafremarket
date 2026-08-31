@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.manufacturers') }}
@endsection

@section('content')
  @php
    $manufacturerModel = \App\Models\Manufacturer::class;
    $massActions = [
      ['url' => route('admin.catalog.manufacturer.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.catalog.manufacturer.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.manufacturers'),
    'icon' => 'fa-industry',
    'actions' => view('admin.manufacturer._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $manufacturerModel, 'massActions' => $massActions])
        @cannot('massDelete', $manufacturerModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.phone') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.country') }}</th>
        <th>{{ trans('app.products') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($manufacturers as $manufacturer)
        <tr>
          @can('massDelete', $manufacturerModel)
            <td><input id="{{ $manufacturer->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td><img src="{{ get_logo_url($manufacturer, 'tiny') }}" class="img-sm admin-table__avatar" alt=""></td>
          <td>
            {{ $manufacturer->name }}
            @unless ($manufacturer->active)
              <span class="label label-default">{{ trans('app.inactive') }}</span>
            @endunless
          </td>
          <td>{{ $manufacturer->phone }}</td>
          <td>{{ $manufacturer->email }}</td>
          <td>{{ optional($manufacturer->country)->name }}</td>
          <td><span class="label label-default">{{ $manufacturer->products_count }}</span></td>
          <td class="row-options admin-row-actions">
            @can('view', $manufacturer)
              <a href="javascript:void(0)" data-link="{{ route('admin.catalog.manufacturer.show', $manufacturer->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $manufacturer)
              <a href="{{ route('admin.catalog.manufacturer.translate.form', ['manufacturer' => $manufacturer, 'language' => config('system_settings.default_language')]) }}" class="admin-action-btn" title="{{ trans('app.manage_translations') }}" data-toggle="tooltip"><em class="fa fa-language"></em></a>
              <a href="javascript:void(0)" data-link="{{ route('admin.catalog.manufacturer.edit', $manufacturer->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $manufacturer)
              {!! Form::open(['route' => ['admin.catalog.manufacturer.trash', $manufacturer->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.phone') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_logo_url($trash, 'tiny') }}" class="img-circle img-sm" alt="">
              <span>{{ $trash->name }}</span>
            </div>
          </td>
          <td>{{ $trash->phone }}</td>
          <td>{{ $trash->email }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.catalog.manufacturer.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.catalog.manufacturer.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
