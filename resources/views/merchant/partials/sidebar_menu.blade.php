{{-- Catalog --}}
@if (Gate::allows('index', \App\Models\Category::class) || Gate::allows('index', \App\Models\Attribute::class) || Gate::allows('index', \App\Models\Product::class) || Gate::allows('index', \App\Models\Manufacturer::class) || Gate::allows('index', \App\Models\CategoryGroup::class) || Gate::allows('index', \App\Models\CategorySubGroup::class))
  <div class="mp-sidebar__section">{{ trans('nav.catalog') ?? 'Commerce' }}</div>

  <div class="mp-nav-group {{ mp_is_any(['merchant/catalog*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle">
      <i class="fa fa-tags"></i>
      <span>{{ trans('nav.catalog') }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      @if (Gate::allows('index', \App\Models\Category::class) || Gate::allows('index', \App\Models\CategoryGroup::class) || Gate::allows('index', \App\Models\CategorySubGroup::class))
        @if (Auth::user()->isFromPlatform())
          @can('index', \App\Models\CategoryGroup::class)
            <a href="{{ mp_url('merchant/catalog/categoryGroup') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/categoryGroup*') ? 'is-active' : '' }}">{{ trans('nav.groups') }}</a>
          @endcan
          @can('index', \App\Models\CategorySubGroup::class)
            <a href="{{ mp_url('merchant/catalog/categorySubGroup') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/categorySubGroup*') ? 'is-active' : '' }}">{{ trans('nav.sub-groups') }}</a>
          @endcan
        @endif
        @can('index', \App\Models\Category::class)
          <a href="{{ mp_url('merchant/catalog/category') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/category') ? 'is-active' : '' }}">{{ trans('nav.categories') }}</a>
        @endcan
      @endif
      @can('index', \App\Models\Attribute::class)
        <a href="{{ mp_url('merchant/catalog/attribute') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/attribute*') ? 'is-active' : '' }}">{{ trans('nav.attributes') }}</a>
      @endcan
      @if (is_catalog_enabled() || Auth::user()->isFromPlatform())
        @can('index', \App\Models\Product::class)
          <a href="{{ mp_url('merchant/catalog/product') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/product*') ? 'is-active' : '' }}">{{ trans('nav.products') }}</a>
        @endcan
      @endif
      @can('index', \App\Models\Manufacturer::class)
        <a href="{{ mp_url('merchant/catalog/manufacturer') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/catalog/manufacturer*') ? 'is-active' : '' }}">{{ trans('nav.manufacturers') }}</a>
      @endcan
    </div>
  </div>
@endif

{{-- Stock --}}
@if (Gate::allows('index', \App\Models\Inventory::class) || Gate::allows('index', \App\Models\Warehouse::class) || Gate::allows('index', \App\Models\Supplier::class))
  <div class="mp-nav-group {{ mp_is_any(['merchant/stock*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle">
      <i class="fa fa-cubes"></i>
      <span>{{ trans('nav.stock') }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      @if (is_catalog_enabled())
        @can('index', \App\Models\Inventory::class)
          <a href="{{ mp_route('admin.stock.inventory.index', ['type' => 'physical']) }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/inventory/physical*') ? 'is-active' : '' }}">{{ trans('nav.physical_products') }}</a>
          <a href="{{ mp_route('admin.stock.inventory.index', ['type' => 'digital']) }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/inventory/digital*') ? 'is-active' : '' }}">{{ trans('nav.digital_products') }}</a>
        @endcan
      @endif
      @if (! is_catalog_enabled() && Auth::user()->isFromMerchant())
        @can('index', \App\Models\Product::class)
          <a href="{{ mp_url('merchant/stock/product/physical') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/product/physical*') ? 'is-active' : '' }}">{{ trans('nav.physical_products') }}</a>
          <a href="{{ mp_url('merchant/stock/product/digital') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/product/digital*') ? 'is-active' : '' }}">{{ trans('nav.digital_products') }}</a>
        @endcan
      @endif
      @can('index', \App\Models\Warehouse::class)
        <a href="{{ mp_url('merchant/stock/warehouse') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/warehouse*') ? 'is-active' : '' }}">{{ trans('nav.warehouses') }}</a>
      @endcan
      @can('index', \App\Models\Supplier::class)
        <a href="{{ mp_url('merchant/stock/supplier') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/stock/supplier*') ? 'is-active' : '' }}">{{ trans('nav.suppliers') }}</a>
      @endcan
    </div>
  </div>
@endif

{{-- Orders --}}
@if (Gate::allows('index', \App\Models\Order::class) || Gate::allows('index', \App\Models\Cart::class))
  <div class="mp-nav-group {{ mp_is_any(['merchant/order*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle">
      <i class="fa fa-cart-plus"></i>
      <span>{{ trans('nav.orders') }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      @can('index', \App\Models\Order::class)
        <a href="{{ mp_url('merchant/order/order') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/order/order*') ? 'is-active' : '' }}">{{ trans('nav.orders') }}</a>
      @endcan
      @can('index', \App\Models\Cart::class)
        <a href="{{ mp_url('merchant/order/cart') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/order/cart*') ? 'is-active' : '' }}">{{ trans('nav.carts') }}</a>
      @endcan
      @can('cancelAny', \App\Models\Order::class)
        <a href="{{ mp_url('merchant/order/cancellation') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/order/cancellation*') ? 'is-active' : '' }}">{{ trans('nav.cancellations') }}</a>
      @endcan
    </div>
  </div>
@endif

{{-- People --}}
@if (Auth::user()->isMerchant())
  <div class="mp-sidebar__section">{{ trans('nav.vendors') ?? 'People' }}</div>

  @can('index', \App\Models\Customer::class)
    <a href="{{ mp_url('merchant/admin/customer') }}" class="mp-sidebar__link {{ mp_is('merchant/admin/customer*') ? 'is-active' : '' }}">
      <i class="fa fa-users"></i> {{ trans('nav.customers') }}
    </a>
  @endcan

  @can('index', \App\Models\User::class)
    <a href="{{ mp_url('merchant/admin/user') }}" class="mp-sidebar__link {{ mp_is('merchant/admin/user*') ? 'is-active' : '' }}">
      <i class="fa fa-user-plus"></i> {{ trans('nav.users') }}
    </a>
  @endcan

  <a href="{{ mp_route('admin.admin.deliveryboy.index') }}" class="mp-sidebar__link {{ mp_is('merchant/admin/deliveryboy*') ? 'is-active' : '' }}">
    <i class="fa fa-motorcycle"></i> {{ trans('nav.delivery_boys') }}
  </a>
@endif

{{-- Wallet --}}
@if (is_incevio_package_loaded('wallet') && Route::has('merchant.wallet'))
  <div class="mp-sidebar__section">{{ trans('packages.wallet.wallet') ?? 'Finance' }}</div>
  <a href="{{ route('merchant.wallet') }}" class="mp-sidebar__link {{ mp_is('merchant/wallet*') ? 'is-active' : '' }}">
    <i class="fa fa-money"></i> {{ trans('packages.wallet.wallet') }}
  </a>
@endif

{{-- Promotions --}}
@if (Auth::user()->isFromMerchant())
  <div class="mp-sidebar__section">{{ trans('nav.promotions') ?? 'Marketing' }}</div>
  <div class="mp-nav-group {{ mp_is_any(['merchant/promotion*', 'merchant/promotions*', 'merchant/flashdeal*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle">
      <i class="fa fa-paper-plane"></i>
      <span>{{ trans('nav.promotions') }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      @can('index', \App\Models\Coupon::class)
        <a href="{{ mp_url('merchant/promotion/coupon') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/promotion/coupon*') ? 'is-active' : '' }}">{{ trans('nav.coupons') }}</a>
      @endcan
      @if ((new \App\Helpers\Authorize(Auth::user(), 'manage_flash_deal'))->check())
        <a href="{{ mp_route('admin.flashdeal') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/flashdeal*') ? 'is-active' : '' }}">{{ trans('theme.flash_deal') }}</a>
      @endif
    </div>
  </div>
@endif

{{-- Support --}}
@if (Gate::allows('index', \App\Models\Message::class) || Gate::allows('index', \App\Models\Dispute::class) || Gate::allows('index', \App\Models\Refund::class) || (is_incevio_package_loaded('liveChat') && Gate::allows('index', \Incevio\Package\LiveChat\Models\ChatConversation::class)))
  <div class="mp-sidebar__section">{{ trans('nav.support') ?? 'Support' }}</div>
  <div class="mp-nav-group {{ mp_is_any(['merchant/support*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle">
      <i class="fa fa-support"></i>
      <span>{{ trans('nav.support') }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      @if (is_incevio_package_loaded('liveChat'))
        @can('index', \Incevio\Package\LiveChat\Models\ChatConversation::class)
          <a href="{{ mp_url('merchant/support/chat') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/support/chat*') ? 'is-active' : '' }}">{{ trans('nav.chats') }}</a>
        @endcan
      @endif
      @can('index', \App\Models\Message::class)
        <a href="{{ mp_url('merchant/support/message/labelOf/' . \App\Models\Message::LABEL_INBOX) }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/support/message*') ? 'is-active' : '' }}">{{ trans('nav.support_messages') }}</a>
      @endcan
      @can('index', \App\Models\Dispute::class)
        <a href="{{ mp_url('merchant/support/dispute') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/support/dispute*') ? 'is-active' : '' }}">{{ trans('nav.disputes') }}</a>
      @endcan
      @can('index', \App\Models\Refund::class)
        <a href="{{ mp_url('merchant/support/refund') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/support/refund*') ? 'is-active' : '' }}">{{ trans('nav.refunds') }}</a>
      @endcan
    </div>
  </div>
@endif

{{-- Appearance --}}
@if ((new \App\Helpers\Authorize(Auth::user(), 'customize_appearance'))->check())
  <div class="mp-sidebar__section">{{ trans('nav.appearance') ?? 'Design' }}</div>
  <div class="mp-nav-group {{ mp_is_any(['merchant/appearance*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle">
      <i class="fa fa-paint-brush"></i>
      <span>{{ trans('nav.appearance') }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      <a href="{{ mp_url('merchant/appearance/banner') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/appearance/banner*') ? 'is-active' : '' }}">{{ trans('nav.banners') }}</a>
      <a href="{{ mp_url('merchant/appearance/slider') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/appearance/slider*') ? 'is-active' : '' }}">{{ trans('nav.sliders') }}</a>
    </div>
  </div>
@endif

{{-- Settings --}}
<div class="mp-sidebar__section">{{ trans('nav.settings') ?? 'Settings' }}</div>
<div class="mp-nav-group {{ mp_is_any(['merchant/setting*', 'merchant/account*']) ? 'is-open' : '' }}">
  <button type="button" class="mp-nav-group__toggle">
    <i class="fa fa-gears"></i>
    <span>{{ trans('nav.settings') }}</span>
    <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
  </button>
  <div class="mp-nav-group__items">
    @can('index', \App\Models\Role::class)
      <a href="{{ mp_url('merchant/setting/role') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/role*') ? 'is-active' : '' }}">{{ trans('nav.user_roles') }}</a>
    @endcan
    @can('index', \App\Models\Tax::class)
      <a href="{{ mp_url('merchant/setting/tax') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/tax*') ? 'is-active' : '' }}">{{ trans('nav.taxes') }}</a>
    @endcan
    @can('view', \App\Models\Config::class)
      <a href="{{ mp_url('merchant/setting/general') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/general*') ? 'is-active' : '' }}">{{ trans('nav.shop_settings') }}</a>
      <a href="{{ mp_url('merchant/setting/config') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/config*') ? 'is-active' : '' }}">{{ trans('nav.configurations') }}</a>
      @if (vendor_get_paid_directly() || vendor_can_on_off_payment_method())
        <a href="{{ mp_url('merchant/setting/paymentMethod') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/setting/paymentMethod*') ? 'is-active' : '' }}">{{ trans('nav.payment_methods') }}</a>
      @endif
    @endcan
    <a href="{{ route('merchant.account.billing') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/account/billing*') ? 'is-active' : '' }}">{{ trans('app.billing') ?? 'Billing & plans' }}</a>
    <a href="{{ route('merchant.account.profile') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/account/profile*') ? 'is-active' : '' }}">{{ trans('app.account') }}</a>
  </div>
</div>

{{-- Utilities (platform admin only) --}}
@if (Auth::user()->isFromPlatform() && (Gate::allows('index', \App\Models\Page::class) || Gate::allows('index', \App\Models\EmailTemplate::class) || Gate::allows('index', \App\Models\Blog::class) || Gate::allows('index', \App\Models\Faq::class)))
  <div class="mp-nav-group {{ mp_is_any(['merchant/utility*']) ? 'is-open' : '' }}">
    <button type="button" class="mp-nav-group__toggle">
      <i class="fa fa-asterisk"></i>
      <span>{{ trans('nav.utilities') }}</span>
      <i class="fa fa-chevron-down mp-nav-group__chevron"></i>
    </button>
    <div class="mp-nav-group__items">
      @can('index', \App\Models\EmailTemplate::class)
        <a href="{{ mp_url('merchant/utility/emailTemplate') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/utility/emailTemplate*') ? 'is-active' : '' }}">{{ trans('nav.email_templates') }}</a>
      @endcan
      @can('index', \App\Models\Page::class)
        <a href="{{ mp_url('merchant/utility/page') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/utility/page*') ? 'is-active' : '' }}">{{ trans('nav.pages') }}</a>
      @endcan
      @can('index', \App\Models\Blog::class)
        <a href="{{ mp_url('merchant/utility/blog') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/utility/blog*') ? 'is-active' : '' }}">{{ trans('nav.blogs') }}</a>
      @endcan
      @can('index', \App\Models\Faq::class)
        <a href="{{ mp_url('merchant/utility/faq') }}" class="mp-sidebar__link mp-sidebar__link--sub {{ mp_is('merchant/utility/faq*') ? 'is-active' : '' }}">{{ trans('nav.faqs') }}</a>
      @endcan
    </div>
  </div>
@endif

{{-- Reports --}}
@if (Auth::user()->isMerchant())
  <div class="mp-sidebar__section">{{ trans('nav.reports') ?? 'Reports' }}</div>
  <a href="{{ mp_route('admin.shop-kpi') }}" class="mp-sidebar__link {{ mp_is('merchant/shop/report/kpi*') ? 'is-active' : '' }}">
    <i class="fa fa-bar-chart"></i> {{ trans('nav.performance') }}
  </a>
@endif
