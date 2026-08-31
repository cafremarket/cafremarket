@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.staffs') }}
@endsection

@section('content')
  @include('admin.partials._shop_widget')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.staffs'),
    'icon' => 'fa-users',
  ])
      <table class="table table-hover admin-table table-2nd-no-sort">
        <thead>
          <tr>
            @can('massDelete', \App\Models\User::class)
              <th class="massActionWrapper">
                <!-- Check all button -->
                <div class="btn-group ">
                  <button type="button" class="btn btn-xs btn-default checkbox-toggle">
                    <i class="fa fa-square-o" data-toggle="tooltip" data-placement="top" title="{{ trans('app.select_all') }}"></i>
                  </button>
                  <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span class="caret"></span>
                    <span class="sr-only">{{ trans('app.toggle_dropdown') }}</span>
                  </button>
                  <ul class="dropdown-menu" role="menu">
                    <li><a href="javascript:void(0)" data-link="{{ route('admin.admin.user.massTrash') }}" class="massAction" data-doafter="reload"><i class="fa fa-trash"></i> {{ trans('app.trash') }}</a></li>
                    <li><a href="javascript:void(0)" data-link="{{ route('admin.admin.user.massDestroy') }}" class="massAction" data-doafter="reload"><i class="fa fa-times"></i> {{ trans('app.delete_permanently') }}</a></li>
                  </ul>
                </div>
              </th>
            @endcan
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
          @foreach ($staffs as $user)
            <tr>
              @can('massDelete', \App\Models\User::class)
                <td><input id="{{ $user->id }}" type="checkbox" class="massCheck"></td>
              @endcan
              <td>
                <img src="{{ get_avatar_src($user, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.avatar') }}">
              </td>
              <td>
                {{ $user->nice_name }}
                @if ($user->id == $shop->owner_id)
                  <span class="label label-outline">{{ trans('app.owner') }}</span>
                @endif
              </td>
              <td>{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>
                <span class="label label-outline">{{ optional($user->role)->name }}</span>
              </td>
              <td>{{ $user->active ? trans('app.active') : trans('app.inactive') }}</td>
              <td class="row-options admin-row-actions">
                @if (Auth::user()->isSuperAdmin() || $user->id != $shop->owner_id)
                  @can('view', $user)
                    <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.show', $user->id) }}" class="ajax-modal-btn"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.detail') }}" class="fa fa-user-circle-o"></i></a>&nbsp;
                  @endcan

                  @can('secretLogin', $user)
                    <a href="{{ route('admin.user.secretLogin', $user) }}"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.secret_login_user') }}" class="fa fa-user-secret"></i></a>&nbsp;
                  @endcan

                  @can('update', $user)
                    <a href="javascript:void(0)" data-link="{{ route('admin.admin.user.edit', $user->id) }}" class="ajax-modal-btn"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.edit') }}" class="fa fa-edit"></i></a>&nbsp;

                    @if ($user->primaryAddress)
                      <a href="javascript:void(0)" data-link="{{ route('address.edit', $user->primaryAddress->id) }}" class="ajax-modal-btn"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.update_address') }}" class="fa fa-map-marker"></i></a>&nbsp;
                    @else
                      <a href="javascript:void(0)" data-link="{{ route('address.create', ['user', $user->id]) }}" class="ajax-modal-btn"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.add_address') }}" class="fa fa-plus-square-o"></i></a>&nbsp;
                    @endif
                  @endcan

                  @can('delete', $user)
                    {!! Form::open(['route' => ['admin.admin.user.trash', $user->id], 'method' => 'delete', 'class' => 'data-form']) !!}
                    {!! Form::button('<i class="fa fa-trash-o"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.trash'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
                    {!! Form::close() !!}
                  @endcan
                @endif
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
              <td>
                <img src="{{ get_avatar_src($trash, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.avatar') }}">
              </td>
              <td>{{ $trash->nice_name }}</td>
              <td>{{ $trash->name }}</td>
              <td>{{ $trash->email }}</td>
              <td><span class="label label-outline">{{ optional($user->role)->name }}</span></td>
              <td>{{ $trash->deleted_at->diffForHumans() }}</td>
              <td class="row-options admin-row-actions">
                @can('delete', $trash)
                  <a href="{{ route('admin.admin.user.restore', $trash->id) }}"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.restore') }}" class="fa fa-database"></i></a>&nbsp;

                  {!! Form::open(['route' => ['admin.admin.user.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form']) !!}
                  {!! Form::button('<i class="glyphicon glyphicon-trash"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.delete_permanently'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
                  {!! Form::close() !!}
                @endcan
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    
  @include('admin.partials.ui.card_end')
@endsection
