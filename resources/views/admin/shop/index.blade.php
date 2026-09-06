@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.shops') }}
@endsection

@php
  $translation_language = app()->getLocale();
  $shopModel = \App\Models\Shop::class;
  $massActions = [
    ['url' => route('admin.vendor.shop.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
    ['url' => route('admin.vendor.shop.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
  ];
@endphp

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.shops'),
    'icon' => 'fa-store',
    'actions' => view('admin.shop._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $shopModel, 'massActions' => $massActions])
        @cannot('massDelete', $shopModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.shop_name') }}</th>
        @if (is_subscription_enabled())
          <th>{{ trans('app.current_billing_plan') }}</th>
        @endif
        @if (is_incevio_package_loaded('dynamicCommission'))
          <th>{{ trans('packages.dynamicCommission.periodic_sold_amount') }}</th>
        @endif
        @if (Auth::user()->isFromPlatform())
          <th>{{ trans('app.verification') }}</th>
        @endif
        <th>{{ trans('app.owner') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($shops as $shop)
        <tr class="{{ !$shop->active ? 'inactive' : '' }}">
          @can('massDelete', $shopModel)
            <td><input id="{{ $shop->id }}" type="checkbox" class="massCheck"></td>
          @endcan

          <td>
            <img src="{{ get_logo_url($shop, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="{{ trans('app.logo') }}">
          </td>

          <td>
            <div class="admin-table__shop-cell">
              <div>
                {{ $shop->name }}
                @if ($shop->isVerified())
                  <img src="{{ get_verified_badge() }}" class="verified-badge img-xs" data-toggle="tooltip" title="{{ trans('help.verified_seller') }}" alt="">
                @endif
                @if ($shop->isDown())
                  <span class="label label-default">{{ trans('app.maintenance_mode') }}</span>
                @endif
                {!! $shop->reward_badge !!}
              </div>
              @can('update', $shop)
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.toggle', $shop) }}" data-doafter="reload" class="toggle-widget toggle-confirm admin-table__toggle">
                  <i class="fa fa-{{ $shop->active ? 'heart-o' : 'heart' }}" data-toggle="tooltip" title="{{ $shop->active ? trans('app.deactivate') : trans('app.activate') }}"></i>
                </a>
              @endcan
            </div>
          </td>

          @if (is_subscription_enabled())
            <td>
              {{ $shop->plan->name }}
              @if ($shop->onTrial())
                <span class="label label-info">{{ trans('app.trialing') }}</span>
              @elseif($shop->hasExpiredPlan())
                <span class="label label-default">{{ trans('app.expired') }}</span>
              @endif
              @if ($shop->onTrial() || $shop->hasExpiredPlan())
                @if (Auth::user()->isAdmin())
                  <a href="javascript:void(0)" data-link="{{ route('admin.vendor.subscription.editTrial', $shop) }}" class="ajax-modal-btn">
                    <i data-toggle="tooltip" title="{{ trans('help.update_trial_period') }}" class="fa fa-calendar"></i>
                  </a>
                @endif
              @endif
            </td>
          @endif

          @if (is_incevio_package_loaded('dynamicCommission'))
            <td>{{ get_formated_currency($shop->periodic_sold_amount, 2, config('system_settings.currency.id')) }}</td>
          @endif

          @if (Auth::user()->isFromPlatform())
            <td>
              <span class="label label-{{ $shop->isVerified() ? 'success' : 'default' }}">{{ $shop->getVerificationStatus() }}</span>
            </td>
          @endif

          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_avatar_src($shop->owner, 'tiny') }}" class="img-circle img-sm" alt="">
              <div>
                @can('view', $shop->owner)
                  <a href="javascript:void(0)" data-link="{{ route('admin.vendor.merchant.show', $shop->owner_id) }}" class="ajax-modal-btn">{{ $shop->owner->getName() }}</a>
                @else
                  {{ $shop->owner->getName() }}
                @endcan
                @unless ($shop->owner->active)
                  <span class="label label-default">{{ trans('app.inactive') }}</span>
                @endunless
              </div>
            </div>
          </td>

          <td class="row-options admin-row-actions">
            @can('view', $shop)
              <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $shop->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
              <a href="{{ route('admin.vendor.shop.staffs', $shop->id) }}" class="admin-action-btn" title="{{ trans('app.staffs') }}" data-toggle="tooltip"><i class="fa fa-users"></i></a>
            @endcan
            @can('secretLogin', $shop->owner)
              <a href="{{ route('admin.user.secretLogin', $shop->owner->id) }}" class="admin-action-btn" title="{{ trans('app.secret_login_merchant') }}" data-toggle="tooltip"><i class="fa fa-user-secret"></i></a>
            @endcan
            @can('update', $shop)
              <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.edit', $shop->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              @if (Auth::user()->isFromPlatform())
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify', $shop) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.verify_store') }}" data-toggle="tooltip"><i class="fa fa-check-circle text-green"></i></a>
              @endif
              <a href="{{ route('admin.vendor.shop.translate.form', ['shop' => $shop, 'language' => $translation_language]) }}" class="admin-action-btn" title="{{ trans('app.manage_translations') }}" data-toggle="tooltip"><em class="fa fa-language"></em></a>
            @endcan
            @can('update', $shop->owner)
              <a href="javascript:void(0)" data-link="{{ route('admin.vendor.merchant.edit', $shop->owner_id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.merchant') }}" data-toggle="tooltip"><i class="fa fa-user"></i></a>
              <a href="javascript:void(0)" data-link="{{ route('admin.vendor.merchant.changePassword', $shop->owner_id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.change_password') }}" data-toggle="tooltip"><i class="fa fa-lock"></i></a>
            @endcan
            @can('delete', $shop)
              {!! Form::open(['route' => ['admin.vendor.shop.trash', $shop->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
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
        <th>{{ trans('app.image') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.owner') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td><img src="{{ get_logo_url($trash, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->email }}</td>
          <td>
            <div class="admin-table__shop-cell">
              <img src="{{ get_avatar_src($trash->owner, 'tiny') }}" class="img-circle img-sm" alt="">
              <span>{{ $trash->owner->getName() }}</span>
            </div>
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.vendor.shop.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.vendor.shop.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
