@php
  $access_level = Str::snake($module->access);
  $module_name = Str::snake($module->name);
  $module_enabled = find_string_in_array($role_permissions, $module_name);
  $inStorePanel = in_array($module->name, $storePanelNames, true);
  $showRow = 'common' == $access_level
    || ('merchant' == $access_level && $merchant_user)
    || (isset($role) && (
      ($role->public == 1 && 'merchant' == $access_level)
      || ($role->id == \App\Models\Role::MERCHANT && 'merchant' == $access_level)
      || ($role->shop_id == null && $role->public != 1 && 'platform' == $access_level && $role->id != \App\Models\Role::MERCHANT)
    ));
@endphp

@if (!$merchant_user || ('common' == $access_level || 'merchant' == $access_level))
  <div class="role-perm__row {{ $access_level }}-module {{ $inStorePanel ? 'store-panel-module' : 'not-store-panel-module' }}" {{ $showRow ? '' : 'hidden' }}>
    <div class="role-perm__module">
      {{ Form::hidden($module_name, 0) }}
      <span class="role-perm__help" title="{{ trans('help.module.name', ['module' => Str::plural($module->name)]) . ' ' . trans('help.module.access.' . $access_level, ['access' => $access_level]) }}" data-toggle="tooltip" data-placement="top">
        <i class="fa fa-question-circle"></i>
      </span>
      {!! Form::checkbox($module_name, null, $module_enabled ? 1 : null, ['id' => $module_name, 'class' => 'icheckbox_line role-module']) !!}
      {!! Form::label($module_name, $module->name) !!}
    </div>

    <div class="role-perm__actions">
      @foreach ($module->permissions as $permission)
        <label class="role-perm__action">
          {!! Form::checkbox('permissions[]', $permission->id, null, ['class' => $module_name . '-permission icheck', $module_enabled ? '' : 'disabled']) !!}
          {{ $permission->name }}
        </label>
      @endforeach
    </div>
  </div>
@endif
