@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.warehouses') }}
@endsection

@section('content')
  @php
    $warehouseModel = \App\Models\Warehouse::class;
    $massActions = [
      ['url' => route('admin.stock.warehouse.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.stock.warehouse.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.warehouses'),
    'icon' => 'fa-building',
    'actions' => view('admin.warehouse._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $warehouseModel, 'massActions' => $massActions])
        @cannot('massDelete', $warehouseModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.incharge') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($warehouses as $warehouse)
        <tr>
          @can('massDelete', $warehouseModel)
            <td><input id="{{ $warehouse->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_logo_url($warehouse, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
          </td>
          <td>{{ $warehouse->name }}</td>
          <td>{{ $warehouse->email }}</td>
          <td>{{ $warehouse->manager ? $warehouse->manager->getName() : '' }}</td>
          <td>{{ $warehouse->active ? trans('app.active') : trans('app.inactive') }}</td>
          <td class="row-options admin-row-actions">
            @can('view', $warehouse)
              <a href="javascript:void(0)" data-link="{{ route('admin.stock.warehouse.show', $warehouse->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $warehouse)
              <a href="javascript:void(0)" data-link="{{ route('admin.stock.warehouse.edit', $warehouse->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              @if ($warehouse->primaryAddress)
                <a href="javascript:void(0)" data-link="{{ route('address.edit', $warehouse->primaryAddress->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.update_address') }}" data-toggle="tooltip"><i class="fa fa-map-marker"></i></a>
              @else
                <a href="javascript:void(0)" data-link="{{ route('address.create', ['warehouse', $warehouse->id]) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.add_address') }}" data-toggle="tooltip"><i class="fa fa-plus-square-o"></i></a>
              @endif
            @endcan
            @can('delete', $warehouse)
              {!! Form::open(['route' => ['admin.stock.warehouse.trash', $warehouse->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.incharge') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td><img src="{{ get_logo_url($trash, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->email }}</td>
          <td>{{ $trash->manager ? $trash->manager->getName() : '' }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.stock.warehouse.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.stock.warehouse.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
