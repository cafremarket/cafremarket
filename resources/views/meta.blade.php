@php
  $SEOurl = url()->current();
  $SEOtitle = $title ?? get_platform_title();
  $SEOdescription = config('seo.meta.description');
  $SEOimage = filter_var(config('seo.meta.image'), FILTER_VALIDATE_URL) ? config('seo.meta.image') : get_logo_url('system', 'logo');
  $SEOkeywords = config('seo.meta.keywords');
  $character_limit = config('seo.meta.description_character_limit');

  // For Products
  if (isset($item)) {
      $SEOurl = function_exists('storefront_product_url') ? storefront_product_url($item) : url()->current();
      $SEOtitle = $item->meta_title ?? $item->title;
      $rawDesc = $item->meta_description
          ?? ($item->description ? strip_tags($item->description) : null)
          ?? (optional($item->product)->description ? strip_tags($item->product->description) : null)
          ?? $SEOdescription;
      $SEOdescription = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', (string) $rawDesc)), $character_limit ?: 160, '');
      $SEOimage = get_product_img_src($item, 'full') ?: $SEOimage;
      $SEOkeywords = $item->relationLoaded('tags') || method_exists($item, 'tags')
          ? implode(', ', $item->tags->pluck('name')->filter()->toArray())
          : $SEOkeywords;
      if ($SEOkeywords === '' || $SEOkeywords === null) {
          $SEOkeywords = $SEOtitle;
      }
  }

  // For Categories
  elseif (Request::is('categories/*') || Request::is('categorygrp/*') || Request::is('category/*')) {
      $category = $category ?? ($categorySubGroup ?? $categoryGroup);
      $SEOtitle = $category->meta_title ?? $SEOtitle;
      $SEOdescription = $category->meta_description ?? $SEOdescription;
  }

  // For Shops
  elseif (Request::is('shop/*')) {
      $SEOtitle = $shop->getName() ?? $SEOtitle;
      $SEOdescription = $shop->description ? substr(strip_tags($shop->description), 0, $character_limit) : $SEOdescription;
  }

  // For Brands
  elseif (Request::is('brand/*')) {
      $SEOtitle = $brand->getName() ?? $SEOtitle;
      $SEOdescription = $brand->description ? substr(strip_tags($brand->description), 0, $character_limit) : $SEOdescription;
  }

  // For blogs
  elseif (isset($blog)) {
      $SEOtitle = $blog->title;
      $SEOdescription = substr(strip_tags($blog->excerpt), 0, $character_limit);
      $SEOimage = get_storage_file_url(optional($blog->image)->path, 'blog');
      $SEOkeywords = implode(', ', $blog->tags->pluck('name')->toArray());
  }

  // For events
  elseif (isset($event)) {
      $SEOtitle = $event->title;
      $SEOdescription = substr(strip_tags($event->excerpt), 0, $character_limit);
      $SEOimage = get_storage_file_url(optional($event->image)->path, 'blog');
  }

  // For pages
  elseif (isset($page)) {
      $SEOtitle = $page->title;
      $SEOdescription = substr(strip_tags($page->content), 0, $character_limit);
      $SEOimage = get_storage_file_url(optional($page->image)->path, 'page');
      // $SEOkeywords = implode(', ', $page->tags->pluck('name')->toArray());
  }

  $SEOtitle = strip_tags($SEOtitle);
  $SEOdescription = strip_tags($SEOdescription);
@endphp

<meta charset="utf-8">

@if (is_incevio_package_loaded('googleAnalytics'))
  @include('analytics::scripts.google_tag_manager')
@endif
<!-- End Google Tag Manager -->

@include('scripts.facebook_pixel')

<!-- End Meta Pixel Code -->

@include('scripts.twitter_pixel')

<!-- End Twitter Pixel Base Code -->

@include('scripts.tiktok_pixel')

<!-- End TikTok Pixel Base Code -->

@include('scripts.pinterest_pixel')

