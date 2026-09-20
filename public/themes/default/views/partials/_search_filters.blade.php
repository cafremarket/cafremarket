{{-- Search page filter sidebar. Reuses the same functional hooks as the legacy
     _product_list_sidebar_filters.blade.php (category-filters, filter_opt_checkbox,
     i-check, link-filter-opt, clear-filter, #price-slider) so no JS changes are needed
     — only the markup/styling is new, scoped to the search page via .sf-search-filters. --}}
<button type="button" id="filterBtn" class="sf-search-filter-toggle">
  <i class="fas fa-sliders-h"></i> {{ trans('theme.filters') }}
</button>

<aside class="category-filters sf-search-filters">
  <div class="sf-search-filters__head">
    <h3><i class="fas fa-sliders-h"></i> {{ trans('theme.filters') }}</h3>
    <a href="{{ route('inCategoriesSearch', array_filter(Request::only(['q', 'in', 'insubgrp']))) }}" class="sf-search-filters__clear">
      {{ trans('theme.clear_all_filters') }}
    </a>
  </div>

  @if (! empty($category))
    <div class="sf-filter-section">
      <h4>{{ trans('theme.category') }}</h4>
      <div class="sf-filter-crumbs">
        @if (Request::has('insubgrp') && Request::get('insubgrp') != 'all')
          <span class="sf-filter-crumb sf-filter-crumb--active">
            {{ $category->name }}
            <a href="javascript:void(0)" class="clear-filter" data-name="insubgrp" aria-label="{{ trans('theme.button.clear') }}">&times;</a>
          </span>
        @elseif(Request::has('in'))
          <a href="javascript:void(0)" class="sf-filter-crumb link-filter-opt" data-name="insubgrp" data-value="{{ $category->category->slug }}">
            {{ $category->category->name }}
          </a>
          <span class="sf-filter-crumb sf-filter-crumb--active">
            {{ $category->name }}
            <a href="javascript:void(0)" class="clear-filter" data-name="in" aria-label="{{ trans('theme.button.clear') }}">&times;</a>
          </span>
        @endif
      </div>
    </div>
  @endif

  {{-- Condition --}}
  @if (config('system_settings.show_item_conditions'))
    <div class="sf-filter-section">
      <h4>{{ trans('theme.condition') }}</h4>

      <label class="sf-filter-check">
        <input name="condition[New]" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('condition.New') ? 'checked' : '' }}>
        <span>{{ trans('theme.new') }}</span>
      </label>

      <label class="sf-filter-check">
        <input name="condition[Used]" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('condition.Used') ? 'checked' : '' }}>
        <span>{{ trans('theme.used') }}</span>
      </label>

      <label class="sf-filter-check">
        <input name="condition[Refurbished]" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('condition.Refurbished') ? 'checked' : '' }}>
        <span>{{ trans('theme.refurbished') }}</span>
      </label>
    </div>
  @endif

  {{-- Rating --}}
  <div class="sf-filter-section">
    <h4>
      {{ trans('theme.rating') }}
      @if (Request::has('rating'))
        <a href="javascript:void(0)" data-name="rating" class="clear-filter sf-filter-section__clear">{{ trans('theme.button.clear') }}</a>
      @endif
    </h4>

    <div class="sf-filter-rating-list">
      @for ($i = 4; $i > 0; $i--)
        <a href="javascript:void(0)" data-name="rating" data-value="{{ $i }}" class="sf-filter-rating link-filter-opt {{ (string) Request::get('rating') === (string) $i ? 'is-active' : '' }}">
          <span class="sf-filter-rating__stars">
            @for ($j = 0; $j < 5; $j++)
              <i class="{{ $j < $i ? 'fas' : 'far' }} fa-star"></i>
            @endfor
          </span>
          <span class="sf-filter-rating__label">&amp; {{ trans('theme.up') }}</span>
        </a>
      @endfor
    </div>
  </div>

  {{-- Price --}}
  @php
    $priceRange = $priceRange ?? get_price_ranges_from_listings($products);
  @endphp
  @if ($priceRange['max'] - $priceRange['min'] > 0)
    <div class="sf-filter-section">
      <h4>
        {{ trans('theme.price') }}
        @if (Request::has('price'))
          <a href="javascript:void(0)" data-name="price" class="clear-filter sf-filter-section__clear">{{ trans('theme.button.clear') }}</a>
        @endif
      </h4>

      <div class="sf-filter-price-chips">
        @foreach (generate_ranges($priceRange['min'], $priceRange['max'], 5) as $ranges)
          <a href="javascript:void(0)" data-name="price" data-value="{{ $ranges['lower'] . '-' . $ranges['upper'] }}" class="sf-filter-chip-btn link-filter-opt {{ Request::get('price') == $ranges['lower'] . '-' . $ranges['upper'] ? 'is-active' : '' }}">
            @if ($loop->first)
              {{ trans('theme.price_under', ['value' => get_formated_currency($ranges['upper'])]) }}
            @elseif($loop->last)
              {{ trans('theme.price_above', ['value' => get_formated_currency($ranges['lower'])]) }}
            @else
              {{ get_formated_currency($ranges['lower']) . ' ' . trans('theme.to') . ' ' . get_formated_currency($ranges['upper']) }}
            @endif
          </a>
        @endforeach
      </div>

      <input type="text" id="price-slider" class="sf-filter-price-slider" />
    </div>
  @endif

  {{-- Attributes (only when searching within a category) --}}
  @if (isset($category->attrsList))
    @foreach ($category->attrsList as $attribute)
      <div class="sf-filter-section">
        <h4>
          {{ $attribute->name }}
          @if ($attribute->attributeValues->first() && Request::has('attribute.' . $attribute->attributeValues->first()->id))
            <a href="javascript:void(0)" data-name="attribute[{{ $attribute->attributeValues->first()->id }}]" class="clear-filter sf-filter-section__clear">{{ trans('theme.button.clear') }}</a>
          @endif
        </h4>

        @foreach ($attribute->attributeValues as $attributeValue)
          <label class="sf-filter-check">
            <input type="checkbox" value="{{ $attribute->id }}" name="attribute[{{ $attributeValue->id }}]" class="i-check filter_opt_checkbox" {{ Request::has('attribute.' . $attributeValue->id) ? 'checked' : '' }}>
            <span>{{ \Str::title(\Str::limit($attributeValue->value, 30)) }}</span>
          </label>
        @endforeach
      </div>
    @endforeach
  @endif

  {{-- Brand --}}
  @php
    $brands = $brands ?? \App\Helpers\ListHelper::get_unique_brand_names_from_listings($products);
  @endphp
  @if (count($brands))
    <div class="sf-filter-section">
      <h4>{{ trans('theme.brand') }}</h4>

      @foreach ($brands as $brand)
        <label class="sf-filter-check">
          <input name="brand[{{ str_replace(' ', '%20', $brand) }}]" class="i-check filter_opt_checkbox" type="checkbox" {{ Request::has('brand.' . $brand) ? 'checked' : '' }}>
          <span>{{ \Str::title(\Str::limit($brand, 30)) }}</span>
        </label>
      @endforeach
    </div>
  @endif
</aside>
