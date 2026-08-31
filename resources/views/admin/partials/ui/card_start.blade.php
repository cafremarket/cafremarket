{{-- Opens a modern admin card. Pass: title, icon (optional), headerExtra (optional HTML) --}}
<div class="box admin-card {{ $class ?? '' }}">
  <div class="box-header with-border admin-card__header">
    <div class="admin-card__header-main">
      <h3 class="box-title admin-card__title">
        @if (!empty($icon))
          <span class="admin-card__icon-wrap"><i class="fa {{ $icon }}"></i></span>
        @endif
        {{ $title }}
      </h3>
      {!! $headerExtra ?? '' !!}
    </div>
    @if (!empty($actions))
      <div class="box-tools pull-right admin-card__actions">
        {!! $actions !!}
      </div>
    @endif
  </div>
  <div class="box-body admin-card__body {{ $bodyClass ?? 'responsive-table' }}">
