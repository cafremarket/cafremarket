@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.delivery_boys') }}
@endsection

@section('content')
  @php
    $deliveryBoyModel = \App\Models\User::class;
    $massActions = [
      ['url' => route('admin.admin.deliveryboy.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.delivery_boys'),
    'icon' => 'fa-motorcycle',
    'actions' => view('admin.deliveryboy._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $deliveryBoyModel, 'massActions' => $massActions])
        @cannot('massDelete', $deliveryBoyModel)
          <th></th>
        @endcannot
        <th>{{ trans('app.avatar') }}</th>
        <th>{{ trans('app.nice_name') }}</th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.phone_number') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($deliveryBoys as $deliveryboy)
        <tr>
          @can('massDelete', $deliveryBoyModel)
            <td><input id="{{ $deliveryboy->id }}" type="checkbox" class="massCheck"></td>
          @else
            <td></td>
          @endcan
          <td>
            <img src="{{ get_avatar_src($deliveryboy, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
          </td>
          <td>{{ $deliveryboy->nice_name }}</td>
          <td>{{ $deliveryboy->full_name }}</td>
          <td>{{ $deliveryboy->phone_number ?? '' }}</td>
          <td>{{ $deliveryboy->email }}</td>
          <td>{{ $deliveryboy->status == 1 ? trans('app.active') : trans('app.inactive') }}</td>
          <td class="row-options admin-row-actions">
            <a href="javascript:void(0)" data-link="{{ route('admin.admin.deliveryboy.show', $deliveryboy->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.profile') }}" data-toggle="tooltip"><i class="fa fa-user-circle-o"></i></a>
            <a href="javascript:void(0)" data-link="{{ route('admin.admin.deliveryboy.edit', $deliveryboy->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            <a href="javascript:void(0)" data-link="{{ route('admin.admin.deliveryboy.resetPasswordForm', $deliveryboy->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.reset_password') }}" data-toggle="tooltip"><i class="fa fa-key"></i></a>
            {!! Form::open(['route' => ['admin.admin.deliveryboy.destroy', $deliveryboy->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
            <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip" data-confirm="{{ trans('app.confirm_delete_delivery_boy_permanently') }}"><i class="fa fa-trash-o"></i></button>
            {!! Form::close() !!}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
