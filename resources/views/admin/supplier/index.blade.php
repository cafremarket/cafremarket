@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.suppliers') }}
@endsection

@section('content')
  @php
    $supplierModel = \App\Models\Supplier::class;
    $massActions = [
      ['url' => route('admin.stock.supplier.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.stock.supplier.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.suppliers'),
    'icon' => 'fa-truck',
    'actions' => view('admin.supplier._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $supplierModel, 'massActions' => $massActions])
        @cannot('massDelete', $supplierModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.contact_person') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($suppliers as $supplier)
        <tr>
          @can('massDelete', $supplierModel)
            <td><input id="{{ $supplier->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td><img src="{{ get_logo_url($supplier, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $supplier->name }}</td>
          <td>{{ $supplier->contact_person }}</td>
          <td>{{ $supplier->email }}</td>
          <td>{{ $supplier->active ? trans('app.active') : trans('app.inactive') }}</td>
          <td class="row-options admin-row-actions">
            @can('view', $supplier)
              <a href="javascript:void(0)" data-link="{{ route('admin.stock.supplier.show', $supplier->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $supplier)
              <a href="javascript:void(0)" data-link="{{ route('admin.stock.supplier.edit', $supplier->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              @if ($supplier->primaryAddress)
                <a href="javascript:void(0)" data-link="{{ route('address.edit', $supplier->primaryAddress->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.update_address') }}" data-toggle="tooltip"><i class="fa fa-map-marker"></i></a>
              @else
                <a href="javascript:void(0)" data-link="{{ route('address.create', ['supplier', $supplier->id]) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.add_address') }}" data-toggle="tooltip"><i class="fa fa-plus-square-o"></i></a>
              @endif
            @endcan
            @can('delete', $supplier)
              {!! Form::open(['route' => ['admin.stock.supplier.trash', $supplier->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.contact_person') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td><img src="{{ get_storage_file_url(optional($trash->image)->path, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->contact_person }}</td>
          <td>{{ $trash->email }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.stock.supplier.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.stock.supplier.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
