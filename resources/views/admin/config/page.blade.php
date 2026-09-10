@extends('admin.layouts.master')

@php
  $can_update = $can_update ?? (Gate::allows('update', $config) ?? null);
@endphp

@section('page_title')
  {{ trans($pageMeta['label']) }} — {{ trans('app.config') }}
@endsection

@section('page-style')
  <link href="{{ asset('css/admin-settings.css') }}?v={{ @filemtime(public_path('css/admin-settings.css')) ?: time() }}" rel="stylesheet">
@endsection

@section('content')
  @include('admin.partials.settings._shell_start', [
    'eyebrow' => trans('app.config'),
    'title' => trans($pageMeta['label']),
    'navItems' => $navItems,
    'active' => $active,
    'actions' => '<a href="'.e(route('admin.setting.config.view')).'" class="as-btn as-btn--ghost"><i class="fa fa-th-large"></i> Overview</a>',
  ])

  @include('admin.config._sections.'.$page)

  @include('admin.partials.settings._shell_end')
@endsection
