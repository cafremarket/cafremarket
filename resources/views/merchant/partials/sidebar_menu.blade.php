{{-- Catalog --}}
@if (Gate::allows('index', \App\Models\Product::class) || Gate::allows('index', \App\Models\Attribute::class) || Gate::allows('index', \App\Models\Manufacturer::class))
  <div class="mp-nav-group {{ mp_is_any(['merchant/catalog*', 'merchant/stock/product*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/catalog*', 'merchant/stock/product*']) ? 'true' : 'false' }}">
      <i class="fa fa-tags"></i>
      <span>{{ trans('nav.catalog') ?? 'Products' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        @can('index', \App\Models\Product::class)
          <a href="{{ mp_url('merchant/stock/product/physical') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/product/physical*') || mp_is('merchant/stock/product/create*') || (mp_is('merchant/stock/product*') && ! mp_is('merchant/stock/product/digital*') && ! mp_is('merchant/stock/product/auction*')) ? 'is-active' : '' }}">
            <i class="fa fa-cube"></i>
            <span>{{ trans('nav.products') }}</span>
          </a>
          <a href="{{ mp_url('merchant/stock/product/digital') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/product/digital*') ? 'is-active' : '' }}">
            <i class="fa fa-cloud-download"></i>
            <span>{{ trans('nav.digital_products') }}</span>
          </a>
        @endcan
        @can('index', \App\Models\Attribute::class)
          <a href="{{ mp_url('merchant/catalog/attribute') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/attribute*') ? 'is-active' : '' }}">
            <i class="fa fa-sliders"></i>
            <span>{{ trans('nav.attributes') }}</span>
          </a>
        @endcan
        @can('index', \App\Models\Manufacturer::class)
          <a href="{{ mp_url('merchant/catalog/manufacturer') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/manufacturer*') ? 'is-active' : '' }}">
            <i class="fa fa-industry"></i>
            <span>{{ trans('nav.manufacturers') }}</span>
          </a>
        @endcan
      </div>
    </div>
  </div>
@endif

{{-- Inventory (warehouses / stock tools only — products live under Catalog) --}}
@if (Gate::allows('index', \App\Models\Warehouse::class) || Gate::allows('index', \App\Models\Inventory::class))
  <div class="mp-nav-group {{ mp_is_any(['merchant/stock/warehouse*', 'merchant/stock/inventory*', 'merchant/stock/overview*', 'merchant/stock/movements*', 'merchant/stock/transfer*', 'merchant/stock/low-stock*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/stock/warehouse*', 'merchant/stock/inventory*', 'merchant/stock/overview*', 'merchant/stock/movements*', 'merchant/stock/transfer*', 'merchant/stock/low-stock*']) ? 'true' : 'false' }}">
      <i class="fa fa-cubes"></i>
      <span>{{ trans('nav.stock') ?? 'Inventory' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        @can('index', \App\Models\Inventory::class)
          <a href="{{ route('admin.stock.overview') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/overview*') || (mp_is('merchant/stock/inventory*') && ! mp_is('merchant/stock/inventory/digital*')) ? 'is-active' : '' }}">
            <i class="fa fa-list"></i>
            <span>{{ trans('nav.stock_overview') ?? 'Stock overview' }}</span>
          </a>
        @endcan
        @can('index', \App\Models\Warehouse::class)
          <a href="{{ mp_url('merchant/stock/warehouse') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/warehouse*') ? 'is-active' : '' }}">
            <i class="fa fa-building"></i>
            <span>{{ trans('nav.warehouses') }}</span>
          </a>
        @endcan
      </div>
    </div>
  </div>
@endif

{{-- Orders --}}
@if (Auth::user()->isMerchant() || Gate::allows('index', \App\Models\Order::class))
  <div class="mp-nav-group {{ mp_is_any(['merchant/order*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/order*']) ? 'true' : 'false' }}">
      <i class="fa fa-shopping-cart"></i>
      <span>{{ trans('nav.orders') ?? 'Orders' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        <a href="{{ mp_url('merchant/order/order') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/order/order*') ? 'is-active' : '' }}">
          <i class="fa fa-list-alt"></i>
          <span>{{ trans('nav.orders') ?? 'Orders' }}</span>
        </a>
        {{-- Store carts hidden: customer cart data stays admin-only. --}}
        <a href="{{ mp_url('merchant/order/cancellation') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/order/cancellation*') ? 'is-active' : '' }}">
          <i class="fa fa-times-circle"></i>
          <span>{{ trans('nav.cancellations') ?? 'Cancellations' }}</span>
        </a>
        @can('index', \App\Models\Refund::class)
          @php
            $pendingRefunds = \App\Helpers\Statistics::open_refund_request_count();
          @endphp
          <a href="{{ route('admin.refunds.index') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ Request::is('admin/refunds*') || Request::is('merchant/refunds*') ? 'is-active' : '' }}">
            <i class="fa fa-undo"></i>
            <span>{{ trans('nav.refunds') ?? 'Refunds' }}</span>
            @if ($pendingRefunds > 0)
              <span class="mp-sidebar__badge">{{ $pendingRefunds }}</span>
            @endif
          </a>
        @endcan
      </div>
    </div>
  </div>
@endif

{{-- Delivery --}}
@if (Gate::allows('index', \App\Models\DeliveryBoy::class))
  <div class="mp-nav-group {{ mp_is_any(['merchant/admin/deliveryboy*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/admin/deliveryboy*']) ? 'true' : 'false' }}">
      <i class="fa fa-truck"></i>
      <span>{{ trans('nav.delivery') ?? 'Delivery' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        <a href="{{ mp_route('admin.admin.deliveryboy.index') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/admin/deliveryboy*') ? 'is-active' : '' }}">
          <i class="fa fa-motorcycle"></i>
          <span>{{ trans('nav.delivery_boys') }}</span>
        </a>
      </div>
    </div>
  </div>
@endif

{{-- Staff --}}
@if (Auth::user()->isMerchant() && (Gate::allows('index', \App\Models\User::class) || Gate::allows('index', \App\Models\Role::class)))
  <div class="mp-nav-group {{ mp_is_any(['merchant/admin/user*', 'merchant/setting/role*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/admin/user*', 'merchant/setting/role*']) ? 'true' : 'false' }}">
      <i class="fa fa-users"></i>
      <span>{{ trans('nav.staff') ?? 'Staff' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        @can('index', \App\Models\User::class)
          <a href="{{ mp_url('merchant/admin/user') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/admin/user*') ? 'is-active' : '' }}">
            <i class="fa fa-user-plus"></i>
            <span>{{ trans('nav.users') }}</span>
          </a>
        @endcan
        @can('index', \App\Models\Role::class)
          <a href="{{ mp_url('merchant/setting/role') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/role*') ? 'is-active' : '' }}">
            <i class="fa fa-key"></i>
            <span>{{ trans('nav.user_roles') }}</span>
          </a>
        @endcan
      </div>
    </div>
  </div>
@endif

{{-- Cafrepay / Wallet (shop owner wallet; payment methods need Config) --}}
@php
  $canSeeWallet = Auth::user()->isMerchant()
      && is_incevio_package_loaded('wallet')
      && Route::has('merchant.wallet');
  $canSeePaymentMethods = (vendor_get_paid_directly() || vendor_can_on_off_payment_method())
      && Gate::allows('view', \App\Models\Config::class);
@endphp
@if ($canSeeWallet || $canSeePaymentMethods)
  <div class="mp-nav-group {{ mp_is_any(['merchant/wallet*', 'merchant/setting/paymentMethod*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/wallet*', 'merchant/setting/paymentMethod*']) ? 'true' : 'false' }}">
      <i class="fa fa-money"></i>
      <span>{{ trans('packages.wallet.wallet') ?? 'Cafrepay' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        @if ($canSeeWallet)
          <a href="{{ route('merchant.wallet') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/wallet*') ? 'is-active' : '' }}">
            <i class="fa fa-money"></i>
            <span>{{ trans('packages.wallet.wallet') }}</span>
          </a>
        @endif
        @if ($canSeePaymentMethods)
          <a href="{{ mp_url('merchant/setting/paymentMethod') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/paymentMethod*') ? 'is-active' : '' }}">
            <i class="fa fa-credit-card"></i>
            <span>{{ trans('nav.payment_methods') }}</span>
          </a>
        @endif
      </div>
    </div>
  </div>
@endif

{{-- Live Chat (shop owners always; staff need view_chat_conversation) --}}
@if (Auth::user()->isMerchant() || Gate::allows('index', \Incevio\Package\LiveChat\Models\ChatConversation::class))
  <a href="{{ mp_url('merchant/support/chat') }}" class="mp-sidebar__link {{ mp_is('merchant/support/chat*') ? 'is-active' : '' }}">
    <i class="fa fa-comments"></i>
    <span>{{ trans('nav.chats') ?? 'Chat' }}</span>
  </a>
@endif

{{-- Dispute Tickets --}}
@can('index', \App\Models\Dispute::class)
  <a href="{{ route('merchant.support.dispute.index') }}" class="mp-sidebar__link {{ mp_is('merchant/support/dispute*') ? 'is-active' : '' }}">
    <i class="fa fa-ticket"></i>
    <span>{{ trans('nav.disputes') ?? 'Disputes' }}</span>
  </a>
@endcan

{{-- Reviews --}}
@can('index', \App\Models\Review::class)
  <a href="{{ route('merchant.review.index') }}" class="mp-sidebar__link {{ mp_is('merchant/review*') ? 'is-active' : '' }}">
    <i class="fa fa-star"></i>
    <span>{{ trans('nav.reviews') ?? 'Reviews' }}</span>
  </a>
@endcan

{{-- Reports --}}
@if (Auth::user()->isMerchant())
  <div class="mp-nav-group {{ mp_is_any(['merchant/shop/report*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/shop/report*']) ? 'true' : 'false' }}">
      <i class="fa fa-line-chart"></i>
      <span>{{ trans('nav.reports') ?? 'Reports' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        <a href="{{ mp_route('admin.shop-kpi') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/shop/report/kpi*') ? 'is-active' : '' }}">
          <i class="fa fa-bar-chart"></i>
          <span>{{ trans('nav.performance') }}</span>
        </a>
      </div>
    </div>
  </div>
@endif

{{-- Account --}}
<div class="mp-nav-group {{ mp_is_any(['merchant/account*']) ? 'is-open' : '' }}">
  <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ mp_is_any(['merchant/account*']) ? 'true' : 'false' }}">
    <i class="fa fa-user-circle"></i>
    <span>{{ trans('app.account') ?? 'Account' }}</span>
    <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
  </button>
  <div class="mp-nav-group__items">
    <div class="mp-nav-group__items-inner">
      <a href="{{ route('merchant.account.profile') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/account/profile*') ? 'is-active' : '' }}">
        <i class="fa fa-user"></i>
        <span>{{ trans('app.profile') ?? trans('app.account') }}</span>
      </a>
      @if (Auth::user()->isMerchant() && Route::has('merchant.account.billing'))
        <a href="{{ route('merchant.account.billing') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/account/billing*') ? 'is-active' : '' }}">
          <i class="fa fa-credit-card"></i>
          <span>{{ trans('app.billing') ?? 'Billing' }}</span>
        </a>
      @endif
      @if (Auth::user()->isMerchant() || Gate::allows('index', \App\Models\Ticket::class))
        <a href="{{ route('merchant.account.ticket') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/account/ticket*') ? 'is-active' : '' }}">
          <i class="fa fa-ticket"></i>
          <span>{{ trans('app.tickets') ?? 'Support tickets' }}</span>
        </a>
      @endif
    </div>
  </div>
</div>

{{-- Store management --}}
@can('view', \App\Models\Config::class)
  @php
    $storeManagementOpen = mp_is('merchant/setting/general*') || mp_is('merchant/setting/config*');
  @endphp
  <div class="mp-nav-group {{ $storeManagementOpen ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle" aria-expanded="{{ $storeManagementOpen ? 'true' : 'false' }}">
      <i class="fa fa-shopping-bag"></i>
      <span>{{ trans('nav.store_management') ?? 'Store' }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <div class="mp-nav-group__items-inner">
        <a href="{{ mp_url('merchant/setting/general') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/general*') ? 'is-active' : '' }}">
          <i class="fa fa-cog"></i>
          <span>{{ trans('nav.shop_settings') }}</span>
        </a>
        <a href="{{ mp_url('merchant/setting/config') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/config*') ? 'is-active' : '' }}">
          <i class="fa fa-sliders"></i>
          <span>{{ trans('nav.configurations') }}</span>
        </a>
      </div>
    </div>
  </div>
@endcan
