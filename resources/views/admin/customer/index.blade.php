@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.customers') }}
@endsection

@section('content')
  @php
    $customerModel = \App\Models\Customer::class;
    $massActions = [
      ['url' => route('admin.admin.customer.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.admin.customer.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.customers'),
    'icon' => 'fa-users',
    'actions' => view('admin.customer._header_actions')->render(),
  ])

  <table class="table table-hover admin-table" id="all-customer-table">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $customerModel, 'massActions' => $massActions])
        @cannot('massDelete', $customerModel)
          <th></th>
        @endcannot
        <th>{{ trans('app.avatar') }}</th>
        <th>{{ trans('app.nice_name') }}</th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.orders') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea"></tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.avatar') }}</th>
        <th>{{ trans('app.nice_name') }}</th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td><img src="{{ get_avatar_src($trash, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $trash->nice_name }}</td>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->email }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.admin.customer.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.admin.customer.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip">
                <i class="fa fa-trash-o"></i>
              </button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
