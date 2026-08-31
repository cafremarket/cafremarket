@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.roles') }}
@endsection

@section('content')
  @php
    $roleModel = \App\Models\Role::class;
    $massActions = [
      ['url' => route('admin.setting.role.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.setting.role.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.roles'),
    'icon' => 'fa-shield',
    'actions' => view('admin.role._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $roleModel, 'massActions' => $massActions])
        @cannot('massDelete', $roleModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.users') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.role_level') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($roles as $role)
        <tr>
          @can('massDelete', $roleModel)
            <td><input id="{{ $role->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <h5>{{ $role->name }}</h5>
            @if ($role->description)
              <span class="excerpt-td small">{!! $role->description !!}</span>
            @endif
          </td>
          <td><span class="label label-primary">{{ $role->users_count }}</span></td>
          <td>{{ $role->public ? trans('app.merchant') : trans('app.platform') }}</td>
          <td><span class="label label-default">{{ $role->level }}</span></td>
          <td class="row-options admin-row-actions">
            @can('view', $role)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.role.show', $role->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $role)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.role.edit', $role) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $role)
              {!! Form::open(['route' => ['admin.setting.role.trash', $role->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>
            <h5>{{ $trash->name }}</h5>
            @if ($trash->description)
              <p class="excerpt-td small">{!! $trash->description !!}</p>
            @endif
          </td>
          <td>{{ $trash->public ? trans('app.merchant') : trans('app.platform') }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.setting.role.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.setting.role.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
