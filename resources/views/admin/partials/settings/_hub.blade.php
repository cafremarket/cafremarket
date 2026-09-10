{{-- Hub card grid. Pass: cards => [['url','icon','title','desc']] --}}
<div class="as-hub">
  @foreach ($cards as $card)
    <a href="{{ $card['url'] }}" class="as-hub-card">
      <span class="as-hub-card__icon"><i class="fa {{ $card['icon'] }}"></i></span>
      <h2 class="as-hub-card__title">{{ $card['title'] }}</h2>
      <p class="as-hub-card__desc">{{ $card['desc'] }}</p>
      <span class="as-hub-card__cta">{{ trans('app.open') ?? 'Open' }} <i class="fa fa-arrow-right"></i></span>
    </a>
  @endforeach
</div>
