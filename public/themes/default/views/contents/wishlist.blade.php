@if ($wishlist->count() > 0)
  <div class="sf-wishlist-list">
    @foreach ($wishlist as $wish)
      <article class="sf-wishlist-item">
        <div class="sf-wishlist-item__media">
          <img class="lazy product-img-primary" src="{{ get_product_img_src($wish->inventory, 'tiny_thumb') }}" data-src="{{ get_product_img_src($wish->inventory, 'full') }}" alt="{{ $wish->inventory->title }}" title="{{ $wish->inventory->title }}" />
          <a class="product-link" href="{{ storefront_product_url($wish->inventory) }}"></a>
        </div>

        <div class="sf-wishlist-item__body">
          <ul class="sf-wishlist-item__labels">
            @if ($wish->inventory->free_shipping == 1)
              <li>@lang('theme.free_shipping')</li>
            @endif

            @if ($wish->inventory->stuff_pick == 1)
              <li>@lang('theme.stuff_pick')</li>
            @endif

            @if ($wish->inventory->hasOffer())
              <li>@lang('theme.percent_off', ['value' => get_percentage_of($wish->inventory->sale_price, $wish->inventory->offer_price)])</li>
            @endif
          </ul>

          @include('theme::layouts.ratings', ['ratings' => $wish->inventory->ratings])

          <a href="{{ storefront_product_url($wish->inventory) }}" class="sf-wishlist-item__title">
            {{ $wish->inventory->title }}
          </a>

          <div class="sf-wishlist-item__meta">
            @lang('theme.availability'):
            <span>{{ $wish->inventory->stock_quantity > 0 ? trans('theme.in_stock') : trans('theme.out_of_stock') }}</span>
          </div>

          @include('theme::layouts.pricing', ['item' => $wish->inventory])

          @if ($wish->inventory->condition)
            <div class="sf-wishlist-item__meta">{{ $wish->inventory->condition }}</div>
          @endif
        </div>

        <div class="sf-wishlist-item__actions">
          <a class="btn btn-default btn-sm itemQuickView" href="javascript:void(0);" data-link="{{ storefront_product_quickview_url($wish->inventory) }}" rel="nofollow noindex">
            <i class="far fa-eye"></i> @lang('theme.button.quick_view')
          </a>

          <a class="btn sf-btn-primary btn-sm sc-add-to-cart add-to-card-mod" data-link="{{ route('cart.addItem', $wish->inventory->slug) }}">
            <i class="far fa-shopping-cart"></i> @lang('theme.button.add_to_cart')
          </a>

          <a class="btn btn-default btn-sm" href="{{ route('direct.checkout', $wish->inventory->slug) }}">
            <i class="fas fa-rocket"></i> @lang('theme.button.buy_now')
          </a>

          {!! Form::open(['route' => ['wishlist.remove', $wish], 'method' => 'delete', 'class' => 'data-form']) !!}
          <button class="btn btn-link btn-sm confirm text-danger" type="submit">
            <i class="fas fa-trash-alt"></i> @lang('theme.button.remove')
          </button>
          {!! Form::close() !!}
        </div>
      </article>
    @endforeach
  </div>
@else
  <div class="sf-empty-state">
    <i class="fas fa-heart" aria-hidden="true"></i>
    <p>@lang('theme.empty_wishlist')</p>
    <a href="{{ url('/') }}" class="btn sf-btn-primary btn-sm">@lang('theme.button.shop_now')</a>
  </div>
@endif

<div class="row pagenav-wrapper mb-3">
  {{ $wishlist->links('theme::layouts.pagination') }}
</div>

<script>
  $(".add-to-wishlist").off().on("click", function(e) {
    e.preventDefault();

    $.ajax({
      url: $(this).data('link'),
      type: 'get',
      complete: function(xhr, textStatus) {
        if (200 == xhr.status) {
          @include('theme::layouts.notification', ['message' => trans('theme.item_added_to_wishlist'), 'type' => 'success', 'icon' => 'check-circle'])
        } else if (401 == xhr.status) {
          if (typeof window.openCustomerLoginModal === 'function') {
            window.openCustomerLoginModal();
          } else {
            location.href = @json(route('homepage', ['login' => 1]));
          }
        } else if (404 == xhr.status) {
          @include('theme::layouts.notification', ['message' => trans('theme.item_not_available'), 'type' => 'warning', 'icon' => 'info-circle'])
        } else {
          @include('theme::layouts.notification', ['message' => trans('theme.notify.failed'), 'type' => 'warning', 'icon' => 'times-circle'])
        }
      },
    });
  });
</script>