<!-- End Pinterest Pixel Base Code -->

@include('scripts.linkedin_pixel')

<!-- End LinkedIn Insight Tag Code -->

<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, shrink-to-fit=no">
<meta name="author" content="{{ config('system_settings.name') ?? config('app.name') }}">
<meta name="format-detection" content="telephone=no">

@if (config('seo.enabled'))
  <!-- Standard SEO -->
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="referrer" content="{{ $referrer ?? config('seo.meta.referrer') }}">
  <meta name="robots" content="{{ $robots ?? config('seo.meta.robots') }}">
  <meta name="revisit-after" content="{{ config('seo.meta.revisit_after', '7 days') }}" />
  <meta name="description" content="{{ $SEOdescription }}">
  <meta name="image" content="{{ $SEOimage }}">
  <meta name="keywords" content="{{ $SEOkeywords }}">

  <!-- Geo loacation -->
  @if (config('seo.meta.geo_region') !== '')
    <meta name="geo.region" content="{{ config('seo.meta.geo_region') }}">
    <meta name="geo.placename" content="{{ config('seo.meta.geo_region') }}">
  @endif

  @if (config('seo.meta.geo_position') !== '')
    <meta name="geo.position" content="{{ config('seo.meta.geo_position') }}">
    <meta name="ICBM" content="{{ config('seo.meta.geo_position') }}">
  @endif

  <!-- Dublin Core basic info -->
  <meta name="dcterms.Format" content="text/html">
  <meta name="dcterms.Type" content="text/html">
  <meta name="dcterms.Language" content="{{ config('app.locale') }}">
  <meta name="dcterms.Identifier" content="{{ $SEOurl }}">
  <meta name="dcterms.Relation" content="{{ get_platform_title() }}">
  <meta name="dcterms.Publisher" content="{{ get_platform_title() }}">
  <meta name="dcterms.Coverage" content="{{ $SEOurl }}">
  <meta name="dcterms.Contributor" content="{{ $author ?? get_platform_title() }}">
  <meta name="dcterms.Title" content="{{ $SEOtitle }}">
  <meta name="dcterms.Subject" content="{{ $SEOkeywords }}">
  <meta name="dcterms.Description" content="{{ $SEOdescription }}">

  <!-- Facebook OpenGraph -->
  <meta property="og:locale" content="{{ config('app.locale') }}">
  <meta property="og:url" content="{{ $SEOurl }}">
  <meta property="og:site_name" content="{{ get_platform_title() }}">
  <meta property="og:title" content="{{ $SEOtitle }}">
  <meta property="og:description" content="{{ $SEOdescription }}">

  @if (isset($item))
    <meta property="og:type" content="product">
    <meta property="product:availability" content="{{ $item->stock_quantity > 0 ? 'in stock' : 'out of stock' }}">
    <meta property="product:price:currency" content="{{ get_system_currency() }}">
    <meta property="product:price:amount" content="{{ number_format((float) $item->current_sale_price(), 2, '.', '') }}">
    @if (optional(optional($item->product)->manufacturer)->name)
      <meta property="product:brand" content="{{ $item->product->manufacturer->name }}">
    @elseif (! empty($item->brand))
      <meta property="product:brand" content="{{ $item->brand }}">
    @endif
    @if (optional($item->shop)->name)
      <meta property="product:retailer_item_id" content="{{ $item->sku }}">
      <meta property="og:see_also" content="{{ $SEOurl }}">
    @endif

    @php
      $item_images = ($item->images && $item->images->count())
          ? $item->images
          : (optional($item->product)->images ?? collect());

      if (isset($variants) && $variants) {
          $other_images = $variants
              ->pluck('images')
              ->flatten(1)
              ->filter(function ($value) use ($item) {
                  return $value && optional($value)->imageable_id != $item->id && ! empty(optional($value)->path);
              });
          $item_images = collect($item_images)->concat($other_images);
      }

      $ogImages = collect($item_images)->pluck('path')->filter()->unique()->values();
    @endphp

    @forelse ($ogImages as $imgPath)
      <meta property="og:image" content="{{ get_storage_file_url($imgPath, 'full') }}">
      <meta property="og:image:alt" content="{{ $SEOtitle }}">
    @empty
      <meta property="og:image" content="{{ $SEOimage }}">
      <meta property="og:image:alt" content="{{ $SEOtitle }}">
    @endforelse
  @else
    <meta property="og:type" content="{{ 'website' }}">
    <meta property="og:image" content="{{ $SEOimage }}">
  @endif

  <meta property="og:image:width" content="1200" />
  <meta property="og:image:height" content="630" />

  @if (config('seo.meta.video') !== '')
    <meta name="og:video" content="{{ $video ?? config('seo.meta.video') }}">
  @endif

  @if (config('seo.meta.fb_app_id') !== '')
    <meta property="fb:app_id" content="{{ config('seo.meta.fb_app_id') }}" />
  @endif

  <!-- Twitter Card -->
  <meta name="twitter:title" content="{{ $SEOtitle }}">
  <meta name="twitter:description" content="{{ $SEOdescription }}">
  <meta name="twitter:image" content="{{ $SEOimage }}">
  <meta name="twitter:image:alt" content="{{ $SEOtitle }}">

  @if (isset($item))
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:label1" content="Price">
    <meta name="twitter:data1" content="{{ get_formated_currency($item->current_sale_price()) }}">
    <meta name="twitter:label2" content="Availability">
    <meta name="twitter:data2" content="{{ $item->stock_quantity > 0 ? trans('theme.in_stock') : trans('theme.out_of_stock') }}">
    @php
      $shareBrand = optional(optional($item->product)->manufacturer)->name ?: ($item->brand ?? optional($item->shop)->name);
    @endphp
    @if ($shareBrand)
      <meta name="twitter:label3" content="Brand">
      <meta name="twitter:data3" content="{{ $shareBrand }}">
    @endif
  @elseif(config('seo.meta.twitter_card') !== '')
    <meta name="twitter:card" content="{{ config('seo.meta.twitter_card') }}">
  @endif

  @if (config('seo.meta.twitter_site') !== '')
    <meta name="twitter:site" content="{{ config('seo.meta.twitter_site') }}">
  @endif

  @if (isset($item))
    <!-- Microdata Product Page-->
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": @json($SEOtitle),
        "description": @json($SEOdescription),
        "image": @json($SEOimage),
        "sku": @json($item->sku),
        @if (optional(optional($item->product)->manufacturer)->name)
          "brand": {
            "@type": "Brand",
            "name": @json($item->product->manufacturer->name)
          },
        @elseif (! empty($item->brand))
          "brand": {
            "@type": "Brand",
            "name": @json($item->brand)
          },
        @endif
        @if (optional($item->product)->gtin_type && optional($item->product)->gtin)
          "{{ $item->product->gtin_type }}": @json($item->product->gtin),
        @endif
        "offers": {
          "@type": "Offer",
          "url": @json($SEOurl),
          "availability": "{{ $item->stock_quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
          "priceCurrency": @json(get_system_currency()),
          "price": "{{ number_format((float) $item->current_sale_price(), 2, '.', '') }}"
        }
        @if (($item->feedbacks_count ?? 0) > 0)
          ,
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "{{ get_formated_decimal($item->feedbacks->avg('rating'), true, 1) }}",
            "bestRating": "5",
            "worstRating": "1",
            "reviewCount": "{{ $item->feedbacks_count }}"
          }
        @endif
      }
    </script>
  @endif
@endif

<title>{{ $SEOtitle }}</title>
<link rel="icon" href="{{ get_icon_url('system', 'thumbnail') }}" type="image/x-icon" />
{{-- <link rel="manifest" href="{{ asset('site.webmanifest') }}"/> --}}
<link rel="apple-touch-icon" href="{{ get_icon_url('system', 'thumbnail') }}" />
