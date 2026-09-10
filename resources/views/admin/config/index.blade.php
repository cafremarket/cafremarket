@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.config') }}
@endsection

@section('page-style')
  <link href="{{ asset('css/admin-settings.css') }}?v={{ @filemtime(public_path('css/admin-settings.css')) ?: time() }}" rel="stylesheet">
@endsection

@section('content')
  @include('admin.partials.settings._shell_start', [
    'eyebrow' => trans('nav.configurations'),
    'title' => trans('app.config'),
    'subtitle' => 'Configure inventory, orders, views, support, and notifications.',
    'navItems' => $navItems,
    'active' => $active,
    'withoutPanel' => true,
  ])

  @include('admin.partials.settings._hub', ['cards' => $cards])

  @include('admin.partials.settings._shell_end', ['withoutPanel' => true])
@endsection
