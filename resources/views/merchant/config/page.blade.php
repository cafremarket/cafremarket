@extends('admin.layouts.master')

@php
  $can_update = $can_update ?? (Gate::allows('update', $config) ?? null);
  $configUpdateRoute = panel_route_name('admin.setting.config.update');
  $toggleRoute = panel_route_name('admin.setting.config.notification.toggle');
@endphp

@section('page_title')
  {{ trans($pageMeta['label']) }} — {{ trans('nav.configurations') }}
@endsection

@section('page-style')
  <link href="{{ asset('css/admin-settings.css') }}?v={{ @filemtime(public_path('css/admin-settings.css')) ?: time() }}" rel="stylesheet">
@endsection

@section('content')
  @include('admin.partials.settings._shell_start', [
    'eyebrow' => trans('nav.configurations'),
    'title' => trans($pageMeta['label']),
    'navItems' => $navItems,
    'active' => $active,
    'actions' => '<a href="'.e(route('admin.setting.config.view')).'" class="as-btn as-btn--ghost"><i class="fa fa-th-large"></i> Overview</a>',
  ])

  @include('merchant.config._sections.'.$page)

  @include('admin.partials.settings._shell_end')
@endsection
