<section class="sf-shop-by-category">
  <div class="container">
    <header class="sf-shop-by-category__header">
      <h1 class="sf-shop-by-category__title">
        {{ trans('theme.shop_by_category') }}
      </h1>
    </header>

    @php
      $shopCategories = collect($all_categories ?? [])->filter(function ($category) {
        return $category->subCategories && $category->subCategories->count();
      });
    @endphp

    @if ($shopCategories->isEmpty())
      <p class="sf-shop-by-category__empty text-center text-muted py-5">
        {{ trans('theme.no_category_found') }}
      </p>
    @else
      <div class="sf-shop-by-category__grid">
        @foreach ($shopCategories as $category)
          @php
            $featurePath = optional($category->featureImage)->path;
            $coverPath = optional($category->coverImage)->path;
            $logoPath = optional($category->logoImage)->path;
            $imageUrl = $featurePath
              ? get_storage_file_url($featurePath, 'medium')
              : ($coverPath
                ? get_storage_file_url($coverPath, 'medium')
                : ($logoPath
                  ? get_storage_file_url($logoPath, 'medium')
                  : asset('images/placeholders/category_cover.jpg')));
          @endphp
          <a
            href="{{ route('categories.browse', $category->slug) }}"
            class="sf-category-circle"
            title="{{ $category->name }}"
          >
            <span
              class="sf-category-circle__media"
              style="background-image: url('{{ $imageUrl }}');"
              role="img"
              aria-label="{{ $category->name }}"
            ></span>
            <span class="sf-category-circle__name">{{ $category->name }}</span>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>
