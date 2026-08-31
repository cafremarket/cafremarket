@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.users') }}
@endsection

@section('content')
  @php
    $userModel = \App\Models\User::class;
    $massActions = [
      ['url' => route('admin.admin.user.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.admin.user.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.users'),
    'icon' => 'fa-user',
    'actions' => view('admin.user._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $userModel, 'massActions' => $massActions])
        @cannot('massDelete', $userModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.avatar') }}</th>
        <th>{{ trans('app.nice_name') }}</th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.role') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($users as $user)
        <tr>
          @can('massDelete', $userModel)
            <td><input id="{{ $user->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_avatar_src($user, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
          </td>
          <td>{{ $user->nice_name }}</td>
          <td>{{ $user->name }}</td>
          <td>{{ $user->email }}</td>
          <td><span class="label label-outline">{{ optional($user->role)->name }}</span></td>
          <td>{{ $user->active ? trans('app.active') : trans('app.inactive') }}</td>
          <td class="row-options admin-row-actions">
            @can('view', $user)
              <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.show', $user->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.profile') }}" data-toggle="tooltip"><i class="fa fa-user-circle-o"></i></a>
            @endcan
            @can('secretLogin', $user)
              <a href="{{ route('admin.user.secretLogin', $user) }}" class="admin-action-btn" title="{{ trans('app.secret_login_user') }}" data-toggle="tooltip"><i class="fa fa-user-secret"></i></a>
            @endcan
            @can('update', $user)
              <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.edit', $user->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.changePassword', $user->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.change_password') }}" data-toggle="tooltip"><i class="fa fa-lock"></i></a>
              @if ($user->primaryAddress)
                <a href="javascript:void(0)" data-link="{{ route('address.edit', $user->primaryAddress->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.update_address') }}" data-toggle="tooltip"><i class="fa fa-map-marker"></i></a>
              @else
                <a href="javascript:void(0)" data-link="{{ route('address.create', ['user', $user->id]) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.add_address') }}" data-toggle="tooltip"><i class="fa fa-plus-square-o"></i></a>
              @endif
            @endcan
            @can('delete', $user)
              {!! Form::open(['route' => ['admin.admin.user.trash', $user->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
        <th>{{ trans('app.avatar') }}</th>
        <th>{{ trans('app.nice_name') }}</th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.role') }}</th>
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
          <td><span class="label label-outline">{{ optional($trash->role)->name }}</span></td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.admin.user.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.admin.user.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
