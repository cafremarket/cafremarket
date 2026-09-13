@php
  $user = auth()->user();
  $merchant_user = $user->merchantId();
  $special_role = isset($role) && $role->isSpecial() ? true : false;
  $role_permissions = isset($role) ? $role->permissions()->pluck('slug')->toArray() : [];
  $storePanelNames = \App\Helpers\ListHelper::storePanelModuleNames();
  $permissionGroups = \App\Helpers\ListHelper::rolePermissionGroups();
  $modulesByName = collect($modules)->keyBy('name');
  $usedNames = [];
@endphp

<div class="row">
  <div class="col-md-{{ $merchant_user ? '8' : '5' }} nopadding-right">
    <div class="form-group">
      {!! Form::label('name', trans('app.form.name') . '*', ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="bottom" title="{{ trans('help.role_name') }}"></i>
      {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.role_name'), 'required']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>

  @unless ($merchant_user)
    <div class="col-md-3 nopadding">
      <div class="form-group">
        {!! Form::label('public', trans('app.form.role_type') . '*', ['class' => 'with-help']) !!}
        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="bottom" title="{{ $special_role ? trans('help.cant_edit_special_role') : trans('help.role_type') }}">
        </i>
        {{ Form::hidden('public', null) }}
        {!! Form::select('public', ['0' => trans('app.platform'), '1' => trans('app.merchant')], null, ['id' => $special_role ? '' : 'user-role-status', 'class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.status'), $special_role ? 'disabled' : 'required']) !!}
        <div class="help-block with-errors"></div>
      </div>
    </div>
  @endunless

  <div class="col-md-4 nopadding-left">
    <div class="form-group">
      {!! Form::label('level', trans('app.form.role_level'), ['class' => 'with-help']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="bottom" title="{{ $user->accessLevel() ? trans('help.role_level') : trans('help.you_cant_set_role_level') }}"></i>

      @if ($user->accessLevel())
        <div class="pull-right"> <i class="fa fa-info"></i> {{ trans('help.number_between', ['min' => $user->accessLevel(), 'max' => config('system_settings.max_role_level')]) }}</div>
      @endif

      {!! Form::number('level', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.role_level'), 'min' => $user->accessLevel(), 'max' => config('system_settings.max_role_level'), $user->accessLevel() ? '' : 'disabled']) !!}
      <div class="help-block with-errors"></div>
    </div>
  </div>
</div>

<div class="form-group">
  {!! Form::label('description', trans('app.form.description')) !!}
  {!! Form::textarea('description', null, ['class' => 'form-control summernote-without-toolbar', 'placeholder' => trans('app.placeholder.description')]) !!}
</div>

<div class="form-group role-perm" id="tbl-permissions">
  <div class="role-perm__intro">
    <strong>{{ trans('app.modules') }}</strong>
    <span>{{ trans('help.set_role_permissions') }}</span>
  </div>

  @foreach ($permissionGroups as $groupKey => $group)
    @php
      $groupModules = [];
      foreach ($group['modules'] as $moduleName) {
        $module = $modulesByName->get($moduleName);
        if (! $module) {
          continue;
        }

        $access_level = Str::snake($module->access);
        $inStorePanel = in_array($module->name, $storePanelNames, true);

        if ($merchant_user && ! $inStorePanel) {
          continue;
        }

        if ($merchant_user && ! in_array($access_level, ['common', 'merchant'], true)) {
          continue;
        }

        $groupModules[] = $module;
        $usedNames[] = $module->name;
      }
    @endphp

    @if (count($groupModules))
      <section class="role-perm__group" data-group="{{ $groupKey }}">
        <header class="role-perm__group-head">
          <i class="fa {{ $group['icon'] }}"></i>
          <span>{{ $group['label'] }}</span>
        </header>

        @foreach ($groupModules as $module)
          @include('admin.role._module_row', ['module' => $module, 'role_permissions' => $role_permissions, 'merchant_user' => $merchant_user, 'storePanelNames' => $storePanelNames])
        @endforeach
      </section>
    @endif
  @endforeach

  @unless ($merchant_user)
    @php
      $leftovers = collect($modules)->filter(function ($module) use ($usedNames, $merchant_user) {
        if (in_array($module->name, $usedNames, true)) {
          return false;
        }
        $access_level = Str::snake($module->access);
        if ($merchant_user && ! in_array($access_level, ['common', 'merchant'], true)) {
          return false;
        }
        return $access_level !== 'super_admin';
      });
    @endphp

    @if ($leftovers->count())
      <section class="role-perm__group" data-group="other">
        <header class="role-perm__group-head">
          <i class="fa fa-th"></i>
          <span>{{ trans('app.others') ?? 'Other' }}</span>
        </header>
        @foreach ($leftovers as $module)
          @include('admin.role._module_row', ['module' => $module, 'role_permissions' => $role_permissions, 'merchant_user' => $merchant_user, 'storePanelNames' => $storePanelNames])
        @endforeach
      </section>
    @endif
  @endunless
</div>

<p class="help-block">* {{ trans('app.form.required_fields') }}</p>
