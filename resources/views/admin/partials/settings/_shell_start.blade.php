{{-- Shared settings page shell start. Pass: title, subtitle, eyebrow, navItems, active, actions, withoutPanel --}}
<div class="as-page">
  <div class="as-hero">
    <div class="as-hero__copy">
      @if (!empty($eyebrow))
        <p class="as-hero__eyebrow">{{ $eyebrow }}</p>
      @endif
      <h1 class="as-hero__title">{{ $title }}</h1>
      @if (!empty($subtitle))
        <p class="as-hero__sub">{{ $subtitle }}</p>
      @endif
    </div>
    @if (!empty($actions))
      <div class="as-hero__actions">
        {!! $actions !!}
      </div>
    @endif
  </div>

  @if (!empty($navItems))
    <nav class="as-nav" aria-label="{{ $title }}">
      @foreach ($navItems as $item)
        <a href="{{ $item['url'] }}" class="as-nav__link {{ ($active ?? '') === ($item['key'] ?? '') ? 'is-active' : '' }}">
          @if (!empty($item['icon']))
            <i class="fa {{ $item['icon'] }}"></i>
          @endif
          {{ $item['label'] }}
        </a>
      @endforeach
    </nav>
  @endif

  @if (empty($withoutPanel))
  <div class="as-panel">
  @endif
