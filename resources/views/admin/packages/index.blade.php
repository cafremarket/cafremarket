@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.available_packages') }}
@endsection

@section('content')
  @if (config('app.demo') == true)
    <div class="admin-alert admin-alert--info">
      <strong><i class="fa fa-info"></i> {{ trans('app.info') }}</strong>
      {!! trans('messages.not_accessible_on_demo') !!}
    </div>
  @else
    <div class="admin-alert admin-alert--warning">
      <strong><i class="fa fa-exclamation-triangle"></i> {{ trans('app.alert') }}</strong>
      {!! trans('messages.be_careful_sensitive_area') !!}
    </div>
  @endif

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.available_packages'),
    'icon' => 'fa-puzzle-piece',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th width="30%">{{ trans('app.package') }}</th>
        <th width="120">{{ trans('app.status') }}</th>
        <th>{{ trans('app.description') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($installables as $package)
        @php
          $dependencies = $package['dependency'];
          $can_load = !(bool) $dependencies;
          $registered = $installedPackages->where('slug', $package['slug'])->first();

          if (!$can_load) {
            $arr = explode(',', $dependencies);
            $can_load = is_incevio_package_loaded($arr);
            $dependencies = count($arr) > 1 ? strrev(implode(strrev(', ' . trans('app.and') . ' '), explode(strrev(','), strrev($dependencies), 2))) : $dependencies;
          }

          if ($registered && $registered->active && !$can_load) {
            $registered->deactivate();
          }
        @endphp
        <tr>
          <td>
            <div class="admin-table__shop-cell">
              <i class="fa fa-fw fa-{{ $package['icon'] ?? 'puzzle-piece' }} text-muted"></i>
              <div>
                <strong class="text-{{ $registered ? 'primary' : 'muted' }}">{{ $package['name'] }}</strong>
                @unless ($can_load)
                  <br><small class="text-danger"><i class="fa fa-ban"></i> {{ trans('help.package_dependency_not_loaded', ['dependency' => $dependencies]) }}</small>
                @endunless
              </div>
            </div>
            @if ($registered)
              @unless ($registered->active && $package['active'] == false)
                @if (config('app.demo') == true)
                  <span class="text-muted small" title="{!! trans('messages.demo_restriction') !!}" data-toggle="tooltip"><i class="fa fa-trash-o"></i> {{ trans('app.uninstall') }}</span>
                @else
                  {!! Form::open(['route' => ['admin.package.uninstall', $package['slug']], 'class' => 'admin-inline-form']) !!}
                  <button type="submit" class="confirm btn btn-link btn-sm" data-confirm="{!! trans('help.confirm_uninstall_package', ['package' => $package['name']]) !!}">
                    <i class="fa fa-trash-o"></i> {{ trans('app.uninstall') }}
                  </button>
                  {!! Form::close() !!}
                @endif
              @endunless
            @elseif($can_load)
              @if (config('app.demo') == true)
                <span class="text-muted small" title="{!! trans('messages.demo_restriction') !!}" data-toggle="tooltip"><i class="fa fa-wrench"></i> {{ trans('app.install') }}</span>
              @else
                <a href="javascript:void(0)" data-link="{{ route('admin.package.initiate', $package['slug']) }}" class="ajax-modal-btn btn btn-default btn-sm"><i class="fa fa-wrench"></i> {{ trans('app.install') }}</a>
              @endif
            @endif
          </td>
          <td>
            @if ($registered)
              @if ($package['active'] == true)
                <span class="label label-primary">{{ trans('app.activated') }}</span>
              @else
                <div class="handle horizontal">
                  <a href="javascript:void(0)" data-link="{{ route('admin.package.switch', $package['slug']) }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $registered && $registered->active ? 'active' : '' }}" data-doafter="reload" data-toggle="button" aria-pressed="{{ $registered && $registered->active ? 'true' : 'false' }}" autocomplete="off" {{ $can_load ? '' : 'disabled' }}>
                    <div class="btn-handle"></div>
                  </a>
                </div>
              @endif
            @endif
          </td>
          <td>
            <p class="small">{{ $package['description'] }}</p>
            @unless (empty($package['warning']))
              <p class="text-danger small"><i class="fa fa-warning"></i> {!! $package['warning'] !!}</p>
            @endunless
            <span class="text-muted small">
              {{ trans('app.version') . ' ' . $package['version'] }} &bull;
              {{ trans('app.slug') . ': ' . $package['slug'] }} &bull;
              @if ($registered)
                {{ trans('app.installed_at') . ' ' . $registered->created_at }} &bull;
                {{ trans('app.updated_at') . ' ' . $registered->updated_at }} &bull;
              @endif
              {{ trans('app.zcart_compatibility') . ' ' . $package['compatible'] }}
            </span>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
