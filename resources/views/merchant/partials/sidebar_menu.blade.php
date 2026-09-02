{{-- Products --}}
@if (Gate::allows('index', \App\Models\Product::class))
  <div class="mp-sidebar__section">{{ trans('nav.catalog') ?? 'Products' }}</div>
  @can('index', \App\Models\Product::class)
    <a href="{{ mp_url('merchant/catalog/product') }}" class="mp-sidebar__link {{ mp_is('merchant/catalog/product*') ? 'is-active' : '' }}">
      <i class="fa fa-tags"></i> {{ trans('nav.products') }}
    </a>
  @endcan
@endif

{{-- Inventory --}}
@if (Gate::allows('index', \App\Models\Inventory::class) || Gate::allows('index', \App\Models\Warehouse::class))
  <div class="mp-sidebar__section">{{ trans('nav.stock') ?? 'Inventory' }}</div>
  @if (is_catalog_enabled())
    @can('index', \App\Models\Inventory::class)
      <a href="{{ mp_route('admin.stock.inventory.index', ['type' => 'physical']) }}" class="mp-sidebar__link {{ mp_is('merchant/stock/inventory/physical*') ? 'is-active' : '' }}">
        <i class="fa fa-cubes"></i> {{ trans('nav.physical_products') }}
      </a>
      <a href="{{ mp_route('admin.stock.inventory.index', ['type' => 'digital']) }}" class="mp-sidebar__link {{ mp_is('merchant/stock/inventory/digital*') ? 'is-active' : '' }}">
        <i class="fa fa-cloud"></i> {{ trans('nav.digital_products') }}
      </a>
    @endcan
  @elseif (Auth::user()->isFromMerchant())
    @can('index', \App\Models\Product::class)
      <a href="{{ mp_url('merchant/stock/product/physical') }}" class="mp-sidebar__link {{ mp_is('merchant/stock/product/physical*') ? 'is-active' : '' }}">
        <i class="fa fa-cubes"></i> {{ trans('nav.physical_products') }}
      </a>
      <a href="{{ mp_url('merchant/stock/product/digital') }}" class="mp-sidebar__link {{ mp_is('merchant/stock/product/digital*') ? 'is-active' : '' }}">
        <i class="fa fa-cloud"></i> {{ trans('nav.digital_products') }}
      </a>
    @endcan
  @endif
  @can('index', \App\Models\Warehouse::class)
    <a href="{{ mp_url('merchant/stock/warehouse') }}" class="mp-sidebar__link {{ mp_is('merchant/stock/warehouse*') ? 'is-active' : '' }}">
      <i class="fa fa-building"></i> {{ trans('nav.warehouses') }}
    </a>
  @endcan
@endif

{{-- Delivery --}}
@if (Auth::user()->isMerchant())
  <div class="mp-sidebar__section">{{ trans('nav.delivery') ?? 'Delivery' }}</div>
  <a href="{{ mp_route('admin.admin.deliveryboy.index') }}" class="mp-sidebar__link {{ mp_is('merchant/admin/deliveryboy*') ? 'is-active' : '' }}">
    <i class="fa fa-motorcycle"></i> {{ trans('nav.delivery_boys') }}
  </a>
@endif

{{-- Store management --}}
@can('view', \App\Models\Config::class)
  <div class="mp-sidebar__section">{{ trans('nav.store_management') ?? 'Store' }}</div>
  <a href="{{ mp_url('merchant/setting/general') }}" class="mp-sidebar__link {{ mp_is('merchant/setting/general*') ? 'is-active' : '' }}">
    <i class="fa fa-store"></i> {{ trans('nav.shop_settings') }}
  </a>
  @if ((new \App\Helpers\Authorize(Auth::user(), 'customize_appearance'))->check())
    <a href="{{ mp_url('merchant/appearance/banner') }}" class="mp-sidebar__link {{ mp_is('merchant/appearance/banner*') ? 'is-active' : '' }}">
      <i class="fa fa-image"></i> {{ trans('nav.banners') }}
    </a>
  @endif
@endcan

{{-- Staff --}}
@if (Auth::user()->isMerchant())
  <div class="mp-sidebar__section">{{ trans('nav.staff') ?? 'Staff' }}</div>
  @can('index', \App\Models\User::class)
    <a href="{{ mp_url('merchant/admin/user') }}" class="mp-sidebar__link {{ mp_is('merchant/admin/user*') ? 'is-active' : '' }}">
      <i class="fa fa-user-plus"></i> {{ trans('nav.users') }}
    </a>
  @endcan
  @can('index', \App\Models\Role::class)
    <a href="{{ mp_url('merchant/setting/role') }}" class="mp-sidebar__link {{ mp_is('merchant/setting/role*') ? 'is-active' : '' }}">
      <i class="fa fa-users"></i> {{ trans('nav.user_roles') }}
    </a>
  @endcan
@endif

{{-- Cafrepay / Wallet --}}
@if (is_incevio_package_loaded('wallet'))
  <div class="mp-sidebar__section">{{ trans('packages.wallet.wallet') ?? 'Cafrepay' }}</div>
  @if (Route::has('merchant.wallet'))
    <a href="{{ route('merchant.wallet') }}" class="mp-sidebar__link {{ mp_is('merchant/wallet*') ? 'is-active' : '' }}">
      <i class="fa fa-money"></i> {{ trans('packages.wallet.wallet') }}
    </a>
  @endif
  @if (vendor_get_paid_directly() || vendor_can_on_off_payment_method())
    @can('view', \App\Models\Config::class)
      <a href="{{ mp_url('merchant/setting/paymentMethod') }}" class="mp-sidebar__link {{ mp_is('merchant/setting/paymentMethod*') ? 'is-active' : '' }}">
        <i class="fa fa-credit-card"></i> {{ trans('nav.payment_methods') }}
      </a>
    @endcan
  @endif
@endif

{{-- Customer chat --}}
@if (is_incevio_package_loaded('liveChat') && Gate::allows('index', \Incevio\Package\LiveChat\Models\ChatConversation::class))
  <div class="mp-sidebar__section">{{ trans('nav.support') ?? 'Support' }}</div>
  @can('index', \Incevio\Package\LiveChat\Models\ChatConversation::class)
    <a href="{{ mp_url('merchant/support/chat') }}" class="mp-sidebar__link {{ mp_is('merchant/support/chat*') ? 'is-active' : '' }}">
      <i class="fa fa-comments"></i> {{ trans('nav.chats') }}
    </a>
  @endcan
@endif

{{-- Reports --}}
@if (Auth::user()->isMerchant())
  <div class="mp-sidebar__section">{{ trans('nav.reports') ?? 'Reports' }}</div>
  <a href="{{ mp_route('admin.shop-kpi') }}" class="mp-sidebar__link {{ mp_is('merchant/shop/report/kpi*') ? 'is-active' : '' }}">
    <i class="fa fa-bar-chart"></i> {{ trans('nav.performance') }}
  </a>
@endif

{{-- Account --}}
<div class="mp-sidebar__section">{{ trans('app.account') ?? 'Account' }}</div>
<a href="{{ route('merchant.account.profile') }}" class="mp-sidebar__link {{ mp_is('merchant/account/profile*') ? 'is-active' : '' }}">
  <i class="fa fa-user"></i> {{ trans('app.account') }}
</a>
