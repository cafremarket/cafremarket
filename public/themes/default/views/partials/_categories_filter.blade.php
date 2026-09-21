@isset($categories)
  <div class="category-filters-section">
    <h3>
      <i class="fas fa-angle-left"></i>

      @if (Request::is('search'))
        <a class="link-filter-opt" data-name="insubgrp" data-value="all">
        @else
          <a href="{{ route('categories') }}">
      @endif

      @lang('theme.all_categories')</a>
    </h3>

    <ul class="cateogry-filters-list">
      @if (Request::is('search'))
        <li>
          @if (Request::has('insubgrp') && Request::get('insubgrp') != 'all')
            @php
              $t_categories = $products
                  ->pluck('product.subCategories')
                  ->flatten()
                  ->unique();
              $t_categories = $t_categories
                  ->pluck('slug')
                  ->flatten()
                  ->unique()
                  ->toArray();
            @endphp

            <h4>
              <i class="fas fa-angle-left"></i>
              <a class="link-filter-opt" data-name="insubgrp" data-value="{{ $category->slug }}">
                {{ $category->name }}
              </a>
            </h4>

            <ul>
              @foreach ($category->subCategories as $subCategory)
                @if (in_array($subCategory->slug, $t_categories))
                  <li>
                    <a class="link-filter-opt" data-name="in" data-value="{{ $subCategory->slug }}">
                      {{ $subCategory->name }}
                    </a>
                  </li>
                @endif
              @endforeach
            </ul>
          @elseif(Request::has('in'))
            <h4>
              <i class="fas fa-angle-left"></i>
              <a class="link-filter-opt" data-name="insubgrp" data-value="{{ $category->category->slug }}">
                {{ $category->category->name }}
              </a>
            </h4>

            <ul>
              <li>{{ $category->name }}</li>
            </ul>
          @else
            @php
              $t_categories = $products
                  ->pluck('product.subCategories')
                  ->flatten()
                  ->unique();
              $t_categories = $t_categories
                  ->pluck('category')
                  ->flatten()
                  ->unique();
            @endphp

            @foreach ($t_categories as $category)
        <li>
          <a class="link-filter-opt" data-name="insubgrp" data-value="{{ $category->slug }}">
            {{ $category->name }}
          </a>
        </li>
      @endforeach
      @endif
      </li>
    @elseif(Request::is('categories/*'))
      <li>
        <h4><i class="fas fa-angle-right"></i> {{ $category->name }}</h4>

        <ul>
          @foreach ($category->subCategories as $slug => $t_category)
            <li><a href="{{ get_category_url($t_category) }}">{{ $t_category->name }}</a></li>
          @endforeach
        </ul>
      </li>
    @elseif(Request::routeIs('category.browse') || Request::is('category/*'))
      <li>
        <h4>
          <i class="fas fa-angle-left"></i>
          <a href="{{ route('categories.browse', $category->category->slug) }}">
            {{ $category->category->name }}
          </a>
        </h4>

        <ul>
          @foreach ($category->category->subCategories as $t_category)
            <li>
              @if ($t_category->slug == $category->slug)
                <strong>{{ $t_category->name }}</strong>
              @else
                <a href="{{ get_category_url($t_category) }}">{{ $t_category->name }}</a>
              @endif
            </li>
          @endforeach
        </ul>
      </li>
    @else
      @foreach ($categories as $slug => $t_category)
        <li>
          <a href="{{ get_category_url($t_category) }}">{{ $t_category->name }}
            {{-- <span class="small">({{ $t_category->listings_count }})</span> --}}
          </a>
        </li>
      @endforeach
      @endif
    </ul>
  </div>
@endisset
