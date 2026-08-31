@can('view', $merchant)
  @include('admin.partials.ui.action_btn', ['href' => route('admin.vendor.merchant.show', $merchant->id), 'icon' => 'fa-user-circle-o', 'title' => trans('app.profile'), 'modal' => true])
@endcan

@can('secretLogin', $merchant)
  @include('admin.partials.ui.action_btn', ['href' => route('admin.user.secretLogin', $merchant), 'icon' => 'fa-user-secret', 'title' => trans('app.secret_login_user')])
@endcan

@can('update', $merchant)
  @include('admin.partials.ui.action_btn', ['href' => route('admin.vendor.merchant.edit', $merchant->id), 'icon' => 'fa-edit', 'title' => trans('app.edit'), 'modal' => true])
  @include('admin.partials.ui.action_btn', ['href' => route('admin.vendor.merchant.changePassword', $merchant->id), 'icon' => 'fa-lock', 'title' => trans('app.change_password'), 'modal' => true])

  @if ($merchant->primaryAddress)
    @include('admin.partials.ui.action_btn', ['href' => route('address.edit', $merchant->primaryAddress->id), 'icon' => 'fa-map-marker', 'title' => trans('app.update_address'), 'modal' => true])
  @else
    @include('admin.partials.ui.action_btn', ['href' => route('address.create', ['user', $merchant->id]), 'icon' => 'fa-plus-square-o', 'title' => trans('app.add_address'), 'modal' => true])
  @endif
@endcan

@can('delete', $merchant)
  {!! Form::open(['route' => ['admin.vendor.shop.trash', $merchant->owns->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
  <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip">
    <i class="fa fa-trash-o"></i>
  </button>
  {!! Form::close() !!}
@endcan
