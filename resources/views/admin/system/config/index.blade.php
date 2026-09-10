@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.system_config') }}
@endsection

@section('page-style')
  <link href="{{ asset('css/admin-settings.css') }}?v={{ @filemtime(public_path('css/admin-settings.css')) ?: time() }}" rel="stylesheet">
@endsection

@section('content')
  @include('admin.partials.settings._shell_start', [
    'eyebrow' => trans('nav.configurations'),
    'title' => trans('app.system_config'),
    'subtitle' => 'Choose a settings area to manage platform behaviour.',
    'navItems' => $navItems,
    'active' => $active,
    'withoutPanel' => true,
  ])

  @include('admin.partials.settings._hub', ['cards' => $cards])

  @include('admin.partials.settings._shell_end', ['withoutPanel' => true])
@endsection
