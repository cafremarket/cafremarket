@php
  $shop = $item->shop;
  $productCategories = optional($item->product)->categories ?? collect();
  $t_category = $productCategories->first(function ($category) use ($shop) {
      return $shop && (int) $category->shop_id === (int) $shop->id;
  }) ?: $productCategories->first();
@endphp
<nav class="sf-pdp-crumb" aria-label="breadcrumb">
  <div class="container">
    <ol class="sf-pdp-crumb__list">
      @include('theme::headers.lists.home')

      @if ($shop)
        <li>
          <a href="{{ route('show.store', $shop->slug) }}">{{ $shop->name }}</a>
        </li>
      @endif

      @if ($t_category)
        <li>
          <a href="{{ get_category_url($t_category, $shop) }}">{{ $t_category->name }}</a>
        </li>
      @endif

      <li class="active">{{ \Illuminate\Support\Str::limit($item->title, 48) }}</li>
    </ol>
  </div>
</nav>
