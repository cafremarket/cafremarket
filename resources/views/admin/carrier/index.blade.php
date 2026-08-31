@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.carriers') }}
@endsection

@section('content')
  @php
    $carrierModel = \App\Models\Carrier::class;
    $massActions = [
      ['url' => route('admin.shipping.carrier.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.shipping.carrier.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.carriers'),
    'icon' => 'fa-ship',
    'actions' => view('admin.carrier._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $carrierModel, 'massActions' => $massActions])
        @cannot('massDelete', $carrierModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.active') }}</th>
        <th>{{ trans('app.shipping_zones') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($carriers as $carrier)
        <tr>
          @can('massDelete', $carrierModel)
            <td><input id="{{ $carrier->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td><img src="{{ get_logo_url($carrier, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $carrier->name }} <span class="label label-outline">{{ $carrier->source }}</span></td>
          <td>
            <span class="label label-{{ $carrier->active ? 'success' : 'default' }}">
              {{ $carrier->active ? trans('app.active') : trans('app.inactive') }}
            </span>
          </td>
          <td>
            @foreach ($carrier->shippingZones->unique('name') as $zone)
              <span class="label label-outline">{{ $zone->name }}</span>
            @endforeach
          </td>
          <td class="row-options admin-row-actions">
            @can('view', $carrier)
              <a href="javascript:void(0)" data-link="{{ route('admin.shipping.carrier.show', $carrier->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $carrier)
              <a href="javascript:void(0)" data-link="{{ route('admin.shipping.carrier.edit', $carrier->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $carrier)
              {!! Form::open(['route' => ['admin.shipping.carrier.trash', $carrier->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td><img src="{{ get_storage_file_url(optional($trash->image)->path, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.shipping.carrier.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.shipping.carrier.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
