@extends('admin.layouts.master')

@php
  $section = $section ?? 'profile';
  $sectionTitles = [
    'profile' => trans('app.profile'),
    'billing' => trans('app.billing'),
    'ticket' => trans('app.tickets'),
  ];
  $sectionIcons = [
    'profile' => 'fa-user',
    'billing' => 'fa-credit-card',
    'ticket' => 'fa-ticket',
  ];
  $pageTitle = $sectionTitles[$section] ?? trans('app.account');
  $pageIcon = $sectionIcons[$section] ?? 'fa-user';
@endphp

@section('page_title')
  {{ $pageTitle }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => $pageTitle,
    'icon' => $pageIcon,
    'bodyClass' => '',
  ])
    @if ($section === 'billing')
      @include('admin.account._billing')
    @elseif ($section === 'ticket')
      @include('admin.account._ticket')
    @else
      @include('admin.account._profile')
    @endif
  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  @includeWhen(
    $section === 'billing'
      && Auth::user()->isFromMerchant()
      && \App\Models\SystemConfig::isPaymentConfigured('stripe')
      && !\App\Models\SystemConfig::isBillingThroughWallet(),
    'plugins.stripe-scripts'
  )
@endsection
