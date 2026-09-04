<section class="sf-order-confirm__header">
  <div class="container">
    <ol class="breadcrumb nav-breadcrumb mb-0">
      <li><a href="{{ url('/') }}">@lang('theme.home')</a></li>
      @if (\Auth::guard('customer')->check())
        <li><a href="{{ route('account', 'orders') }}">@lang('theme.orders')</a></li>
      @endif
      <li class="active">@lang('theme.order_confirmation')</li>
    </ol>
  </div>
</section>
